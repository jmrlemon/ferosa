package com.example.ferosa_landscaping.ui.web

import android.app.DownloadManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import android.webkit.CookieManager
import android.webkit.URLUtil
import android.webkit.WebChromeClient
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import androidx.core.net.toUri
import java.io.File

/**
 * File in and file out for the shell WebView: downloading what the site serves,
 * and letting the user answer an `<input type="file">` with the camera.
 */

// ─── Downloads ───────────────────────────────────────────────────────────────

/**
 * A download held while the legacy storage permission is requested, so it can
 * be started once the user answers instead of being dropped.
 */
internal data class PendingDownload(
    val url: String,
    val userAgent: String?,
    val contentDisposition: String?,
    val mimeType: String?,
)

/**
 * Hands a download off to the system DownloadManager.
 *
 * Every downloadable route on the site - order receipts, appointment receipts,
 * the admin CSV exports - sits behind `auth`. DownloadManager makes its own
 * request outside the WebView, so without the session cookie the user silently
 * downloads the login page instead of their receipt.
 *
 * @return false if the download could not be started, so the caller can tell
 *   the user rather than leaving a tap that did nothing.
 */
internal fun enqueueBrowserDownload(
    context: Context,
    url: String,
    userAgent: String?,
    contentDisposition: String?,
    mimeType: String?,
): Boolean {
    // DownloadManager only speaks http(s); blob: and data: URLs would throw.
    if (!url.startsWith("http://") && !url.startsWith("https://")) return false

    return runCatching {
        val fileName = URLUtil.guessFileName(url, contentDisposition, mimeType)
        val request = DownloadManager.Request(url.toUri()).apply {
            setMimeType(mimeType)
            CookieManager.getInstance().getCookie(url)?.let { addRequestHeader("Cookie", it) }
            userAgent?.let { addRequestHeader("User-Agent", it) }
            setTitle(fileName)
            setDescription("Downloading from Ferosa")
            setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
            setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName)
        }

        val manager = context.getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
        manager.enqueue(request)
        true
    }.getOrDefault(false)
}

/**
 * Writing into the public Downloads directory needs WRITE_EXTERNAL_STORAGE up
 * to API 28. From API 29 scoped storage grants it implicitly.
 */
internal fun downloadNeedsLegacyPermission(context: Context): Boolean =
    Build.VERSION.SDK_INT <= Build.VERSION_CODES.P &&
        ContextCompat.checkSelfPermission(
            context,
            android.Manifest.permission.WRITE_EXTERNAL_STORAGE,
        ) != PackageManager.PERMISSION_GRANTED

// ─── Uploads ─────────────────────────────────────────────────────────────────

/**
 * Whether the page would accept a photo.
 *
 * An empty accept list means "anything", which includes an image.
 */
internal fun acceptsImages(params: WebChromeClient.FileChooserParams?): Boolean {
    val types = params?.acceptTypes.orEmpty().filter { it.isNotBlank() }
    return types.isEmpty() || types.any {
        it == "*/*" || it.equals("image", ignoreCase = true) || it.startsWith("image/")
    }
}

/**
 * Creates the destination a camera app will write the captured photo into.
 *
 * Lives in the cache directory behind a FileProvider: the photo is a temporary
 * hand-off to the WebView's upload, not something to keep.
 */
internal fun createImageCaptureUri(context: Context): Uri? = runCatching {
    val directory = File(context.cacheDir, "captures").apply { mkdirs() }
    val file = File.createTempFile("ferosa_capture_", ".jpg", directory)
    FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", file)
}.getOrNull()

/**
 * Builds the chooser shown for `<input type="file">`.
 *
 * WebView's own `createIntent()` offers documents and the gallery but never the
 * camera, which is why uploading a GCash payment proof or a photo of a garden
 * used to mean leaving the app. The camera is added as an initial intent when
 * [captureUri] is non-null.
 */
internal fun buildFileChooserIntent(
    context: Context,
    params: WebChromeClient.FileChooserParams?,
    captureUri: Uri?,
): Intent {
    val contentIntent = runCatching { params?.createIntent() }.getOrNull()
        ?: Intent(Intent.ACTION_GET_CONTENT).apply {
            type = "*/*"
            addCategory(Intent.CATEGORY_OPENABLE)
        }

    val title = if (captureUri != null) "Select or take a photo" else "Select a file"
    val chooser = Intent.createChooser(contentIntent, title)

    if (captureUri != null) {
        val cameraIntent = Intent(MediaStore.ACTION_IMAGE_CAPTURE)
            .putExtra(MediaStore.EXTRA_OUTPUT, captureUri)
            .addFlags(Intent.FLAG_GRANT_WRITE_URI_PERMISSION)

        // Some camera apps ignore the flag on an intent they were handed inside
        // a chooser, so grant explicitly to everything that can service it.
        runCatching {
            context.packageManager
                .queryIntentActivities(cameraIntent, PackageManager.MATCH_DEFAULT_ONLY)
                .forEach { resolved ->
                    context.grantUriPermission(
                        resolved.activityInfo.packageName,
                        captureUri,
                        Intent.FLAG_GRANT_WRITE_URI_PERMISSION,
                    )
                }
        }

        chooser.putExtra(Intent.EXTRA_INITIAL_INTENTS, arrayOf(cameraIntent))
    }

    return chooser
}

/**
 * Turns an activity result back into the URIs the WebView is waiting for.
 *
 * A gallery or document pick returns its selection in the intent, which
 * [WebChromeClient.FileChooserParams.parseResult] understands. A camera capture
 * returns no intent at all - the photo is at the URI we supplied - so that case
 * has to be recognised separately, and only when the file was actually written
 * (the user can back out of the camera after it has been launched).
 */
internal fun resolveFileChooserResult(
    context: Context,
    resultCode: Int,
    data: Intent?,
    captureUri: Uri?,
): Array<Uri>? {
    WebChromeClient.FileChooserParams.parseResult(resultCode, data)?.let { return it }

    if (resultCode != android.app.Activity.RESULT_OK || captureUri == null) return null

    return if (captureHasContent(context, captureUri)) arrayOf(captureUri) else null
}

/** True when the camera actually wrote bytes to the capture destination. */
private fun captureHasContent(context: Context, uri: Uri): Boolean = runCatching {
    context.contentResolver.openInputStream(uri)?.use { it.read() != -1 } == true
}.getOrDefault(false)
