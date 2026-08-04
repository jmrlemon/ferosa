package com.example.ferosa_landscaping

import android.content.Intent
import android.graphics.Color as AndroidColor
import android.net.Uri
import android.os.Bundle
import android.view.View
import android.webkit.CookieManager
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.ValueCallback
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.browser.customtabs.CustomTabsIntent
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.compose.ui.platform.LocalContext
import androidx.compose.runtime.DisposableEffect
import androidx.lifecycle.compose.LocalLifecycleOwner
import androidx.activity.ComponentActivity
import androidx.activity.compose.BackHandler
import androidx.activity.compose.setContent
import androidx.core.net.toUri
import androidx.core.view.WindowCompat
import androidx.compose.animation.AnimatedContent
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.core.tween
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInHorizontally
import androidx.compose.animation.slideOutHorizontally
import androidx.compose.animation.togetherWith
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.automirrored.filled.EventNote
import androidx.compose.material.icons.filled.AccountCircle
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Calculate
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.Collections
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Dashboard
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.MoreHoriz
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.ViewInAr
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.hapticfeedback.HapticFeedbackType
import androidx.compose.ui.platform.LocalHapticFeedback
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import com.example.ferosa_landscaping.ui.theme.*

/**
 * Injected on [WebViewClient.onPageFinished] to fix Android WebView IME resetting the caret
 * to index 0 after each keystroke. Password fields were previously excluded (`:not([type=password])`)
 * but they hit the same bug on physical devices.
 */
private val WEBVIEW_CURSOR_FIX_JS = """
(function(){
  // This runs from onPageFinished, which can fire when there is no document to
  // touch yet - an about:blank frame, or a page torn down by a fast tab switch.
  // Passing a null root to MutationObserver.observe() threw
  // "parameter 1 is not of type 'Node'" and aborted the rest of this script,
  // taking the in-app class and the caret fix with it.
  var root = document.body || document.documentElement;
  if(!root)return;

  function isPatchable(el){
    if(!el||el.tagName!=='INPUT')return false;
    var t=(el.getAttribute('type')||'text').toLowerCase();
    return ['checkbox','radio','hidden','button','submit','file','image','range','color','reset'].indexOf(t)<0;
  }

  if(document.body)document.body.classList.add('in-app');

  var patched=new WeakSet();
  function patch(el){
    if(!isPatchable(el)||patched.has(el))return;
    patched.add(el);
    el.setAttribute('dir','ltr');
    el.setAttribute('autocorrect','off');
    el.setAttribute('spellcheck','false');
    el.addEventListener('input',function(){
      var end=this.value.length;
      if(this.selectionStart===0&&end>0){
        try{this.setSelectionRange(end,end);}catch(e){}
      }
    },true);
  }

  // Re-running this injection must not stack a second observer on the page.
  if(window.__ferosaCursorFix)return;
  window.__ferosaCursorFix=true;

  try{
    Array.prototype.forEach.call(document.querySelectorAll('input'),patch);
    new MutationObserver(function(ms){
      ms.forEach(function(m){
        Array.prototype.forEach.call(m.addedNodes,function(n){
          if(n.tagName==='INPUT')patch(n);
          if(n.querySelectorAll)Array.prototype.forEach.call(n.querySelectorAll('input'),patch);
        });
      });
    }).observe(root,{childList:true,subtree:true});
  }catch(e){}
})();
""".trimIndent()

/** Marker the Laravel layouts look for to render the in-app (chrome-free) view. */
private const val FEROSA_APP_UA_MARKER = "FerosaApp/1.0"

/**
 * Appends the in-app marker to a WebView User-Agent, leaving it alone if it is
 * already there so a recreated WebView cannot stack duplicates.
 */
private fun withFerosaAppMarker(userAgent: String?): String {
    val base = userAgent.orEmpty()
    return if (base.contains("FerosaApp")) base else "$base $FEROSA_APP_UA_MARKER".trim()
}

enum class AppScreen {
    HOME,
    SHOP,
    PROJECTS,
    ORDERS,
    APPOINTMENTS,
    BOOK,
    ACCOUNT,
    ESTIMATOR,
    FEEDBACK,
    MESSAGES,
    NOTIFICATIONS,
    ADMIN_DASHBOARD,
    ADMIN_PROJECTS,
    ADMIN_INVENTORY,
    ADMIN_APPOINTMENTS,
    ADMIN_ORDERS,
    ADMIN_MESSAGES,
    ADMIN_BUSINESS_PROFILE,
    ADMIN_ACCOUNT,
    MORE,
}

private fun webDestinationMatches(actualUrl: String, expectedUrl: String): Boolean {
    val actual = actualUrl.toUri()
    val expected = expectedUrl.toUri()
    if (actual.scheme != expected.scheme ||
        actual.host != expected.host ||
        actual.port != expected.port ||
        actual.path?.trimEnd('/') != expected.path?.trimEnd('/')
    ) {
        return false
    }

    return expected.queryParameterNames.all { name ->
        actual.getQueryParameters(name) == expected.getQueryParameters(name)
    }
}

