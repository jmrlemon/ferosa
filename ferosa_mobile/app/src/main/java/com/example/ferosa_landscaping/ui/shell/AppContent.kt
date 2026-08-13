package com.example.ferosa_landscaping.ui.shell

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color as AndroidColor
import android.net.Uri
import android.view.View
import android.view.ViewGroup
import android.webkit.CookieManager
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Toast
import androidx.activity.compose.BackHandler
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.browser.customtabs.CustomTabsIntent
import androidx.compose.animation.AnimatedContent
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.core.tween
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInHorizontally
import androidx.compose.animation.slideOutHorizontally
import androidx.compose.animation.togetherWith
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import androidx.core.net.toUri
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.example.ferosa_landscaping.ArActivity
import com.example.ferosa_landscaping.BuildConfig
import com.example.ferosa_landscaping.NativeEstimatorScreen
import com.example.ferosa_landscaping.SERVER_URL
import com.example.ferosa_landscaping.ui.home.HomeScreen
import com.example.ferosa_landscaping.ui.more.MoreScreen
import com.example.ferosa_landscaping.ui.navigation.AppScreen
import com.example.ferosa_landscaping.ui.navigation.isNativeFor
import com.example.ferosa_landscaping.ui.navigation.nativeNavigationOrder
import com.example.ferosa_landscaping.ui.navigation.webUrl
import com.example.ferosa_landscaping.ui.summary.AccountSummary
import com.example.ferosa_landscaping.ui.theme.Brand50
import com.example.ferosa_landscaping.ui.theme.Brand600
import com.example.ferosa_landscaping.ui.theme.Surface50
import com.example.ferosa_landscaping.ui.web.ConnectionErrorScreen
import com.example.ferosa_landscaping.ui.web.PendingDownload
import com.example.ferosa_landscaping.ui.web.WEBVIEW_CURSOR_FIX_JS
import com.example.ferosa_landscaping.ui.web.WEBVIEW_LOADING_INDICATOR_DELAY_MS
import com.example.ferosa_landscaping.ui.web.WEBVIEW_LOAD_TIMEOUT_MS
import com.example.ferosa_landscaping.ui.web.acceptsImages
import com.example.ferosa_landscaping.ui.web.applyFerosaDefaults
import com.example.ferosa_landscaping.ui.web.buildFileChooserIntent
import com.example.ferosa_landscaping.ui.web.createImageCaptureUri
import com.example.ferosa_landscaping.ui.web.downloadNeedsLegacyPermission
import com.example.ferosa_landscaping.ui.web.enqueueBrowserDownload
import com.example.ferosa_landscaping.ui.web.resolveFileChooserResult
import com.example.ferosa_landscaping.ui.web.webDestinationMatches
import kotlinx.coroutines.delay

/**
 * Main content area that holds:
 * 1. A SINGLE persistent WebView (never removed from composition) — avoids session loss.
 * 2. Native Compose screens drawn on top when a native tab is active.
 *
 * When the user switches to a web tab, [LaunchedEffect] calls loadUrl() on the
 * already-live WebView instead of creating a new one, so all cookies/session data
 * from the login step are kept intact.
 */
