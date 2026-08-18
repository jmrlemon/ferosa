# AR placement-controls manual device test

This is the repeatable physical-device script for the approved AR placement-controls feature. Run
it on a physical ARCore phone; an emulator is not evidence for camera tracking, real-world scale,
plane anchoring, or renderer stability. The current development fixture is **Monstera Deliciosa**
(product `11`, configured height `100 cm`). Five distinct AR products remain a separate release gate
until four more validated assets are imported.

## Required setup

- A physical Android phone with Google Play Services for AR installed and camera permission granted.
- The phone and development machine on the same Wi-Fi network when using the online catalog.
- The authenticated catalog contains the in-stock Monstera Deliciosa with a validated `.glb` and
  configured height `100 cm`.
- Android platform tools (`adb`), the repository, and a debug APK.
- One terminal reserved for unfiltered-enough logcat. Do not hide `FerosaAR`, `Filament`, `ARCore`,
  `sceneview`, or `AndroidRuntime` while testing.

From the repository root in PowerShell:

```powershell
Set-Location .\ferosa_mobile
$env:JAVA_HOME = 'C:\Program Files\Android\Android Studio\jbr'
$env:Path = "$env:JAVA_HOME\bin;$env:Path"
.\gradlew.bat :app:testDebugUnitTest :app:lintDebug :app:assembleDebug
.\gradlew.bat :app:installDebug
adb devices -l
adb logcat -c
adb logcat -v threadtime FerosaAR:V Filament:V ARCore:V sceneview:V AndroidRuntime:E '*:S'
```

Record the date, phone model, Android/ARCore versions, APK version, host IP, and catalog/network
mode. If the catalog returns `401`/`302` or no AR product, fix authentication/LAN setup before
calling any renderer result a failure.

## Placement-control sequence

Run each step in one AR Activity session unless the step explicitly asks for a lifecycle restart.
Save screenshots and the relevant filtered logcat lines under a local evidence folder (for example,
`docs/evidence/ar-placement-controls/<date>/`).

1. **Aim without placing.** Launch AR with Monstera selected. Wait for a horizontal surface. The
   centre crosshair and a grounded, actual-size Monstera preview must appear while the header remains
   `0/5`. The drawer must show `Place` and, once the preview is ready, `Turn 180°`. Tapping the
   camera/floor outside those controls must not change the counter or create a `Placed product=`
   line.

2. **Turn the transient preview.** Tap `Turn 180°` once. The preview may face the opposite yaw, but
   it must remain at the same position, grounded base, scale, and apparent `100 cm` height. A debug
   run records `Turned preview product=11, yawDegrees=180.0`; tapping again returns to `0.0`.

3. **Confirm exactly one placement.** With a fresh tracked target at the crosshair, tap `Place` once.
   The counter must become `1/5`; one committed model must remain anchored at the crosshair, and a
   new preview must be prepared for the next slot. The confirming line is:

   ```text
   FerosaAR: Placed product=11, ... heightMeters=1.0, ... yawDegrees=..., renderables=1
   ```

   Repeated taps on the floor, and a second tap while the first placement is loading, must not create
   an extra anchor. A rejected/cancelled load must restore the preview and leave the counter
   unchanged.

4. **Place a second model and verify the old one is independent.** Aim at another tracked location
   and press `Place` once. The header must become `2/5`, and both models must remain visible. Turning
   the current preview must not rotate either committed model. If only one model is available, use
   the same Monstera twice for this interaction check; do not count that reuse toward the final
   five-distinct-product gate.

5. **Open the selected-object panel and turn one object.** Tap a committed model. Its product panel
   must expose `Turn 180°`, `Move`, `Remove`, and the existing cart action. Tap `Turn 180°`; only
   that selected instance may rotate. The log records `Turned placed product=11, yawDegrees=...`.
   Position, grounding, scale, height, anchor, counter, and the other instance must be unchanged.

6. **Move and cancel.** Long-press one committed model, drag it to a second tracked surface, and
   release. It must remain grounded at the new anchor. Repeat, release over an invalid surface, or
   cancel the move; the model must snap back to its original anchor and yaw. No preview placement is
   allowed while Move is active, and there must be no `AndroidRuntime:E` or orphan-anchor symptom.