private fun AppScreen.nativeNavigationOrder(): Int = when (this) {
    AppScreen.HOME -> 0
    AppScreen.ESTIMATOR -> 1
    AppScreen.MORE -> 2
    else -> 1
}

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        // Keep decor fitting system windows so that adjustResize (set in AndroidManifest)
        // correctly shrinks the WebView when the soft keyboard appears.
        // Do NOT call enableEdgeToEdge() here because it overrides adjustResize on API 30+.
        WindowCompat.setDecorFitsSystemWindows(window, true)
        setContent {
            Ferosa_landscapingTheme(darkTheme = false) {
                var isLoggedIn by remember { mutableStateOf(false) }
                var userRole by remember { mutableStateOf("user") }
                var currentScreen by remember { mutableStateOf(AppScreen.HOME) }

                if (!isLoggedIn) {
                    // Full-screen login WebView
                    LoginWebViewScreen(
                        url = "$SERVER_URL/login",
                        onLoggedIn = { role ->
                            // Flush cookies so the persistent WebView can use the session
                            CookieManager.getInstance().flush()
                            userRole = role
                            currentScreen = if (role == "admin" || role == "staff") {
                                AppScreen.ADMIN_DASHBOARD
                            } else {
                                AppScreen.HOME
                            }
                            isLoggedIn = true
                        }
                    )
                } else {
                    Scaffold(
                        modifier = Modifier.fillMaxSize(),
                        containerColor = Surface50,
                        bottomBar = {
                            FerosaBottomNavigation(
                                currentScreen = currentScreen,
                                userRole = userRole,
                                onNavigate = { currentScreen = it },
                            )
                        }
                    ) { innerPadding ->
                        AppContent(
                            modifier = Modifier.padding(innerPadding),
                            currentScreen = currentScreen,
                            userRole = userRole,
                            onNavigate = { currentScreen = it },
                            onLoggedOut = {
                                isLoggedIn = false
                                userRole = "user"
                                currentScreen = AppScreen.HOME
                            },
                            onOpenAr = {
                                startActivity(
                                    Intent(this@MainActivity, ArActivity::class.java).apply {
                                        data = "ferosa://ar?designId=demo".toUri()
                                    }
                                )
                            }
                        )
                    }
                }
            }
        }
    }
}