@Composable
fun AppContent(
    modifier: Modifier = Modifier,
    currentScreen: AppScreen,
    userRole: String,
    summary: AccountSummary,
    onNavigate: (AppScreen) -> Unit,
    onLoggedOut: () -> Unit,
    onOpenAr: (ArLaunchRequest) -> Unit,
    onRefreshSummary: () -> Unit,
) {
    val context = LocalContext.current
    val serverHost = remember { SERVER_URL.toUri().host ?: "" }
    var isLoading by remember { mutableStateOf(false) }
    var webPageReadyFor by remember { mutableStateOf<AppScreen?>(null) }
    // The WebView can finish a page while a native screen is covering it. Keep
    // that URL so selecting the matching tab can reveal the already-rendered
    // document immediately instead of showing an empty transition frame.
    var readyWebUrl by remember { mutableStateOf<String?>(null) }
    var showLoadingIndicator by remember { mutableStateOf(false) }
    // Set when the main frame fails to load, so the shell can show a branded
    // offline screen with a retry instead of the WebView's default error page.
    var loadFailed by remember { mutableStateOf(false) }
    // Index in the shared WebView's history where the current tab began. Back
    // rewinds pages opened inside the tab, but never past it into another tab —
    // the WebView is shared, so its raw history spans every tab visited.
    var tabHistoryBaseline by remember { mutableStateOf(-1) }

    // ── File upload plumbing ────────────────────────────────────────────────
    var filePathCallback by remember { mutableStateOf<ValueCallback<Array<Uri>>?>(null) }
    var pendingChooserParams by remember {
        mutableStateOf<WebChromeClient.FileChooserParams?>(null)
    }
    var pendingCaptureUri by remember { mutableStateOf<Uri?>(null) }

    val fileChooserLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.StartActivityForResult(),
    ) { result ->
        val callback = filePathCallback
        val captureUri = pendingCaptureUri
        filePathCallback = null
        pendingCaptureUri = null
        callback?.onReceiveValue(
            resolveFileChooserResult(context, result.resultCode, result.data, captureUri)
        )
    }

    /**
     * Opens the picker. Any failure here still has to answer the page's
     * callback, otherwise the file input stays stuck waiting forever.
     */
    fun launchFileChooser(withCamera: Boolean) {
        val params = pendingChooserParams
        pendingChooserParams = null

        val captureUri = if (withCamera) createImageCaptureUri(context) else null
        pendingCaptureUri = captureUri

        val launched = runCatching {
            fileChooserLauncher.launch(buildFileChooserIntent(context, params, captureUri))
            true
        }.getOrDefault(false)

        if (!launched) {
            val callback = filePathCallback
            filePathCallback = null
            pendingCaptureUri = null
            callback?.onReceiveValue(null)
            Toast.makeText(context, "No app available to pick a file", Toast.LENGTH_SHORT).show()
        }
    }

    // The app declares the CAMERA permission (for AR), which means the system
    // refuses ACTION_IMAGE_CAPTURE unless it has been granted. Ask at the point
    // the user is about to need it, and fall back to a gallery-only chooser if
    // they decline — never block the upload itself.
    val cameraPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission(),
    ) { granted -> launchFileChooser(withCamera = granted) }

    // ── Download plumbing ───────────────────────────────────────────────────
    var pendingDownload by remember { mutableStateOf<PendingDownload?>(null) }

    fun startDownload(download: PendingDownload) {
        val started = enqueueBrowserDownload(
            context = context,
            url = download.url,
            userAgent = download.userAgent,
            contentDisposition = download.contentDisposition,
            mimeType = download.mimeType,
        )
        Toast.makeText(
            context,
            if (started) "Downloading…" else "That file could not be downloaded",
            Toast.LENGTH_SHORT,
        ).show()
    }

    val downloadPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission(),
    ) { granted ->
        val download = pendingDownload
        pendingDownload = null
        when {
            granted && download != null -> startDownload(download)
            else -> Toast.makeText(
                context,
                "Storage access is needed to save downloads on this Android version",
                Toast.LENGTH_LONG,
            ).show()
        }
    }

    // Reference to the single shared WebView and its pull-to-refresh container.
    val webViewRef = remember { mutableStateOf<WebView?>(null) }
    val swipeRefreshRef = remember { mutableStateOf<SwipeRefreshLayout?>(null) }

    val targetUrl = remember(currentScreen) { currentScreen.webUrl() }

    val latestScreen = rememberUpdatedState(currentScreen)
    val latestTargetUrl = rememberUpdatedState(targetUrl)

    // URL currently being loaded into the shared WebView. Both the AndroidView
    // factory and the navigation effect can start a load, and on startup they
    // both wanted the same page - this keeps that from becoming two requests.
    val inFlightUrl = remember { mutableStateOf<String?>(null) }
    val isNativeScreen = currentScreen.isNativeFor(userRole)
    val hasReadyTarget = targetUrl != null && readyWebUrl?.let { readyUrl ->
        webDestinationMatches(readyUrl, targetUrl)
    } == true
    val showWebLoading = !loadFailed && !isNativeScreen && targetUrl != null &&
        !hasReadyTarget && (isLoading || webPageReadyFor != currentScreen)

    // Where "back" bottoms out before the app should close.
    val rootScreen = if (userRole == "admin" || userRole == "staff") {
        AppScreen.ADMIN_DASHBOARD
    } else {
        AppScreen.HOME
    }

    val webView = webViewRef.value
    val canGoBackInTab = !isNativeScreen &&
        webView != null &&
        webView.copyBackForwardList().currentIndex > tabHistoryBaseline &&
        webView.canGoBack()

    // Without this, system back closed the app from every screen — including
    // partway through a booking or a product page.
    BackHandler(enabled = canGoBackInTab || currentScreen != rootScreen) {
        when {
            loadFailed -> {
                loadFailed = false
                onNavigate(rootScreen)
            }
            canGoBackInTab -> webView?.goBack()
            else -> onNavigate(rootScreen)
        }
    }

    // Navigate the single WebView whenever the screen (and thus targetUrl) changes
    LaunchedEffect(currentScreen, targetUrl, isNativeScreen) {
        // Customer Home is a native Compose screen. Its URL exists only as a
        // navigation destination; loading it here would cancel the hidden Shop
        // warm-up and briefly expose the web Home page before Shop.
        if (isNativeScreen) {
            isLoading = false
            return@LaunchedEffect
        }

        val url = targetUrl
        if (url != null && readyWebUrl?.let { webDestinationMatches(it, url) } == true) {
            webPageReadyFor = currentScreen
            tabHistoryBaseline =
                webViewRef.value?.copyBackForwardList()?.currentIndex ?: -1
            inFlightUrl.value = null
            isLoading = false
        } else if (url != null && webPageReadyFor != currentScreen) {
            isLoading = true
            // The WebView's factory issues the first load itself, so without this
            // guard startup requested the landing page twice.
            if (inFlightUrl.value != url) {
                webViewRef.value?.let { view ->
                    inFlightUrl.value = url
                    // Cancel whatever is still in flight first. Without this an
                    // earlier page could finish after this one and win, putting
                    // the wrong tab's content on screen.
                    view.stopLoading()
                    view.loadUrl(url)
                }
            }
        } else {
            isLoading = false
        }
    }

    // WebView has no dependable upper bound for onPageFinished(): one stalled
    // sub-resource can leave it pending long after the main document should
    // have appeared. Never leave the native shell on an endless spinner.
    LaunchedEffect(currentScreen, targetUrl, showWebLoading) {
        if (!showWebLoading) return@LaunchedEffect

        delay(WEBVIEW_LOAD_TIMEOUT_MS)

        if (latestScreen.value == currentScreen &&
            latestTargetUrl.value == targetUrl &&
            webPageReadyFor != currentScreen
        ) {
            webViewRef.value?.stopLoading()
            inFlightUrl.value = null
            loadFailed = true
            isLoading = false
        }
    }

    // Fast local tab changes should feel like a direct transition, not flash a
    // large spinner for a fraction of a second. Show only the slim progress bar
    // when a navigation lasts long enough to need feedback.
    LaunchedEffect(showWebLoading) {
        if (!showWebLoading) {
            showLoadingIndicator = false
            return@LaunchedEffect
        }

        delay(WEBVIEW_LOADING_INDICATOR_DELAY_MS)
        showLoadingIndicator = true
    }

    Box(modifier = modifier.fillMaxSize()) {

        // ── 1. Persistent WebView — ALWAYS in composition ───────────────────
        // Keeping it here means it is never destroyed on tab switches, so the
        // Laravel session cookie stays alive for the lifetime of the login.
        AndroidView(
            factory = { ctx ->
                val createdWebView = WebView(ctx).apply {
                    setBackgroundColor(AndroidColor.rgb(248, 247, 243))
                    overScrollMode = View.OVER_SCROLL_NEVER

                    // Accept cookies — required for Laravel session auth
                    val wv = this
                    CookieManager.getInstance().apply {
                        setAcceptCookie(true)
                        setAcceptThirdPartyCookies(wv, true)
                    }

                    settings.applyFerosaDefaults(ctx, BuildConfig.DEBUG)
                    settings.setSupportMultipleWindows(true) // receipt "open in new tab"

                    setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
                        val download = PendingDownload(url, userAgent, contentDisposition, mimeType)
                        if (downloadNeedsLegacyPermission(ctx)) {
                            pendingDownload = download
                            downloadPermissionLauncher.launch(
                                Manifest.permission.WRITE_EXTERNAL_STORAGE
                            )
                        } else {
                            startDownload(download)
                        }
                    }

                    webChromeClient = object : WebChromeClient() {
                        override fun onShowFileChooser(
                            webView: WebView?,
                            filePath: ValueCallback<Array<Uri>>?,
                            fileChooserParams: FileChooserParams?,
                        ): Boolean {
                            // A second request supersedes the first; the earlier
                            // page callback must still be answered.
                            filePathCallback?.onReceiveValue(null)
                            filePathCallback = filePath
                            pendingChooserParams = fileChooserParams

                            val cameraIsUseful = acceptsImages(fileChooserParams)
                            val cameraGranted = ContextCompat.checkSelfPermission(
                                ctx,
                                Manifest.permission.CAMERA,
                            ) == PackageManager.PERMISSION_GRANTED

                            when {
                                !cameraIsUseful -> launchFileChooser(withCamera = false)
                                cameraGranted -> launchFileChooser(withCamera = true)
                                else -> cameraPermissionLauncher.launch(Manifest.permission.CAMERA)
                            }
                            return true
                        }

                        override fun onCreateWindow(
                            view: WebView?,
                            isDialog: Boolean,
                            isUserGesture: Boolean,
                            resultMsg: android.os.Message?
                        ): Boolean {
                            val transport = resultMsg?.obj as? WebView.WebViewTransport
                                ?: return false
                            transport.webView = view
                            resultMsg.sendToTarget()
                            return true
                        }
                    }

                    webViewClient = object : WebViewClient() {
                        /** Reveal the current tab as soon as WebView commits drawable content. */
                        private fun settleVisiblePage(view: WebView?, url: String?) {
                            inFlightUrl.value = null
                            swipeRefreshRef.value?.isRefreshing = false
                            if (url == null) return

                            if (url.endsWith("/login") || url.contains("/login?")) {
                                isLoading = false
                                onLoggedOut()
                                return
                            }

                            // This also records pages warmed behind a native
                            // screen. In particular, /shop is prepared while the
                            // customer is on Home so its first visible frame is
                            // the real Shop page, never an empty WebView.
                            readyWebUrl = url
                            val expectedUrl = latestTargetUrl.value
                            if (expectedUrl != null && webDestinationMatches(url, expectedUrl)) {
                                if (webPageReadyFor != latestScreen.value) {
                                    tabHistoryBaseline =
                                        view?.copyBackForwardList()?.currentIndex ?: -1
                                }
                                webPageReadyFor = latestScreen.value
                                isLoading = false
                            }
                        }

                        override fun shouldOverrideUrlLoading(
                            view: WebView?,
                            request: WebResourceRequest?
                        ): Boolean {
                            val dest = request?.url?.toString() ?: return false
                            val uri = dest.toUri()

                            return when {
                                // ferosa://ar → launch AR activity
                                uri.scheme == "ferosa" && uri.host == "ar" -> {
                                    ctx.startActivity(
                                        Intent(ctx, ArActivity::class.java).apply { data = uri }
                                    )
                                    true
                                }
                                // Same server → stay inside WebView
                                (uri.scheme == "http" || uri.scheme == "https") &&
                                    uri.host == serverHost -> false
                                // External URLs → Custom Tab
                                uri.scheme == "http" || uri.scheme == "https" -> {
                                    CustomTabsIntent.Builder()
                                        .setShowTitle(true)
                                        .build()
                                        .launchUrl(ctx, uri)
                                    true
                                }
                                // Other schemes (tel: mailto: etc.)
                                else -> {
                                    runCatching {
                                        ctx.startActivity(Intent(Intent.ACTION_VIEW, uri))
                                    }
                                    true
                                }
                            }
                        }

                        override fun onPageCommitVisible(view: WebView?, url: String?) {
                            settleVisiblePage(view, url)
                            // The observer also patches inputs streamed into the
                            // document after this first visible frame.
                            view?.evaluateJavascript(WEBVIEW_CURSOR_FIX_JS, null)
                        }

                        override fun onPageFinished(view: WebView?, url: String?) {
                            // Backstop for WebView implementations that do not
                            // dispatch onPageCommitVisible consistently.
                            settleVisiblePage(view, url)
                            view?.evaluateJavascript(WEBVIEW_CURSOR_FIX_JS, null)
                            // A page the user navigated to may have changed the
                            // cart or read their messages; keep the badges honest.
                            onRefreshSummary()
                        }

                        override fun onPageStarted(
                            view: WebView?,
                            url: String?,
                            favicon: android.graphics.Bitmap?
                        ) {
                            // A fresh navigation clears any previous failure.
                            loadFailed = false
                        }

                        @Suppress("OVERRIDE_DEPRECATION")
                        override fun onReceivedError(
                            view: WebView?,
                            errorCode: Int,
                            description: String?,
                            failingUrl: String?
                        ) {
                            inFlightUrl.value = null
                            swipeRefreshRef.value?.isRefreshing = false
                            webPageReadyFor = latestScreen.value
                            isLoading = false
                        }

                        // API 23+ variant: tells us whether the failure was the
                        // page itself or just a sub-resource (image, script). Only
                        // a main-frame failure should replace the screen.
                        override fun onReceivedError(
                            view: WebView?,
                            request: WebResourceRequest?,
                            error: android.webkit.WebResourceError?
                        ) {
                            if (request?.isForMainFrame != true) return
                            inFlightUrl.value = null
                            swipeRefreshRef.value?.isRefreshing = false
                            loadFailed = true
                            webPageReadyFor = latestScreen.value
                            isLoading = false
                        }
                    }
                }

                webViewRef.value = createdWebView

                // Home is native for customers, so use that otherwise-idle
                // time to fully render Shop behind it. The first Shop tap can
                // then reveal a ready page in the same frame.
                val firstUrl = if (
                    userRole == "user" && latestScreen.value == AppScreen.HOME
                ) {
                    "$SERVER_URL/shop"
                } else latestTargetUrl.value
                    ?: if (userRole == "admin" || userRole == "staff") {
                        "$SERVER_URL/admin"
                    } else {
                        "$SERVER_URL/home"
                    }
                inFlightUrl.value = firstUrl
                createdWebView.loadUrl(firstUrl)

                // Compose's PullToRefreshBox never sees scroll from a WebView
                // inside AndroidView, so the classic view-system container is
                // the one that actually works here.
                SwipeRefreshLayout(ctx).apply {
                    setColorSchemeColors(Brand600.toArgb())
                    addView(
                        createdWebView,
                        ViewGroup.LayoutParams.MATCH_PARENT,
                        ViewGroup.LayoutParams.MATCH_PARENT,
                    )
                    // Only take over the gesture at the very top of the document,
                    // otherwise scrolling up through a long page triggers refresh.
                    setOnChildScrollUpCallback { _, _ -> createdWebView.scrollY > 0 }
                    setOnRefreshListener {
                        loadFailed = false
                        createdWebView.reload()
                    }
                    swipeRefreshRef.value = this
                }
            },
            modifier = Modifier.fillMaxSize(),
            update = { container ->
                // Native screens are opaque Compose overlays, so the WebView can
                // remain visible and keep its drawing surface warm underneath.
                // Toggling INVISIBLE here cost one blank frame when Home was
                // removed, even if Shop had already finished loading.
                container.visibility = if (showWebLoading) View.INVISIBLE else View.VISIBLE
                // Pull-to-refresh would otherwise fire under a native screen or
                // while the shell is already loading that tab.
                container.isEnabled = !isNativeScreen && !showWebLoading && !loadFailed
            },
        )

        // ── 2. Native screen overlay ────────────────────────────────────────
        // Drawn on top of the WebView when a native tab is active. When the user
        // switches to a web tab, this is removed and the WebView (which was
        // always there) becomes fully visible.
        if (isNativeScreen) {
            AnimatedContent(
                targetState = currentScreen,
                modifier = Modifier
                    .fillMaxSize()
                    .background(Surface50),
                transitionSpec = {
                    val direction = if (
                        targetState.nativeNavigationOrder() >= initialState.nativeNavigationOrder()
                    ) 1 else -1
                    (slideInHorizontally(animationSpec = tween(220)) { width ->
                        direction * (width / 12)
                    } + fadeIn(animationSpec = tween(180))) togetherWith
                        (slideOutHorizontally(animationSpec = tween(180)) { width ->
                            -direction * (width / 16)
                        } + fadeOut(animationSpec = tween(120)))
                },
                label = "native-screen-transition",
            ) { screen ->
                when {
                    userRole == "user" && screen == AppScreen.HOME -> HomeScreen(
                        modifier = Modifier.fillMaxSize(),
                        summary = summary,
                        onOpenAr = { onOpenAr(ArLaunchRequest()) },
                        onOpenShop = { onNavigate(AppScreen.SHOP) },
                        onOpenOrders = { onNavigate(AppScreen.ORDERS) },
                        onOpenBook = { onNavigate(AppScreen.BOOK) },
                        onOpenEstimator = { onNavigate(AppScreen.ESTIMATOR) },
                        onOpenMessages = { onNavigate(AppScreen.MESSAGES) },
                        onOpenNotifications = { onNavigate(AppScreen.NOTIFICATIONS) },
                        onOpenAppointments = { onNavigate(AppScreen.APPOINTMENTS) },
                    )

                    userRole == "user" && screen == AppScreen.ESTIMATOR -> NativeEstimatorScreen(
                        modifier = Modifier.fillMaxSize(),
                        onBook = { onNavigate(AppScreen.BOOK) },
                        onOpenAr = onOpenAr,
                    )

                    screen == AppScreen.MORE -> MoreScreen(
                        modifier = Modifier.fillMaxSize(),
                        userRole = userRole,
                        summary = summary,
                        onNavigate = onNavigate,
                        onOpenAr = { onOpenAr(ArLaunchRequest()) },
                    )
                }
            }
        }

        // ── 2.5. Loading cover — prevents stale/unstyled content flashing ───
        AnimatedVisibility(
            visible = showWebLoading,
            enter = fadeIn(animationSpec = tween(100)),
            exit = fadeOut(animationSpec = tween(200)),
            modifier = Modifier.fillMaxSize()
        ) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Surface50)
            )
        }

        // ── 3. Top loading bar ──────────────────────────────────────────────
        AnimatedVisibility(
            visible = showLoadingIndicator,
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

        // ── 4. Connection failure ───────────────────────────────────────────
        AnimatedVisibility(
            visible = loadFailed && !isNativeScreen,
            enter = fadeIn(),
            exit = fadeOut(),
            modifier = Modifier.fillMaxSize()
        ) {
            ConnectionErrorScreen(
                onRetry = {
                    loadFailed = false
                    isLoading = true
                    val url = latestTargetUrl.value
                    if (url != null) webViewRef.value?.loadUrl(url) else webViewRef.value?.reload()
                }
            )
        }
    }
}
