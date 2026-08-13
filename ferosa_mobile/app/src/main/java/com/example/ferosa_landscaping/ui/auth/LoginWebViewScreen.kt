package com.example.ferosa_landscaping.ui.auth

import android.webkit.CookieManager
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.browser.customtabs.CustomTabsIntent
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.net.toUri
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import com.example.ferosa_landscaping.BuildConfig
import com.example.ferosa_landscaping.SERVER_URL
import com.example.ferosa_landscaping.ui.theme.Brand50
import com.example.ferosa_landscaping.ui.theme.Brand600
import com.example.ferosa_landscaping.ui.web.ConnectionErrorScreen
import com.example.ferosa_landscaping.ui.web.WEBVIEW_CURSOR_FIX_JS
import com.example.ferosa_landscaping.ui.web.WEBVIEW_LOAD_TIMEOUT_MS
import com.example.ferosa_landscaping.ui.web.applyFerosaDefaults
import kotlinx.coroutines.delay

/**
 * Full-screen login WebView. Calls [onLoggedIn] when the server redirects to /home.
 *
 * Google and Facebook OAuth are opened in a Custom Tab (system browser) because both
 * providers block OAuth inside Android WebViews. When the user returns from the Custom
 * Tab the WebView silently loads /home; if the session was established the server
 * serves the page and onLoggedIn fires.
 */