/**
 * Main content area that holds:
 * 1. A SINGLE persistent WebView (never removed from composition) — avoids session loss.
 * 2. A native HomeScreen overlay shown on top when the HOME tab is active.
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
    onNavigate: (AppScreen) -> Unit,
    onLoggedOut: () -> Unit,
    onOpenAr: () -> Unit,
) {
    val context = LocalContext.current
    val serverHost = remember { SERVER_URL.toUri().host ?: "" }
    var isLoading by remember { mutableStateOf(false) }
    var webPageReadyFor by remember { mutableStateOf<AppScreen?>(null) }
    var filePathCallback by remember { mutableStateOf<ValueCallback<Array<Uri>>?>(null) }
    // Set when the main frame fails to load, so the shell can show a branded
    // offline screen with a retry instead of the WebView's default error page.
    var loadFailed by remember { mutableStateOf(false) }
    // Index in the shared WebView's history where the current tab began. Back
    // rewinds pages opened inside the tab, but never past it into another tab —
    // the WebView is shared, so its raw history spans every tab visited.
    var tabHistoryBaseline by remember { mutableStateOf(-1) }

    val fileChooserLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.StartActivityForResult(),
    ) { result ->
        val callback = filePathCallback
        filePathCallback = null
        callback?.onReceiveValue(
            WebChromeClient.FileChooserParams.parseResult(result.resultCode, result.data)
        )
    }

    // Reference to the single shared WebView
    val webViewRef = remember { mutableStateOf<WebView?>(null) }

    // Resolve the target URL whenever the screen changes
    val targetUrl = remember(currentScreen) {
        when (currentScreen) {
            AppScreen.HOME          -> "$SERVER_URL/home"
            AppScreen.SHOP          -> "$SERVER_URL/shop"
            AppScreen.PROJECTS      -> "$SERVER_URL/projects"
            AppScreen.ORDERS        -> "$SERVER_URL/orders"
            AppScreen.APPOINTMENTS  -> "$SERVER_URL/appointments"
            AppScreen.BOOK          -> "$SERVER_URL/schedule"
            AppScreen.ACCOUNT       -> "$SERVER_URL/account"
            AppScreen.ESTIMATOR     -> null
            AppScreen.FEEDBACK      -> "$SERVER_URL/feedback"
            AppScreen.MESSAGES      -> "$SERVER_URL/messages"
            AppScreen.NOTIFICATIONS -> "$SERVER_URL/notifications"
            AppScreen.ADMIN_DASHBOARD        -> "$SERVER_URL/admin"
            AppScreen.ADMIN_PROJECTS         -> "$SERVER_URL/admin/projects"
            AppScreen.ADMIN_INVENTORY        -> "$SERVER_URL/admin?tab=products"
            AppScreen.ADMIN_APPOINTMENTS     -> "$SERVER_URL/admin/service-scheduling"
            AppScreen.ADMIN_ORDERS           -> "$SERVER_URL/admin/ordering-delivery"
            AppScreen.ADMIN_MESSAGES         -> "$SERVER_URL/admin?tab=messages"
            AppScreen.ADMIN_BUSINESS_PROFILE -> "$SERVER_URL/admin/business-profile"
            AppScreen.ADMIN_ACCOUNT          -> "$SERVER_URL/admin/account"
            AppScreen.MORE                    -> null
        }
    }

    val latestScreen = rememberUpdatedState(currentScreen)
    val latestTargetUrl = rememberUpdatedState(targetUrl)

    // URL currently being loaded into the shared WebView. Both the AndroidView
    // factory and the navigation effect can start a load, and on startup they
    // both wanted the same page - this keeps that from becoming two requests.
    val inFlightUrl = remember { mutableStateOf<String?>(null) }
    val isNativeScreen = currentScreen == AppScreen.MORE ||
        (userRole == "user" && currentScreen in setOf(AppScreen.HOME, AppScreen.ESTIMATOR))
    val showWebLoading = !isNativeScreen && targetUrl != null &&
        (isLoading || webPageReadyFor != currentScreen)

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
    LaunchedEffect(currentScreen, targetUrl) {
        val url = targetUrl
        if (url != null && webPageReadyFor != currentScreen) {
            isLoading = true
            // The WebView's factory issues the first load itself, so without this
            // guard startup requested the landing page twice.
            if (inFlightUrl.value != url) {
                webViewRef.value?.let { webView ->
                    inFlightUrl.value = url
                    // Cancel whatever is still in flight first. Without this an
                    // earlier page could finish after this one and win, putting
                    // the wrong tab's content on screen.
                    webView.stopLoading()
                    webView.loadUrl(url)
                }
            }
        } else {
            isLoading = false
        }
    }

    Box(modifier = modifier.fillMaxSize()) {

        // ── 1. Persistent WebView — ALWAYS in composition ───────────────────
        // Keeping it here means it is never destroyed on tab switches, so the
        // Laravel session cookie stays alive for the lifetime of the login.
        AndroidView(
            factory = { ctx ->
                WebView(ctx).apply {
                    setBackgroundColor(AndroidColor.rgb(248, 247, 243))
                    overScrollMode = View.OVER_SCROLL_NEVER

                    // Accept cookies — required for Laravel session auth
                    val wv = this
                    CookieManager.getInstance().apply {
                        setAcceptCookie(true)
                        setAcceptThirdPartyCookies(wv, true)
                    }

                    settings.apply {
                        // The app is a shell around our own Laravel site, which
                        // needs JavaScript to work at all. Navigation away from
                        // our host is handed to a Custom Tab / the system
                        // browser (see shouldOverrideUrlLoading), so untrusted
                        // pages never run inside this WebView.
                        @Suppress("SetJavaScriptEnabled")
                        javaScriptEnabled    = true
                        domStorageEnabled    = true
                        useWideViewPort      = true
                        loadWithOverviewMode = true
                        // Lets the server render the in-app layout on the first
                        // paint. Without it the site only learns it is running
                        // inside the app once the injected script runs on page
                        // finish, so the web nav bar appears below the native
                        // one for the whole load.
                        userAgentString = withFerosaAppMarker(userAgentString)
                        setSupportMultipleWindows(true)  // needed for receipt "open in new tab"
                        allowFileAccess = true
                        // Reuse cached CSS, JS, fonts and product images instead of
                        // refetching them on every tab switch. The pages themselves
                        // send no-store headers, so navigating still gets fresh data
                        // - this only spares the static assets.
                        cacheMode = android.webkit.WebSettings.LOAD_DEFAULT
                        // Zoom must be exactly 100% — any other value corrupts Android IME
                        // cursor position calculations, causing cursor-at-0 bug
                        textZoom = 100
                        @Suppress("DEPRECATION")
                        mixedContentMode = android.webkit.WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
                    }
                    // Handle window.open() calls (e.g. receipt links)
                    webChromeClient = object : WebChromeClient() {
                        override fun onShowFileChooser(
                            webView: WebView?,
                            filePath: ValueCallback<Array<Uri>>?,
                            fileChooserParams: FileChooserParams?,
                        ): Boolean {
                            filePathCallback?.onReceiveValue(null)
                            filePathCallback = filePath

                            val chooserIntent = runCatching {
                                fileChooserParams?.createIntent()
                                    ?: Intent(Intent.ACTION_GET_CONTENT).apply {
                                        type = "image/*"
                                        addCategory(Intent.CATEGORY_OPENABLE)
                                    }
                            }.getOrNull()

                            if (chooserIntent == null) {
                                filePathCallback = null
                                filePath?.onReceiveValue(null)
                                return false
                            }

                            return runCatching {
                                fileChooserLauncher.launch(chooserIntent)
                                true
                            }.getOrElse {
                                filePathCallback = null
                                filePath?.onReceiveValue(null)
                                false
                            }
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
                        override fun shouldOverrideUrlLoading(
                            view: WebView?,
                            request: WebResourceRequest?
                        ): Boolean {
                            val dest = request?.url?.toString() ?: return false
                            val uri  = dest.toUri()

                            return when {
                                // ferosa://ar → launch AR activity
                                uri.scheme == "ferosa" && uri.host == "ar" -> {
                                    ctx.startActivity(
                                        Intent(ctx, ArActivity::class.java).apply { data = uri }
                                    )
                                    true
                                }
                                // Same server → stay inside WebView
                                (uri.scheme == "http" || uri.scheme == "https")
                                    && uri.host == serverHost -> false
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
                                    runCatching { ctx.startActivity(Intent(Intent.ACTION_VIEW, uri)) }
                                    true
                                }
                            }
                        }

                        override fun onPageFinished(view: WebView?, url: String?) {
                            // This load is done, so a later navigation to the same
                            // address must not be treated as already in flight.
                            inFlightUrl.value = null

                            // Server redirected to /login → session expired, log out
                            if (url != null &&
                                (url.endsWith("/login") || url.contains("/login?"))
                            ) {
                                isLoading = false
                                onLoggedOut()
                            } else {
                                val expectedUrl = latestTargetUrl.value
                                if (url != null && expectedUrl != null &&
                                    webDestinationMatches(url, expectedUrl)
                                ) {
                                    // Landing on a tab's own page marks the point
                                    // system-back should stop rewinding history.
                                    if (webPageReadyFor != latestScreen.value) {
                                        tabHistoryBaseline =
                                            view?.copyBackForwardList()?.currentIndex ?: -1
                                    }
                                    webPageReadyFor = latestScreen.value
                                    isLoading = false
                                }
                            }
                            view?.evaluateJavascript(WEBVIEW_CURSOR_FIX_JS, null)
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
                            loadFailed = true
                            webPageReadyFor = latestScreen.value
                            isLoading = false
                        }
                    }
                    // Load whatever tab is actually open, not a hardcoded landing
                    // page. This block used to always request /home for customers,
                    // so a WebView created (or finishing its warm-up load) after the
                    // user had already tapped another tab would replace that tab's
                    // page with the home page - leaving Shop highlighted while the
                    // home page was on screen.
                    val firstUrl = latestTargetUrl.value
                        ?: if (userRole == "admin" || userRole == "staff") {
                            "$SERVER_URL/admin"
                        } else {
                            "$SERVER_URL/home"
                        }
                    inFlightUrl.value = firstUrl
                    loadUrl(firstUrl)
                }.also { webViewRef.value = it }
            },
            modifier = Modifier.fillMaxSize(),
            update = { webView ->
                webView.visibility = if (isNativeScreen || showWebLoading) {
                    View.INVISIBLE
                } else {
                    View.VISIBLE
                }
            },
        )

        // ── 2. Native HomeScreen overlay ────────────────────────────────────
        // Drawn on top of the WebView when the HOME tab is active.
        // When the user switches to another tab, this is removed and the WebView
        // (which was always there) becomes fully visible.
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
                        onOpenAr = onOpenAr,
                        onOpenShop = { onNavigate(AppScreen.SHOP) },
                        onOpenOrders = { onNavigate(AppScreen.ORDERS) },
                        onOpenBook = { onNavigate(AppScreen.BOOK) },
                        onOpenEstimatorWebView = { onNavigate(AppScreen.ESTIMATOR) },
                        onOpenMessages = { onNavigate(AppScreen.MESSAGES) },
                        onOpenNotifications = { onNavigate(AppScreen.NOTIFICATIONS) },
                    )

                    userRole == "user" && screen == AppScreen.ESTIMATOR -> NativeEstimatorScreen(
                        modifier = Modifier.fillMaxSize(),
                        onBook = { onNavigate(AppScreen.BOOK) },
                        onOpenAr = onOpenAr,
                    )

                    screen == AppScreen.MORE -> MoreScreen(
                        modifier = Modifier.fillMaxSize(),
                        userRole = userRole,
                        onNavigate = onNavigate,
                        onOpenAr = onOpenAr,
                    )
                }
            }
        }

        // ── 2.5. Loading overlay — covers WebView while new page loads ──────
        // Prevents flash of old content when switching tabs
        AnimatedVisibility(
            visible  = showWebLoading,
            enter    = fadeIn(animationSpec = tween(100)),
            exit     = fadeOut(animationSpec = tween(200)),
            modifier = Modifier.fillMaxSize()
        ) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Surface50),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(
                    color       = Brand600,
                    modifier    = Modifier.size(32.dp),
                    strokeWidth = 3.dp
                )
            }
        }

        // ── 3. Top loading bar ──────────────────────────────────────────────
        AnimatedVisibility(
            visible  = showWebLoading,
            enter    = fadeIn(),
            exit     = fadeOut(),
            modifier = Modifier.align(Alignment.TopCenter)
        ) {
            LinearProgressIndicator(
                modifier   = Modifier.fillMaxWidth(),
                color      = Brand600,
                trackColor = Brand50
            )
        }

        // ── 4. Connection failure ───────────────────────────────────────────
        // Without this the WebView shows its own grey "webpage not available"
        // page, which looks like the app itself is broken.
        AnimatedVisibility(
            visible = loadFailed && !isNativeScreen,
            enter   = fadeIn(),
            exit    = fadeOut(),
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

/**
 * Circular header action. Keeps the 48dp minimum touch target while the visible
 * circle stays 44dp, and exposes itself to TalkBack as a button.
 */
