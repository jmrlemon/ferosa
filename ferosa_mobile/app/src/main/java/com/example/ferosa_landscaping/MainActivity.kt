package com.example.ferosa_landscaping

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.webkit.CookieManager
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.browser.customtabs.CustomTabsIntent
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.compose.ui.platform.LocalContext
import androidx.compose.runtime.DisposableEffect
import androidx.lifecycle.compose.LocalLifecycleOwner
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.core.view.WindowCompat
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountCircle
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Calculate
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.ViewInAr
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import com.example.ferosa_landscaping.ui.theme.*

enum class AppScreen { HOME, SHOP, ORDERS, BOOK, ACCOUNT, ESTIMATOR, FEEDBACK }

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        // Keep decor fitting system windows so that adjustResize (set in AndroidManifest)
        // correctly shrinks the WebView when the soft keyboard appears.
        // Do NOT call enableEdgeToEdge() here — it overrides adjustResize on API 30+.
        WindowCompat.setDecorFitsSystemWindows(window, true)
        setContent {
            Ferosa_landscapingTheme(darkTheme = false) {
                var isLoggedIn by remember { mutableStateOf(false) }
                var currentScreen by remember { mutableStateOf(AppScreen.HOME) }

                if (!isLoggedIn) {
                    // Full-screen login WebView
                    LoginWebViewScreen(
                        url = "$SERVER_URL/login",
                        onLoggedIn = {
                            // Flush cookies so the persistent WebView can use the session
                            CookieManager.getInstance().flush()
                            isLoggedIn = true
                        }
                    )
                } else {
                    Scaffold(
                        modifier = Modifier.fillMaxSize(),
                        containerColor = Surface50,
                        bottomBar = {
                            NavigationBar(
                                containerColor = Color.White,
                                tonalElevation = 0.dp
                            ) {
                                NavigationBarItem(
                                    icon = { Icon(Icons.Default.Home, contentDescription = "Home") },
                                    label = { BottomNavLabel("Home") },
                                    selected = currentScreen == AppScreen.HOME,
                                    onClick = { currentScreen = AppScreen.HOME },
                                    colors = NavigationBarItemDefaults.colors(
                                        selectedIconColor = Brand600,
                                        selectedTextColor = Brand600,
                                        indicatorColor = Brand50
                                    )
                                )
                                NavigationBarItem(
                                    icon = { Icon(Icons.Default.ShoppingCart, contentDescription = "Shop") },
                                    label = { BottomNavLabel("Shop") },
                                    selected = currentScreen == AppScreen.SHOP,
                                    onClick = { currentScreen = AppScreen.SHOP },
                                    colors = NavigationBarItemDefaults.colors(
                                        selectedIconColor = Brand600,
                                        selectedTextColor = Brand600,
                                        indicatorColor = Brand50
                                    )
                                )
                                NavigationBarItem(
                                    icon = { Icon(Icons.Default.Receipt, contentDescription = "Orders") },
                                    label = { BottomNavLabel("Orders") },
                                    selected = currentScreen == AppScreen.ORDERS,
                                    onClick = { currentScreen = AppScreen.ORDERS },
                                    colors = NavigationBarItemDefaults.colors(
                                        selectedIconColor = Brand600,
                                        selectedTextColor = Brand600,
                                        indicatorColor = Brand50
                                    )
                                )
                                NavigationBarItem(
                                    icon = { Icon(Icons.Default.CalendarMonth, contentDescription = "Book") },
                                    label = { BottomNavLabel("Book") },
                                    selected = currentScreen == AppScreen.BOOK,
                                    onClick = { currentScreen = AppScreen.BOOK },
                                    colors = NavigationBarItemDefaults.colors(
                                        selectedIconColor = Brand600,
                                        selectedTextColor = Brand600,
                                        indicatorColor = Brand50
                                    )
                                )
                                NavigationBarItem(
                                    icon = { Icon(Icons.Default.AccountCircle, contentDescription = "Account") },
                                    label = { BottomNavLabel("Acct") },
                                    selected = currentScreen == AppScreen.ACCOUNT,
                                    onClick = { currentScreen = AppScreen.ACCOUNT },
                                    colors = NavigationBarItemDefaults.colors(
                                        selectedIconColor = Brand600,
                                        selectedTextColor = Brand600,
                                        indicatorColor = Brand50
                                    )
                                )
                                NavigationBarItem(
                                    icon = { Icon(Icons.Default.Calculate, contentDescription = "Estimator") },
                                    label = { BottomNavLabel("Est.") },
                                    selected = currentScreen == AppScreen.ESTIMATOR,
                                    onClick = { currentScreen = AppScreen.ESTIMATOR },
                                    colors = NavigationBarItemDefaults.colors(
                                        selectedIconColor = Brand600,
                                        selectedTextColor = Brand600,
                                        indicatorColor = Brand50
                                    )
                                )
                                NavigationBarItem(
                                    icon = { Icon(Icons.Default.Star, contentDescription = "Feedback") },
                                    label = { BottomNavLabel("Feed") },
                                    selected = currentScreen == AppScreen.FEEDBACK,
                                    onClick = { currentScreen = AppScreen.FEEDBACK },
                                    colors = NavigationBarItemDefaults.colors(
                                        selectedIconColor = Brand600,
                                        selectedTextColor = Brand600,
                                        indicatorColor = Brand50
                                    )
                                )
                            }
                        }
                    ) { innerPadding ->
                        AppContent(
                            modifier = Modifier.padding(innerPadding),
                            currentScreen = currentScreen,
                            onNavigate = { currentScreen = it },
                            onLoggedOut = {
                                isLoggedIn = false
                                currentScreen = AppScreen.HOME
                            },
                            onOpenAr = {
                                startActivity(
                                    Intent(this@MainActivity, ArActivity::class.java).apply {
                                        data = Uri.parse("ferosa://ar?designId=demo")
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
    onNavigate: (AppScreen) -> Unit,
    onLoggedOut: () -> Unit,
    onOpenAr: () -> Unit,
) {
    val context = LocalContext.current
    val serverHost = remember { Uri.parse(SERVER_URL).host ?: "" }
    var isLoading by remember { mutableStateOf(false) }

    // Reference to the single shared WebView
    val webViewRef = remember { mutableStateOf<WebView?>(null) }

    // Resolve the target URL whenever the screen changes
    val targetUrl = remember(currentScreen) {
        when (currentScreen) {
            AppScreen.SHOP      -> "$SERVER_URL/shop"
            AppScreen.ORDERS    -> "$SERVER_URL/orders"
            AppScreen.BOOK      -> "$SERVER_URL/schedule"
            AppScreen.ACCOUNT   -> "$SERVER_URL/account"
            AppScreen.ESTIMATOR -> "$SERVER_URL/estimator"
            AppScreen.FEEDBACK  -> "$SERVER_URL/feedback"
            AppScreen.HOME      -> null
        }
    }

    // Navigate the single WebView whenever the screen (and thus targetUrl) changes
    LaunchedEffect(currentScreen) {
        val url = targetUrl
        if (url != null) {
            isLoading = true
            webViewRef.value?.loadUrl(url)
        }
    }

    Box(modifier = modifier.fillMaxSize()) {

        // ── 1. Persistent WebView — ALWAYS in composition ───────────────────
        // Keeping it here means it is never destroyed on tab switches, so the
        // Laravel session cookie stays alive for the lifetime of the login.
        AndroidView(
            factory = { ctx ->
                WebView(ctx).apply {
                    // Accept cookies — required for Laravel session auth
                    val wv = this
                    CookieManager.getInstance().apply {
                        setAcceptCookie(true)
                        setAcceptThirdPartyCookies(wv, true)
                    }

                    settings.apply {
                        javaScriptEnabled    = true
                        domStorageEnabled    = true
                        useWideViewPort      = true
                        loadWithOverviewMode = true
                        setSupportMultipleWindows(true)  // needed for receipt "open in new tab"
                        allowFileAccess = true
                        // Zoom must be exactly 100% — any other value corrupts Android IME
                        // cursor position calculations, causing cursor-at-0 bug
                        textZoom = 100
                        @Suppress("DEPRECATION")
                        mixedContentMode = android.webkit.WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
                    }
                    // Clear cache once so fresh HTML (with our input fixes) is loaded
                    clearCache(false)

                    // Handle window.open() calls (e.g. receipt links)
                    webChromeClient = object : WebChromeClient() {
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
                            val uri  = Uri.parse(dest)

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
                            isLoading = false
                            // Server redirected to /login → session expired, log out
                            if (url != null &&
                                (url.endsWith("/login") || url.contains("/login?"))
                            ) {
                                onLoggedOut()
                            }
                            // Inject cursor fix: Android IME resets selectionStart to 0
                            // after each keystroke on text inputs. This JS detects that
                            // and immediately moves the cursor back to the end.
                            view?.evaluateJavascript("""
                                (function(){
                                  // Mark page as running inside Android app—hides web mobile header
                                  document.body && document.body.classList.add('in-app');
                                  var patched = new WeakSet();
                                  function patch(el) {
                                    if (patched.has(el)) return;
                                    patched.add(el);
                                    el.setAttribute('dir','ltr');
                                    el.setAttribute('autocorrect','off');
                                    el.setAttribute('spellcheck','false');
                                    el.addEventListener('input', function(){
                                      var end = this.value.length;
                                      if (this.selectionStart === 0 && end > 0) {
                                        try { this.setSelectionRange(end, end); } catch(e){}
                                      }
                                    }, true);
                                  }
                                  document.querySelectorAll('input:not([type=password])').forEach(patch);
                                  new MutationObserver(function(ms){
                                    ms.forEach(function(m){
                                      m.addedNodes.forEach(function(n){
                                        if(n.tagName==='INPUT') patch(n);
                                        if(n.querySelectorAll) n.querySelectorAll('input:not([type=password])').forEach(patch);
                                      });
                                    });
                                  }).observe(document.body||document.documentElement,{childList:true,subtree:true});
                                })();
                            """, null)
                        }

                        @Suppress("OVERRIDE_DEPRECATION")
                        override fun onReceivedError(
                            view: WebView?,
                            errorCode: Int,
                            description: String?,
                            failingUrl: String?
                        ) {
                            isLoading = false
                        }
                    }
                    // No initial URL — LaunchedEffect drives navigation
                }.also { webViewRef.value = it }
            },
            modifier = Modifier.fillMaxSize()
        )

        // ── 2. Native HomeScreen overlay ────────────────────────────────────
        // Drawn on top of the WebView when the HOME tab is active.
        // When the user switches to another tab, this is removed and the WebView
        // (which was always there) becomes fully visible.
        if (currentScreen == AppScreen.HOME) {
            HomeScreen(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Surface50),
                onOpenAr = onOpenAr,
                onOpenShop             = { onNavigate(AppScreen.SHOP) },
                onOpenOrders           = { onNavigate(AppScreen.ORDERS) },
                onOpenBook             = { onNavigate(AppScreen.BOOK) },
                onOpenEstimatorWebView = { onNavigate(AppScreen.ESTIMATOR) },
                onOpenFeedback         = { onNavigate(AppScreen.FEEDBACK) }
            )
        }

        // ── 3. Top loading bar ──────────────────────────────────────────────
        AnimatedVisibility(
            visible  = isLoading && currentScreen != AppScreen.HOME,
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

@Composable
private fun BottomNavLabel(text: String) {
    Text(
        text = text,
        fontSize = 9.sp,
        maxLines = 1,
        overflow = TextOverflow.Ellipsis
    )
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
fun LoginWebViewScreen(url: String, onLoggedIn: () -> Unit) {
    val context       = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    var isLoading by remember { mutableStateOf(true) }

    val awaitingOAuthReturn = remember { mutableStateOf(false) }
    val webViewRef = remember { mutableStateOf<WebView?>(null) }

    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME && awaitingOAuthReturn.value) {
                awaitingOAuthReturn.value = false
                webViewRef.value?.loadUrl("$SERVER_URL/home")
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
                    settings.javaScriptEnabled = true
                    settings.domStorageEnabled = true
                    settings.useWideViewPort = true
                    settings.loadWithOverviewMode = true
                    // textZoom=100 is critical — prevents IME cursor-at-0 bug on Android
                    settings.textZoom = 100
                    clearCache(false)
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
                                    .launchUrl(ctx, Uri.parse(dest))
                                return true
                            }
                            return false
                        }

                        override fun onPageFinished(view: WebView?, url: String?) {
                            isLoading = false
                            if (url != null && (url.endsWith("/home") || url.contains("/home?"))) {
                                onLoggedIn()
                            }
                            // Same cursor fix injected into the login WebView
                            view?.evaluateJavascript("""
                                (function(){
                                  var patched = new WeakSet();
                                  function patch(el) {
                                    if (patched.has(el)) return;
                                    patched.add(el);
                                    el.setAttribute('dir','ltr');
                                    el.setAttribute('autocorrect','off');
                                    el.setAttribute('spellcheck','false');
                                    el.addEventListener('input', function(){
                                      var end = this.value.length;
                                      if (this.selectionStart === 0 && end > 0) {
                                        try { this.setSelectionRange(end, end); } catch(e){}
                                      }
                                    }, true);
                                  }
                                  document.querySelectorAll('input:not([type=password])').forEach(patch);
                                  new MutationObserver(function(ms){
                                    ms.forEach(function(m){
                                      m.addedNodes.forEach(function(n){
                                        if(n.tagName==='INPUT') patch(n);
                                        if(n.querySelectorAll) n.querySelectorAll('input:not([type=password])').forEach(patch);
                                      });
                                    });
                                  }).observe(document.body||document.documentElement,{childList:true,subtree:true});
                                })();
                            """, null)
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
    onOpenFeedback: () -> Unit = {},
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 20.dp, vertical = 16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // Header
        Column(modifier = Modifier.padding(top = 8.dp)) {
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
                        style = MaterialTheme.typography.titleLarge,
                        color = Surface900
                    )
                    Text(
                        "Landscaping & Garden",
                        style = MaterialTheme.typography.bodySmall,
                        color = Surface400
                    )
                }
            }
        }

        // AR Hero Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = Surface900)
        ) {
            Column(modifier = Modifier.padding(24.dp)) {
                Text(
                    "Visualize Your Dream Garden",
                    style = MaterialTheme.typography.headlineMedium,
                    color = Color.White
                )
                Spacer(Modifier.height(8.dp))
                Text(
                    "Use augmented reality to see how your landscaping design looks in your actual space — before any work begins.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = Surface400
                )
                Spacer(Modifier.height(20.dp))
                Button(
                    onClick = onOpenAr,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Brand600,
                        contentColor = Color.White
                    ),
                    contentPadding = PaddingValues(vertical = 14.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.ViewInAr,
                        contentDescription = null,
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(Modifier.width(8.dp))
                    Text("Open AR Visualizer", fontWeight = FontWeight.SemiBold)
                }
            }
        }

        // Quick Actions
        Text(
            "Quick Actions",
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
                icon = Icons.Default.Star,
                title = "Feedback",
                subtitle = "Rate our service",
                modifier = Modifier.weight(1f),
                onClick = onOpenFeedback
            )
        }

        // What You Can Do
        Text(
            "What You Can Do",
            style = MaterialTheme.typography.titleMedium,
            color = Surface900,
            modifier = Modifier.padding(top = 4.dp)
        )

        FeatureCard(
            title = "Place 3D Models",
            description = "Tap any surface to place landscaping elements — plants, patios, water features — right in your yard.",
            accent = Brand600
        )
        FeatureCard(
            title = "Shop & Order Products",
            description = "Browse our catalogue, add items to cart, and checkout. Track your orders anytime from the Orders tab.",
            accent = Brand700
        )
        FeatureCard(
            title = "Book & Schedule Services",
            description = "Use the estimator to plan your budget, then book a landscaping service appointment at your preferred time.",
            accent = Surface700
        )

        // Footer
        Text(
            "Ferosa Landscaping v1.0",
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
        colors = CardDefaults.cardColors(containerColor = Color.White)
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
        modifier = modifier.clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Icon(
                imageVector = icon,
                contentDescription = null,
                tint = Brand600,
                modifier = Modifier.size(22.dp)
            )
            Spacer(Modifier.height(8.dp))
            Text(title, style = MaterialTheme.typography.titleSmall, color = Surface900, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(2.dp))
            Text(subtitle, style = MaterialTheme.typography.bodySmall, color = Surface400)
        }
    }
}
