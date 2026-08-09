# Task List: AR Visualizer Rendering + Asset Pipeline

**Spec:** `docs/specs/ar-visualizer-rendering.md` · **Plan:** `tasks/plan.md`

12 tasks, 4 checkpoints. Ordered by dependency. Phase 3 runs parallel with Phases 1–2.
Every task also clears the standing bar in `tasks/plan.md` § Definition of Done.

---

# Phase 1: Baseline — make the catalog real and define "verified"

## Task 1: Add an empty-state to the AR screen

**Description:** With zero AR-enabled products, `/api/ar/products` returns `[]`, `selectedProduct` stays
`null`, and `onSingleTapUp` bails at `?: return false` — so the user sees a working camera view that
silently discards every tap. This is defect D0b and is almost certainly what was being observed as
"models don't show". Do this task **first**: the catalog is empty right now, which is the only moment
this state is trivially testable.

**Acceptance criteria:**
- [ ] When `products` is empty and `isLoading` is false, the screen shows *"No AR products available
      yet"* with a brief explanation and a way back
- [ ] Taps in this state attempt no placement and log nothing alarming
- [ ] The state clears automatically once the catalog loads a product

**Verification:**
- [ ] Tests pass: `.\gradlew.bat :app:testDebugUnitTest`
- [ ] Build succeeds: `.\gradlew.bat :app:assembleDebug`
- [ ] Manual check: `.\gradlew.bat :app:installDebug` with `plant_models` still empty → open AR →
      message appears, tapping does nothing surprising, back exits cleanly

**Dependencies:** None

**Files likely touched:**
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ArActivity.kt` (the `ArScreen`
  composable, near the notice-bar block ~line 1487)

**Estimated scope:** Small (1 file)

---

## Task 2: Create a throwaway test product and upload the lantern GLB

**Description:** `plant_models` is empty, so nothing can be placed (defect D0a). Register the existing
`Hanging_Lantern` GLB against a clearly-named throwaway product rather than a real catalog item, so
nobody opening the app mid-development sees a lantern labelled "Monstera Deliciosa". Uploading through
the admin form — not a direct insert — is deliberate: it exercises the real validation path, so we learn
whether upload works at the same time.

**Acceptance criteria:**
- [ ] A clearly-named throwaway product (e.g. *"AR Test Object"*) exists with a `plant_models` row
      created **through the admin upload form**
- [ ] `height_cm` is `57` (matches the asset's 0.570 m Y extent)
- [ ] No real catalog product is modified

**Verification:**
- [ ] Manual check:
      `php artisan tinker --execute="echo App\Models\Product::arEnabled()->where('is_active',true)->whereNull('archived_at')->count();"`
      returns `1`
- [ ] Manual check: on the phone, the AR catalog drawer lists it with thumbnail and price
- [ ] Tests pass: `php artisan test --filter=Ar` (unchanged, no regression)

**Dependencies:** None (independent of Task 1, but Checkpoint 1 wants Task 1 verified first)

**Files likely touched:**
- None — data entry via the admin UI at `admin/products/{product}/edit`
- Source asset: `storage/app/public/ar-models/O8s2aEI4J3Uu0w1gGm9LYtWwW0OsUKVyEMhKvJJ7.glb`
  (`Hanging_Lantern`, 7,644 triangles, 0.21 MB, no textures, no required extensions)

**Estimated scope:** XS (0 files — configuration/data)

---

## Task 3: Write the manual device test script

**Description:** The rendering fix can only be proven on a physical ARCore phone, so the script that
proves it must exist *before* Checkpoint 2 rather than after — writing it first also pins down what
"verified" means before any renderer code changes.

**Acceptance criteria:**
- [ ] `docs/ar-manual-test.md` is a numbered script, each step naming the logcat line that confirms it
- [ ] Step 1 compares `ipconfig` output against `FEROSA_SERVER_URL` in `gradle.properties` (Risk R7)
- [ ] Covers all seven rendering criteria from the spec plus the empty-state and corrupt-GLB cases, and
      explicitly requires five placements in one session (Risk R2)

**Verification:**
- [ ] Manual check: hand the script to someone who did not write it; every step is unambiguous
- [ ] Manual check: run steps 1–2 now to confirm the environment section is accurate

**Dependencies:** None

**Files likely touched:**
- `docs/ar-manual-test.md`

**Estimated scope:** Small (1 file)

---

> ## ✅ Checkpoint: Baseline
> - [ ] Empty state confirmed on device *while the catalog is still empty*
> - [ ] `Product::arEnabled()` returns 1; catalog drawer lists the test product on the phone
> - [ ] Device test script runnable by someone who did not write it
> - [ ] **Any failure after this point is a rendering failure, not a catalog or auth failure.**
>       If the catalog does *not* load, see Risk R3 — a JSON parse error here means "not logged in"
> - [ ] Review with human before proceeding

---

# Phase 2: Rendering — the core fix

## Task 4: Load the cached GLB as a ByteBuffer instead of a path string

**Description:** This is the bug that stops models rendering (D1). `ArActivity.kt:842` passes
`file.absolutePath` to `loadModelInstanceAsync`, but SceneView resolves that through
`FileLoader.readFileBuffer`, which switches on `Uri.parse(location).scheme` — a bare path has no scheme,
so it falls to `context.assets.open(path)` and searches the APK. Read the file ourselves and hand
Filament a buffer. See spec § D1 for the full evidence trail.

**Acceptance criteria:**
- [ ] `loadModelInstanceAsync(fileLocation = …)` is gone; no scheme-less path reaches SceneView
- [ ] The cached file is read into a `ByteBuffer` on `Dispatchers.IO`, then
      `modelLoader.createModelInstance(buffer)` runs on the main thread, still guarded by
      `isSceneCurrent(sv, generation)`
- [ ] Every failure path detaches the anchor and surfaces a customer-readable message

**Verification:**
- [ ] Tests pass: `.\gradlew.bat :app:testDebugUnitTest`
- [ ] Build succeeds: `.\gradlew.bat :app:assembleDebug`
- [ ] Manual check: place one model on the phone → it appears; the `FerosaAR` debug log shows
      `renderables` > 0 with a sane `halfExtent` (Risk R1)

**Dependencies:** Task 2 (needs a placeable product to verify)

**Files likely touched:**
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ArActivity.kt` (`placeModel`,
  ~lines 810–953)

