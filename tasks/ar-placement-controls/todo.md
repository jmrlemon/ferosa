# Task List: AR Placement Preview and Object Controls

**Spec:** `docs/specs/ar-placement-controls.md`<br>
**Plan:** `tasks/ar-placement-controls/plan.md`<br>
**Status:** Implementation complete — automated gates and implementation reviews passed; the
surface-target stability follow-up is included; physical device closeout still needs one clean
ARCore-phone run

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

> ## Checkpoint: Preview foundation — ✅ reviewed
>
> - [x] Tasks 1–2 code acceptance criteria pass
> - [x] Unit tests and debug build are green
> - [x] Exactly one unanchored, uncounted preview can exist
> - [x] Product switch/reset/disposal cannot reveal stale preview geometry
> - [x] Review physical-phone result before changing placement input
> - [ ] Preview-only phone interaction evidence (deferred until Task 3 removes legacy tap placement)

> **Code review (2026-08-18):** Tests were reviewed first, followed by correctness, readability,
> architecture, security, and performance. One required correctness finding was fixed: a Filament
> `ModelInstance` could outlive a failed `ModelNode` construction. The guarded cleanup is in
> commit `f0d91db`. Focused tests, the full JVM suite, `lintDebug`, and `assembleDebug` passed after
> the fix. No optional findings remain open for this checkpoint.

---

## Task 3: Drive the preview from the crosshair and place only from the button

**Description:** Use the exact rendered reticle coordinate for centre hit testing, position the
preview from tracked valid hits, and add the explicit Place control. Remove the tap-on-empty-surface
placement branch. On Place, perform a fresh hit test and commit exactly one separately tracked model.

**Acceptance criteria:**

- [x] A valid centre target shows the grounded actual-size preview and enables a minimum-48-dp Place
      button
