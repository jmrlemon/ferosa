# Spec: AR Visualizer — Make Models Render + Harden Asset Upload

**Status:** Reviewed. All open questions resolved. Ready for planning.
**Relationship to prior work:** extends `.kiro/specs/mobile-ar-plant-placement/`, which defined the
feature and whose behavioural requirements still hold. That folder is left as the historical record
and is not updated by this work. This document covers *why the built feature does not work* and
*how the asset pipeline must change*.

---

## Objective

The AR visualizer is fully built — catalog drawer, plane detection, anchoring, drag-to-reposition,
screenshot, cart integration, a 200 MB LRU model cache, real-world scaling from `height_cm`, and a
genuinely thorough server-side GLB validator. **Placement never succeeds.** Two independent blockers
sit in front of it, and a third defect silences the symptom.

Two goals:

1. **Fix rendering.** A customer taps a detected surface and the selected plant appears, grounded, at
   its configured real-world height — verified on a physical ARCore device.
2. **Close the asset-pipeline gaps** that let a structurally-valid GLB still fail or look wrong on a
   phone. Assets are sourced from free/paid model libraries (Sketchfab, Poly Haven, TurboSquid), so
   uploads vary in units, orientation, polycount, and glTF extension usage.

**Users:** Ferosa customers browsing the plant catalog on Android (placement); Ferosa admin/staff
(upload).

**Done looks like:** five models placed in one AR session on a real phone, each at correct height,
none invisible, none black, no crash — and an admin who uploads a bad asset learns *why* before a
customer ever sees it.

---

## Current state (measured, not assumed)

### Database

```
plant_models rows:            0
products total:              11
products active+unarchived:  11
AR-enabled products:          0     ← Product::arEnabled() = whereHas('plantModel')
```

`plant_models` is **empty**, while two `.glb` files sit orphaned in
`storage/app/public/ar-models/`. That is the signature of a `migrate:fresh`: the table was wiped, the
files remained.

### The two orphaned files

Both parsed directly from their GLB JSON chunks:

| | `FkMPbMsk….glb` | `O8s2aEI4….glb` |
|---|---|---|
| Identity (from internal names) | pink keycap with a face — `M_Key_Body_LtPink`, `M_Face2`, `M_Eyes_Glossy_Black`, `M_Cheeks_Blush_Lt` | `Hanging_Lantern` — `Light`, `Metal_Frame` |
| Size | 0.66 MB | 0.21 MB |
| Triangles | 29,186 | 7,644 |
| Vertices | 15,459 | 7,188 |
| Primitives / materials | 4 / 4 | 2 / 2 |
| Textures | one 512×512 PNG (19 KB) | none |
| `extensionsRequired` | none | none |
| `extensionsUsed` | none | `KHR_materials_emissive_strength` |
| Bounding box (m) | 0.056 × 0.094 × 0.013 | 0.288 × 0.570 × 0.287 |
| Base at origin? | no — 0.059 below | yes — node `translation [0, 0.261, 0]` cancels the mesh offset |
| Exporter | Blender glTF I/O 4.5.51 | Blender glTF I/O 4.5.47 |

Neither matches a catalog product. The keycap is leftover test junk. The lantern is a well-authored,
valid GLB and is **useful as a temporary test asset** (see Test Assets below), but there is no lantern
product to ship it against.

Two facts worth carrying forward:

- **Arbitrary pivots and non-metre authoring scales are already handled.** Asset 1 is authored at a
  9 cm bounding box with its origin 63% up the model. `calculateGroundedModelTransform` derives scale
  from the Y extent (`desiredHeight / 0.0937`) and offsets by `-sourceBottomY * scale`. It is correct
  and unit-tested; do not rewrite it.
- **`extensionsUsed` must never be a rejection reason.** Asset 2 declares
  `KHR_materials_emissive_strength` as *used* but not *required* — the correct authoring pattern. See
  D3.

### Catalog shape

| # | Product | AR-suitable as a placeable object? |
|---|---|---|
| 1 | Garden Soil | no — bulk material |
| 2 | Gravel | no — bulk material |
| 3 | Plants (Various) | weak — a grouping, not one object |
| 4 | Natural Stones (Pebbles, River Rocks, Boulder) | weak — bulk, three things in one row |
| 5–7 | Carabao / Bermuda / Frog Grass | no — ground coverage, not an object |
| 8 | Grass Paver | yes — a discrete tile |
| 9 | Decorative Stone/Claddings | maybe — as a sample piece |
| 10 | Fruit bearing plants | weak — a grouping |
| 11 | **Monstera Deliciosa** | **yes — specific plant, definite real height** |

