package com.example.ferosa_landscaping

import android.Manifest
import android.content.Intent
import android.os.Build
import android.os.Bundle
import android.webkit.CookieManager
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Calculate
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Collections
import androidx.compose.material.icons.filled.Dashboard
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.MoreHoriz
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.MutableState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.hapticfeedback.HapticFeedbackType
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalHapticFeedback
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.net.toUri
import androidx.core.splashscreen.SplashScreen.Companion.installSplashScreen
import androidx.core.view.WindowCompat
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.ferosa_landscaping.data.api.ApiClient
import com.example.ferosa_landscaping.data.repository.SummaryRepository
import com.example.ferosa_landscaping.notifications.FerosaNotifications
import com.example.ferosa_landscaping.notifications.SummaryPollWorker
import com.example.ferosa_landscaping.ui.auth.LoginWebViewScreen
import com.example.ferosa_landscaping.ui.components.formatBadgeCount
import com.example.ferosa_landscaping.ui.navigation.AppScreen
import com.example.ferosa_landscaping.ui.shell.AppContent
import com.example.ferosa_landscaping.ui.shell.ArLaunchRequest
import com.example.ferosa_landscaping.ui.summary.SummaryViewModel
import com.example.ferosa_landscaping.ui.theme.Brand50
import com.example.ferosa_landscaping.ui.theme.Brand600
import com.example.ferosa_landscaping.ui.theme.Ferosa_landscapingTheme
import com.example.ferosa_landscaping.ui.theme.Surface50

class MainActivity : ComponentActivity() {

    /**
     * Screen a notification asked for, handed to the composable to consume.
     * Held on the activity because it can arrive through [onNewIntent] long
     * after composition.
     */
    private val pendingTargetScreen = mutableStateOf<AppScreen?>(null)

    override fun onCreate(savedInstanceState: Bundle?) {
        // Must run before super.onCreate() so the splash theme is installed for
        // the very first frame; it hands over to Theme.Ferosa_landscaping after.
        installSplashScreen()
        super.onCreate(savedInstanceState)
        // Keep decor fitting system windows so that adjustResize (set in AndroidManifest)
        // correctly shrinks the WebView when the soft keyboard appears.
        // Do NOT call enableEdgeToEdge() here because it overrides adjustResize on API 30+.
        WindowCompat.setDecorFitsSystemWindows(window, true)

        FerosaNotifications.ensureChannels(this)
        pendingTargetScreen.value = intent.targetScreen()

        setContent {
            Ferosa_landscapingTheme {
                FerosaApp(
                    pendingTargetScreen = pendingTargetScreen,
                    onOpenAr = { request ->
                        startActivity(
                            Intent(this@MainActivity, ArActivity::class.java).apply {
                                data = request.toDeepLink().toUri()
                            }
                        )
                    },
                )
            }
        }
    }

    /** The activity is singleTop, so a notification tap lands here, not in onCreate. */
    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        pendingTargetScreen.value = intent.targetScreen()
    }

    private fun Intent.targetScreen(): AppScreen? =
        getStringExtra(FerosaNotifications.EXTRA_TARGET_SCREEN)
            ?.let { name -> AppScreen.entries.firstOrNull { it.name == name } }
}