- [ ] An invalid/stale target hides the preview and disables Place within 500 ms (final timing
      evidence remains in Task 6's device script)
- [x] Empty-surface taps never place, while taps on committed models still open their existing panel
- [x] One Place press creates at most one anchor/placement, increments the counter once on success,
      and cleans up without incrementing on failure or cancellation

**Verification:**

- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:lintDebug`
- [x] Physical phone: `0/5` while aiming; one Place press becomes `1/5`; repeated floor taps leave it
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

- [x] Preview controls expose an accessible **Turn 180°** action only while the preview is ready
- [x] The existing product panel exposes **Turn 180°** for its selected committed object
- [x] Turning changes yaw only: no mirror, tilt, position, anchor, scale, height, grounding, counter,
      or other-instance change

**Verification:**

- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:assembleDebug`
- [x] Physical phone: turn preview, place it, and compare orientation; the connected device logged
      `Turned preview ... yawDegrees=180.0` followed by one `Placed ... yawDegrees=180.0`
- [ ] Physical phone: compare grounding and apparent height before/after each turn (retain with the
      final device evidence package)

**Dependencies:** Task 3

**Files likely touched:**

- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ArActivity.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/ArModelPlacement.kt`
- `ferosa_mobile/app/src/main/java/com/example/ferosa_landscaping/ui/ar/components/ProductInfoPanel.kt`
- `ferosa_mobile/app/src/test/java/com/example/ferosa_landscaping/ArPlacementControlsTest.kt`

**Estimated scope:** Medium (4 files)

---

> ## Checkpoint: Core interaction — ✅ reviewed
>
> - [x] Tasks 3–4 implementation acceptance criteria pass for the exercised valid-target paths;
>       invalid-target timing and visual height evidence remain in the final device script
> - [x] Valid target and explicit Place pass on the physical phone
> - [x] Surface taps no longer place models
> - [x] Preview and committed Turn 180° preserve actual size and grounding by changing yaw only
> - [x] Unit tests, lint, and debug build are green

> **Code review (2026-08-18):** Tests were reviewed first, followed by correctness, readability,
> architecture, security, and performance. No required findings remained. The preview and selected
> object both use the normalized half-turn helper; the committed model receives the preview yaw;
> the selected object's node is the only node mutated. Bounds-based SurfaceView routing keeps
> explicit controls reachable without reintroducing tap-to-place. Physical evidence showed the
> controls, `0/5` aiming, one `1/5` commit, and `yawDegrees=180.0` in logcat. Task 5/6 still own
> cancellation/reset hardening and invalid-target timing evidence.

---

## Task 5: Coordinate preview with selection, Move, Remove, limit, and lifecycle

**Description:** Make preview/commit/move/selection mutually safe. Preserve the current direct Remove
path while proving it detaches only the selected anchor and re-enables placement. Harden reset,
background/foreground, and failure cleanup around the new transient preview.

**Acceptance criteria:**

- [x] Moving or inspecting a committed model cannot simultaneously commit the preview; cancelling a
      move restores original anchor, position, yaw, and actual-size scale
- [x] Remove deletes only the selected instance, detaches its anchor, decrements once, closes the
      panel, and immediately frees a slot without touching cache, catalog, or cart
- [x] At `5/5`, preview placement is unavailable; after one removal it returns on a valid target, and
      all lifecycle/reset/failure paths leave no duplicate preview or orphan anchor

**Verification:**

- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:lintDebug`
- [ ] Physical phone: place two, move/cancel one, remove the other, and verify the correct object and
      counters throughout
- [ ] Physical phone: reach `5/5`, remove one, then successfully place one at the newly freed slot
- [ ] Physical phone: background/foreground and reset during preview preparation without a crash or
      duplicate

**Task 5 code review (2026-08-18):** Tests were reviewed first, followed by correctness,
readability, architecture, security, and performance. Required findings fixed in `0c283af` and
`1fb888`: Place remains available after the first commit, an atomic latch blocks duplicate queued
Place requests until the async job completes, and reset/remove/disposal detach both current and
temporary original Move anchors. Synchronous placement-start failures now release the latch and
restore the preview. Full JVM tests, lint, and the debug build are green. One connected-device run
showed `1/5` with the next preview controls; the phone disconnected before the final five-slot and
background/foreground sequence, which remains open for Task 6 evidence.

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

- [x] `docs/ar-manual-test.md` names the action, expected UI state/counter, cleanup invariant, and
      expected `FerosaAR`/crash evidence for every new control
- [x] Unit tests, lint, and debug build pass without regression in the implementation paths for Move,
      screenshot, cart, offline, catalog, actual-size grounding, and readable errors
- [x] Results distinguish interaction acceptance with one Monstera from the still-pending
      five-distinct-product release gate

**Verification:**

- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:lintDebug`
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:assembleDebug`
- [ ] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:installDebug` (the final build is ready;
      the wireless-debug phone is currently unavailable)
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

> **Final implementation review (2026-08-18):** Tests were reviewed first, followed by correctness,
> readability, architecture, security, and performance. The Activity teardown review found one
> required edge case—Move's temporary original anchor was not detached by the outer Activity release
> path—and it was fixed in `c881815`. No further code findings remain. Automated tests, lint, and
> debug build are green; the manual script is committed. The connected phone has already shown
> `0/5`, preview Turn 180°, one `1/5` commit, and the next preview controls, but its wireless ADB
> endpoint now refuses connections, so the complete physical sequence remains an explicit handoff
> item rather than a claimed pass. Five distinct products also remain pending four assets.

---

## Follow-up: Stabilize surface targeting and clear transient previews — ✅ implemented and reviewed

**Problem:** The ARCore raycast could return a nearby horizontal-plane pose outside the tracked
polygon, so the preview or plane texture could look detached from the physical surface. After a
placement, the next transient preview also remained selected and could visually overlap the newly
committed model. Users needed a way to clear that preview without deleting committed objects.

**Change:** Placement accepts only horizontal hits whose pose is inside both the ARCore plane polygon
and its extents; depth points remain excluded. The plain scene-node preview, two-sample
confirmation, and bounded 350 ms miss grace continue to absorb transient tracking gaps, while the
Place action still re-tests the exact reticle coordinate before creating an anchor. Once a target is
confirmed, the plane guide texture is hidden so its coarse mesh cannot suggest a floating surface;
a target miss restores the guide. Tapping the selected catalog card again now clears the product
selection and disposes only the transient preview; already-placed models remain unchanged. The
selected card exposes the same action through its accessibility description.

**Verification:**

- [x] Unit tests cover confirmation, one-miss grace, expiry, and misses before confirmation
- [x] Unit tests cover strict polygon/extents acceptance and selected-product toggle behavior
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:testDebugUnitTest`
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:lintDebug`
- [x] `Set-Location .\ferosa_mobile; .\gradlew.bat :app:assembleDebug`
- [ ] Physical-device before/after evidence remains pending: a diagnostic run on the connected
      Xiaomi 2511FPC34G / Android 16 was inconclusive after a native ARCore `FatalException` during
      repeated session restarts, so no visual pass is claimed

**Code review (2026-08-18):** Tests were reviewed first, followed by correctness, readability,
architecture, security, and performance. No required findings remained. The review specifically
checked stale-hit expiry, session/reset cleanup, fresh placement revalidation, hot-path allocation,
that plane-guide visibility is restored on reset/miss, that clearing selection cannot delete an
anchor, and that no new dependency or external-data surface was introduced. The implementation and
tests are committed in `3c4e6dd`.