The design assumes *one GLB per product, scaled to `height_cm`, standing on a plane*. That is
meaningless for soil or gravel — there is no "height" of gravel. **Realistically 1–2 of 11 products
are AR candidates today**, growing as specifically-named plants are added.

The architecture already handles this correctly: `arEnabled` is `whereHas('plantModel')`, so bulk
materials simply never enter the AR catalog. Nothing to change — but it does mean the empty-catalog
state (D0) is the *normal* state for most of the catalog, which raises its priority.

---

## Defects to fix

### D0 — CONFIRMED. Empty catalog silently swallows every tap. Hit first.

With zero AR-enabled products, `/api/ar/products` returns `[]`. The auto-select effect
(`ArActivity.kt:736-742`) never runs, so `selectedProduct` stays `null`, and `onSingleTapUp`
(`ArActivity.kt:1199`) bails immediately:

```kotlin
val product = selectedProductRef.value ?: return false
```

**No download, no error, no notice, no logcat line.** Nothing at all. If no error message was ever
seen on screen, this — not D1 — is what was actually being observed, and D1 was never reached.

There is also no empty state: the user sees a working camera view that ignores them.

**Fix:** (a) register real `plant_models` rows, and (b) show a clear
*"No AR products available yet"* empty state with a way back, instead of a camera that eats taps.

### D1 — CONFIRMED. The reason models don't render, once a product exists.

`ArActivity.kt:842-845`:

```kotlin
val localFilePath = modelResult.file.absolutePath   // /data/user/0/…/ar_model_cache/model_11.glb
sv.modelLoader.loadModelInstanceAsync(fileLocation = localFilePath) { instance -> … }
```

SceneView resolves `fileLocation` in `io.github.sceneview.utils.FileLoader.readFileBuffer`
(`sceneview-2.2.1-sources.jar`, `utils/File.kt:61`):

```kotlin
val uri = Uri.parse(fileLocation)
return when (uri.scheme) {
    ContentResolver.SCHEME_FILE     -> File(uri.path!!).inputStream().toByteArray()
    ContentResolver.SCHEME_CONTENT  -> …
    ContentResolver.SCHEME_ANDROID_RESOURCE -> …
    else -> context.assets.open(fileLocation).toByteArray()   // ← bare path lands HERE
}
```

`Uri.parse("/data/user/0/…").scheme` is `null`, so the `else` branch runs and the loader looks for the
model **inside the APK's assets**. `FileNotFoundException`, every placement, every device, every
product.

**Aggravating factor that will mislead debugging:** `ModelLoader`'s internal scope is
`CoroutineScope(Dispatchers.IO)` (`loaders/ModelLoader.kt:35`) — a plain `Job`, no `SupervisorJob`,
no `CoroutineExceptionHandler`. The first uncaught failure **cancels that scope**, so every later
`loadModelInstanceAsync` on the same `ARSceneView` silently no-ops even after the original cause is
gone. Testing "place once" will misread this; the fix must be verified with five consecutive
placements.

`ArActivity.kt:919-935` does surface the failure via `loadJob.invokeOnCompletion` →
`viewModel.setModelLoadError(cause.message)`, so once D0 is fixed the notice bar should name the
`.glb` path. That message is the confirmation to look for in the first debug pass.

**Fix:** stop handing SceneView a path string. Read the cached file into a `ByteBuffer` on
`Dispatchers.IO`, then call the synchronous `modelLoader.createModelInstance(buffer)` on the main
thread. This also sidesteps `FileLoader`'s `InputStream.toByteArray()` (`utils/File.kt:115`), which
does a single unchecked `read()` sized by `available()` and can silently truncate a large model.

### D2 — CONFIRMED. Session config silently discarded.

`ArActivity.kt:1071-1091` builds the view, *then* assigns the config:

```kotlin
ARSceneView(ctx, null, 0, 0, it, lifecycleOwner.lifecycle).also { sv ->
    sv.sessionConfiguration = { session, config -> … }   // assigned too late
}
```