@Composable
private fun HeaderIconButton(
    icon: ImageVector,
    contentDescription: String,
    onClick: () -> Unit,
) {
    Box(
        modifier = Modifier
            .size(48.dp)
            .clip(CircleShape)
            .clickable(
                onClick = onClick,
                role = Role.Button,
                onClickLabel = contentDescription,
            ),
        contentAlignment = Alignment.Center,
    ) {
        Box(
            modifier = Modifier
                .size(44.dp)
                .clip(CircleShape)
                .background(Surface100),
            contentAlignment = Alignment.Center,
        ) {
            Icon(
                icon,
                contentDescription = contentDescription,
                tint = Surface600,
                modifier = Modifier.size(20.dp),
            )
        }
    }
}

/** Shown when the WebView cannot reach the server. */
@Composable
private fun ConnectionErrorScreen(onRetry: () -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Surface50)
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Box(
            modifier = Modifier
                .size(72.dp)
                .clip(CircleShape)
                .background(Brand50),
            contentAlignment = Alignment.Center,
        ) {
            Icon(
                Icons.Default.CloudOff,
                contentDescription = null,
                tint = Brand600,
                modifier = Modifier.size(32.dp),
            )
        }
        Spacer(Modifier.height(20.dp))
        Text(
            "No connection",
            style = MaterialTheme.typography.titleLarge,
            color = Surface900,
            fontWeight = FontWeight.Bold,
        )
        Spacer(Modifier.height(8.dp))
        Text(
            "We couldn't reach Ferosa. Check your internet connection and try again.",
            style = MaterialTheme.typography.bodyMedium,
            color = Surface500,
            textAlign = TextAlign.Center,
            lineHeight = 21.sp,
        )
        Spacer(Modifier.height(24.dp))
        Button(
            onClick = onRetry,
            colors = ButtonDefaults.buttonColors(containerColor = Brand600),
            shape = RoundedCornerShape(12.dp),
            modifier = Modifier.defaultMinSize(minHeight = 48.dp),
        ) {
            Icon(Icons.Default.Refresh, contentDescription = null, modifier = Modifier.size(18.dp))
            Spacer(Modifier.width(8.dp))
            Text("Try again", fontWeight = FontWeight.SemiBold)
        }
    }
}

