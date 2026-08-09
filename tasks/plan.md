# Implementation Plan: AR Visualizer Rendering + Asset Pipeline

**Spec:** `docs/specs/ar-visualizer-rendering.md` — read the *Defects to fix* section before starting
any task. Every task cites the defect it closes.
**Task list:** `tasks/todo.md`

## Overview

The Android AR visualizer is fully built but has never successfully placed a model. Three defects sit
in a chain: an empty product catalog silently swallows every tap (D0), a bare filesystem path handed to
SceneView's loader makes it search the APK's assets and throw `FileNotFoundException` (D1), and the
ARCore session configuration is assigned after the session has already been created, so it is silently
discarded (D2). This plan fixes that chain, then hardens the admin GLB upload against the two failure
classes that library-sourced assets actually hit — unsupported required glTF extensions (D3) and
unbounded triangle/texture cost (D4) — and gives the admin an in-browser 3D preview so bad assets are
caught before a customer's phone (D5).

## Architecture Decisions

- **Read the GLB ourselves; never hand SceneView a path string.** SceneView's `FileLoader` switches on
  `Uri.parse(location).scheme` and treats a scheme-less path as an APK asset name. Passing a
  `ByteBuffer` to `createModelInstance` sidesteps that entire resolution path, and also avoids
  `FileLoader`'s single unchecked `read()` sized by `available()`, which can truncate a large model.
  *Rationale: fixes the root cause rather than papering over it with a `file://` prefix, which would
  work but leave the truncation hazard.*
- **Configure the ARCore session at construction time.** The `sessionConfiguration` property is read
  during `onSessionCreated`, which fires synchronously inside the `ARSceneView` constructor when the
  host Activity is already RESUMED. Pass the lambda as a constructor argument or use
  `configureSession { }`, which re-configures an already-created session.
- **Extension allowlist derived from the shipped binary, not from memory.** The 17 supported glTF
  extensions were extracted from `libgltfio-jni.so` 1.52.0. *Rationale: my first attempt at this rule
  asserted meshopt was unsupported from recall and was wrong. Evidence from the actual artifact is the
  only defensible basis, and it caught `EXT_texture_webp` and `KHR_materials_anisotropy`, which recall
  had missed.*
- **Warn on performance, reject only what cannot render.** Hard rejects are reserved for arithmetic
  certainties (non-allowlisted required extensions; >4096 px textures, since five of those cannot fit
  in memory). Blocking a paid asset an admin cannot re-export is hostile.
- **Keep `calculateGroundedModelTransform` untouched.** It is correct, unit-tested, and already handles
  the arbitrary export pivots and non-metre authoring scales present in both real assets.
- **Deliberate deviation from strict vertical slicing:** Tasks 4 and 5 (loader fix, session config)
  together form one user-visible slice — *"a customer places a correctly-lit model on a detected
  plane."* They are kept as two tasks so each diff stays reviewable and a regression can be bisected to
  one cause. Every other task is an independently verifiable slice.

## Dependency Graph

```
Task 3  device test script ─────────────┐   (defines what "verified" means for Phase B)
                                        │
Task 1  empty-state UI                  │
   │  (testable only while catalog is    │
   │   still empty — do it first)        │
   ▼                                    │
Task 2  test product + GLB upload ──────┼──► Task 4  loader fix (D1)
   │  (unblocks all device testing)     │        │
   │                                    │        ▼
   │                                    └──► Task 5  session config (D2)
   │                                             │
   │                                             ▼
   │                                        Task 6  regression test
   │
   └──────────────────────────────────────► Task 10 admin 3D preview ⏸ DEFERRED (see Decisions)

BACKEND TRACK — no Android dependency, fully parallel:

Task 7  extension allowlist (D3)
   │
   ▼
Task 8  performance budgets (D4)      (same validator chain — sequential to keep diffs clean)
   │
   ▼
Task 9  asset guidelines doc          (documents the numbers 7 and 8 enforce)

CLOSEOUT:  Task 11 symlink report · Task 12 delete orphaned keycap
```