`ARSceneView.init` runs `sharedLifecycle?.addObserver(lifecycleObserver)` (`ARSceneView.kt:418`). The
Activity is already `RESUMED` when the `AndroidView` factory runs during composition, so
`LifecycleRegistry` replays `ON_CREATE`/`ON_START`/`ON_RESUME` **synchronously inside the
constructor** → `arCore.create()` → `onSessionCreated(session)` →
`sessionConfiguration?.invoke(session, config)` (`ARSceneView.kt:438`) while the property is still
`null`.

So `planeFindingMode`, `lightEstimationMode = ENVIRONMENTAL_HDR`, `depthMode`, and
`cameraStream.isDepthOcclusionEnabled` are **never applied**; the session runs on ARCore defaults.
SceneView provides `configureSession { session, config -> … }` (`ARSceneView.kt:538`) exactly for
this — it queues into `_onSessionCreated` and re-configures an already-created session.

**Fix:** pass the config lambda as the `sessionConfiguration` constructor argument, or use
`configureSession { }`, so it applies regardless of when the session was created.

### D3 — Unsupported required extensions pass validation, then render nothing.

`AdminController::validateGlbResources` checks `asset.version`, embedded-only buffers and images, BIN
chunk length, and walks the default scene for reachable meshes with finite POSITION bounds. It is a
good validator — but it never inspects `extensionsRequired`. A glTF loader is required by spec to
refuse a file whose `extensionsRequired` it does not implement, so such an asset uploads cleanly and
then fails on the phone.

**The allowlist is evidence, not memory.** Extracted from `libgltfio-jni.so` 1.52.0 — the actual
loader bundled in this app — every glTF extension literal it contains:

```
KHR_draco_mesh_compression       KHR_materials_ior              KHR_materials_unlit
EXT_meshopt_compression          KHR_materials_iridescence      KHR_materials_variants
EXT_mesh_gpu_instancing          KHR_materials_sheen            KHR_materials_volume
KHR_lights_punctual              KHR_materials_specular         KHR_texture_basisu
KHR_materials_clearcoat          KHR_materials_transmission     KHR_texture_transform
KHR_materials_emissive_strength  KHR_materials_pbrSpecularGlossiness
```

- **Draco is confirmed decoded** — the binary carries real decoder diagnostics
  (`"Cannot decompress mesh, Draco decoding error."`, `"Unexpected component type for Draco
  vertices."`).
- **`EXT_meshopt_compression` appears with no decoder diagnostics**, consistent with cgltf
  recognising the extension while leaving decompression to the caller. Parsed, probably not decoded —
  but not proven from binary strings alone, so it is a **warn**, not a reject, until a meshopt asset
  is tested on the phone.
- **Notable absences** that this rule now catches: `EXT_texture_webp` (common from optimisers) and
  `KHR_materials_anisotropy`.

**Fix:**
- **Hard reject** any `extensionsRequired` entry outside the allowlist, naming the extension and how
  to remove it.
- **Warn** on `extensionsRequired` containing `EXT_meshopt_compression`, pending device verification.
- **Never** reject based on `extensionsUsed` — a loader ignores an unknown *used* extension and still
  renders the mesh. Asset 2 would fail a naive check and is a perfectly good file.

### D4 — No performance budget.

Nothing limits triangle count or texture resolution; the only cap is 100 MB, and the scene allows 5
concurrent models. Triangles are not the real risk — **texture memory is**, because a texture must be
decompressed to be used:

| Texture dimensions | On disk | Decoded + mipmaps | × 5 models, 2 each |
|---|---|---|---|
| 512² | ~20 KB | ~1.4 MB | ~14 MB |
| 1024² | ~500 KB | ~5.6 MB | ~56 MB |
| 2048² | ~2 MB | ~22 MB | ~224 MB |
| 4096² | ~8 MB | **~90 MB** | ~896 MB → OOM |

Agreed budgets. Current assets (29,186 triangles max, 512² texture max, 0.66 MB max) sit 3–10× inside
every threshold, so these change nothing about what can be uploaded today — they are a net for a
future film-quality library asset.