@Composable
fun LoginWebViewScreen(url: String, onLoggedIn: (String) -> Unit) {
    val context = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    var isLoading by remember { mutableStateOf(true) }
    var loadFailed by remember { mutableStateOf(false) }

    val awaitingOAuthReturn = remember { mutableStateOf(false) }
    val webViewRef = remember { mutableStateOf<WebView?>(null) }

    // onLoggedIn swaps this screen for the real app shell, so it must run once.
    val loginHandled = remember { mutableStateOf(false) }

    /**
     * Fires as soon as the logged-in landing page is identifiable.
     *
     * This used to run only from onPageFinished, which waits for every image on
     * the page - including the remote product photos. The result was that the
     * fully rendered home page sat on screen inside this bare WebView, with no
     * bottom navigation, until the last image arrived. The role meta tag lives
     * at the top of <head>, so it can be read far earlier than that.
     */
    fun detectLogin(view: WebView?, pageUrl: String?, allowUrlFallback: Boolean) {
        if (loginHandled.value || view == null || pageUrl == null) return

        val reachedCustomerHome = pageUrl.endsWith("/home") || pageUrl.contains("/home?")
        val reachedAdmin = pageUrl.endsWith("/admin") || pageUrl.contains("/admin?")
        if (!reachedCustomerHome && !reachedAdmin) return

        view.evaluateJavascript(
            "(document.querySelector('meta[name=ferosa-user-role]')||{}).content||''"
        ) { encodedRole ->
            val role = encodedRole
                ?.trim()
                ?.trim('"')
                ?.lowercase()
                ?.takeIf { it == "admin" || it == "staff" || it == "user" }

            // The meta tag distinguishes admin from staff, so prefer it. Only
            // guess from the URL once the page has finished and it never showed.
            val resolved = role ?: if (allowUrlFallback) {
                if (reachedAdmin) "staff" else "user"
            } else {
                null
            }

            if (resolved != null && !loginHandled.value) {
                loginHandled.value = true
                onLoggedIn(resolved)
            }
        }
    }

    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME && awaitingOAuthReturn.value) {
                awaitingOAuthReturn.value = false
                webViewRef.value?.loadUrl(SERVER_URL)
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose { lifecycleOwner.lifecycle.removeObserver(observer) }
    }

    LaunchedEffect(isLoading, loadFailed) {
        if (!isLoading || loadFailed) return@LaunchedEffect

        delay(WEBVIEW_LOAD_TIMEOUT_MS)

        if (isLoading && !loginHandled.value) {
            webViewRef.value?.stopLoading()
            isLoading = false
            loadFailed = true
        }
    }

    Box(modifier = Modifier.fillMaxSize()) {
        AndroidView(
            factory = { ctx ->
                WebView(ctx).apply {
                    val wv = this
                    CookieManager.getInstance().apply {
                        setAcceptCookie(true)
                        setAcceptThirdPartyCookies(wv, true)
                    }
                    // Same settings as the main shell WebView, including the
                    // in-app User-Agent marker; this also warms the shared HTTP
                    // cache with the fonts and CSS the rest of the app needs.
                    settings.applyFerosaDefaults(ctx, BuildConfig.DEBUG)

                    webViewClient = object : WebViewClient() {
                        override fun shouldOverrideUrlLoading(
                            view: WebView?,
                            request: WebResourceRequest?
                        ): Boolean {
                            val dest = request?.url?.toString() ?: return false
                            if (dest.contains("accounts.google.com") ||
                                dest.contains("facebook.com/dialog/oauth") ||
                                dest.contains("graph.facebook.com/oauth")
                            ) {
                                awaitingOAuthReturn.value = true
                                CustomTabsIntent.Builder()
                                    .setShowTitle(true)
                                    .build()
                                    .launchUrl(ctx, dest.toUri())
                                return true
                            }
                            return false
                        }

                        override fun onPageStarted(
                            view: WebView?,
                            pageUrl: String?,
                            favicon: android.graphics.Bitmap?,
                        ) {
                            loadFailed = false
                        }

                        override fun onPageCommitVisible(view: WebView?, pageUrl: String?) {
                            detectLogin(view, pageUrl, allowUrlFallback = false)
                            val isLandingPage = pageUrl?.endsWith("/home") == true ||
                                pageUrl?.contains("/home?") == true ||
                                pageUrl?.endsWith("/admin") == true ||
                                pageUrl?.contains("/admin?") == true
                            if (!isLandingPage) isLoading = false
                            view?.evaluateJavascript(WEBVIEW_CURSOR_FIX_JS, null)
                        }

                        override fun onReceivedError(
                            view: WebView?,
                            request: WebResourceRequest?,
                            error: android.webkit.WebResourceError?,
                        ) {
                            if (request?.isForMainFrame != true) return
                            isLoading = false
                            loadFailed = true
                        }

                        // Fires when the navigation commits, well before the page's
                        // images finish - the earliest point the landing URL is known.
                        override fun doUpdateVisitedHistory(
                            view: WebView?,
                            pageUrl: String?,
                            isReload: Boolean,
                        ) {
                            super.doUpdateVisitedHistory(view, pageUrl, isReload)
                            detectLogin(view, pageUrl, allowUrlFallback = false)
                        }

                        override fun onPageFinished(view: WebView?, pageUrl: String?) {
                            isLoading = false
                            // Backstop: if the meta tag was still unparsed on every
                            // earlier attempt, settle for the URL-derived role now.
                            detectLogin(view, pageUrl, allowUrlFallback = true)
                            view?.evaluateJavascript(WEBVIEW_CURSOR_FIX_JS, null)
                        }
                    }
                    // Retries detection as the document streams in. The commit
                    // hook above can land before <head> is parsed; this catches
                    // it moments later, still long before the images settle.
                    webChromeClient = object : WebChromeClient() {
                        override fun onProgressChanged(view: WebView?, newProgress: Int) {
                            super.onProgressChanged(view, newProgress)
                            if (newProgress >= 25) {
                                detectLogin(view, view?.url, allowUrlFallback = false)
                            }
                        }
                    }
                    loadUrl(url)
                }.also { webViewRef.value = it }
            },
            modifier = Modifier.fillMaxSize()
        )

        AnimatedVisibility(
            visible = isLoading,
            enter = fadeIn(),
            exit = fadeOut(),
            modifier = Modifier.align(Alignment.TopCenter)
        ) {
            LinearProgressIndicator(
                modifier = Modifier.fillMaxWidth(),
                color = Brand600,
                trackColor = Brand50
            )
        }

        AnimatedVisibility(
            visible = loadFailed,
            enter = fadeIn(),
            exit = fadeOut(),
            modifier = Modifier.fillMaxSize(),
        ) {
            ConnectionErrorScreen(
                onRetry = {
                    loadFailed = false
                    isLoading = true
                    webViewRef.value?.reload()
                },
            )
        }
    }
}
