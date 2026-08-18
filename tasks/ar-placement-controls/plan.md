# Implementation Plan: AR Placement Preview and Object Controls

**Approved spec:** `docs/specs/ar-placement-controls.md`<br>
**Detailed checklist:** `tasks/ar-placement-controls/todo.md`<br>
**Status:** Implementation complete — automated gates and implementation review passed; physical
device closeout awaits reconnecting the ARCore phone

## Overview

Extend the existing Android AR screen from tap-to-place to aim-and-confirm placement. The current
centre reticle and 150 ms centre hit-test probe provide the foundation, while the validated Monstera
provides a complete development fixture. The implementation adds one unanchored real-size preview,
an explicit Place button, a deterministic 180-degree turn, and stricter coordination with the
existing Move and Remove flows. No resizing, dependency, backend, database, or catalog work is part
of this plan.

## Measured Baseline

- `ArActivity.kt` already probes the centre of `ARSceneView` every 150 ms and renders a
  `PlacementReticle`, but the reticle shows only readiness—not the selected model.
- `onSingleTapUp` currently selects a nearby placed model first and otherwise calls `placeModel` at
  the tap coordinates. That second branch must be removed.
- `placeModel` currently performs download/cache resolution, model-instance construction,
  real-height scaling, grounding, anchoring, and ViewModel registration as one operation.
- `ProductInfoPanel` already exposes Move and Remove; removal detaches the anchor and frees the slot.
- `calculateGroundedModelTransform` is already correct and unit-tested. This plan reuses it without
  altering scale policy.
- The five-placement limit is owned by `ArViewModel.MAX_PLACED_MODELS` and remains authoritative.

## Architecture Decisions

1. **One source of truth for control eligibility.** Add pure placement-control state/rules that drive
   button enablement and are unit-testable without Compose, ARCore, or Filament.
2. **One preview generation at a time.** Product selection increments a generation token. Any older
   async cache/model completion becomes stale and must release its result instead of entering the
   scene.
3. **No preview anchor.** The preview is a transient scene node positioned from the latest centre
   hit pose. Place performs a fresh centre hit test and is the only action that creates an ARCore
   anchor.
4. **Prepare once, instantiate deliberately.** Selecting a product resolves and validates its cached
   GLB off the main thread. Filament model-instance creation remains on the main thread. Repeated AR
   frames never perform network/file/model construction work.
5. **Keep placement transactional.** A Place press disables itself, performs one fresh hit test,
   creates at most one anchor/instance, registers one placement, and either completes once or cleans
   up every temporary resource.
6. **Use positive-scale yaw rotation.** “Turn 180°” changes yaw only and normalizes it to `[0, 360)`;
   it never mirrors, tilts, moves, or rescales the model.
7. **Extend existing object controls.** Keep the current product panel and direct Remove path. Add
   Turn 180° there; do not introduce a second selection system, confirmation dialog, or Undo stack.
8. **Namespaced planning files.** The repository's root `tasks/plan.md` and `tasks/todo.md` belong to
   the still-relevant rendering/asset-pipeline work, so this extension uses
   `tasks/ar-placement-controls/` rather than overwriting them.

## Dependency Graph

```text
Task 1  pure control state and rules
   │
   ▼
Task 2  one real-size transient preview
   │
   ▼
Task 3  crosshair-driven preview + explicit Place button
   │
   ├──────────────► Task 4  Turn 180° for preview and placed object
   │                         │
   └──────────────► Task 5  Move/Remove/lifecycle coordination
                             │
                             ▼
                    Task 6  device script and regression gate
```

Tasks 4 and 5 touch shared AR screen state and therefore run sequentially even though their user
behaviors are conceptually independent.

## Task List

### Phase 1: Deterministic foundation

- [x] Task 1: Define and test placement-control rules
- [x] Task 2: Build the single transient preview lifecycle

### Checkpoint: Preview foundation — ✅ reviewed

- [x] Unit tests and debug build pass
- [ ] Selecting Monstera creates no more than one preview instance (manual confirmation is deferred
      until Task 3 removes the legacy tap-to-place path)
- [x] Preview owns no ARCore anchor and does not change the placement counter
- [x] Switching product/reset/disposal cannot show a stale preview

Checkpoint review is recorded in `tasks/ar-placement-controls/todo.md`; the required Filament
cleanup fix is committed as `f0d91db`. The physical preview-only interaction check continues after
Task 3 removes the legacy tap-to-place path.

### Phase 2: Aim and confirm

- [x] Task 3: Drive the preview from the crosshair and place only from the button
- [x] Task 4: Add Turn 180° to preview and selected-object controls

### Checkpoint: Core interaction — ✅ reviewed

