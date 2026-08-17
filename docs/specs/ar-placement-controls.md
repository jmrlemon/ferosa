# Spec: AR Placement Preview and Object Controls

**Status:** Approved on 2026-08-18 — planning gate open; implementation still requires plan approval<br>
**Scope:** Android AR visualizer only<br>
**Plan:** `tasks/ar-placement-controls/plan.md`<br>
**Task list:** `tasks/ar-placement-controls/todo.md`
**Relationship to existing work:** This is a new interaction extension. The completed
`docs/specs/ar-visualizer-rendering.md` remains the rendering and asset-pipeline contract; this
document restates every rendering invariant that the new interaction must preserve.

## Objective

Replace tap-anywhere placement with a deliberate aim-and-confirm flow. A Ferosa customer selects a
product, aims the centre crosshair at a tracked horizontal surface, sees that product at its real
size before committing it, and presses a visible **Place** button to add it to the design. A placed
object can be moved, turned 180 degrees, inspected, added to the cart, or removed.

The change is intended to reduce accidental placements and let customers judge location and
orientation before consuming one of the five placement slots.

### User flow

1. The customer opens AR and selects an AR-enabled product.
2. The app prepares one real-size preview for that product.
3. The customer moves the phone until the centre crosshair intersects a tracked, upward-facing
   horizontal surface.
4. The preview follows the current crosshair target and remains grounded on that surface.
5. The customer may press **Turn 180°** to reverse the preview's facing direction.
6. The customer presses **Place**. Only then does the app create a persistent anchor and add one to
   the placement counter.
7. Tapping a placed object opens its existing product controls. The customer can move it, turn it
   180 degrees, remove it, or add the product to the cart.

### Terminology

- **Preview:** one temporary, uncommitted model associated with the selected catalog product.
- **Place:** commit the preview at the current valid crosshair target as an anchored model.
- **Turn 180°:** rotate around the vertical Y axis by half a turn. The UI must not call this merely
  "Flip," because mirroring and turning upside down are explicitly not supported.
- **Remove:** delete one selected placed object from the scene and release its placement slot.
- **Actual size:** the uniform scale calculated from the product's authoritative `height_cm`.

## Scope

### In scope

- A real-size model preview at the centre crosshair.
- A dedicated Place button with valid, disabled, loading, and five-item-limit states.
- Turning the preview or a placed model by exactly 180 degrees around the vertical axis.
- Existing placed-model selection, Move, Remove, product information, and Add to cart behavior.
- Preview lifecycle cleanup when the product, AR session, or Activity changes.
- Automated tests for pure placement-control rules and physical-device testing for AR behavior.

### Out of scope

- Resizing, pinch-to-scale, or any control that changes actual product dimensions.
- Mirroring geometry, negative scale, vertical flipping, or placing a product upside down.
- Free-angle rotation gestures. This version provides a deterministic 180-degree turn only.
- Vertical-wall placement, persisted/cloud anchors, multi-user designs, or saving a scene between
  launches.
- Backend, database, catalog API, GLB upload, or asset-budget changes.
- Importing the remaining catalog models. One validated Monstera is sufficient to develop this
  interaction; the five-distinct-model release test remains required later.

## Functional Requirements

### R1 — Crosshair target

- The target is always the centre of the visible AR camera area, not the centre of the entire window
  behind the catalog drawer.
- The app probes only tracked, upward-facing horizontal planes or supported depth points using the
  existing placement hit-test policy.
- The crosshair visibly distinguishes these states:
  - searching or invalid target;
  - valid target but preview still loading;
  - valid target with preview ready;
  - placement unavailable because five models are already placed.
- Surface probing must be throttled; it must not create an anchor, start a download, or construct a
  model instance every frame.

### R2 — Real-size preview

- Selecting a product starts preparing one preview model. Switching products disposes the old
  preview and prevents stale completion callbacks from showing it later.
- At most one preview model exists at a time.
- The preview uses the same validated/cached GLB and `calculateGroundedModelTransform` path as a
  committed placement.
- The preview's height equals the selected product's `height_cm`; its bottom remains on the target
  surface regardless of the asset's export pivot.
- The preview follows the most recent valid centre hit without creating an ARCore anchor.
- When no valid target exists, the preview is hidden and the Place button is disabled. It must not
  remain visible at an obsolete world position that could mislead the customer.
