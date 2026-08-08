# AR rendering manual device test

This script is the release gate for the Android AR renderer. Run it on a physical ARCore phone; an
emulator is not evidence for model loading, real-world scale, plane anchoring, or session
configuration. The temporary `AR Test Object` product should be present for the rendering cases and
must be restored after the empty-catalog case.

## Required setup

- A physical Android phone with Google Play Services for AR installed and the camera permission
  granted.
- The phone and the Ferosa development machine on the same Wi-Fi network.
- The validated `AR Test Object` product visible in the authenticated catalog, with its lantern model
  at `height_cm = 57`.
- Android platform tools (`adb`), the repository, and the debug build available on the machine.
- One terminal reserved for the logcat command below. Do not filter out `FerosaAR`, `Filament`,
  `ARCore`, `sceneview`, or `AndroidRuntime` while testing.

Start from the repository root in PowerShell:

```powershell
ipconfig | Select-String "IPv4"
Select-String -LiteralPath .\ferosa_mobile\gradle.properties -Pattern 'FEROSA_SERVER_URL'

Set-Location .\ferosa_mobile
$env:JAVA_HOME = 'C:\Program Files\Android\Android Studio\jbr'
$env:Path = "$env:JAVA_HOME\bin;$env:Path"
cmd.exe /d /c gradlew.bat :app:installDebug
adb devices
adb logcat -c
adb logcat -v threadtime FerosaAR:V Filament:V ARCore:V sceneview:V AndroidRuntime:E '*:S'
```

The IPv4 address used by the phone must match the host portion of `FEROSA_SERVER_URL` (currently
`192.168.254.102`). If it differs, update the local Gradle property and rebuild before continuing.

## Numbered test

1. **Confirm the network and authenticated catalog.** With the logcat terminal running, open Ferosa,
   sign in if needed, and launch AR from the project flow. The catalog drawer must list `AR Test
   Object` with its product photo and price. A valid run has no `AndroidRuntime:E` line and no
   `401`/`302` catalog failure; a catalog or auth failure is not a renderer result. Record the
   `FEROSA_SERVER_URL` comparison from setup.

2. **Verify the empty catalog state.** In the admin product edit page, temporarily remove the AR
   model from `AR Test Object`, then relaunch AR while the catalog has zero AR-enabled products. The
   screen must show **“No AR products available yet”**, explain that products need a 3D model, and
   offer **“Back to App”**. Tap the camera area once: no `FerosaAR` placement/download/load line
   should appear, and there must be no `AndroidRuntime:E` line. Re-upload the lantern through the
   admin form with height `57` before step 3.

3. **Place one warm-cache model.** Return to AR, select `AR Test Object`, and wait for a tracked
   horizontal plane. Tap the plane once and wait at most 10 seconds. The visual result must be
   visible, upright, and free of an error notice. The confirming logcat line is:

   ```text
   FerosaAR: Placed product=<id>, ... halfExtent=..., ... renderables=<n>
   ```

   `renderables` must be greater than zero and the `halfExtent` values must be finite and non-zero.
   A failed load must instead produce the customer-readable notice and a `FerosaAR` model-load
   failure line; it must not produce an `AndroidRuntime:E` crash.

4. **Check real-world height and grounding.** Put a metre rule or another known-length object in
   the camera view beside the placed lantern. Its rendered height must be within ±10% of 57 cm, and
   the lowest point of the model must sit on the detected plane with no visible float or sink. Keep
   the step-3 `FerosaAR` line as evidence; `heightMeters=0.57`, `scale=...`, and `position=...` are
   the fields that explain the result.

5. **Place five models in one session.** Reset the scene, then place five products/models without
   restarting the Activity. If only the temporary product is available, repeat it five times after
   resetting between placements only when the app permits the same selection; otherwise use five
   AR-enabled catalog products. Each successful placement must add one `FerosaAR: Placed product=`
   line with `renderables>0`. There must be five lines, no renderer crash, and no later placement
   silently disappearing. This step is mandatory: one successful placement does not prove that the
   SceneView model-loader scope survived its first load.

6. **Verify the requested session configuration.** On the first session creation and after returning
   from the background, capture the `FerosaAR` line containing:

   ```text
   Session config applied ... planeFindingMode=HORIZONTAL ...
   lightEstimationMode=ENVIRONMENTAL_HDR ... depthMode=... depthOcclusion=...
   ```

   The live values must match the values requested by the app. The camera must still find horizontal
   planes and placed models must receive scene lighting rather than appearing flat-shaded.

7. **Drag and reposition a placed model.** Long-press a placed model, drag it to a second tracked
   plane location, and release. It must remain grounded at the new anchor; cancelling a drag over an
   invalid surface must return it to the original anchor. Keep the step-5 placement lines and confirm
   there is no `AndroidRuntime:E` line or leaked-anchor symptom.

8. **Background and foreground the session.** Press Home or switch apps for at least five seconds,
   return to Ferosa, and place another model without restarting the Activity. The confirming evidence
   is another successful `FerosaAR: Placed product=` line after the lifecycle round-trip, with no
   `ARCore` session failure and no `AndroidRuntime:E` line.

9. **Airplane mode with a warm cache.** Place or load the lantern once, enable airplane mode, return
   to AR, and place it again. The catalog should identify cached/offline content, the model should
   render, and the confirming line remains `FerosaAR: Placed product=...` with `renderables>0`. No
   network exception should be the only result.

10. **Airplane mode with a cold cache.** Clear the app's model cache or use a fresh app install,
    enable airplane mode, and attempt the same placement. The app must report that the model is
    unavailable offline in the notice area, detach the temporary anchor, and return to an idle state.
    Confirm there is a `FerosaAR` model-load/download failure line or equivalent readable diagnostic,
    and no `AndroidRuntime:E` line. Restore network access and re-download the model before step 11.

11. **Corrupt-GLB failure path.** In the local admin upload form, replace the temporary model with a
    deliberately corrupt `.glb` fixture that passes the file extension check but cannot be loaded,
    then open AR and tap a tracked plane. The anchor must be detached, the notice must tell the user
    the 3D model could not be opened/prepared, and the Activity must remain usable. Confirm the
    `FerosaAR` model-load failure line and the absence of `AndroidRuntime:E`. Restore the validated
    lantern upload with `height_cm = 57` after the test.

12. **Record the result and clean up.** Save the date, device model/Android version, host IP, APK
    version, and the relevant `FerosaAR` lines. Leave the temporary product clearly named and active
    only while rendering work is in progress; before launch, delete it and its uploaded model as
    described in the spec. Do not delete the lantern before the rendering checkpoint is signed off.

## Failure triage

- No catalog or a `401`/`302` response: fix the login/session or LAN setup before diagnosing
  rendering.
- A first placement fails and later taps do nothing: restart the Activity and capture the first
  failure; the old SceneView loader scope can be cancelled by an uncaught error.
- A `FerosaAR` line reports `renderables=0`, a non-finite extent, or an unexpected scale: stop and
  preserve the GLB and logcat; do not attach the asset to a real product.
- Any `AndroidRuntime:E` or native Filament crash: stop the test, preserve the complete preceding
  logcat lines, and do not call the renderer checkpoint passed.
