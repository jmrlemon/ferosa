# AR storage finding

Checked 2026-08-08 for Task 11 of the AR visualizer rendering spec.

`ferosa-laravel/public/storage` is absent, and no symlink or directory was created. The current
catalog's 11 `products.image_url` values are HTTPS Unsplash URLs, so the existing product thumbnails
and AR drawer thumbnails are not broken by this missing symlink. Future admin-uploaded product images
are stored on the public disk and generate `/storage/...` URLs, so those local thumbnails would return
404 until the deployment creates the standard link.

AR model downloads are unaffected: `ArController::downloadModel()` uses
`Storage::disk('public')->download()` directly. Creating the symlink is intentionally out of scope
for this rendering spec.