| Metric (per model) | Warn | Hard reject | Basis |
|---|---|---|---|
| Triangles | 100,000 | 250,000 | 5-model scene; current max is 29k |
| Largest texture edge | 2048 px | 4096 px | five 4096² textures cannot fit in memory |
| Total decoded texture memory | 48 MB | — | catches many mid-size textures |
| File size | 8 MB | *(100 MB cap stays)* | derived from the existing 15 s load timeout (parent Requirement 3.4) |

Everything here is computable from the JSON chunk the validator **already parses** — accessor `count`
for triangles, and PNG/JPEG headers inside the BIN chunk for image dimensions. Verified by doing
exactly this parse to produce the tables in this spec.

Warn = upload proceeds with the actual number shown against the budget. Hard-rejecting a paid asset an
admin cannot re-export is hostile; the two hard rejects above are arithmetic certainties, not taste.

### D5 — Admin cannot see what they uploaded. Fixed by documentation, not code.

The form shows filename, size, and height only. An admin cannot tell whether a model is upside-down,
mis-scaled, or an empty shell until someone walks outside with a phone. **No amount of server-side
validation can catch this** — a model can pass every structural check and still be upside-down.

An in-browser 3D preview via `@google/model-viewer` was considered and **rejected for now**. The same
information is available at zero cost by opening the `.glb` in **Windows 3D Viewer** or **Blender**
before uploading, and bundling a 3D engine is not justified while the uploader is the developer (who
has Blender open anyway) and the catalog realistically supports 1–2 AR products (see § Catalog shape).

**Fix:** `docs/ar-asset-guidelines.md` requires a pre-upload visual check. Revisit the in-admin viewer
if a non-developer starts uploading models, the catalog passes roughly five AR products, or a bad model
first costs a wasted device trip. Tracked on `tasks/todo.md` Task 10.

If it is ever un-deferred, note that the viewer can point straight at the existing
`/api/ar/products/{id}/model` route — it is behind `['web','auth']` and an admin in a browser is already
session-authenticated, so no new endpoint is needed and the missing `public/storage` symlink (D6) is
irrelevant.

### D6 — Verify only, do not fix here.

`public/storage` symlink is absent. AR model downloads stream through
`Storage::disk('public')->download()` and do **not** need it, so this is not an AR blocker — but
catalog thumbnails may be broken. Confirm and report separately.

---

## Test Assets

Separating *"does the renderer work"* from *"do we have shippable assets"* — conflating them is how
you end up unsure which is broken.

**Now, to unblock phone testing:** create a throwaway product (e.g. *"AR Test Object"*, inactive or
clearly named) and upload the existing `Hanging_Lantern` GLB against it through the admin form. It is
a clean, valid, well-authored GLB at a believable 57 cm; its real identity is irrelevant to a
rendering test, and going through the form exercises the real upload and validation path. This
resolves D0(a) without writing a wrong model against a real catalog product — the real catalog stays
presentable to anyone who opens the app mid-development.

**Before launch:** delete the throwaway product and both orphaned files, source a real Monstera GLB,
and upload it to product 11 with a measured `height_cm`.

**D3 cannot be tested with either orphan** — neither uses meshopt, Draco, or WebP. Its rejection paths
need synthetic GLB fixtures built byte-by-byte in Laravel feature tests, which is how the existing
suite already builds GLB fixtures.

---

## Tech Stack

**Android** (`ferosa_mobile/`)
- Kotlin 2.2.10, AGP 9.1.0, Compose BOM 2024.09.00, minSdk 24 / targetSdk 36
- `io.github.sceneview:arsceneview:2.2.1` → Filament 1.52.0 renderer + ARCore session management
- Retrofit 2.11.0 / OkHttp 4.12.0 / Gson 2.11.0, Coil 2.7.0
- Auth: Laravel session cookies shared from the WebView via `WebViewCookieJar`

**Backend** (`ferosa-laravel/`)
- Laravel + Blade + Tailwind + Vite, MySQL (XAMPP, database `laravel`)
- `plant_models` table, one row per product: `file_path`, `file_name`, `file_size`, `height_cm`
- GLB files on the `public` disk under `storage/app/public/ar-models/`
- `/api/ar/*` behind `['web', 'auth']`

**New dependencies: none.** `@google/model-viewer` was considered for the admin preview and
**deliberately not adopted** — see D5.

---

## Commands

PowerShell, from repo root `C:\xampp\htdocs\ferosa`.