- The preview is clearly identified by the crosshair and the text **Preview — not placed**. Material
  transparency is optional because arbitrary uploaded materials may not support safe opacity
  mutation.
- Preview preparation or rendering failure produces a customer-readable notice and leaves the AR
  screen usable.

### R3 — Explicit Place action

- Surface taps no longer place new models. They remain available for selecting placed objects.
- A persistent, minimum-48-dp Place button appears above the catalog drawer while a product is
  selected.
- The Place button is enabled only when:
  - a product is selected;
  - its preview is ready;
  - the current crosshair target is valid and fresh;
  - no placement is already being committed; and
  - fewer than five models are placed.
- Pressing Place creates an anchor from the latest valid target and commits a distinct model
  instance with the preview's scale, grounding, and yaw.
- Each press can create at most one placement. The button remains disabled until that commit either
  succeeds or fails.
- A successful commit increments the counter exactly once. The preview remains available for
  another placement while under the five-model limit.
- A failed or cancelled commit detaches any temporary anchor, does not increment the counter, and
  shows a readable notice.
- The app must reject a stale target rather than placing at a position observed before tracking was
  lost.

### R4 — Turn 180 degrees

- The preview controls include **Turn 180°** once the preview is ready.
- The existing placed-object panel includes **Turn 180°** for the selected object.
- Each activation adds 180 degrees of yaw around the object's local vertical Y axis and normalizes
  the stored value into `[0, 360)`.
- Turning does not change position, anchor, scale, height, grounding, placement count, or cart
  identity.
- The preview's yaw is copied to the committed placement.
- Selecting a different catalog product resets preview yaw to zero. Turning an already placed model
  affects only that placement.

### R5 — Remove a placed object

- Tapping a placed object continues to open its product panel, which contains a clearly labelled
  Remove action.
- Remove affects only the selected placement, even when several instances represent the same
  product.
- Removal immediately removes the node from the scene, detaches its anchor, closes the product
  panel, decrements the counter once, and frees a placement slot.
- Removing the last placed model returns the interface to preview/placement mode when a product is
  selected and a valid target exists.
- Removing an object never removes its catalog product, cached GLB, or cart item.
- A separate confirmation dialog and Undo are not part of this version.

### R6 — Move and selection compatibility

- Long-press/drag and the existing Move action remain available for committed models only.
- While moving a model, preview controls and the Place button are disabled to avoid simultaneous
  state changes.
- Cancelling a move restores the original anchor, position, yaw, and scale.
- Surface taps first attempt to select a placed model; tapping empty surface does not commit the
  preview.

### R7 — Lifecycle and resource ownership

- Preview model creation and all Filament node mutations occur on the main thread; cache reads remain
  off the main thread.
- Product changes, scene reset, Activity disposal, session replacement, and load cancellation all
  dispose the preview and invalidate stale callbacks.
- Only committed models own ARCore anchors. The preview must never consume tracking budget by holding
  an anchor.
- Background/foreground must restore a usable preview flow without duplicating previews or losing
  already committed models under the existing lifecycle policy.
- No per-frame network requests, GLB reads, or model-instance creation are permitted.

## Interaction States

| State | Preview | Place button | Message |
|---|---|---|---|
| No product selected | Hidden | Disabled | Select an element to place |
| Product loading, no surface | Hidden | Disabled/loading | Preparing preview; move slowly and aim at the ground |
| Product ready, no valid surface | Hidden | Disabled | Move slowly and aim at the ground |
| Valid surface, preview loading | Hidden | Disabled/loading | Preparing real-size preview |
| Valid surface, preview ready | Visible and grounded | Enabled | Preview — not placed |
| Commit in progress | Visible or temporarily hidden | Disabled/loading | Placing… |
| Five models placed | Hidden | Disabled | Maximum 5 items placed |
| Moving a placed model | Hidden | Disabled | Move item; release on a detected surface |
| Preview/load failure | Hidden | Disabled until retry/product change | Customer-readable failure notice |

## Architecture Decisions

1. **Retain SceneView 2.2.1 and the current ARCore session.** No dependency or renderer change is
   needed.
2. **Use one transient preview node and zero preview anchors.** The latest centre hit supplies its
   world pose; Place is the only operation that creates an anchor.
3. **Keep actual-size scaling authoritative.** Preview and committed objects share the existing
   grounding transform based on `height_cm`; resizing is forbidden.
4. **Separate preview readiness from placement commitment.** A loaded preview does not count as a
   placed model and cannot appear in the product-info/cart selection flow.