@Composable
private fun BottomNavLabel(text: String) {
    Text(
        text = text,
        fontSize = 11.sp,
        maxLines = 1,
        overflow = TextOverflow.Ellipsis
    )
}

private data class BottomDestination(
    val screen: AppScreen,
    val label: String,
    val icon: ImageVector,
)

@Composable
private fun FerosaBottomNavigation(
    currentScreen: AppScreen,
    userRole: String,
    onNavigate: (AppScreen) -> Unit,
) {
    val isStaffWorkspace = userRole == "admin" || userRole == "staff"
    val destinations = if (isStaffWorkspace) {
        listOf(
            BottomDestination(AppScreen.ADMIN_DASHBOARD, "Dashboard", Icons.Default.Dashboard),
            BottomDestination(AppScreen.ADMIN_PROJECTS, "Portfolio", Icons.Default.Collections),
            BottomDestination(AppScreen.ADMIN_INVENTORY, "Inventory", Icons.Default.Inventory2),
            BottomDestination(AppScreen.ADMIN_MESSAGES, "Messages", Icons.AutoMirrored.Filled.Chat),
            BottomDestination(AppScreen.MORE, "More", Icons.Default.MoreHoriz),
        )
    } else {
        listOf(
            BottomDestination(AppScreen.HOME, "Home", Icons.Default.Home),
            BottomDestination(AppScreen.SHOP, "Shop", Icons.Default.ShoppingCart),
            BottomDestination(AppScreen.ESTIMATOR, "Estimator", Icons.Default.Calculate),
            BottomDestination(AppScreen.BOOK, "Book", Icons.Default.CalendarMonth),
            BottomDestination(AppScreen.MORE, "More", Icons.Default.MoreHoriz),
        )
    }

    val moreScreens = if (isStaffWorkspace) {
        setOf(
            AppScreen.MORE,
            AppScreen.ADMIN_APPOINTMENTS,
            AppScreen.ADMIN_ORDERS,
            AppScreen.ADMIN_BUSINESS_PROFILE,
            AppScreen.ADMIN_ACCOUNT,
            AppScreen.HOME,
            AppScreen.NOTIFICATIONS,
        )
    } else {
        setOf(
            AppScreen.MORE,
            AppScreen.ORDERS,
            AppScreen.APPOINTMENTS,
            AppScreen.ACCOUNT,
            AppScreen.PROJECTS,
            AppScreen.FEEDBACK,
            AppScreen.MESSAGES,
            AppScreen.NOTIFICATIONS,
        )
    }

    val haptics = LocalHapticFeedback.current

    NavigationBar(
        containerColor = Color.White,
        tonalElevation = 0.dp,
    ) {
        destinations.forEach { destination ->
            val selected = if (destination.screen == AppScreen.MORE) {
                currentScreen in moreScreens
            } else {
                currentScreen == destination.screen
            }
            NavigationBarItem(
                icon = {
                    Icon(
                        destination.icon,
                        contentDescription = destination.label,
                    )
                },
                label = { BottomNavLabel(destination.label) },
                selected = selected,
                onClick = {
                    // Confirms the tap even before the next screen paints.
                    if (!selected) haptics.performHapticFeedback(HapticFeedbackType.TextHandleMove)
                    onNavigate(destination.screen)
                },
                colors = NavigationBarItemDefaults.colors(
                    selectedIconColor = Brand600,
                    selectedTextColor = Brand600,
                    indicatorColor = Brand50,
                ),
            )
        }
    }
}