```powershell
# Android
cd ferosa_mobile
.\gradlew.bat :app:assembleDebug              # build
.\gradlew.bat :app:testDebugUnitTest          # unit tests (JVM, no device)
.\gradlew.bat :app:lintDebug                  # lint
.\gradlew.bat :app:installDebug               # install to connected phone

# AR diagnostics on device
adb logcat -c
adb logcat FerosaAR:V Filament:V ARCore:V sceneview:V AndroidRuntime:E *:S

# Laravel
cd ferosa-laravel
php artisan test                              # full suite
php artisan test --filter=Ar                  # AR-related tests only
composer test                                 # config:clear + artisan test
npm run build                                 # compile admin assets
composer dev                                  # serve + queue + vite
```

---

## Project Structure

```
ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/
  ArActivity.kt                     AR host Activity + ArScreen composable  ← D0(b), D1, D2
  ui/ar/ArModelPlacement.kt         pure transform + GLB header validation (unit-tested)
  ui/ar/ArViewModel.kt              catalog, placement list, cart, offline state
  ui/ar/ArProductState.kt           ArProduct / PlacedModel / ArError
  ui/ar/components/                 CatalogDrawer, ProductInfoPanel
  data/repository/ModelRepository.kt  download + cache-freshness + retry
  data/cache/ModelCacheManager.kt     LRU disk cache (200 MB, 7-day validity)
  data/api/                           Retrofit service, cookie jar, DTOs
  util/ArCompatibilityChecker.kt      ARCore availability + session probe
app/src/test/java/.../               ArModelPlacementTest, ApiContractTest   ← new tests land here

ferosa-laravel/
  app/Http/Controllers/ArController.php     AR API (index / model / model-info / cart)
  app/Http/Controllers/AdminController.php  updateArModel + validateGlb*      ← D3, D4
  app/Models/PlantModel.php
  resources/views/admin/partials/ar-models.blade.php   upload UI             ← D5
  storage/app/public/ar-models/             GLB storage
  tests/Feature/                            AR upload + API feature tests

docs/specs/ar-visualizer-rendering.md     this document
docs/ar-asset-guidelines.md               asset requirements for admins uploading GLBs
docs/ar-manual-test.md                    device test script (see Testing Strategy)
tasks/plan.md, tasks/todo.md              implementation plan + task list
```

---

## Code Style

Match the existing house style: comments explain *why* a non-obvious choice was made, never *what* the
line does. Failure messages are customer-readable, not stack traces. Every renderer mutation happens
on the main thread and re-checks that the scene generation is still current before touching Filament.

The D1 fix, in the style of the surrounding code:

```kotlin
/**
 * SceneView resolves a `fileLocation` string through `FileLoader.readFileBuffer`, which treats a
 * scheme-less path as an APK asset name and throws FileNotFoundException. Reading the cached file
 * ourselves also avoids that loader's single-read `available()` copy, which can truncate a large
 * GLB. The buffer is built off the main thread; Filament entity creation must stay on it.
 */
private suspend fun loadCachedModelInstance(
    sceneView: ARSceneView,
    file: File,
): ModelInstance {
    val buffer = withContext(Dispatchers.IO) { ByteBuffer.wrap(file.readBytes()) }
    return withContext(Dispatchers.Main) {
        requireNotNull(sceneView.modelLoader.createModelInstance(buffer)) {
            "This 3D model could not be opened. Try another item."
        }
    }
}
```

Kotlin: 4-space indent, trailing commas in multi-line argument lists, `lowerCamelCase` members,
`SCREAMING_SNAKE_CASE` constants in `companion object`. PHP: Laravel conventions, typed signatures,
validator helpers returning `?string` (null = valid), as `validateGlb` already does.

---

## Testing Strategy

Three levels, because the interesting failure is device-only.

**1. JVM unit tests** — `ferosa_mobile/app/src/test/`, JUnit 4, no Android or Filament dependency.
Extends the existing `ArModelPlacementTest` / `ApiContractTest`. Covers pure logic: grounded transform
maths, GLB header validation, cache-freshness comparison, DTO contract. New: a regression test
asserting the loader receives a readable buffer or `File`, never a scheme-less path string.
Run on every change: `.\gradlew.bat :app:testDebugUnitTest`.