- [x] Valid target shows one grounded actual-size preview and enables Place
- [ ] Invalid/stale target hides preview and disables Place within the specified 500 ms (final
      timing evidence remains in Task 6's device script)
- [x] Empty-surface taps never place; one Place press commits exactly one model
- [x] Preview/placed half-turn preserves position, grounding, and actual size by reusing the same
      grounded transform and mutating yaw only
- [x] Physical phone behavior reviewed before lifecycle hardening

Checkpoint review (2026-08-18): tests were reviewed first, then correctness, readability,
architecture, security, and performance. No required findings remained: the preview and committed
Turn 180° paths use the pure normalized-yaw helper, copy the preview yaw into the committed node,
and mutate only the selected placed node. The SurfaceView bounds bridge prevents camera taps from
falling through to placement while retaining accessible Compose controls. Focused/full JVM tests,
`lintDebug`, and `assembleDebug` passed; the connected ARCore phone showed the preview controls,
`0/5` aiming state, a single `1/5` commit, and `yawDegrees=180.0` in filtered logcat. Preview
cancellation/reset hardening and invalid-target timing remain explicitly assigned to Task 5/6.

### Phase 3: Existing controls and closeout

- [x] Task 5: Coordinate preview with selection, Move, Remove, limit, and lifecycle
- [x] Task 6: Extend the device script and clear the automated regression gate

### Checkpoint: Complete — implementation reviewed; physical evidence pending

- [x] Remove deletes only the selected instance, detaches its anchor, and immediately frees one slot
- [x] At `5/5`, Place is unavailable; after removal it becomes available on a valid target
- [x] Background/foreground, reset, product switch, cancellation, and failure paths have guarded
      preview/anchor cleanup
- [x] Unit tests, lint, and debug build pass
- [ ] The updated physical-device script passes with the current Monstera (the phone's wireless
      ADB endpoint is currently refusing connections)
- [ ] Final five-distinct-product sign-off remains explicitly deferred until four more assets exist

Task 5 review (2026-08-18): tests were reviewed first, followed by correctness, readability,
architecture, security, and performance. Three required correctness findings were fixed during the
review: the old one-model visibility gate hid Place after the first commit, duplicate/queued Place
events lacked an async in-flight latch, and reset/remove/disposal could leave a Move operation's
temporary original anchor attached. The fixes are in `0c283af` and `1fb8880`; synchronous placement
start failures also restore the preview and release the latch. Full JVM tests, `lintDebug`, and
`assembleDebug` pass. The device showed the corrected `1/5` state with the next preview controls
visible; the remaining five-slot/remove/background sequence is assigned to Task 6 because the
connected phone disconnected during the final run.

Final review (2026-08-18): tests were reviewed first, followed by correctness, readability,
architecture, security, and performance across the complete implementation. One required teardown
finding was fixed in `c881815`: Activity-level release/force-release now detach both the active and
temporary original Move anchors. No further code findings remain. The updated device script is
committed, automated gates are green, and the implementation is ready for the remaining physical
Monstera run; the five-distinct-product release gate remains pending the four additional assets.

## Verification Commands

From `C:\xampp\htdocs\ferosa` in PowerShell:

```powershell
Set-Location .\ferosa_mobile
$env:JAVA_HOME = 'C:\Program Files\Android\Android Studio\jbr'
$env:Path = "$env:JAVA_HOME\bin;$env:Path"
.\gradlew.bat :app:testDebugUnitTest
.\gradlew.bat :app:lintDebug
.\gradlew.bat :app:assembleDebug
.\gradlew.bat :app:installDebug
```

Physical-device evidence uses `docs/ar-manual-test.md` and filtered logcat:

```powershell
adb logcat -c
adb logcat -v threadtime FerosaAR:V Filament:V ARCore:V sceneview:V AndroidRuntime:E '*:S'
```

## Definition of Done

Every implementation task must satisfy all applicable items:

- Behavior is covered by focused JVM tests when it can be expressed without renderer objects.
- `:app:testDebugUnitTest` passes after logic changes.
- `:app:lintDebug` and `:app:assembleDebug` pass at each checkpoint.
- Renderer mutation remains on the main thread; cache/file work remains off it.
- New async work is generation-guarded and cleans up on cancellation, reset, and disposal.
- New controls have readable labels/content descriptions and at least 48 dp touch targets.
- No resize, dependency, backend/API, database, or five-model-limit change is introduced.
- A renderer-facing checkpoint is demonstrated on the physical ARCore phone.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Preview pose jitters as hit results vary | High | Retain the existing throttled probe; update only from tracked valid hits and add smoothing only if device evidence requires it. |
| Stored target becomes stale before Place | High | Re-hit-test the exact reticle coordinate on button press; never anchor solely from cached readiness. |
| Preview and committed model briefly overlap | Medium | Make commit state explicit and disable controls; hide or replace the preview during the transaction. |
| Product switches while a model is loading | High | Use product/scene generation tokens and dispose stale completion results. |
| Model bytes/instances multiply across frames | High | Resolve on product change only; keep one preview; prohibit creation in `onSessionUpdated`. |
| Turning modifies the wrong transform component | Medium | Test yaw normalization and manually verify position, scale, and grounding before/after turn. |
| Remove and Move race with preview placement | High | Make moving/selecting/committing mutually exclusive states and derive enablement from one pure rule. |
| Current projected-centre selection misses large models | Medium | Preserve behavior for this scope; record a separate defect if device testing proves selection unreliable. |
| One Monstera cannot prove cross-model compatibility | Medium | It is sufficient for interaction development; retain the rendering spec's five-distinct-product release gate. |

## Open Questions

None. Any request for arbitrary rotation, mirror flip, resizing, Undo, or persisted scenes reopens the
approved spec before it enters this plan.