private data class MoreAction(
    val icon: ImageVector,
    val title: String,
    val subtitle: String,
    val screen: AppScreen? = null,
    val opensAr: Boolean = false,
)

@Composable
private fun MoreScreen(
    modifier: Modifier = Modifier,
    userRole: String,
    onNavigate: (AppScreen) -> Unit,
    onOpenAr: () -> Unit,
) {
    val isStaffWorkspace = userRole == "admin" || userRole == "staff"
    val actions = if (isStaffWorkspace) {
        buildList {
            add(MoreAction(Icons.AutoMirrored.Filled.EventNote, "Appointments", "Pending confirmations and visits", AppScreen.ADMIN_APPOINTMENTS))
            add(MoreAction(Icons.Default.Receipt, "Orders & Delivery", "Processing, fulfilment, and proof", AppScreen.ADMIN_ORDERS))
            if (userRole == "admin") {
                add(MoreAction(Icons.Default.AccountCircle, "Business Profile", "Trust details and policies", AppScreen.ADMIN_BUSINESS_PROFILE))
            }
            add(MoreAction(Icons.Default.Notifications, "Notifications", "New work and status alerts", AppScreen.NOTIFICATIONS))
            add(MoreAction(Icons.Default.Home, "Customer Portal", "Review the customer experience", AppScreen.HOME))
            add(MoreAction(Icons.Default.AccountCircle, "Admin Account", "Work profile and secure sign-out", AppScreen.ADMIN_ACCOUNT))
            add(MoreAction(Icons.Default.ViewInAr, "AR Visualizer", "Test placement models", opensAr = true))
        }
    } else {
        listOf(
            MoreAction(Icons.Default.Receipt, "Orders", "Purchases and delivery tracking", AppScreen.ORDERS),
            MoreAction(Icons.AutoMirrored.Filled.EventNote, "Appointments", "Scheduled services and history", AppScreen.APPOINTMENTS),
            MoreAction(Icons.Default.Collections, "Projects", "Explore completed landscapes", AppScreen.PROJECTS),
            MoreAction(Icons.AutoMirrored.Filled.Chat, "Messages", "Talk with the Ferosa team", AppScreen.MESSAGES),
            MoreAction(Icons.Default.Notifications, "Notifications", "Order and booking updates", AppScreen.NOTIFICATIONS),
            MoreAction(Icons.Default.Star, "Feedback", "Share your experience", AppScreen.FEEDBACK),
            MoreAction(Icons.Default.AccountCircle, "Account", "Profile, preferences, and sign-out", AppScreen.ACCOUNT),
            MoreAction(Icons.Default.ViewInAr, "AR Visualizer", "Preview elements in your space", opensAr = true),
        )
    }

    Column(
        modifier = modifier
            .background(Surface50)
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 18.dp, vertical = 18.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Column(modifier = Modifier.padding(vertical = 4.dp)) {
            Text(
                if (isStaffWorkspace) "Operations tools" else "More from Ferosa",
                style = MaterialTheme.typography.headlineSmall,
                color = Surface900,
                fontWeight = FontWeight.Bold,
            )
            Spacer(Modifier.height(5.dp))
            Text(
                if (isStaffWorkspace) {
                    "Open the same live admin workflows available on the web dashboard."
                } else {
                    "Your orders, appointments, messages, planning tools, and account settings."
                },
                style = MaterialTheme.typography.bodyMedium,
                color = Surface500,
                lineHeight = 20.sp,
            )
        }

        actions.chunked(2).forEach { rowActions ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                rowActions.forEach { action ->
                    QuickActionCard(
                        icon = action.icon,
                        title = action.title,
                        subtitle = action.subtitle,
                        modifier = Modifier.weight(1f),
                        onClick = {
                            if (action.opensAr) onOpenAr()
                            else action.screen?.let(onNavigate)
                        },
                    )
                }
                if (rowActions.size == 1) {
                    Spacer(Modifier.weight(1f))
                }
            }
        }

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(14.dp),
            colors = CardDefaults.cardColors(containerColor = Brand50),
        ) {
            Text(
                "Your products, prices, stock, projects, and notifications stay synchronized with Ferosa.",
                modifier = Modifier.padding(16.dp),
                style = MaterialTheme.typography.bodySmall,
                color = Brand700,
                lineHeight = 19.sp,
            )
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Login WebView
// ─────────────────────────────────────────────────────────────────────────────

/** Full-screen login WebView. Calls [onLoggedIn] when the server redirects to /home.
 *  Google and Facebook OAuth are opened in a Custom Tab (system browser) because both
 *  providers block OAuth inside Android WebViews. When the user returns from the Custom
 *  Tab the WebView silently loads /home; if the session was established the server
 *  serves the page and onLoggedIn fires. */
@Composable
fun LoginWebViewScreen(url: String, onLoggedIn: (String) -> Unit) {
    val context       = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    var isLoading by remember { mutableStateOf(true) }

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
    fun detectLogin(view: WebView?, url: String?, allowUrlFallback: Boolean) {
        if (loginHandled.value || view == null || url == null) return

        val reachedCustomerHome = url.endsWith("/home") || url.contains("/home?")
        val reachedAdmin = url.endsWith("/admin") || url.contains("/admin?")
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

    Box(modifier = Modifier.fillMaxSize()) {
        AndroidView(
            factory = { ctx ->
                WebView(ctx).apply {
                    val wv = this
                    CookieManager.getInstance().apply {
                        setAcceptCookie(true)
                        setAcceptThirdPartyCookies(wv, true)
                    }
                    // Login screen for our own site; see the note on the main
                    // WebView above.
                    @Suppress("SetJavaScriptEnabled")
                    settings.javaScriptEnabled = true
                    settings.domStorageEnabled = true
                    settings.useWideViewPort = true
                    settings.loadWithOverviewMode = true
                    // Same in-app marker as the main WebView, so this one also
                    // renders without the web chrome. See the note there.
                    settings.userAgentString = withFerosaAppMarker(settings.userAgentString)
                    // Warms the shared HTTP cache with the fonts and CSS the rest
                    // of the app is about to need. See the main WebView's note.
                    settings.cacheMode = android.webkit.WebSettings.LOAD_DEFAULT
                    // textZoom=100 is critical because it prevents the cursor-at-0 bug on Android.
                    settings.textZoom = 100
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

                        // Fires when the navigation commits, well before the page's
                        // images finish - the earliest point the landing URL is known.
                        override fun doUpdateVisitedHistory(
                            view: WebView?,
                            url: String?,
                            isReload: Boolean,
                        ) {
                            super.doUpdateVisitedHistory(view, url, isReload)
                            detectLogin(view, url, allowUrlFallback = false)
                        }

                        override fun onPageFinished(view: WebView?, url: String?) {
                            isLoading = false
                            // Backstop: if the meta tag was still unparsed on every
                            // earlier attempt, settle for the URL-derived role now.
                            detectLogin(view, url, allowUrlFallback = true)
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
            visible  = isLoading,
            enter    = fadeIn(),
            exit     = fadeOut(),
            modifier = Modifier.align(Alignment.TopCenter)
        ) {
            LinearProgressIndicator(
                modifier   = Modifier.fillMaxWidth(),
                color      = Brand600,
                trackColor = Brand50
            )
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Native Home Screen
// ─────────────────────────────────────────────────────────────────────────────

@Composable
fun HomeScreen(
    modifier: Modifier = Modifier,
    onOpenAr: () -> Unit = {},
    onOpenShop: () -> Unit = {},
    onOpenOrders: () -> Unit = {},
    onOpenBook: () -> Unit = {},
    onOpenEstimatorWebView: () -> Unit = {},
    onOpenMessages: () -> Unit = {},
    onOpenNotifications: () -> Unit = {},
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .background(Surface50)
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 20.dp, vertical = 16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // Header
        Column(modifier = Modifier.padding(top = 8.dp)) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Box(
                        modifier = Modifier
                            .size(36.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .background(Brand600),
                        contentAlignment = Alignment.Center
                    ) {
                        Text("F", color = Color.White, fontWeight = FontWeight.Bold, fontSize = 18.sp)
                    }
                    Column {
                        Text(
                            "Ferosa",
                            style = MaterialTheme.typography.titleMedium,
                            color = Surface900,
                            fontWeight = FontWeight.Bold,
                        )
                        Text(
                            "Garden companion",
                            style = MaterialTheme.typography.bodySmall,
                            color = Surface400
                        )
                    }
                }
                // 44dp circles inside 48dp targets: Android's minimum touch size
                // is 48dp, and these were 38dp — small enough to miss regularly.
                Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    HeaderIconButton(
                        icon = Icons.AutoMirrored.Filled.Chat,
                        contentDescription = "Messages",
                        onClick = onOpenMessages,
                    )
                    HeaderIconButton(
                        icon = Icons.Default.Notifications,
                        contentDescription = "Notifications",
                        onClick = onOpenNotifications,
                    )
                }
            }
        }

        Column(modifier = Modifier.padding(top = 4.dp, bottom = 2.dp)) {
            // Matches the greeting the web home page shows, rather than always
            // claiming it is morning.
            val greeting = remember {
                when (java.util.Calendar.getInstance().get(java.util.Calendar.HOUR_OF_DAY)) {
                    in 0..11 -> "GOOD MORNING"
                    in 12..17 -> "GOOD AFTERNOON"
                    else -> "GOOD EVENING"
                }
            }
            Text(
                greeting,
                style = MaterialTheme.typography.labelSmall,
                color = Brand600,
                fontWeight = FontWeight.Bold,
                letterSpacing = 1.2.sp,
            )
            Spacer(Modifier.height(5.dp))
            Text(
                "Your outdoor space,\nmade simple.",
                style = MaterialTheme.typography.headlineMedium,
                color = Surface900,
                fontWeight = FontWeight.Bold,
                lineHeight = 34.sp,
            )
            Spacer(Modifier.height(7.dp))
            Text(
                "Plan, shop, book, and preview your garden from one Android app.",
                style = MaterialTheme.typography.bodyMedium,
                color = Surface500,
                lineHeight = 21.sp,
            )
        }

        // AR Hero Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(18.dp),
            colors = CardDefaults.cardColors(containerColor = Surface900),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            Column(
                modifier = Modifier
                    .background(
                        Brush.linearGradient(
                            listOf(Surface900, Color(0xFF0F3D24), Brand700)
                        )
                    )
                    .padding(22.dp)
            ) {
                Surface(
                    color = Color.White.copy(alpha = 0.12f),
                    shape = RoundedCornerShape(999.dp)
                ) {
                    Text(
                        "CAMERA AR PREVIEW",
                        color = Color.White,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp)
                    )
                }
                Spacer(Modifier.height(14.dp))
                Text(
                    "See it before you plant it",
                    style = MaterialTheme.typography.headlineMedium,
                    color = Color.White
                )
                Spacer(Modifier.height(8.dp))
                Text(
                    "Place real 3D products in your space and explore the result from every angle.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = Color(0xDDF4F4F5),
                    lineHeight = 20.sp
                )
                Spacer(Modifier.height(20.dp))
                Button(
                    onClick = onOpenAr,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color.White,
                        contentColor = Brand700
                    ),
                    contentPadding = PaddingValues(vertical = 14.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.ViewInAr,
                        contentDescription = null,
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(Modifier.width(8.dp))
                    Text("Launch AR camera", fontWeight = FontWeight.Bold)
                }
            }
        }

        // Quick Actions
        Text(
            "Start here",
            style = MaterialTheme.typography.titleMedium,
            color = Surface900,
            modifier = Modifier.padding(top = 4.dp)
        )

        Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
            QuickActionCard(
                icon = Icons.Default.ShoppingCart,
                title = "Shop",
                subtitle = "Browse products",
                modifier = Modifier.weight(1f),
                onClick = onOpenShop
            )
            QuickActionCard(
                icon = Icons.Default.Receipt,
                title = "Orders",
                subtitle = "Track & history",
                modifier = Modifier.weight(1f),
                onClick = onOpenOrders
            )
        }

        Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
            QuickActionCard(
                icon = Icons.Default.CalendarMonth,
                title = "Book Service",
                subtitle = "Schedule a visit",
                modifier = Modifier.weight(1f),
                onClick = onOpenBook
            )
            QuickActionCard(
                icon = Icons.Default.Calculate,
                title = "Estimator",
                subtitle = "Get a cost estimate",
                modifier = Modifier.weight(1f),
                onClick = onOpenEstimatorWebView
            )
        }

        Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
            QuickActionCard(
                icon = Icons.Default.ViewInAr,
                title = "AR Mode",
                subtitle = "Camera view",
                modifier = Modifier.weight(1f),
                onClick = onOpenAr
            )
            QuickActionCard(
                icon = Icons.AutoMirrored.Filled.Chat,
                title = "Messages",
                subtitle = "Chat with support",
                modifier = Modifier.weight(1f),
                onClick = onOpenMessages
            )
        }

        // What You Can Do
        Text(
            "Made for your garden",
            style = MaterialTheme.typography.titleMedium,
            color = Surface900,
            modifier = Modifier.padding(top = 4.dp)
        )

        FeatureCard(
            title = "Preview products in your space",
            description = "Tap any surface to place landscaping elements — plants, patios, water features — right in your yard.",
            accent = Brand600
        )
        FeatureCard(
            title = "Shop and track in one place",
            description = "Browse our catalogue, add items to cart, and checkout. Track your orders anytime from the Orders tab.",
            accent = Brand700
        )
        FeatureCard(
            title = "Plan before you book",
            description = "Use the estimator to plan your budget, then book a landscaping service appointment at your preferred time.",
            accent = Surface700
        )

        // Footer
        Text(
            "Ferosa for Android",
            style = MaterialTheme.typography.labelSmall,
            color = Surface300,
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 12.dp),
            textAlign = TextAlign.Center
        )
    }
}