**2. Laravel feature tests** — `ferosa-laravel/tests/Feature/`, with GLB fixtures built byte-by-byte
in the test as the existing suite already does. Covers: valid upload accepted; `extensionsRequired`
outside the allowlist rejected with the extension named; `EXT_meshopt_compression` warns rather than
rejects; `extensionsUsed`-only extensions never rejected (asset 2's exact shape); over-budget triangle
count and texture edge warn with real numbers; 4096 px texture rejected; `/api/ar/products` shape;
`model-info` matches the stored file. Run: `php artisan test --filter=Ar`.

**3. Manual device script** — `docs/ar-manual-test.md`, run on the physical ARCore phone. The only
level that proves D1 and D2 are actually fixed, so it is a required gate, not optional polish. Each
step names its expected logcat line. Minimum:
place one model → grounded at correct height; **place five different products in one session** (catches
the cancelled-`ModelLoader`-scope trap); drag-reposition; background/foreground; airplane mode on a
warm cache; a deliberately corrupt GLB shows a readable notice rather than crashing; empty catalog
shows the empty state.

**Coverage expectation:** every pure function in `ui/ar/` and every new validator branch has a test. No
coverage target on Compose or renderer code — that is what the device script is for.

---

## Boundaries

**Always**
- Run `:app:testDebugUnitTest` and `php artisan test --filter=Ar` before any commit.
- Verify a rendering change on the physical ARCore phone before calling it done. An emulator pass is
  not evidence.
- Keep every Filament/renderer mutation on the main thread, guarded by the existing
  `isSceneCurrent(sv, generation)` check.
- Detach the ARCore anchor on every failure path — a leaked anchor costs tracking budget.
- Keep failure text customer-readable.
- Preserve `height_cm` as the authoritative real-world scale. `calculateGroundedModelTransform` is
  correct and unit-tested; do not rewrite it.

**Ask first**
- Adding any dependency. This scope intentionally adds none.
- Any `plant_models` schema change.
- Changing the `/api/ar/*` response shape — `ArProductDto` and `ApiContractTest` are a contract.
- Replacing SceneView, upgrading past 2.2.1, or changing `minSdk`/`targetSdk`.
- Loosening the existing GLB validator. Tightening is in scope; relaxing needs a decision.
- Touching `MainActivity`'s WebView, cookie, or auth plumbing.

**Never**
- Commit a `.glb` fixture as a binary blob — build fixtures in test code, as the suite does now.
- Delete or weaken a failing test to make a build pass.
- Commit credentials, `.env`, or `local.properties`.
- Swallow a model-load exception without surfacing something the user can act on.
- Bypass admin GLB validation, or accept a non-`.glb` upload.
- Load a model from a URL the app did not get from the authenticated catalog.
- Insert `plant_models` rows that skip validation as a shortcut to a working catalog.

---

## Success Criteria

**Rendering (device-verified on the physical ARCore phone)**
1. Tapping a tracked horizontal plane with a product selected renders that model within 10 s on a warm
   cache, with no error in the notice bar.
2. Rendered height is within ±10% of the product's `height_cm`, measured against a metre rule in the
   camera view — parent Requirement 3.2, currently unverified.
3. The model's base sits on the plane with no float or sink, regardless of the asset's export pivot.
4. **Five consecutive placements of five different products succeed in one session** — proves the
   `ModelLoader` scope is no longer cancelled by a first failure.
5. `planeFindingMode`, `lightEstimationMode`, and `depthMode` are observably applied — asserted via
   `onSessionConfigChanged` logging matching what the app requested.
6. A deliberately corrupt GLB yields a readable notice and a detached anchor, never a crash.
7. Airplane mode with a warm cache still places; with a cold cache it reports offline clearly.

**Catalog and empty state**
8. `Product::arEnabled()` returns at least one product, and `/api/ar/products` returns it with a
   `height_cm` and a working `model_url`.
9. With zero AR-enabled products the AR screen shows *"No AR products available yet"* and a way back —
   never a camera view that silently ignores taps.

**Asset pipeline**
10. A GLB whose `extensionsRequired` contains an entry outside the 17-item Filament allowlist is
    rejected at upload, with the extension named and removal guidance.
11. A GLB declaring extensions only in `extensionsUsed` uploads successfully — asset 2's exact shape.
12. Over-budget triangle counts and texture sizes are flagged with the actual numbers against the
    budget; a texture edge above 4096 px is rejected.
13. The guidelines require a pre-upload visual check in Windows 3D Viewer or Blender, so orientation
    and scale errors — which no server-side check can detect — are caught before upload rather than on
    a phone.
14. `docs/ar-asset-guidelines.md` states the requirements for library-sourced assets: self-contained
    `.glb`, glTF 2.0, Y-up, metres, base at origin, allowlisted extensions, the budget table, how to
    fix each in Blender or `gltf-transform`, **and that bulk materials such as soil, gravel, and sod
    are not AR candidates**.
15. Every rejection and warning path has a Laravel feature test.

**No regressions**
16. `:app:testDebugUnitTest`, `:app:lintDebug`, and `php artisan test` all pass.
17. Existing behaviour — drag-to-reposition, delete, the 5-model cap, screenshot, add-to-cart, offline
    catalog, and the ARCore-unsupported screen — still works. The "AR not available" screen stays as
    is; no non-AR 3D fallback in this scope.

---

## Out of Scope

- Non-AR 3D turntable fallback for unsupported devices, and a customer-facing `<model-viewer>` on web
  product pages. The admin-side viewer is deferred too — see D5.
- **Model-render thumbnails.** The app already uses the product photo for catalog thumbnails
  (`ArController::index` sets `'thumbnail_url' => $p->image_url`), so a captured model render benefits
  nothing today, and it is the only change that would need a migration. D5's preview covers the real
  need.
- Customer-uploaded models.
- A separate bulk AR asset manager page — per-product upload stays the entry point.
- Vertical-surface placement, occlusion tuning, multi-user or persisted cloud anchors.
- Fixing the `public/storage` symlink / thumbnails (D6) — investigate and report, fix separately.
- Sourcing a real Monstera GLB. Tracked as a follow-up, not a blocker for the rendering fix.

---

## Verified Environment

Device testing is already wired up; confirmed before planning so it is not discovered mid-task:

| | Value | Status |
|---|---|---|
| `FEROSA_SERVER_URL` | `http://192.168.254.102:6969/ferosa/ferosa-laravel/public` (`gradle.properties`) | matches this machine's current LAN IP |
| Apache | listening on `0.0.0.0:6969` and `[::]:6969` | LAN-reachable |
| Site root | HTTP 200 | reachable |
| `/api/ar/products` unauthenticated | HTTP 302 → `/login` | route exists, `['web','auth']` active |
| Cleartext HTTP | `usesCleartextTraffic=true` on debug | plain HTTP allowed in debug builds |

The phone must be on the same Wi-Fi as `192.168.254.102`. If that IP changes, `gradle.properties`
must be updated and the app rebuilt — the URL is compiled in via `buildConfigField`.

---

## Resolved Decisions

| Question | Decision |
|---|---|
| Test device | Physical ARCore phone. Emulator passes are not evidence. |
| Upload scope | Harden the existing admin upload; do not rebuild it. |
| Asset source | Free/paid model libraries — hence the normalization guidance and budgets. |
| Non-AR devices | Keep the current "AR not available" screen. No 3D fallback. |
| Budgets | Adopted as tabled in D4. Tunable after real device measurement. |
| Warn vs reject | Warn on performance; hard reject only what cannot render (non-allowlisted required extensions, >4096 px textures). |
| Extension rule | Allowlist derived from `libgltfio-jni.so` 1.52.0. Never reject on `extensionsUsed`. Meshopt warns pending device test. |
| Test asset | Lantern GLB on a throwaway test product, keeping the real catalog clean; real Monstera model on product 11 before launch. |
| Empty catalog | Add a clear empty-state message. |
| Model thumbnails | Dropped from scope. |
| Admin 3D preview | Deferred; no dependency added. Replaced by a required pre-upload check in Windows 3D Viewer or Blender. Revisit triggers on `tasks/todo.md` Task 10. |

## Carried Forward

1. **Verify meshopt empirically.** Build or download one `EXT_meshopt_compression` GLB and place it on
   the phone. If it renders, move meshopt from warn to allow; if not, move it to hard reject. Until
   then it warns.
2. **Tune the budget numbers** after measuring five simultaneous models on the target phone. The
   current values are conservative by design.
3. **Delete the two orphaned GLB files** once a real Monstera asset is in place.