Implementation order is bottom-up on this graph. The backend track (7→8→9) can proceed at any time and
does not gate the rendering chain.

## Task List

### Phase 1: Baseline — make the catalog real and define "verified"

- [ ] Task 1: Add an empty-state to the AR screen (D0b)
- [ ] Task 2: Create a throwaway test product and upload the lantern GLB (D0a)
- [ ] Task 3: Write the manual device test script

### Checkpoint: Baseline
- [ ] Empty state confirmed on device *while the catalog is still empty*
- [ ] `Product::arEnabled()` returns 1; catalog drawer lists the test product on the phone
- [ ] Device test script is runnable by someone who did not write it
- [ ] **Any failure after this point is a rendering failure, not a catalog or auth failure**
- [ ] Review with human before proceeding

### Phase 2: Rendering — the core fix

- [ ] Task 4: Load the cached GLB as a ByteBuffer instead of a path string (D1)
- [ ] Task 5: Apply the session configuration at construction time (D2)
- [ ] Task 6: Regression test — the loader never receives a scheme-less path

### Checkpoint: Rendering (the checkpoint that matters)
- [ ] Model appears on tap, base on the plane, no float or sink
- [ ] Height within ±10% of `height_cm`, measured against a metre rule
- [ ] **Five models placed in one session without restarting the Activity** (see Risk R2 — a single
      successful placement does *not* prove the fix)
- [ ] Session config confirmed applied via `onSessionConfigChanged`
- [ ] `:app:testDebugUnitTest` and `:app:lintDebug` green
- [ ] Review with human before proceeding

### Phase 3: Asset pipeline (parallel with Phases 1–2)

- [ ] Task 7: Reject non-allowlisted required glTF extensions (D3)
- [ ] Task 8: Add performance budgets with real numbers in the message (D4)
- [ ] Task 9: Write the asset guidelines document, including the pre-upload visual check
- [ ] ~~Task 10: Interactive 3D preview in the admin upload panel (D5)~~ — ⏸ **deferred**, see below

### Checkpoint: Asset pipeline
- [ ] `php artisan test --filter=Ar` green across every rejection and warning path
- [ ] Re-uploading the lantern (7,644 tris, no textures) produces no warning
- [ ] An admin unfamiliar with glTF can follow the guidelines to prepare a Sketchfab download
- [ ] Task 10 remains deferred, with its revisit triggers recorded

### Phase 4: Closeout

- [ ] Task 11: Confirm and report the `public/storage` symlink situation (D6)
- [ ] Task 12: Delete the orphaned keycap GLB

### Checkpoint: Complete
- [ ] `:app:testDebugUnitTest`, `:app:lintDebug`, and `php artisan test` all green
- [ ] Manual device script passes end to end
- [ ] All 17 spec success criteria met
- [ ] Ready for review

## Definition of Done

`references/definition-of-done.md` is cited by the planning skill but absent from this repo, so the
standing bar is taken from the spec's **Boundaries → Always** list. Every task clears all of it before
it counts as done:

- `:app:testDebugUnitTest` and `php artisan test --filter=Ar` pass
- Any rendering change is verified on the physical ARCore phone — an emulator pass is not evidence
- Every Filament/renderer mutation is on the main thread, guarded by `isSceneCurrent(sv, generation)`
- Every failure path detaches its ARCore anchor
- Failure text is customer-readable, not a stack trace
- No new dependency, schema change, or `/api/ar/*` shape change without asking first

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| **R1** After D1 is fixed, models still don't appear for a different reason (materials, lighting, scale) | High | The debug log at `ArActivity.kt:889` already prints `center`, `halfExtent`, scale, position, and `renderableNodes.size`. Non-empty renderables with a sane halfExtent means the model loaded and the problem is visual — a different investigation. Test with the known-good lantern first. |
| **R2** One failed placement poisons the whole session | High | `ModelLoader`'s scope has no `SupervisorJob`, so the first uncaught exception cancels it and every later load silently no-ops. Fully restart the Activity between debug attempts. Never conclude "still broken" from a second placement after a first failure. Checkpoint 2 requires five placements for exactly this reason. |
| **R3** Catalog load failure presents as a JSON syntax error, not an auth error | Medium | `/api/ar/products` returns `302 → /login` unauthenticated (verified), and OkHttp follows redirects, so the app gets HTTP 200 with HTML and Gson throws while `ModelRepository`'s 401 branch never fires. A malformed-JSON message here means "not logged in" — suspect WebView cookie sharing, not the contract. |
| **R4** `createModelInstance` on the main thread janks a frame | Low | Filament entity creation must be on the main thread. Current assets are ≤0.66 MB so this is theoretical; the Task 8 file-size budget is the long-term guard. Measure if a placement visibly hitches. |
| **R5** `ARSceneView` is constructed in two branches | Medium | `ArActivity.kt:1071-1076` builds the view one way with an Activity and another via `?:` fallback. Task 5 must cover **both**, or the config keeps being silently dropped in whichever branch was missed. |
| **R6** ~~`@google/model-viewer` is not approved~~ — **retired**: Task 10 deferred, no dependency added | — | Superseded by the decision below. The gap it addressed is covered by Task 9's pre-upload visual check. |
| **R7** The dev machine's LAN IP changes | Medium | `FEROSA_SERVER_URL` is compiled into the APK via `buildConfigField`, so a DHCP change silently breaks device testing and looks like a server outage. Comparing `ipconfig` against `gradle.properties` is step 1 of the device test script. |
| **R8** The throwaway test product is seen by someone mid-development | Low | It is clearly named and inactive, and Task 12's follow-up retires it. Chosen over attaching the lantern to a real product precisely to avoid a customer seeing a lantern labelled "Monstera Deliciosa". |

## Parallelization

- **Safe to parallelize:** the backend track (Tasks 7→8→9) against the Android track (Tasks 1→2→4→5→6).
  They share no files. Task 3 is documentation and can be written at any point before Checkpoint 2.
- **Must be sequential:** Tasks 4 → 5 (same file, different regions — sequential for reviewable diffs
  and bisectability). Tasks 7 → 8 (same validator chain in `AdminController`).
- **Needs coordination:** none currently. Task 10 would have needed an uploaded model to preview (a
  cross-track dependency on Task 2), but it is deferred.

## Decisions Taken During Planning

- **Task 10 (in-admin 3D preview) is deferred; no dependency added.** The gap is real — server-side
  validation cannot detect a model that is structurally valid but upside-down — but it is closed more
  cheaply by Task 9 requiring a visual check in Windows 3D Viewer or Blender before upload. Bundling a
  3D engine is not justified while the uploader is the developer (who has Blender open anyway) and the
  catalog realistically supports 1–2 AR products. Revisit triggers are recorded on Task 10: a
  non-developer uploader, roughly five or more AR products, or a first wasted device trip.

## Open Questions

- **Does `EXT_meshopt_compression` actually render?** It appears in `libgltfio-jni.so` with no decoder
  diagnostics, which suggests cgltf parses it while leaving decompression to the caller — but that is
  not proven from binary strings. Until one meshopt GLB is tested on the phone it warns rather than
  rejects. Tracked in the spec's Carried Forward.
- **Final budget numbers** are conservative by design and should be re-tuned after measuring five
  simultaneous models on the target phone.

## What this plan deliberately does not do

- Rewrite `calculateGroundedModelTransform` — correct and already handling both assets' odd pivots.
- Replace or upgrade SceneView. The bugs are in how the app calls it, not in the library.
- Add a non-AR 3D fallback, customer uploads, or model-render thumbnails — all out of scope per spec.
- Fix the missing `public/storage` symlink. Task 11 investigates and reports only.