@Composable
private fun FeatureCard(title: String, description: String, accent: Color) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            horizontalArrangement = Arrangement.spacedBy(14.dp),
            verticalAlignment = Alignment.Top
        ) {
            Box(
                modifier = Modifier
                    .size(8.dp)
                    .offset(y = 6.dp)
                    .clip(CircleShape)
                    .background(accent)
            )
            Column {
                Text(title, style = MaterialTheme.typography.titleMedium, color = Surface900)
                Spacer(Modifier.height(4.dp))
                Text(description, style = MaterialTheme.typography.bodySmall, color = Surface500)
            }
        }
    }
}

@Composable
private fun QuickActionCard(
    icon: ImageVector,
    title: String,
    subtitle: String,
    modifier: Modifier = Modifier,
    onClick: () -> Unit
) {
    Card(
        modifier = modifier
            .border(1.dp, Surface100, RoundedCornerShape(14.dp))
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Box(
                modifier = Modifier
                    .size(36.dp)
                    .clip(RoundedCornerShape(10.dp))
                    .background(Brand50),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    tint = Brand600,
                    modifier = Modifier.size(20.dp)
                )
            }
            Spacer(Modifier.height(10.dp))
            Text(title, style = MaterialTheme.typography.titleSmall, color = Surface900, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(2.dp))
            Text(subtitle, style = MaterialTheme.typography.bodySmall, color = Surface400)
        }
    }
}
