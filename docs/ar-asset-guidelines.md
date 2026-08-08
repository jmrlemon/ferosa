# Ferosa AR asset guidelines

Use this guide before uploading a model to the Android AR catalog. Ferosa places one GLB as one
real-world object on a detected horizontal plane and scales it to the product's `height_cm` value.
The server checks the file structure and device-cost budgets, but it cannot tell whether a valid model
is upside-down, mis-scaled, or the wrong object. Complete the visual check below before uploading.

## What qualifies as an AR asset

An acceptable asset is:

- A self-contained binary glTF 2.0 `.glb`. Embed every buffer and image; separate `.gltf`, `.bin`, or
  texture files are not accepted.
- One discrete, visible object with reachable mesh geometry in the default scene. Bulk materials such
  as soil, gravel, sod, or ground coverage are not AR candidates because they have no meaningful
  placeable height.
- Authored in metres, with Y as the up axis and the object's base resting on the ground plane. The
  model's configured `height_cm` must describe the real object customers will see.
- Free of an empty shell, missing materials, broken textures, and unsupported required glTF
  extensions.

The upload form's height is authoritative. Do not use the model's export scale as a substitute for
entering the real height.

## Required-extension allowlist

The Android renderer is built with Filament/SceneView 2.2.1. A required extension means the loader
must understand that extension or it must refuse the asset. Ferosa accepts these 17 values in
`extensionsRequired`:

```text
KHR_draco_mesh_compression
EXT_meshopt_compression
EXT_mesh_gpu_instancing
KHR_lights_punctual
KHR_materials_clearcoat
KHR_materials_emissive_strength
KHR_materials_ior
KHR_materials_iridescence
KHR_materials_pbrSpecularGlossiness
KHR_materials_sheen
KHR_materials_specular
KHR_materials_transmission
KHR_materials_unlit
KHR_materials_variants
KHR_materials_volume
KHR_texture_basisu
KHR_texture_transform
```

`extensionsUsed` alone is not a rejection reason. A model may list an optional extension that the
loader ignores, including `KHR_materials_emissive_strength`, as long as it is not listed in
`extensionsRequired`. `EXT_meshopt_compression` is allowlisted but produces a warning: verify it on a
physical ARCore phone before publishing it.

If the admin form rejects an extension, remove that requirement in the exporter or re-export without
the feature. Do not rename an unsupported extension to hide it.

## Performance budgets

Warnings do not block an upload, but they require a device check before publishing. Hard limits do
block the upload.

| Metric per model | Review warning | Hard limit | Practical fix |
| --- | ---: | ---: | --- |
| Triangles | Above 100,000 | Above 250,000 | Decimate or simplify the mesh; remove unseen detail |
| Largest texture edge | Above 2,048 px | Above 4,096 px | Resize and re-export textures; keep the long edge at 2,048 px or less |
| Decoded texture memory, including mipmaps | Above 48 MB | — | Reduce texture dimensions/count and use appropriate compression |
| GLB file size | Above 8 MB | 100 MB upload cap remains | Prune unused data, deduplicate, simplify, and resize textures |

The validator reports the measured value against the budget in the admin panel. A warning is not a
substitute for opening the model on a phone: five concurrent AR objects multiply texture memory.

## Blender preparation

1. Open the downloaded model in Blender and save a working copy. Keep the original download
   untouched so it can be recovered.
2. Select the object and use `Object > Apply > Rotation & Scale`. Set the scene/export orientation to
   Y-up in the glTF 2.0 export settings. Do not apply a visual correction only in the AR app.
3. Put the bottom of the object on the ground plane. Use `Object > Set Origin` as needed, then move
   the mesh so its lowest point is at the intended base. Check that the model is not buried or floating.
4. Remove cameras, lights, hidden duplicates, unused collections, and invisible geometry that are not
   part of the placeable object. Keep a reachable mesh in the default scene.
5. Check the statistics overlay. If triangles exceed the warning budget, add a Decimate modifier or
   use `Mesh > Clean Up` to remove unnecessary detail, then inspect the silhouette and apply the
   modifier.
6. Resize image textures so no edge exceeds 2,048 px. Pack images into the `.blend`, then export with
   `File > Export > glTF 2.0`, format `glTF Binary (.glb)`, and the option that embeds or packs images.
   Do not upload a `.gltf` that depends on neighbouring files.
7. If a required extension is not on the allowlist, disable that exporter feature or re-export the
   asset without it. Do not rely on an `extensionsUsed`-only warning to make an actually required
   feature work.

Useful Blender checks:

- `Scene Statistics` shows vertices, faces, and triangles while you simplify.
- `N` panel > Item shows dimensions; compare the object to a known real-world measurement.
- The glTF export panel must say `GLB` and keep the intended Y-up orientation.

## `gltf-transform` alternatives

Install or run the CLI in a scratch directory and inspect the result after every transformation:

```powershell
npx gltf-transform inspect input.glb
npx gltf-transform prune input.glb pruned.glb
npx gltf-transform dedup pruned.glb deduped.glb
npx gltf-transform simplify deduped.glb simplified.glb --ratio 0.5
npx gltf-transform resize simplified.glb resized.glb --width 2048 --height 2048
npx gltf-transform inspect resized.glb
```

Use the final output only after checking its visual orientation and materials. `prune` removes
unreachable resources, `dedup` removes duplicate resources, `simplify` reduces mesh detail, and
`resize` limits texture dimensions. Keep the source file and compare the final triangle count and
texture edges against the table. If an optimizer introduces a required extension, remove that option
or re-export in Blender and inspect `extensionsRequired` before uploading.

## Mandatory visual check before upload

Open the final `.glb` in Windows 3D Viewer or Blender and confirm all of the following:

- The model is upright and faces the expected direction.
- The base touches the ground and the object is not visibly floating, sunk, or rotated onto its side.
- Its dimensions are roughly the expected real-world size; record the value you will enter as
  `height_cm`.
- The default scene shows the intended object, with visible geometry and materials.
- Textures are present, not black, stretched, missing, or unexpectedly blurry.
- The object is a discrete AR candidate, not a bulk material or a collection of unrelated objects.

This check is required even when server validation succeeds. Structural validation cannot detect an
upside-down or mis-scaled model, and the in-admin 3D preview is intentionally deferred while the
catalog remains small.

## Upload and review

1. Prepare and visually inspect the `.glb`.
2. Open the product's admin edit page, choose the `.glb`, and enter the real height in centimetres.
3. Read every warning after upload. A meshopt, triangle, texture, or file-size warning means the
   upload succeeded but needs a physical-device review before the product is published.
4. Re-open the file on the phone and place it on a tracked plane. Confirm it is visible, grounded, and
   within ±10% of the configured height.
5. Keep a copy of the validated source and the device result with the product record.