7. **Remove and free a slot.** Open the panel for one selected model and press `Remove`. Only that
   instance disappears, its anchor is detached, the panel closes, and the counter decrements once
   (for example `2/5` → `1/5`). The next valid preview/Place control must become available without
   restarting AR, and the catalog/cache/cart state must remain intact.

8. **Verify the five-slot limit.** Place the current fixture until the header reaches `5/5` in one
   session. `Place` and the preview must be unavailable at the limit, with a clear maximum-items
   notice. Remove one selected model; the counter must become `4/5`, a new preview must prepare, and
   one more valid `Place` must return it to `5/5`. For this development run, repeated Monstera
   placements prove slot/lifecycle behavior only; five *distinct* products are still pending assets.

9. **Reset and re-prepare.** Use the design preview `Reset` action. All committed models and anchors
   must disappear, the counter must return to `0/5`, and exactly one Monstera preview must prepare
   again. Reset during preview download or placement preparation must leave no visible duplicate,
   orphan anchor, or stuck loading state.

10. **Background/foreground.** Press Home or switch apps for at least five seconds, return to Ferosa,
    and wait for tracking. The current preview/placements must not duplicate; place one more model
    successfully. Capture a new `Session config applied ...` line and a post-resume `Placed product=`
    line. Any `AndroidRuntime:E`, native Filament crash, or AR session failure fails this step.

## Existing regression checks

11. **Real-world scale and grounding.** Put a metre rule or another known-length object beside the
    rendered Monstera. Its height must be within ±10% of the configured `100 cm`; the pot/base must
    sit on the detected plane with no visible float or sink. Retain the `Preview ready` and `Placed`
    lines containing `heightMeters=1.0`, `scale=...`, and `position=...`.

12. **Screenshot and product panel.** Save an AR screenshot using the camera button. Confirm the
    saved image contains the camera view and placed model, and that the selected product panel still
    exposes Add to cart, Turn 180°, Move, and Remove.

13. **Offline warm-cache.** Place or prepare Monstera once, enable airplane mode, return to AR, and
    place it again from cached/offline catalog data. It must render and log `Placed product=...` with
    `renderables>0`; a network exception alone is not an acceptable result.

14. **Offline cold-cache/failure cleanup.** On a disposable debug install, clear the model cache,
    enable airplane mode, and attempt placement. The UI must show a readable unavailable-offline
    notice, detach any temporary anchor, restore an idle/usable state, and emit no
    `AndroidRuntime:E`. Restore network/cache before continuing.

15. **Corrupt model failure.** On a disposable debug install, replace the cached Monstera `.glb` with
    a truncated/invalid copy, then attempt placement. The app must show the customer-readable model
    error, detach the temporary anchor, remain usable for another attempt, and log a model-load
    failure without `AndroidRuntime:E`. Restore the validated cache/upload afterward.

16. **Catalog/empty state.** If the catalog has no cached AR-enabled product, the screen must show
    `No AR products available yet`, explain that products need a 3D model, and offer `Back to App`.
    Tapping the camera area in this state must not start a placement or download.

## Evidence and pass/fail record

Retain, at minimum:

- device metadata and APK/version/date;
- screenshots for `0/5` preview, turned preview, committed `1/5`, selected-object panel, `5/5`,
  removal/free-slot, and reset;
- filtered logcat containing `Preview ready`, `Turned preview`, `Placed product`, `Turned placed`,
  `Session config applied`, and any failure cleanup; and
- a pass/fail table with notes for each numbered step.

The current Monstera run can sign off the interaction and lifecycle controls. Do not mark the
five-distinct-product criterion complete until four additional validated AR models are available and
each has been placed successfully in the same session. Preserve any `AndroidRuntime:E`, Filament
crash, non-finite extent, `renderables=0`, unexpected scale, leaked anchor, or stuck-counter evidence
and mark the affected step failed.

## Failure triage

- `401`/`302`, empty catalog, or no AR product: fix authentication, catalog, or LAN setup first.
- `Place` disabled: wait for a fresh tracked horizontal target and a ready preview; do not fall back
  to tapping the camera surface.
- Counter changes more than once for one press: stop, preserve logcat/screenshots, and fail the
  duplicate-placement step.
- A model-load failure or cancellation must restore the preview/idle state and leave the counter
  unchanged. If it does not, preserve the first failure and do not continue the release gate.
- Any `AndroidRuntime:E`, native Filament crash, orphan anchor, `renderables=0`, non-finite extent,
  or unexpected scale fails the affected step.