5. **Use a half-turn, not mirroring.** A positive-scale 180-degree Y-axis rotation is predictable for
   arbitrary GLB assets and preserves normals, handedness, and grounding.
6. **Preserve current selection and Remove behavior.** The existing product panel is extended with
   Turn 180° rather than replaced.

## Tech Stack

- Android/Kotlin, Jetpack Compose, ARCore, and `io.github.sceneview:arsceneview:2.2.1`.
- Existing `ArActivity`, `ArViewModel`, `PlacedModel`, model cache, authenticated catalog, and
  Filament loader.
- New dependencies: none.
- Backend/API/database changes: none.

## Commands

Run from `C:\xampp\htdocs\ferosa` in PowerShell:

```powershell
Set-Location .\ferosa_mobile
$env:JAVA_HOME = 'C:\Program Files\Android\Android Studio\jbr'
$env:Path = "$env:JAVA_HOME\bin;$env:Path"
.\gradlew.bat :app:testDebugUnitTest
.\gradlew.bat :app:lintDebug
.\gradlew.bat :app:assembleDebug
.\gradlew.bat :app:installDebug

adb logcat -c
adb logcat -v threadtime FerosaAR:V Filament:V ARCore:V sceneview:V AndroidRuntime:E '*:S'
```

## Project Structure

```text
ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/
  ArActivity.kt                         AR scene, preview lifecycle, hit testing, UI wiring
  ui/ar/ArProductState.kt               preview/placement state contracts if extraction is needed
  ui/ar/ArViewModel.kt                  committed placements and selected-object state
  ui/ar/ArPlacementControls.kt          new Compose controls if extraction keeps ArActivity focused
  ui/ar/ArModelPlacement.kt             pure placement/rotation rules
  ui/ar/components/ProductInfoPanel.kt  existing Move/Remove/cart panel; add Turn 180°

ferosa_mobile/app/src/test/java/com/example/ferosa_landscaping/
  ArModelPlacementTest.kt               pure placement/rotation rule tests
  ArViewModelTest.kt                    placement-count and removal state tests if required

docs/
  ar-manual-test.md                     extend with preview/button/turn/remove device cases
```

File extraction is permitted only when it improves testability or keeps a touched file reasonably
focused. It is not a requirement to create every optional file shown above.

## Code Style

- Follow existing Kotlin formatting and Compose naming.
- Keep renderer objects out of pure state helpers so unit tests do not need Filament or ARCore.
- Express button eligibility as one tested rule rather than duplicating conditions in callbacks and
  composables.

```kotlin
internal fun canConfirmPlacement(
    hasSelectedProduct: Boolean,
    isPreviewReady: Boolean,
    hasFreshTarget: Boolean,
    isBusy: Boolean,
    placedCount: Int,
): Boolean = hasSelectedProduct &&
    isPreviewReady &&
    hasFreshTarget &&
    !isBusy &&
    placedCount < ArViewModel.MAX_PLACED_MODELS
```

## Testing Strategy

### JVM unit tests

Add focused tests for pure rules:

- Place eligibility for every disabled condition and the valid condition.
- The fifth object is allowed and a sixth is rejected.
- Half-turn normalization: `0 → 180`, `180 → 0`, and repeated turns never change scale or position
  inputs.
- Product change resets preview yaw.
- Remove deletes only the selected placement and frees exactly one slot.
- Stale target/generation tokens cannot be committed.

Run `:app:testDebugUnitTest` after each logic change.

### Physical-device tests

Extend `docs/ar-manual-test.md`. A physical ARCore phone is required because unit tests cannot prove
hit-test stability, preview grounding, or anchor ownership.

Minimum device sequence using the validated Monstera:

1. Select Monstera and aim at empty floor: one real-size preview appears at the crosshair without
   increasing `0/5`.
2. Move the crosshair off the floor: the preview disappears and Place becomes disabled.
3. Aim at the floor and press Place once: exactly one model is anchored and the counter becomes
   `1/5`.
4. Tap empty floor repeatedly: no additional model is placed.
5. Turn the preview 180 degrees, place it, and verify the placed yaw matches the preview.
6. Turn one placed model: only that instance turns; its base and height do not change.
7. Remove one selected instance: only it disappears, its anchor is detached, and one slot is freed.
8. Move a remaining model and cancel one move: existing reposition behavior remains correct.
9. Background and foreground the Activity: no duplicate preview appears and controls remain usable.
10. Place five objects: Place is disabled at `5/5`; remove one and confirm it is enabled again.
11. Force preview/model failure: show a readable notice, no orphan preview/anchor, and no crash.