**Estimated scope:** Small (1 file)

---

## Task 5: Apply the session configuration at construction time

**Description:** `sv.sessionConfiguration = { … }` is assigned *after* the `ARSceneView` constructor,
but `ARSceneView.init` adds its lifecycle observer, and an already-RESUMED Activity makes
`LifecycleRegistry` replay `ON_CREATE`/`ON_RESUME` synchronously inside that constructor — so
`onSessionCreated` reads the property while it is still `null`. Plane-finding mode, HDR light
estimation, depth mode, and depth occlusion are all silently discarded (D2).

**Acceptance criteria:**
- [ ] Configuration is applied whether or not the session was created inside the constructor, via the
      `sessionConfiguration` constructor argument or `configureSession { }`
- [ ] **Both** construction branches at `ArActivity.kt:1071-1076` are covered (Risk R5)
- [ ] `onSessionConfigChanged` logs the live config for verification

**Verification:**
- [ ] Tests pass: `.\gradlew.bat :app:testDebugUnitTest`
- [ ] Build succeeds: `.\gradlew.bat :app:lintDebug`
- [ ] Manual check: logged session config matches what the app requested; plane detection still works
      and placed models are lit rather than flat-shaded

**Dependencies:** Task 4 (same file — sequential for reviewable diffs)