@Composable
private fun FerosaApp(
    pendingTargetScreen: MutableState<AppScreen?>,
    onOpenAr: (ArLaunchRequest) -> Unit,
) {
    val context = LocalContext.current

    var isLoggedIn by remember { mutableStateOf(false) }
    var userRole by remember { mutableStateOf("user") }
    var currentScreen by remember { mutableStateOf(AppScreen.HOME) }

    val summaryViewModel: SummaryViewModel = viewModel(
        factory = SummaryViewModel.Factory(SummaryRepository(ApiClient.service))
    )
    val summary by summaryViewModel.summary.collectAsStateWithLifecycle()
    val sessionExpired by summaryViewModel.sessionExpired.collectAsStateWithLifecycle()

    // Asked for once, right after sign-in, when there is finally something
    // worth notifying about. Declining costs the user nothing else.
    val notificationPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission(),
    ) { /* Either answer is fine; the poll still keeps the badges current. */ }

    fun signOut() {
        summaryViewModel.clear()
        SummaryPollWorker.cancel(context)
        isLoggedIn = false
        userRole = "user"
        currentScreen = AppScreen.HOME
    }

    // A rejected session shows up as a 401 on the summary poll before the user
    // notices anything, so return them to the login screen rather than leaving
    // badges that will never change again.
    LaunchedEffect(sessionExpired) {
        if (sessionExpired) signOut()
    }

    if (!isLoggedIn) {
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

                summaryViewModel.refresh(force = true)
                SummaryPollWorker.enqueue(context)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
                    !FerosaNotifications.canPost(context)
                ) {
                    notificationPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
                }
            }
        )
        return
    }

    // Consume a notification tap once signed in.
    LaunchedEffect(pendingTargetScreen.value, isLoggedIn) {
        val target = pendingTargetScreen.value ?: return@LaunchedEffect
        currentScreen = target
        pendingTargetScreen.value = null
    }

    // Badges have to be right the moment a tab is opened, not a poll later.
    LaunchedEffect(currentScreen) { summaryViewModel.refresh() }

    Scaffold(
        modifier = Modifier.fillMaxSize(),
        containerColor = Surface50,
        bottomBar = {
            FerosaBottomNavigation(
                currentScreen = currentScreen,
                userRole = userRole,
                cartCount = summary.cartCount,
                unreadCount = summary.unreadMessages + summary.unreadNotifications,
                onNavigate = { currentScreen = it },
            )
        }
    ) { innerPadding ->
        AppContent(
            modifier = Modifier.padding(innerPadding),
            currentScreen = currentScreen,
            userRole = userRole,
            summary = summary,
            onNavigate = { currentScreen = it },
            onLoggedOut = ::signOut,
            onOpenAr = onOpenAr,
            onRefreshSummary = { summaryViewModel.refresh() },
        )
    }
}

private data class BottomDestination(
    val screen: AppScreen,
    val label: String,
    val icon: ImageVector,
    val badgeCount: Int = 0,
    /** What the badge counts, so TalkBack reads "Shop, 3 items in cart". */
    val badgeNoun: String = "",
)

@Composable
private fun FerosaBottomNavigation(
    currentScreen: AppScreen,
    userRole: String,
    cartCount: Int,
    unreadCount: Int,
    onNavigate: (AppScreen) -> Unit,
) {
    val isStaffWorkspace = userRole == "admin" || userRole == "staff"
    val destinations = if (isStaffWorkspace) {
        listOf(
            BottomDestination(AppScreen.ADMIN_DASHBOARD, "Dashboard", Icons.Default.Dashboard),
            BottomDestination(AppScreen.ADMIN_PROJECTS, "Portfolio", Icons.Default.Collections),
            BottomDestination(AppScreen.ADMIN_INVENTORY, "Inventory", Icons.Default.Inventory2),
            BottomDestination(AppScreen.ADMIN_MESSAGES, "Messages", Icons.AutoMirrored.Filled.Chat),
            BottomDestination(AppScreen.MORE, "More", Icons.Default.MoreHoriz, unreadCount, "unread"),
        )
    } else {
        // Estimator sits between Shop and Book because that is the order it is
        // used in: price a project, then book the visit (its own primary button
        // is "Book consultation").
        //
        // There is deliberately no Cart tab. /checkout is the cart *and* the
        // delivery and payment form, which is not something to put behind a
        // permanent nav slot, and /shop already floats a cart button as soon as
        // there is anything in it. What was worth keeping is the count, so it
        // rides on Shop as a badge - hence the cart icon rather than a bag.
        listOf(
            BottomDestination(AppScreen.HOME, "Home", Icons.Default.Home),
            BottomDestination(
                AppScreen.SHOP,
                "Shop",
                Icons.Default.ShoppingCart,
                cartCount,
                "items in cart",
            ),
            BottomDestination(AppScreen.ESTIMATOR, "Estimator", Icons.Default.Calculate),
            BottomDestination(AppScreen.BOOK, "Book", Icons.Default.CalendarMonth),
            BottomDestination(AppScreen.MORE, "More", Icons.Default.MoreHoriz, unreadCount, "unread"),
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
            // Estimator is NOT listed: it has its own tab now, and leaving it
            // here would light up both that tab and More at the same time.
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
            val label = if (destination.badgeCount > 0) {
                "${destination.label}, ${destination.badgeCount} ${destination.badgeNoun}".trim()
            } else {
                destination.label
            }
            NavigationBarItem(
                icon = {
                    BadgedBox(
                        badge = {
                            if (destination.badgeCount > 0) {
                                Badge(containerColor = Brand600, contentColor = Color.White) {
                                    Text(formatBadgeCount(destination.badgeCount))
                                }
                            }
                        }
                    ) {
                        Icon(destination.icon, contentDescription = label)
                    }
                },
                label = {
                    Text(
                        text = destination.label,
                        fontSize = 11.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                },
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
