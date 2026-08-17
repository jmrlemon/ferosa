# Task List: AR Placement Preview and Object Controls

**Spec:** `docs/specs/ar-placement-controls.md`<br>
**Plan:** `tasks/ar-placement-controls/plan.md`<br>
**Status:** Implementation in progress — Tasks 1–2 complete; Preview foundation checkpoint under review

Six ordered tasks and three checkpoints. The current Monstera is the development fixture; importing
four more assets is not a dependency for these tasks.

## Task 1: Define and test placement-control rules — ✅ complete

**Description:** Create a renderer-independent contract for preview readiness, target freshness,
busy/move state, placement count, and the 180-degree yaw transition. Compose callbacks and buttons
will consume this contract instead of duplicating conditions.

**Acceptance criteria:**

- [x] A pure rule enables Place only with a selected product, ready preview, fresh valid target, idle
      transaction, no active move, and fewer than five committed models
- [x] A pure half-turn helper normalizes yaw into `[0, 360)` without accepting scale or position as
      mutable inputs
- [x] Unit tests cover every disabled reason, the valid state, fifth-versus-sixth placement, and
      repeated half-turns

**Verification:**

- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [x] Temporarily negating one eligibility condition makes a focused test fail

**Dependencies:** None

**Files likely touched:**

- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArPlacementControls.kt`
- `ferosa_mobile/app/src/test/java/com/example/ferosa_landscaping/ArPlacementControlsTest.kt`

**Estimated scope:** Small (2 files)

---

## Task 2: Build the single transient preview lifecycle — ✅ complete

**Description:** When the selected product changes, resolve and validate its cached GLB off the main
thread, create one actual-size grounded preview instance on the main thread, and invalidate/dispose it
on product switch, reset, cancellation, scene replacement, or Activity disposal. Do not yet change
tap placement; this task establishes resource ownership before UI behavior depends on it.

**Acceptance criteria:**

- [x] Selecting Monstera prepares at most one preview instance using the existing validated cache and
      grounded-transform path
- [x] The preview owns no anchor, consumes no placement slot, and cannot enter product-info/cart state
- [x] Generation guards prevent an older product/load/session callback from replacing the current
      preview, and every disposal path releases the transient node/instance

**Verification:**

- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:assembleDebug`
- [ ] Physical phone: selecting/reselecting Monstera never increments `0/5` or produces duplicate
      preview geometry (the current legacy tap-to-place path is intentionally removed in Task 3)
- [x] Logcat contains no `AndroidRuntime:E` or Filament crash during rapid product selection/reset

**Dependencies:** Task 1

**Files likely touched:**

- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ArActivity.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArModelPlacement.kt`
- `ferosa_mobile/app/src/test/java/com/example/ferosa_landscaping/ArModelPlacementTest.kt`

**Estimated scope:** Medium (3 files)

---

> ## Checkpoint: Preview foundation
>
> - [ ] Tasks 1–2 acceptance criteria pass (manual preview check continues after the Task 3 input fix)
> - [x] Unit tests and debug build are green
> - [x] Exactly one unanchored, uncounted preview can exist
> - [x] Product switch/reset/disposal cannot reveal stale preview geometry
> - [x] Review physical-phone result before changing placement input

---

## Task 3: Drive the preview from the crosshair and place only from the button

**Description:** Use the exact rendered reticle coordinate for centre hit testing, position the
preview from tracked valid hits, and add the explicit Place control. Remove the tap-on-empty-surface
placement branch. On Place, perform a fresh hit test and commit exactly one separately tracked model.

**Acceptance criteria:**

- [ ] A valid centre target shows the grounded actual-size preview and enables a minimum-48-dp Place
      button; an invalid/stale target hides it and disables Place within 500 ms
- [ ] Empty-surface taps never place, while taps on committed models still open their existing panel
- [ ] One Place press creates at most one anchor/placement, increments the counter once on success,
      and cleans up without incrementing on failure or cancellation

**Verification:**

- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:lintDebug`
- [ ] Physical phone: `0/5` while aiming; one Place press becomes `1/5`; repeated floor taps leave it
      at `1/5`
- [ ] Physical phone: aim away from the tracked floor and confirm preview hides/Place disables

**Dependencies:** Task 2

**Files likely touched:**

- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ArActivity.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArPlacementControls.kt`
- `ferosa_mobile/app/src/test/java/com/example/ferosa_landscaping/ArPlacementControlsTest.kt`

**Estimated scope:** Medium (3 files)

---

## Task 4: Add Turn 180° to preview and selected-object controls

**Description:** Wire the tested half-turn rule into the active preview and the existing placed-model
product panel. Preview yaw must be copied to the committed model; turning a placed model changes only
that specific instance.

**Acceptance criteria:**

- [ ] Preview controls expose an accessible **Turn 180°** action only while the preview is ready
- [ ] The existing product panel exposes **Turn 180°** for its selected committed object
- [ ] Turning changes yaw only: no mirror, tilt, position, anchor, scale, height, grounding, counter,
      or other-instance change

**Verification:**

- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:assembleDebug`
- [ ] Physical phone: turn preview, place it, and compare orientation; then turn only one of two
      committed instances
- [ ] Physical phone: compare grounding and apparent height before/after each turn

**Dependencies:** Task 3

**Files likely touched:**

- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ArActivity.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArModelPlacement.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/components/ProductInfoPanel.kt`
- `ferosa_mobile/app/src/test/java/com/example/ferosa_landscaping/ArPlacementControlsTest.kt`

**Estimated scope:** Medium (4 files)

---

> ## Checkpoint: Core interaction
>
> - [ ] Tasks 3–4 acceptance criteria pass
> - [ ] Valid/invalid target behavior and explicit Place pass on the physical phone
> - [ ] Surface taps no longer place models
> - [ ] Preview and committed Turn 180° preserve actual size and grounding
> - [ ] Unit tests, lint, and debug build are green

---

## Task 5: Coordinate preview with selection, Move, Remove, limit, and lifecycle

**Description:** Make preview/commit/move/selection mutually safe. Preserve the current direct Remove
path while proving it detaches only the selected anchor and re-enables placement. Harden reset,
background/foreground, and failure cleanup around the new transient preview.

**Acceptance criteria:**

- [ ] Moving or inspecting a committed model cannot simultaneously commit the preview; cancelling a
      move restores original anchor, position, yaw, and actual-size scale
- [ ] Remove deletes only the selected instance, detaches its anchor, decrements once, closes the
      panel, and immediately frees a slot without touching cache, catalog, or cart
- [ ] At `5/5`, preview placement is unavailable; after one removal it returns on a valid target, and
      all lifecycle/reset/failure paths leave no duplicate preview or orphan anchor

**Verification:**

- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:lintDebug`
- [ ] Physical phone: place two, move/cancel one, remove the other, and verify the correct object and
      counters throughout
- [ ] Physical phone: reach `5/5`, remove one, then successfully place one at the newly freed slot
- [ ] Physical phone: background/foreground and reset during preview preparation without a crash or
      duplicate

**Dependencies:** Task 4

**Files likely touched:**

- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ArActivity.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArViewModel.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/components/ProductInfoPanel.kt`

**Estimated scope:** Medium (3 files)

---

## Task 6: Extend the device script and clear the regression gate

**Description:** Update the repeatable manual script with preview, explicit Place, half-turn, Remove,
limit-release, and lifecycle cases. Run automated gates and the current-Monstera device sequence;
record that five-distinct-product validation is pending assets rather than silently marking it passed.

**Acceptance criteria:**

- [ ] `docs/ar-manual-test.md` names the action, expected UI state/counter, cleanup invariant, and
      expected `FerosaAR`/crash evidence for every new control
- [ ] Unit tests, lint, debug build, and the current-Monstera device sequence pass without regression
      in Move, screenshot, cart, offline, catalog, actual-size grounding, or readable errors
- [ ] Results distinguish interaction acceptance with one Monstera from the still-pending
      five-distinct-product release gate

**Verification:**

- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:lintDebug`
- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:assembleDebug`
- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:installDebug`
- [ ] Run the updated `docs/ar-manual-test.md` sequence with filtered logcat and retain screenshots
      for preview `0/5`, committed `1/5`, turned object, removal, and limit release

**Dependencies:** Task 5

**Files likely touched:**

- `docs/ar-manual-test.md`

**Estimated scope:** Small (1 file plus device execution)

---

> ## Checkpoint: Complete
>
> - [ ] All six tasks and their verification items pass
> - [ ] All 14 success criteria in `docs/specs/ar-placement-controls.md` are accounted for
> - [ ] Physical-device evidence retained; no `AndroidRuntime:E` or Filament crash
> - [ ] No resize, dependency, backend/API, database, or five-model-limit change entered the diff
> - [ ] Final five-distinct-product test remains pending until the other four assets are imported
> - [ ] Ready for code review