The current Monstera is sufficient for steps 1–11. Before final release sign-off, repeat the existing
five-distinct-product loader test after the remaining four validated models are imported.

### Regression gate

- `:app:testDebugUnitTest`, `:app:lintDebug`, and `:app:assembleDebug` pass.
- Existing screenshot, cart, catalog, offline cache, session configuration, real-size grounding,
  five-model cap, unsupported-device screen, and readable error behavior still pass.
- Device logs contain no `AndroidRuntime:E`, native Filament crash, or leaked-anchor symptom.

## Boundaries

### Always

- Preserve `height_cm` as the only authoritative real-world scale.
- Keep the preview and every committed model grounded through the existing transform calculation.
- Perform Filament/SceneView mutations on the main thread and cache/file reads off the main thread.
- Guard asynchronous preview callbacks with the current scene/product generation.
- Detach every anchor on failure, removal, reset, cancellation, and disposal.
- Provide readable UI text, minimum 48-dp actions, and accessibility descriptions for Place, Turn
  180°, and Remove.
- Verify the interaction on the physical ARCore phone.

### Ask first

- Adding a dependency or changing SceneView/ARCore versions.
- Changing `height_cm`, the catalog API, database schema, model cache contract, or five-model limit.
- Adding free-angle rotation, gesture rotation, confirmation/Undo, or scene persistence.
- Changing the current Move, cart, screenshot, offline, or unsupported-device flows.

### Never

- Add resizing, negative scale, mirroring, or vertical flipping under this spec.
- Create an ARCore anchor for the transient preview.
- Let a preview consume a placement slot or appear as a cart/selectable placed object.
- Place from an empty-surface tap or from a stale/untracked target.
- Create/download/read a model on every AR frame.
- Swallow preview/placement errors or leak nodes, model instances, or anchors.
- Weaken existing rendering or validator tests to make the extension pass.

## Success Criteria

1. Selecting the Monstera produces exactly one real-size, grounded preview at a valid centre target
   without changing the placement counter.
2. The preview hides and Place disables within 500 ms after the centre target becomes invalid or
   tracking is lost.
3. Place is enabled only for a ready preview at a fresh valid target and commits no more than one
   object per press.
4. Empty-surface taps never place a model; taps on placed models still open their product panel.
5. The committed object's product, position, actual-size scale, grounding, and yaw match the preview.
6. Preview and committed height stay within ±10% of `height_cm`; turning never changes measured
   height or grounding.
7. Turn 180° rotates only the intended preview or selected placement around vertical Y, with no
   mirroring, tilt, position change, or placement-count change.
8. Remove affects exactly one selected placement, detaches its anchor, decrements the counter once,
   and makes the freed slot immediately reusable.
9. At `5/5`, preview placement is unavailable and Place is disabled; after one removal it becomes
   available again when the target is valid.
10. Product switching, reset, background/foreground, load failure, and Activity disposal leave no
    stale or duplicate preview and no orphan anchor.
11. The preview loop performs no repeated network request, file read, or model-instance creation.
12. The existing Move, screenshot, cart, offline, catalog, real-size rendering, and error flows have
    no regression.
13. Unit tests, lint, and debug build pass, and the complete manual device sequence passes on the
    target ARCore phone without a renderer or Android runtime crash.
14. The feature can be developed with the current Monstera; final release remains pending the
    separate five-distinct-product stability test required by the rendering spec.

## Resolved Decisions

| Question | Decision |
|---|---|
| Can work begin before all models are imported? | Yes. Use the validated Monstera; defer only final cross-model sign-off. |
| Resize? | No. Product dimensions remain actual size from `height_cm`. |
| What does flip mean? | Turn 180° around vertical Y; no mirroring and no upside-down transform. |
| How is placement confirmed? | Dedicated Place button at the latest valid centre target. |
| Do surface taps still place? | No. They only participate in placed-object selection. |
| Does preview use an anchor or count toward 5? | Neither. Only a committed placement does. |
| Remove confirmation or Undo? | Neither in this version; retain the existing direct Remove action. |
| New dependency/backend work? | None. |

## Open Questions

None. Any later request for resizing, arbitrary rotation, mirroring, Undo, or persisted scenes reopens
the spec before implementation.