**Files likely touched:**
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ArActivity.kt` (the `AndroidView`
  factory, ~lines 1069–1145)

**Estimated scope:** Small (1 file)

---

## Task 6: Regression test — the loader never receives a scheme-less path

**Description:** Lock D1 shut. The failure was invisible in unit tests because the path-to-loader
handoff lived inline in a Compose lambda. Extract that decision into a pure helper and test it, so the
bug cannot silently return.

**Acceptance criteria:**
- [ ] A JVM unit test fails if the model-loading input is a bare filesystem path
- [ ] The test targets a pure helper (resolving a cache `File` into loader input), not Compose code
- [ ] Reverting Task 4 makes this test fail

**Verification:**
- [ ] Tests pass: `.\gradlew.bat :app:testDebugUnitTest`
- [ ] Manual check: temporarily revert Task 4's change and confirm the test goes red

**Dependencies:** Task 4

**Files likely touched:**
- `ferosa_mobile/app/src/test/java/com/example/ferosa_landscaping/ArModelPlacementTest.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArModelPlacement.kt` (helper)

**Estimated scope:** Small (2 files)

---

> ## ✅ Checkpoint: Rendering — the checkpoint that matters
> On the physical ARCore phone, following `docs/ar-manual-test.md`:
> - [ ] Tap a tracked plane → model appears, base on the plane, no float or sink
> - [ ] Height within ±10% of `height_cm`, measured against a metre rule (spec criterion 2)
> - [ ] **Five models placed in one session without restarting the Activity** — proves the
>       `ModelLoader` scope is no longer cancelled by a first failure (Risk R2). A single successful
>       placement does **not** prove the fix
> - [ ] Session config confirmed applied via `onSessionConfigChanged`
> - [ ] `:app:testDebugUnitTest` and `:app:lintDebug` green
> - [ ] Review with human before proceeding

---

# Phase 3: Asset pipeline (parallel with Phases 1–2)

## Task 7: Reject non-allowlisted required glTF extensions

**Description:** `validateGlbResources` never inspects `extensionsRequired`, yet a glTF loader must
refuse a file whose required extensions it does not implement — so such an asset uploads cleanly and
fails on the phone (D3). The allowlist in spec § D3 was extracted from `libgltfio-jni.so` 1.52.0, the
loader actually shipped in this app.

**Acceptance criteria:**
- [ ] `extensionsRequired` entries outside the 17-item allowlist are rejected, naming the extension and
      how to remove it
- [ ] `EXT_meshopt_compression` **warns** rather than rejects (undecided pending device test)
- [ ] `extensionsUsed` is **never** a rejection reason — asset 2's `KHR_materials_emissive_strength`
      must still upload

**Verification:**
- [ ] Tests pass: `php artisan test --filter=Ar`, with new fixtures for a rejected `EXT_texture_webp`,
      a warned meshopt, and an accepted `extensionsUsed`-only file
- [ ] Manual check: re-upload the lantern GLB → still accepted

**Dependencies:** None

**Files likely touched:**
- `ferosa-laravel/app/Http/Controllers/AdminController.php`
- `ferosa-laravel/tests/Feature/` (AR upload test)

**Estimated scope:** Small (2 files)

---

## Task 8: Add performance budgets with real numbers in the message

**Description:** Nothing limits triangle count or texture resolution, and the scene allows 5 concurrent
models — five 4096² textures alone would need roughly 900 MB of decoded texture memory (D4). Everything
needed is already parsed: accessor `count` for triangles, PNG/JPEG headers in the BIN chunk for image
dimensions.

**Acceptance criteria:**
- [ ] Warn above 100k triangles, 2048 px texture edge, 48 MB total decoded texture memory, or 8 MB file
      size — each message stating the actual value against the budget
- [ ] Hard reject above 250k triangles or a 4096 px texture edge
- [ ] Warnings never block the upload; they surface in the admin panel

**Verification:**
- [ ] Tests pass: `php artisan test --filter=Ar` with over-budget and over-limit fixtures
- [ ] Manual check: re-uploading the lantern (7,644 tris, no textures) produces no warning

**Dependencies:** Task 7 (same validator chain — sequential for clean diffs)

**Files likely touched:**
- `ferosa-laravel/app/Http/Controllers/AdminController.php`
- `ferosa-laravel/tests/Feature/` (AR upload test)
- `ferosa-laravel/resources/views/admin/partials/ar-models.blade.php`

**Estimated scope:** Medium (3 files)

---

## Task 9: Write the asset guidelines document

**Description:** Assets come from free/paid model libraries, so uploads vary in units, orientation,
polycount, and extension usage. Admins need one page telling them what a good asset looks like and how
to fix a bad one. Written after Tasks 7–8 so the documented numbers match what is enforced.

**Acceptance criteria:**
- [ ] `docs/ar-asset-guidelines.md` covers self-contained `.glb`, glTF 2.0, Y-up, metres, base at
      origin, the allowlisted extensions, and the budget table
- [ ] Gives concrete fixes in Blender or `gltf-transform` for each failure mode
- [ ] States that bulk materials — soil, gravel, sod — are **not** AR candidates (spec § Catalog shape)
- [ ] **Requires a visual check before upload** — open the `.glb` in Windows 3D Viewer or Blender and
      confirm it is upright, right-way-round, and roughly the expected size. This is the documentation
      substitute for the deferred in-admin preview (Task 10), and it closes the same gap at zero cost:
      server-side validation cannot detect a model that is structurally valid but upside-down

**Verification:**
- [ ] Manual check: an admin unfamiliar with glTF can follow it to prepare a Sketchfab download for
      upload

**Dependencies:** Tasks 7, 8 (documents the rules they implement)

**Files likely touched:**
- `docs/ar-asset-guidelines.md`

**Estimated scope:** Small (1 file)

---

## Task 10: Interactive 3D preview in the admin upload panel — ⏸ DEFERRED

**Description:** The form shows filename, size, and height only, so an admin cannot tell whether a model
is upside-down, mis-scaled, or an empty shell (D5). **Deferred in favour of documentation:** Task 9 now
requires checking the `.glb` in Windows 3D Viewer or Blender before upload, which gives the same
information at zero cost and no dependency. Bundling a 3D engine is not worth it while (a) the uploader
is the developer, who has Blender open anyway, and (b) the catalog realistically supports 1–2 AR
products. Do not implement unless a trigger below fires.

**Revisit when any of these is true:**
- [ ] Someone other than the developer starts uploading models — they will not open Blender
- [ ] The catalog passes roughly four or five AR-enabled products
- [ ] An upside-down or mis-scaled model reaches a phone and costs a wasted device trip

**Acceptance criteria (only if un-deferred):**
- [ ] After upload, the admin sees the actual model in a rotatable 3D viewer
- [ ] `@google/model-viewer` bundled via npm + Vite — no CDN, no external network request at runtime
- [ ] Wrong orientation or scale is visually obvious in the preview
- [ ] Loaded only on admin pages; the customer-facing bundle is unchanged in size

**Verification:**
- [ ] Build succeeds: `npm run build` (note: `build` also runs `lint:blade-js`)
- [ ] Manual check: load the product edit page, confirm the lantern renders and orbits
- [ ] Manual check: browser devtools shows no external request for the viewer

**Dependencies:** Task 2 (needs an uploaded model to preview). Blocks nothing — deferred by decision,
not by a blocker.

**Implementation note if a trigger fires:** the viewer can point straight at the existing
`/api/ar/products/{id}/model` route. It sits behind `['web','auth']`, and an admin logged into the
browser is already session-authenticated — so no new endpoint is needed and the missing `public/storage`
symlink (D6) is irrelevant. Add `@google/model-viewer` as a fourth admin-only Vite entry point
(`resources/js/admin/ar-preview.js`) alongside the existing `admin/dashboard.js` and
`admin/notifications.js`, so customer-facing pages carry none of its weight.

**Files likely touched:**
- `ferosa-laravel/package.json`
- `ferosa-laravel/resources/js/app.js` (or an admin entry point)
- `ferosa-laravel/resources/views/admin/partials/ar-models.blade.php`

**Estimated scope:** Medium (3 files)

---

> ## ✅ Checkpoint: Asset pipeline
> - [ ] `php artisan test --filter=Ar` green across every rejection and warning path
> - [ ] Re-uploading the lantern produces no warning
> - [ ] Guidelines usable by someone unfamiliar with glTF, including the pre-upload visual check
> - [ ] Task 10 remains deferred, with its revisit triggers recorded

---

# Phase 4: Closeout

## Task 11: Confirm and report the `public/storage` symlink situation

**Description:** `public/storage` is absent. AR model downloads stream via
`Storage::disk('public')->download()` and are unaffected, but catalog thumbnails may be broken (D6).
Investigate and report only — fixing it is explicitly out of scope.

**Acceptance criteria:**
- [ ] Written finding on whether catalog thumbnails are broken by the missing symlink
- [ ] No fix applied

**Verification:**
- [ ] Manual check: load a product page and the AR catalog drawer; note whether images resolve

**Dependencies:** None

**Files likely touched:**
- `docs/specs/ar-visualizer-rendering.md` (a note appended to Carried Forward)

**Estimated scope:** XS (1 file, documentation only)

---

## Task 12: Delete the orphaned keycap GLB

**Description:** `FkMPbMskGclrfDBGDQJ6zJsZqStqCkrfuj4PhcCV.glb` is a pink keycap with a face — leftover
test junk with no database row and no relationship to a landscaping catalog. Remove it. Keep the lantern
until the throwaway test product is retired.

**Acceptance criteria:**
- [ ] The keycap file is gone from `storage/app/public/ar-models/`
- [ ] The lantern file remains

**Verification:**
- [ ] Tests pass: `php artisan test`
- [ ] Manual check: AR catalog unaffected

**Dependencies:** None

**Files likely touched:**
- None (file deletion)

**Estimated scope:** XS (0 files)

---

> ## ✅ Checkpoint: Complete
> - [ ] `.\gradlew.bat :app:testDebugUnitTest`, `.\gradlew.bat :app:lintDebug`, and
>       `php artisan test` all green
> - [ ] Manual device script passes end to end
> - [ ] All 17 spec success criteria met
> - [ ] Ready for review

---

# Follow-ups (not this scope — spec § Carried Forward)

- [ ] Source a real Monstera Deliciosa GLB, upload to product 11 with a measured `height_cm`, then
      retire the throwaway test product and delete the lantern file.
- [ ] Test one `EXT_meshopt_compression` GLB on the phone. If it renders, move meshopt to allow; if
      not, to hard reject. Until then it warns.
- [ ] Re-tune the Task 8 budget numbers after measuring five simultaneous models on the target phone.
