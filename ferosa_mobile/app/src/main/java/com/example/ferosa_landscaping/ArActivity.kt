package com.example.ferosa_landscaping

import android.Manifest
import android.annotation.SuppressLint
import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.os.SystemClock
import android.provider.MediaStore
import android.provider.Settings
import android.util.Log
import android.view.GestureDetector
import android.view.MotionEvent
import android.view.PixelCopy
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.ErrorOutline
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.TouchApp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Rect
import androidx.compose.ui.layout.boundsInRoot
import androidx.compose.ui.layout.onGloballyPositioned
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import androidx.core.graphics.createBitmap
import androidx.core.net.toUri
import androidx.core.view.GestureDetectorCompat
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.compose.LocalLifecycleOwner
import com.google.android.filament.Engine
import com.example.ferosa_landscaping.data.api.ApiClient
import com.example.ferosa_landscaping.data.cache.ModelCacheManager
import com.example.ferosa_landscaping.data.repository.CartRepository
import com.example.ferosa_landscaping.data.repository.ModelRepository
import com.example.ferosa_landscaping.ui.ar.ArError
import com.example.ferosa_landscaping.ui.ar.ArProduct
import com.example.ferosa_landscaping.ui.ar.ArViewModel
import com.example.ferosa_landscaping.ui.ar.PlacedModel
import com.example.ferosa_landscaping.ui.ar.PlacementControlState
import com.example.ferosa_landscaping.ui.ar.PlacementTargetStability
import com.example.ferosa_landscaping.ui.ar.AR_EMPTY_CATALOG_TITLE
import com.example.ferosa_landscaping.ui.ar.canConfirmPlacement
import com.example.ferosa_landscaping.ui.ar.calculateGroundedModelTransform
import com.example.ferosa_landscaping.ui.ar.components.CatalogDrawer
import com.example.ferosa_landscaping.ui.ar.components.ProductInfoPanel
import com.example.ferosa_landscaping.ui.ar.crosshairCoordinates
import com.example.ferosa_landscaping.ui.ar.containsScreenHitBounds
import com.example.ferosa_landscaping.ui.ar.formatArSessionConfigLog
import com.example.ferosa_landscaping.ui.ar.isPlacementPlanePoseValid
import com.example.ferosa_landscaping.ui.ar.isPlacementTargetStable
import com.example.ferosa_landscaping.ui.ar.isPreviewRequestCurrent
import com.example.ferosa_landscaping.ui.ar.PLACEMENT_TARGET_MISS_GRACE_MILLIS
import com.example.ferosa_landscaping.ui.ar.resolveCachedModelLoaderInput
import com.example.ferosa_landscaping.ui.ar.screenHitBoundsFromPoints
import com.example.ferosa_landscaping.ui.ar.shouldShowArEmptyState
import com.example.ferosa_landscaping.ui.ar.turn180Degrees
import com.example.ferosa_landscaping.ui.ar.toggleSelectedProduct
import com.example.ferosa_landscaping.ui.ar.updatePlacementTargetStability
import com.example.ferosa_landscaping.ui.ar.validateGlbFile
import com.example.ferosa_landscaping.ui.theme.*
import com.example.ferosa_landscaping.util.ArAvailability
import com.example.ferosa_landscaping.util.ArCompatibilityChecker
import com.example.ferosa_landscaping.util.ConnectivityMonitor
import com.google.ar.core.Config
import com.google.ar.core.HitResult
import com.google.ar.core.Plane
import com.google.ar.core.Session
import com.google.ar.core.TrackingState
import io.github.sceneview.ar.ARSceneView
import io.github.sceneview.ar.arcore.transform
import io.github.sceneview.ar.node.AnchorNode
import io.github.sceneview.math.Position
import io.github.sceneview.math.Rotation
import io.github.sceneview.math.Scale
import io.github.sceneview.node.ModelNode
import io.github.sceneview.node.Node
import kotlinx.coroutines.*
import java.util.Collections
import java.util.concurrent.atomic.AtomicBoolean
import java.util.concurrent.atomic.AtomicInteger
import java.util.concurrent.atomic.AtomicReference

// ─── Screenshot helper ─────────────────────────────────────────────────────────

private fun saveBitmapToGallery(context: Context, bitmap: Bitmap): Boolean {
    val resolver = context.contentResolver
    var insertedUri: Uri? = null

    return try {
        val values = ContentValues().apply {
            put(MediaStore.Images.Media.DISPLAY_NAME, "Ferosa_AR_${System.currentTimeMillis()}.jpg")
            put(MediaStore.Images.Media.MIME_TYPE, "image/jpeg")
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                put(MediaStore.Images.Media.RELATIVE_PATH, "Pictures/Ferosa")
                put(MediaStore.Images.Media.IS_PENDING, 1)
            }
        }
        val uri = resolver.insert(
            MediaStore.Images.Media.EXTERNAL_CONTENT_URI, values
        ) ?: return false
        insertedUri = uri

        val output = resolver.openOutputStream(uri)
        if (output == null) {
            resolver.delete(uri, null, null)
            return false
        }
        val compressed = output.use { out ->
            bitmap.compress(Bitmap.CompressFormat.JPEG, 95, out)
        }
        if (!compressed) {
            resolver.delete(uri, null, null)
            return false
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            values.clear()
            values.put(MediaStore.Images.Media.IS_PENDING, 0)
            if (resolver.update(uri, values, null, null) <= 0) {
                resolver.delete(uri, null, null)
                return false
            }
        }
        true
    } catch (_: Exception) {
        insertedUri?.let { uri ->
            runCatching { resolver.delete(uri, null, null) }
        }
        false
    }
}

// ─── Activity ──────────────────────────────────────────────────────────────────

private enum class ModelPlacementStage(val progressMessage: String) {
    Idle(""),
    Downloading("Downloading 3D product..."),
    Preparing("Preparing real-size preview..."),
}

private enum class PreviewStage {
    Idle,
    Loading,
    Ready,
}

private data class PreviewHandle(
    val productId: Int,
    val hitNode: PlacementPreviewNode,
    val modelNode: ModelNode,
)

/**
 * A transient preview node deliberately does not inherit SceneView's trackable visibility policy.
 * HitResultNode hides itself whenever its plane reports PAUSED, which makes a valid preview blink
 * during normal ARCore plane refinement. PlacementTargetStability owns visibility; this node only
 * follows the last accepted hit pose and retains it briefly across transient tracking pauses.
 */
private class PlacementPreviewNode(engine: Engine) : Node(engine) {
    var hitResult: HitResult? = null
        set(value) {
            field = value
            value?.hitPose?.let { worldTransform(it.transform) }
        }

    init {
        isSmoothTransformEnabled = true
    }
}

private fun customerReadableModelError(exception: Throwable): String {
    val message = exception.message.orEmpty()
    return when {
        "no network" in message.lowercase() || "offline" in message.lowercase() ->
            "This model is not available offline. Connect to the internet once, then try again."
        "authentication" in message.lowercase() ->
            "Your sign-in session expired. Sign in again, then try this item."
        exception is IllegalArgumentException && message.isNotBlank() -> message
        else -> "This 3D model could not be opened for AR. Try another item."
    }
}

class ArActivity : ComponentActivity() {

    /** Coroutine scope for session cleanup operations. */
    private val cleanupScope = CoroutineScope(Dispatchers.Main + SupervisorJob())

    /** Reference to the AR scene view for cleanup. */
    private var sceneViewInstance: ARSceneView? = null

    /** Whether cleanup has been initiated. */
    private var cleanupStarted = false

    /** The ViewModel (retained for cleanup access to placed models). */
    private var arViewModel: ArViewModel? = null

    /** True after ARCore install has already been requested once for this launch. */
    private var arCoreInstallRequested = false

    /** Prevents launching the AR screen more than once across install/resume checks. */
    private var arScreenLaunched = false

    /** Tracks a round-trip to app settings after camera permission was denied. */
    private var cameraSettingsOpened = false

    // Request camera permission at runtime (required Android 6+)
    private val cameraPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) {
            validateArSessionAndLaunch()
        } else {
            showPermissionDenied()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        showArChecking()

        // Handle back navigation with session cleanup
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                performSessionCleanup()
                isEnabled = false
                onBackPressedDispatcher.onBackPressed()
            }
        })

        checkArAvailabilityAndLaunch()
    }

    override fun onResume() {
        super.onResume()
        if (cameraSettingsOpened) {
            cameraSettingsOpened = false
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) ==
                PackageManager.PERMISSION_GRANTED
            ) {
                showArChecking()
                checkArAvailabilityAndLaunch()
            }
        } else if (arCoreInstallRequested && !arScreenLaunched) {
            checkArAvailabilityAndLaunch()
        }
    }

    override fun onDestroy() {
        // finish() can follow performSessionCleanup() before its coroutine gets a
        // chance to run. Release synchronously here, then cancel every pending AR
        // compatibility/session callback so none can call setContent after destroy.
        runCatching { releaseArResources() }
        cleanupScope.cancel()
        super.onDestroy()
    }

    private fun isActivityAlive(): Boolean = !isFinishing && !isDestroyed

    /**
     * Performs AR session cleanup with timeout guarantees:
     * - Normal cleanup within 2 seconds
     * - Force-release all resources after 5 seconds
     */
    private fun performSessionCleanup() {
        if (cleanupStarted) return
        cleanupStarted = true

        cleanupScope.launch {
            // Race: normal cleanup vs force-release timeout
            val cleanupJob = launch {
                try {
                    releaseArResources()
                } catch (_: Exception) {
                    // Swallow errors during cleanup
                }
            }

            // Force-release after 5 seconds if normal cleanup hangs
            launch {
                delay(5000L)
                if (cleanupJob.isActive) {
                    cleanupJob.cancel()
                    forceReleaseAllResources()
                }
            }

            // Wait up to 2 seconds for graceful cleanup
            withTimeoutOrNull(2000L) {
                cleanupJob.join()
            }

            // If still not done after 2s, let the 5s force-release handle it
        }
    }

    /**
     * Gracefully releases ARCore session, camera, and all anchors.
     */
    private fun releaseArResources() {
        val viewModel = arViewModel
        val sv = sceneViewInstance

        // Detach all anchors from placed models
        viewModel?.placedModels?.value?.forEach { placed ->
            try {
                placed.anchorNode.anchor.detach()
            } catch (_: Exception) { /* already detached */ }
            placed.originalAnchor
                ?.takeUnless { it === placed.anchorNode }
                ?.let { originalAnchor ->
                    try {
                        originalAnchor.anchor.detach()
                    } catch (_: Exception) { /* already detached */ }
                }
        }
        viewModel?.clearSceneState()

        // Destroy the scene view (releases ARCore session and camera)
        try {
            sv?.destroy()
        } catch (_: Exception) { /* best effort */ }

        sceneViewInstance = null
    }

    /**
     * Force-releases all resources without waiting for graceful shutdown.
     */
    private fun forceReleaseAllResources() {
        arViewModel?.placedModels?.value?.forEach { placed ->
            runCatching { placed.anchorNode.anchor.detach() }
            placed.originalAnchor
                ?.takeUnless { it === placed.anchorNode }
                ?.let { originalAnchor -> runCatching { originalAnchor.anchor.detach() } }
        }
        arViewModel?.clearSceneState()
        try {
            sceneViewInstance?.destroy()
        } catch (_: Exception) { /* force release */ }
        sceneViewInstance = null
    }

    private fun launchArScreen() {
        if (!isActivityAlive() || arScreenLaunched) return
        arScreenLaunched = true

        val data: Uri?   = intent?.data
        val projectType  = data?.getQueryParameter("type") ?: "design"
        val propertySize = data?.getQueryParameter("size") ?: "100"
        val estimatedCost = data?.getQueryParameter("cost")
        val initialProductId = data?.getQueryParameter("productId")?.toIntOrNull()

        // Create ViewModel dependencies manually
        val apiService = ApiClient.service
        val cacheManager = ModelCacheManager(applicationContext)
        val connectivityMonitor = ConnectivityMonitor(applicationContext)
        val modelRepository = ModelRepository(apiService, cacheManager, connectivityMonitor)
        val cartRepository = CartRepository(apiService)
        val viewModel = ViewModelProvider(
            this,
            object : ViewModelProvider.Factory {
                override fun <T : ViewModel> create(modelClass: Class<T>): T {
                    require(modelClass.isAssignableFrom(ArViewModel::class.java)) {
                        "Unsupported ViewModel: ${modelClass.name}"
                    }
                    @Suppress("UNCHECKED_CAST")
                    return ArViewModel(
                        apiService = apiService,
                        modelRepository = modelRepository,
                        cartRepository = cartRepository,
                        cacheManager = cacheManager,
                        connectivityMonitor = connectivityMonitor
                    ) as T
                }
            }
        )[ArViewModel::class.java]
        arViewModel = viewModel

        setContent {
            Ferosa_landscapingTheme(darkTheme = false) {
                ArScreen(
                    viewModel        = viewModel,
                    modelRepository  = viewModel.modelRepository,
                    projectType      = projectType,
                    propertySize     = propertySize,
                    estimatedCost    = estimatedCost,
                    initialProductId = initialProductId,
                    onExit           = { performSessionCleanup(); finish() },
                    onBackToWeb      = {
                        performSessionCleanup()
                        finish()
                    },
                    onSceneViewReady = { sv -> sceneViewInstance = sv }
                )
            }
        }
    }

    private fun checkArAvailabilityAndLaunch() {
        val checker = ArCompatibilityChecker()
        cleanupScope.launch {
            val availability = checker.checkAvailability(this@ArActivity)
            if (!isActivityAlive()) return@launch
            when (availability) {
                is ArAvailability.Supported -> {
                    if (ContextCompat.checkSelfPermission(
                            this@ArActivity, Manifest.permission.CAMERA
                        ) == PackageManager.PERMISSION_GRANTED
                    ) {
                        validateArSessionAndLaunch()
                    } else {
                        cameraPermissionLauncher.launch(Manifest.permission.CAMERA)
                    }
                }
                is ArAvailability.SupportedNotInstalled -> {
                    try {
                        val installRequested = checker.requestInstall(
                            this@ArActivity,
                            !arCoreInstallRequested
                        )
                        if (!isActivityAlive()) return@launch
                        arCoreInstallRequested = true
                        if (installRequested) {
                            showArInstallRequired()
                        } else {
                            checkArAvailabilityAndLaunch()
                        }
                    } catch (_: Exception) {
                        showArUnsupported("ARCore services could not be installed.")
                    }
                }
                is ArAvailability.Unsupported -> {
                    showArUnsupported("This device does not support AR experiences.")
                }
                is ArAvailability.CheckFailed -> {
                    showArUnsupported(availability.reason)
                }
            }
        }
    }

    private fun validateArSessionAndLaunch() {
        cleanupScope.launch {
            val error = ArCompatibilityChecker().validateSessionCreation(this@ArActivity)
            if (!isActivityAlive()) return@launch
            if (error == null) {
                launchArScreen()
            } else {
                showArUnsupported(
                    "This phone cannot start AR camera tracking. ${friendlyArCoreError(error)}"
                )
            }
        }
    }

    private fun friendlyArCoreError(error: String): String {
        val lower = error.lowercase()
        return when {
            "unsupported" in lower || "not supported" in lower ->
                "The device is not certified for Google Play Services for AR."
            "camera" in lower ->
                "Close other apps using the camera, then try again."
            "install" in lower || "arcore" in lower ->
                "Install or update Google Play Services for AR, then try again."
            else ->
                "Install or update Google Play Services for AR, then try again."
        }
    }

    private fun showArChecking() {
        setContent {
            Ferosa_landscapingTheme(darkTheme = false) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(Brand900),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.padding(32.dp)
                    ) {
                        CircularProgressIndicator(
                            color = Color.White,
                            modifier = Modifier.size(38.dp),
                            strokeWidth = 3.dp
                        )
                        Spacer(Modifier.height(20.dp))
                        Text(
                            "Preparing AR",
                            color = Color.White,
                            fontSize = 20.sp,
                            fontWeight = FontWeight.SemiBold,
                            textAlign = TextAlign.Center
                        )
                        Spacer(Modifier.height(10.dp))
                        Text(
                            "Checking camera and AR services...",
                            color = Color(0xAAFFFFFF),
                            fontSize = 14.sp,
                            textAlign = TextAlign.Center
                        )
                    }
                }
            }
        }
    }

    private fun showArInstallRequired() {
        setContent {
            Ferosa_landscapingTheme(darkTheme = false) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(Brand900),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.padding(32.dp)
                    ) {
                        Text("AR", fontSize = 40.sp, color = Color.White, fontWeight = FontWeight.Bold)
                        Spacer(Modifier.height(20.dp))
                        Text(
                            "ARCore Services Needed",
                            color = Color.White,
                            fontSize = 20.sp,
                            fontWeight = FontWeight.SemiBold,
                            textAlign = TextAlign.Center
                        )
                        Spacer(Modifier.height(10.dp))
                        Text(
                            "Install or update Google Play Services for AR, then return to Ferosa AR.",
                            color = Color(0xAAFFFFFF),
                            fontSize = 14.sp,
                            textAlign = TextAlign.Center
                        )
                        Spacer(Modifier.height(28.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            OutlinedButton(
                                onClick = { finish() },
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White)
                            ) {
                                Text("Go Back", fontWeight = FontWeight.SemiBold)
                            }
                            Button(
                                onClick = {
                                    arCoreInstallRequested = false
                                    checkArAvailabilityAndLaunch()
                                },
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = Brand500)
                            ) {
                                Text("Try Again", fontWeight = FontWeight.SemiBold)
                            }
                        }
                    }
                }
            }
        }
    }

    private fun showArUnsupported(message: String) {
        setContent {
            Ferosa_landscapingTheme(darkTheme = false) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(Brand900),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.padding(32.dp)
                    ) {
                        Surface(
                            color = Color.White.copy(alpha = 0.10f),
                            shape = CircleShape
                        ) {
                            Icon(
                                imageVector = Icons.Default.ErrorOutline,
                                contentDescription = null,
                                tint = Color.White,
                                modifier = Modifier.padding(18.dp).size(34.dp)
                            )
                        }
                        Spacer(Modifier.height(20.dp))
                        Text(
                            "AR Not Available",
                            color      = Color.White,
                            fontSize   = 20.sp,
                            fontWeight = FontWeight.SemiBold,
                            textAlign  = TextAlign.Center
                        )
                        Spacer(Modifier.height(10.dp))
                        Text(
                            message,
                            color     = Color(0xAAFFFFFF),
                            fontSize  = 14.sp,
                            textAlign = TextAlign.Center
                        )
                        Spacer(Modifier.height(28.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            OutlinedButton(
                                onClick = { finish() },
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White)
                            ) {
                                Text("Go Back", fontWeight = FontWeight.SemiBold)
                            }
                            Button(
                                onClick = {
                                    arScreenLaunched = false
                                    showArChecking()
                                    checkArAvailabilityAndLaunch()
                                },
                                shape   = RoundedCornerShape(12.dp),
                                colors  = ButtonDefaults.buttonColors(containerColor = Brand500)
                            ) {
                                Text("Try Again", fontWeight = FontWeight.SemiBold)
                            }
                        }
                    }
                }
            }
        }
    }

    private fun showPermissionDenied() {
        setContent {
            Ferosa_landscapingTheme(darkTheme = false) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(Brand900),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.padding(32.dp)
                    ) {
                        Surface(
                            color = Color.White.copy(alpha = 0.10f),
                            shape = CircleShape
                        ) {
                            Icon(
                                imageVector = Icons.Default.CameraAlt,
                                contentDescription = null,
                                tint = Color.White,
                                modifier = Modifier.padding(18.dp).size(34.dp)
                            )
                        }
                        Spacer(Modifier.height(20.dp))
                        Text(
                            "Camera Access Required",
                            color      = Color.White,
                            fontSize   = 20.sp,
                            fontWeight = FontWeight.SemiBold,
                            textAlign  = TextAlign.Center
                        )
                        Spacer(Modifier.height(10.dp))
                        Text(
                            "Ferosa AR Visualizer needs camera access to show the augmented reality view.",
                            color     = Color(0xAAFFFFFF),
                            fontSize  = 14.sp,
                            textAlign = TextAlign.Center
                        )
                        Spacer(Modifier.height(28.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            OutlinedButton(
                                onClick = { finish() },
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White)
                            ) {
                                Text("Go back", fontWeight = FontWeight.SemiBold)
                            }
                            Button(
                                onClick = {
                                    cameraSettingsOpened = true
                                    startActivity(
                                        Intent(
                                            Settings.ACTION_APPLICATION_DETAILS_SETTINGS,
                                            "package:$packageName".toUri()
                                        )
                                    )
                                },
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = Brand500)
                            ) {
                                Text("Open settings", fontWeight = FontWeight.SemiBold)
                            }
                        }
                    }
                }
            }
        }
    }
}

// ─── AR Screen ─────────────────────────────────────────────────────────────────

// ARSceneView ships from io.github.sceneview and cannot be made to override
// performClick without subclassing the renderer, which would mean taking over
// its session setup. The touch listener below already routes taps through
// performClick, which is the part we control.
@SuppressLint("ClickableViewAccessibility")
@Composable
private fun ArScreen(
    viewModel: ArViewModel,
    modelRepository: ModelRepository,
    projectType: String,
    propertySize: String,
    estimatedCost: String?,
    initialProductId: Int?,
    onExit: () -> Unit,
    onBackToWeb: () -> Unit,
    onSceneViewReady: (ARSceneView) -> Unit,
) {
    val context = LocalContext.current
    val activity = context as? ComponentActivity

    // Collect ViewModel StateFlows
    val products by viewModel.products.collectAsState()
    val selectedProduct by viewModel.selectedProduct.collectAsState()
    val placedModels by viewModel.placedModels.collectAsState()
    val isViewModelLoading by viewModel.isLoading.collectAsState()
    val error by viewModel.error.collectAsState()
    val isOffline by viewModel.isOffline.collectAsState()
    val canPlace by viewModel.canPlace.collectAsState()
    val repositioningModel by viewModel.repositioningModel.collectAsState()
    val selectedInfoModel by viewModel.selectedInfoModel.collectAsState()
    val cartActionState by viewModel.cartActionState.collectAsState()

    // Local UI state for surface discovery and the model placement pipeline.
    var placementStage by remember { mutableStateOf(ModelPlacementStage.Idle) }
    var isPlacementSurfaceReady by remember { mutableStateOf(false) }
    var placementNotice by remember { mutableStateOf<String?>(null) }
    var showInfoPanel  by remember { mutableStateOf(false) }
    var showPlacementCoach by rememberSaveable { mutableStateOf(true) }

    val sceneViewRef   = remember { mutableStateOf<ARSceneView?>(null) }
    val sceneGeneration = remember { AtomicInteger(0) }
    val pendingModelLoads = remember {
        Collections.synchronizedSet(mutableSetOf<Job>())
    }
    val placementRequestInFlight = remember { AtomicBoolean(false) }
    val previewJobRef = remember { AtomicReference<Job?>(null) }
    var previewStage by remember { mutableStateOf(PreviewStage.Idle) }
    var previewYawDegrees by remember { mutableStateOf(0f) }
    val previewRef = remember { mutableStateOf<PreviewHandle?>(null) }
    val placementTargetStabilityRef = remember {
        AtomicReference(PlacementTargetStability())
    }
    val retainedPlacementHitRef = remember {
        AtomicReference<HitResult?>(null)
    }
    val previewGeneration = remember { AtomicInteger(0) }
    var arSessionMessage by remember { mutableStateOf<String?>("Starting camera...") }
    var hasArFrame by remember { mutableStateOf(false) }

    // Coroutine scope for model download operations
    val coroutineScope = rememberCoroutineScope()

    fun resetPlacementTarget() {
        placementTargetStabilityRef.set(PlacementTargetStability())
        retainedPlacementHitRef.set(null)
        isPlacementSurfaceReady = false
        sceneViewRef.value?.planeRenderer?.isVisible = true
        previewRef.value?.let { preview ->
            preview.hitNode.hitResult = null
            preview.hitNode.isVisible = false
        }
    }

    fun disposePreview() {
        resetPlacementTarget()
        val preview = previewRef.value ?: return
        previewRef.value = null
        val sv = sceneViewRef.value
        runCatching { sv?.removeChildNode(preview.hitNode) }
        runCatching { preview.modelNode.destroy() }
        runCatching { preview.hitNode.destroy() }
        runCatching { sv?.modelLoader?.destroyModel(preview.modelNode.model) }
        if (BuildConfig.DEBUG) {
            Log.d("FerosaAR", "Preview disposed product=${preview.productId}")
        }
        previewStage = PreviewStage.Idle
    }

    fun detachPlacedAnchors(sv: ARSceneView?, placed: PlacedModel) {
        runCatching { sv?.removeChildNode(placed.anchorNode) }
        runCatching { placed.anchorNode.anchor.detach() }
        placed.originalAnchor
            ?.takeUnless { it === placed.anchorNode }
            ?.let { originalAnchor ->
                runCatching { sv?.removeChildNode(originalAnchor) }
                runCatching { originalAnchor.anchor.detach() }
            }
    }

    // Screenshot toast
    var screenshotMsg by remember { mutableStateOf<String?>(null) }
    LaunchedEffect(screenshotMsg) {
        if (screenshotMsg != null) { delay(2500); screenshotMsg = null }
    }
    LaunchedEffect(placementNotice) {
        if (placementNotice != null) {
            delay(3000)
            placementNotice = null
        }
    }

    LaunchedEffect(Unit) {
        delay(5000)
        if (!hasArFrame && arSessionMessage == "Starting camera...") {
            arSessionMessage = "Camera is taking too long to start. Check camera permission and Google Play Services for AR."
        }
    }

    // Load products when the screen starts
    LaunchedEffect(Unit) {
        viewModel.loadProducts()
    }

    // Auto-select first product when products load
    LaunchedEffect(products) {
        if (products.isNotEmpty() && selectedProduct == null) {
            viewModel.selectProduct(
                products.firstOrNull { it.id == initialProductId } ?: products.first()
            )
        }
    }

    // The ViewModel list is the source of truth for committed scene objects. The placement coach
    // is only shown for an empty scene; explicit Place remains available for the second through
    // fifth model until the authoritative limit is reached.
    val hasPlacedModels = placedModels.isNotEmpty()
    LaunchedEffect(hasPlacedModels) {
        if (hasPlacedModels) showPlacementCoach = false
    }

    // Refs for gesture handler access
    val selectedProductRef = remember { mutableStateOf<ArProduct?>(null) }
    selectedProductRef.value = selectedProduct

    val canPlaceRef = remember { mutableStateOf(true) }
    canPlaceRef.value = canPlace

    val repositioningRef = remember { mutableStateOf<PlacedModel?>(null) }
    repositioningRef.value = repositioningModel

    val placedModelsRef = remember { mutableStateOf<List<PlacedModel>>(emptyList()) }
    placedModelsRef.value = placedModels
    val placeButtonBoundsRef = remember { mutableStateOf<Rect?>(null) }
    val turnPreviewButtonBoundsRef = remember { mutableStateOf<Rect?>(null) }
    val coachButtonBoundsRef = remember { mutableStateOf<Rect?>(null) }

    // SceneView follows the Activity lifecycle. ArActivity owns the single
    // explicit destroy path so the renderer is not torn down concurrently.
    val lifecycleOwner = LocalLifecycleOwner.current

    DisposableEffect(viewModel) {
        onDispose {
            // Invalidate every outstanding model-loader callback before releasing
            // scene references. This also prevents a retained ViewModel from
            // carrying nodes created by the old renderer into a recreated Activity.
            sceneGeneration.incrementAndGet()
            previewGeneration.incrementAndGet()
            val loadsToCancel = synchronized(pendingModelLoads) {
                pendingModelLoads.toList().also {
                    pendingModelLoads.clear()
                }
            }
            loadsToCancel.forEach { it.cancel() }
            previewJobRef.getAndSet(null)?.cancel()
            placementRequestInFlight.set(false)
            disposePreview()
            val sv = sceneViewRef.value
            viewModel.placedModels.value.forEach { placed ->
                detachPlacedAnchors(sv, placed)
            }
            sceneViewRef.value = null
            viewModel.clearSceneState()
        }
    }

    fun isSceneCurrent(sv: ARSceneView, generation: Int): Boolean {
        val activityIsStopping = activity?.let { it.isFinishing || it.isDestroyed } == true
        return sceneGeneration.get() == generation &&
            sceneViewRef.value === sv &&
            !activityIsStopping &&
            lifecycleOwner.lifecycle.currentState != Lifecycle.State.DESTROYED
    }

    fun findPlacementHit(sv: ARSceneView, x: Float, y: Float): HitResult? = sv.hitTestAR(
        xPx = x,
        yPx = y,
        planeTypes = setOf(Plane.Type.HORIZONTAL_UPWARD_FACING),
        point = false,
        // Depth points can be noisy and are not guaranteed to be horizontal. Placement is only
        // allowed on an ARCore horizontal plane; depth remains enabled for occlusion/rendering.
        depthPoint = false,
        instantPlacementPoint = false,
        trackingStates = setOf(TrackingState.TRACKING),
        // Keep the generic hit-test filtering permissive, then apply the placement-specific
        // polygon + extents rule in the predicate. This avoids the SceneView fallback that can
        // return a nearby plane pose outside the actually tracked surface.
        planePoseInPolygon = false,
        predicate = placementPredicate@{ hit ->
            val plane = hit.trackable as? Plane ?: return@placementPredicate false
            runCatching {
                isPlacementPlanePoseValid(
                    isPoseInPolygon = plane.isPoseInPolygon(hit.hitPose),
                    isPoseInExtents = plane.isPoseInExtents(hit.hitPose),
                )
            }.getOrDefault(false)
        },
    )

    fun applyPlacementTarget(hit: HitResult?, nowMillis: Long) {
        val nextStability = updatePlacementTargetStability(
            state = placementTargetStabilityRef.get(),
            hasValidHit = hit != null,
            nowMillis = nowMillis,
        )
        placementTargetStabilityRef.set(nextStability)

        if (hit != null) {
            retainedPlacementHitRef.set(hit)
        }

        val targetIsStable = isPlacementTargetStable(nextStability, nowMillis)
        val visualHit = if (targetIsStable) retainedPlacementHitRef.get() else null
        if (visualHit == null) {
            retainedPlacementHitRef.set(null)
        }

        isPlacementSurfaceReady = targetIsStable && visualHit != null
        // The plane texture is a discovery aid, not a placement surface. Once a target has been
        // confirmed, hide it so a coarse ARCore mesh cannot visually suggest that the model is
        // floating above the real surface. A miss restores the guide in resetPlacementTarget().
        sceneViewRef.value?.planeRenderer?.isVisible = !isPlacementSurfaceReady
        previewRef.value
            ?.takeIf { previewStage == PreviewStage.Ready }
            ?.let { preview ->
                preview.hitNode.hitResult = visualHit
                preview.hitNode.isVisible = visualHit != null
            }
    }

    fun placementControlState(): PlacementControlState {
        val preview = previewRef.value
        return PlacementControlState(
            hasSelectedProduct = selectedProductRef.value != null,
            isPreviewReady = previewStage == PreviewStage.Ready && preview != null,
            hasFreshTarget = isPlacementSurfaceReady && preview?.hitNode?.hitResult != null,
            isBusy = placementStage != ModelPlacementStage.Idle,
            isMoving = repositioningRef.value != null,
            placedCount = placedModelsRef.value.size,
            maxPlacedModels = ArViewModel.MAX_PLACED_MODELS,
        )
    }

    fun hidePreviewTarget() {
        resetPlacementTarget()
    }

    fun updatePreviewTarget(sv: ARSceneView) {
        if (previewStage != PreviewStage.Ready || sv.width <= 0 || sv.height <= 0) return

        val (x, y) = crosshairCoordinates(sv.width, sv.height)
        val hit = findPlacementHit(sv, x, y)
        applyPlacementTarget(hit, SystemClock.elapsedRealtime())
    }

    fun preparePreview(product: ArProduct?) {
        previewJobRef.getAndSet(null)?.cancel()
        val requestGeneration = previewGeneration.incrementAndGet()
        previewYawDegrees = 0f
        disposePreview()
        previewStage = if (product == null) PreviewStage.Idle else PreviewStage.Loading

        val sv = sceneViewRef.value ?: run {
            previewStage = PreviewStage.Idle
            return
        }
        if (product == null) return

        val requestProductId = product.id
        val previewJob = coroutineScope.launch {
            try {
                val modelResult = modelRepository.getModel(requestProductId)
                val modelBuffer = withContext(Dispatchers.IO) {
                    validateGlbFile(modelResult.file)
                    resolveCachedModelLoaderInput(modelResult.file)
                }

                withContext(Dispatchers.Main.immediate) {
                    if (!isPreviewRequestCurrent(
                            requestGeneration = requestGeneration.toLong(),
                            currentGeneration = previewGeneration.get().toLong(),
                            requestProductId = requestProductId,
                            selectedProductId = selectedProductRef.value?.id,
                        ) || !isSceneCurrent(sv, sceneGeneration.get())
                    ) {
                        return@withContext
                    }

                    val modelInstance = sv.modelLoader.createModelInstance(modelBuffer)
                    val modelNode = try {
                        ModelNode(modelInstance = modelInstance)
                    } catch (exception: Exception) {
                        runCatching { sv.modelLoader.destroyModel(modelInstance.asset) }
                        throw exception
                    }
                    try {
                        require(modelNode.renderableNodes.isNotEmpty()) {
                            "This 3D model does not contain visible geometry."
                        }

                        val transform = calculateGroundedModelTransform(
                            centerX = modelNode.center.x,
                            centerY = modelNode.center.y,
                            centerZ = modelNode.center.z,
                            halfExtentX = modelNode.halfExtent.x,
                            halfExtentY = modelNode.halfExtent.y,
                            halfExtentZ = modelNode.halfExtent.z,
                            desiredHeightMeters = product.heightCm / 100f,
                        )
                        modelNode.scale = Scale(
                            x = transform.uniformScale,
                            y = transform.uniformScale,
                            z = transform.uniformScale,
                        )
                        modelNode.position = Position(
                            x = transform.positionX,
                            y = transform.positionY,
                            z = transform.positionZ,
                        )
                        modelNode.rotation = Rotation(y = previewYawDegrees)
                        modelNode.isShadowCaster = false
                        modelNode.isTouchable = false
                        modelNode.isHittable = false

                        val hitNode = PlacementPreviewNode(engine = sv.engine).apply {
                            isVisible = false
                            isTouchable = false
                            isHittable = false
                            addChildNode(modelNode)
                        }
                        sv.addChildNode(hitNode)
                        previewRef.value = PreviewHandle(
                            productId = requestProductId,
                            hitNode = hitNode,
                            modelNode = modelNode,
                        )
                        previewStage = PreviewStage.Ready
                        if (BuildConfig.DEBUG) {
                            Log.d(
                                "FerosaAR",
                                "Preview ready product=$requestProductId, " +
                                    "halfExtent=${modelNode.halfExtent}, " +
                                    "heightMeters=${product.heightCm / 100f}, " +
                                    "scale=${transform.uniformScale}, " +
                                    "position=${modelNode.position}, " +
                                    "renderables=${modelNode.renderableNodes.size}",
                            )
                        }
                        updatePreviewTarget(sv)
                    } catch (exception: Exception) {
                        runCatching { modelNode.destroy() }
                        runCatching { sv.modelLoader.destroyModel(modelNode.model) }
                        throw exception
                    }
                }
            } catch (exception: CancellationException) {
                throw exception
            } catch (exception: Exception) {
                withContext(Dispatchers.Main.immediate) {
                    if (isPreviewRequestCurrent(
                            requestGeneration = requestGeneration.toLong(),
                            currentGeneration = previewGeneration.get().toLong(),
                            requestProductId = requestProductId,
                            selectedProductId = selectedProductRef.value?.id,
                        ) && isSceneCurrent(sv, sceneGeneration.get())
                    ) {
                        previewStage = PreviewStage.Idle
                        placementNotice = customerReadableModelError(exception)
                    }
                }
            }
        }
        previewJobRef.set(previewJob)
        pendingModelLoads += previewJob
        previewJob.invokeOnCompletion {
            pendingModelLoads -= previewJob
            previewJobRef.compareAndSet(previewJob, null)
        }
    }

    fun preparePreviewIfNeeded() {
        val product = selectedProductRef.value ?: return
        if (viewModel.placedModels.value.size >= ArViewModel.MAX_PLACED_MODELS) return
        if (previewRef.value == null && previewStage == PreviewStage.Idle) {
            preparePreview(product)
        }
    }

    LaunchedEffect(selectedProduct?.id, sceneViewRef.value) {
        if (placedModels.size < ArViewModel.MAX_PLACED_MODELS) {
            preparePreview(selectedProduct)
        } else {
            previewGeneration.incrementAndGet()
            previewJobRef.getAndSet(null)?.cancel()
            disposePreview()
        }
    }

    LaunchedEffect(placedModels.size) {
        if (placedModels.size >= ArViewModel.MAX_PLACED_MODELS) {
            previewGeneration.incrementAndGet()
            previewJobRef.getAndSet(null)?.cancel()
            disposePreview()
        } else {
            preparePreviewIfNeeded()
        }
    }

    fun restorePreviewIfCurrent(sv: ARSceneView, productId: Int) {
        val preview = previewRef.value ?: return
        if (preview.productId != productId || selectedProductRef.value?.id != productId) return
        if (!isSceneCurrent(sv, sceneGeneration.get())) return
        previewStage = PreviewStage.Ready
        updatePreviewTarget(sv)
    }

    fun disposePreviewIfCurrent(productId: Int) {
        if (previewRef.value?.productId == productId) {
            disposePreview()
        }
    }

    fun turnPreview180() {
        val preview = previewRef.value ?: return
        if (previewStage != PreviewStage.Ready) return
        previewYawDegrees = turn180Degrees(previewYawDegrees)
        preview.modelNode.rotation = Rotation(y = previewYawDegrees)
        if (BuildConfig.DEBUG) {
            Log.d("FerosaAR", "Turned preview product=${preview.productId}, yawDegrees=$previewYawDegrees")
        }
    }

    fun placeModel(
        sv: ARSceneView,
        x: Float,
        y: Float,
        product: ArProduct,
        yawDegrees: Float,
    ): Boolean {
        if (!canPlaceRef.value || placementStage != ModelPlacementStage.Idle) return false
        val hit = findPlacementHit(sv, x, y)
        if (hit == null) {
            resetPlacementTarget()
            placementNotice = "No ground found here. Move your phone slowly and aim at a textured floor or lawn."
            return false
        }

        val anchor = hit.createAnchor()
        val anchorDetached = AtomicBoolean(false)
        fun detachPlacementAnchor() {
            if (anchorDetached.compareAndSet(false, true)) {
                anchor.detach()
            }
        }
        val placementGeneration = sceneGeneration.get()
        placementNotice = null
        viewModel.clearError()
        placementStage = ModelPlacementStage.Downloading

        // Download via ModelRepository (handles caching + retry with exponential backoff), read the
        // cache off the main thread, then create Filament entities on the main thread.
        val modelLoadJob = coroutineScope.launch {
            try {
                val modelResult = modelRepository.getModel(product.id)
                val modelBuffer = withContext(Dispatchers.IO) {
                    validateGlbFile(modelResult.file)
                    resolveCachedModelLoaderInput(modelResult.file)
                }

                withContext(Dispatchers.Main) {
                    if (!isSceneCurrent(sv, placementGeneration)) {
                        detachPlacementAnchor()
                        return@withContext
                    }

                    placementStage = ModelPlacementStage.Preparing
                    var anchorNode: AnchorNode? = null
                    var modelNode: ModelNode? = null
                    var committed = false
                    try {
                        val instance = sv.modelLoader.createModelInstance(modelBuffer)
                        if (!isSceneCurrent(sv, placementGeneration)) {
                            runCatching { sv.modelLoader.destroyModel(instance.asset) }
                            detachPlacementAnchor()
                            return@withContext
                        }

                        placementStage = ModelPlacementStage.Idle
                        val builtModelNode = try {
                            ModelNode(modelInstance = instance)
                        } catch (exception: Exception) {
                            runCatching { sv.modelLoader.destroyModel(instance.asset) }
                            throw exception
                        }
                        modelNode = builtModelNode
                        require(builtModelNode.renderableNodes.isNotEmpty()) {
                            "This 3D model does not contain visible geometry."
                        }

                        val transform = calculateGroundedModelTransform(
                            centerX = builtModelNode.center.x,
                            centerY = builtModelNode.center.y,
                            centerZ = builtModelNode.center.z,
                            halfExtentX = builtModelNode.halfExtent.x,
                            halfExtentY = builtModelNode.halfExtent.y,
                            halfExtentZ = builtModelNode.halfExtent.z,
                            desiredHeightMeters = product.heightCm / 100f,
                        )
                        builtModelNode.scale = Scale(
                            x = transform.uniformScale,
                            y = transform.uniformScale,
                            z = transform.uniformScale,
                        )
                        builtModelNode.position = Position(
                            x = transform.positionX,
                            y = transform.positionY,
                            z = transform.positionZ,
                        )
                        builtModelNode.rotation = Rotation(y = yawDegrees)
                        builtModelNode.isShadowCaster = true

                        val builtAnchorNode = AnchorNode(engine = sv.engine, anchor = anchor)
                        anchorNode = builtAnchorNode
                        builtAnchorNode.addChildNode(builtModelNode)
                        sv.addChildNode(builtAnchorNode)
                        if (viewModel.placeModel(builtAnchorNode, product)) {
                            committed = true
                            disposePreviewIfCurrent(product.id)
                            preparePreviewIfNeeded()
                        } else {
                            // The limit may have been reached while the model loaded. A rejected
                            // node must never become visible or untracked.
                            runCatching { sv.removeChildNode(builtAnchorNode) }
                            runCatching { builtAnchorNode.removeChildNode(builtModelNode) }
                            runCatching { builtModelNode.destroy() }
                            runCatching { sv.modelLoader.destroyModel(builtModelNode.model) }
                            detachPlacementAnchor()
                            restorePreviewIfCurrent(sv, product.id)
                        }

                        if (committed && BuildConfig.DEBUG) {
                            Log.d(
                                "FerosaAR",
                                "Placed product=${product.id}, center=${builtModelNode.center}, " +
                                    "halfExtent=${builtModelNode.halfExtent}, " +
                                    "heightMeters=${product.heightCm / 100f}, " +
                                    "scale=${transform.uniformScale}, " +
                                    "position=${builtModelNode.position}, " +
                                    "yawDegrees=$yawDegrees, " +
                                    "renderables=${builtModelNode.renderableNodes.size}"
                            )
                        }
                    } catch (exception: Exception) {
                        if (!committed) {
                            modelNode?.let { node ->
                                runCatching { anchorNode?.removeChildNode(node) }
                                runCatching { node.destroy() }
                                runCatching { sv.modelLoader.destroyModel(node.model) }
                            }
                            runCatching { anchorNode?.let(sv::removeChildNode) }
                        }
                        detachPlacementAnchor()
                        placementStage = ModelPlacementStage.Idle
                        restorePreviewIfCurrent(sv, product.id)
                        viewModel.setModelLoadError(customerReadableModelError(exception))
                        if (BuildConfig.DEBUG) {
                            Log.w(
                                "FerosaAR",
                                "Model load failed for product=${product.id}: " +
                                    "${exception::class.simpleName}: ${exception.message}",
                            )
                        }
                    }
                }
            } catch (e: CancellationException) {
                detachPlacementAnchor()
                throw e
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    detachPlacementAnchor()
                    if (isSceneCurrent(sv, placementGeneration)) {
                        placementStage = ModelPlacementStage.Idle
                        restorePreviewIfCurrent(sv, product.id)
                        viewModel.setModelLoadError(customerReadableModelError(e))
                        if (BuildConfig.DEBUG) {
                            Log.w(
                                "FerosaAR",
                                "Model load failed for product=${product.id}: " +
                                    "${e::class.simpleName}: ${e.message}",
                            )
                        }
                    }
                }
            }
        }
        pendingModelLoads += modelLoadJob
        modelLoadJob.invokeOnCompletion { cause ->
            pendingModelLoads -= modelLoadJob
            placementRequestInFlight.set(false)
            // Cancellation can win before the coroutine body starts, so keep a main-thread cleanup
            // guard for the anchor created before the job was launched.
            if (cause != null && anchorDetached.compareAndSet(false, true)) {
                Handler(Looper.getMainLooper()).post { anchor.detach() }
            }
        }
        return true
    }

    fun placePreviewAtCrosshair() {
        if (placementStage != ModelPlacementStage.Idle || previewStage == PreviewStage.Loading) {
            return
        }
        if (!placementRequestInFlight.compareAndSet(false, true)) {
            return
        }
        val state = placementControlState()
        if (!canConfirmPlacement(state)) {
            placementRequestInFlight.set(false)
            placementNotice = when {
                state.isMoving -> "Finish moving the selected item before placing another."
                !state.isPreviewReady -> "Preparing the selected 3D preview."
                !state.hasFreshTarget -> "Aim at a tracked horizontal surface and wait for the preview."
                state.placedCount >= state.maxPlacedModels -> "Maximum ${state.maxPlacedModels} items placed."
                else -> "Select an in-stock AR item to place."
            }
            return
        }

        val sv = sceneViewRef.value
        val product = selectedProductRef.value
        if (sv == null || product == null) {
            placementRequestInFlight.set(false)
            placementNotice = "The AR camera is not ready yet."
            return
        }

        val (x, y) = crosshairCoordinates(sv.width, sv.height)
        previewRef.value?.let { preview ->
            preview.hitNode.isVisible = false
        }
        previewStage = PreviewStage.Loading
        val started = try {
            placeModel(sv, x, y, product, previewYawDegrees)
        } catch (exception: Exception) {
            placementStage = ModelPlacementStage.Idle
            placementNotice = customerReadableModelError(exception)
            if (BuildConfig.DEBUG) {
                Log.w(
                    "FerosaAR",
                    "Placement start failed for product=${product.id}: " +
                        "${exception::class.simpleName}: ${exception.message}",
                )
            }
            false
        }
        if (!started) {
            placementRequestInFlight.set(false)
            restorePreviewIfCurrent(sv, product.id)
        }
    }

    fun resetScene() {
        // Invalidates downloads and model preparation started before this reset. Pending jobs detach
        // their anchors instead of placing into the new scene generation.
        sceneGeneration.incrementAndGet()
        previewGeneration.incrementAndGet()
        previewJobRef.getAndSet(null)?.cancel()
        val loadsToCancel = synchronized(pendingModelLoads) {
            pendingModelLoads.toList().also {
                pendingModelLoads.clear()
            }
        }
        loadsToCancel.forEach { it.cancel() }
        disposePreview()
        placementStage = ModelPlacementStage.Idle
        placementNotice = null
        val currentPlacements = viewModel.placedModels.value
        sceneViewRef.value?.let { sv ->
            for (placed in currentPlacements) {
                detachPlacedAnchors(sv, placed)
            }
        }
        viewModel.clearSceneState()
        preparePreviewIfNeeded()
    }

    fun deletePlacedModel(placed: PlacedModel) {
        detachPlacedAnchors(sceneViewRef.value, placed)
        viewModel.deleteModel(placed)
        preparePreviewIfNeeded()
    }

    fun turnPlacedModel180(placed: PlacedModel) {
        val nextYaw = turn180Degrees(placed.modelNode.rotation.y)
        placed.modelNode.rotation = Rotation(y = nextYaw)
        if (BuildConfig.DEBUG) {
            Log.d("FerosaAR", "Turned placed product=${placed.product.id}, yawDegrees=$nextYaw")
        }
    }

    fun startMoveMode(placed: PlacedModel) {
        viewModel.startRepositioning(placed)
    }

    fun cancelMoveMode() {
        val moving = repositioningModel ?: return
        val originalAnchor = moving.originalAnchor
        val sceneView = sceneViewRef.value

        try {
            if (sceneView != null && originalAnchor != null && moving.anchorNode !== originalAnchor) {
                runCatching { moving.anchorNode.removeChildNode(moving.modelNode) }
                runCatching { sceneView.removeChildNode(moving.anchorNode) }
                runCatching { moving.anchorNode.anchor.detach() }
                runCatching { originalAnchor.addChildNode(moving.modelNode) }
            }
        } finally {
            viewModel.cancelRepositioning()
        }
    }

    fun captureScreenshotNow() {
        val sv = sceneViewRef.value ?: return
        if (sv.width <= 0 || sv.height <= 0) {
            screenshotMsg = "AR view is not ready yet"
            return
        }
        val bitmap = createBitmap(sv.width, sv.height)
        PixelCopy.request(sv, bitmap, { result ->
            screenshotMsg = if (result == PixelCopy.SUCCESS && saveBitmapToGallery(context, bitmap))
                "📸 Screenshot saved to gallery" else "Failed to capture"
        }, Handler(Looper.getMainLooper()))
    }

    val storagePermissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) {
            captureScreenshotNow()
        } else {
            screenshotMsg = "Storage access is needed to save AR photos on this Android version"
        }
    }

    fun captureScreenshot() {
        val needsLegacyPermission = Build.VERSION.SDK_INT <= Build.VERSION_CODES.P &&
            ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.WRITE_EXTERNAL_STORAGE
            ) != PackageManager.PERMISSION_GRANTED

        if (needsLegacyPermission) {
            storagePermissionLauncher.launch(Manifest.permission.WRITE_EXTERNAL_STORAGE)
        } else {
            captureScreenshotNow()
        }
    }

    // Combined loading state
    val isLoading = isViewModelLoading || placementStage != ModelPlacementStage.Idle
    val placementLimitReached = placedModels.size >= ArViewModel.MAX_PLACED_MODELS
    val coachVisible = showPlacementCoach &&
        arSessionMessage == null &&
        !hasPlacedModels &&
        !isLoading &&
        products.isNotEmpty()
    val placeButtonVisible = !showPlacementCoach &&
        arSessionMessage == null &&
        selectedProduct != null &&
        !placementLimitReached &&
        !isLoading &&
        products.isNotEmpty()
    val turnPreviewButtonVisible = placeButtonVisible && previewStage == PreviewStage.Ready
    LaunchedEffect(placeButtonVisible) {
        if (!placeButtonVisible) {
            placeButtonBoundsRef.value = null
        }
    }
    LaunchedEffect(turnPreviewButtonVisible) {
        if (!turnPreviewButtonVisible) {
            turnPreviewButtonBoundsRef.value = null
        }
    }
    LaunchedEffect(coachVisible) {
        if (!coachVisible) {
            coachButtonBoundsRef.value = null
        }
    }
    val noticeMessage = when {
        error != null -> error?.message
        screenshotMsg != null -> screenshotMsg
        placementNotice != null -> placementNotice
        isOffline -> "Offline · cached AR items only"
        else -> null
    }
    val noticeColor = when {
        error != null -> Color(0xFFE05252)
        screenshotMsg != null -> Brand700
        else -> Color(0xFFE19A24)
    }
    val noticeIcon = when {
        error != null -> Icons.Default.ErrorOutline
        screenshotMsg != null -> Icons.Default.CheckCircle
        placementNotice != null -> Icons.Default.TouchApp
        else -> Icons.Default.CloudOff
    }

    Box(modifier = Modifier.fillMaxSize()) {

        // ── ARSceneView with raycasting-based gesture handling ─────────────
        AndroidView(
            modifier = Modifier.fillMaxSize(),
            factory  = { ctx ->
                var configuredSceneView: ARSceneView? = null
                var depthSupported = false
                val sessionConfiguration: (Session, Config) -> Unit = { session, config ->
                    config.planeFindingMode = Config.PlaneFindingMode.HORIZONTAL
                    config.lightEstimationMode = Config.LightEstimationMode.ENVIRONMENTAL_HDR

                    depthSupported = session.isDepthModeSupported(Config.DepthMode.AUTOMATIC)
                    config.depthMode = if (depthSupported) {
                        Config.DepthMode.AUTOMATIC
                    } else {
                        Config.DepthMode.DISABLED
                    }
                    configuredSceneView?.cameraStream?.isDepthOcclusionEnabled = depthSupported

                    // SceneView can apply this callback while its constructor is still running,
                    // before onSessionConfigChanged is assigned below. Log from the constructor
                    // callback as well so the first live session configuration is never invisible
                    // to the physical-device verification script.
                    if (BuildConfig.DEBUG) {
                        Log.d(
                            "FerosaAR",
                            formatArSessionConfigLog(
                                planeFindingMode = config.planeFindingMode.toString(),
                                lightEstimationMode = config.lightEstimationMode.toString(),
                                depthMode = config.depthMode.toString(),
                                depthOcclusionEnabled = depthSupported,
                            ),
                        )
                    }
                }
                val newSceneView = activity?.let {
                    ARSceneView(
                        context = ctx,
                        sharedActivity = it,
                        sessionConfiguration = sessionConfiguration,
                    )
                } ?: ARSceneView(
                    context = ctx,
                    sessionConfiguration = sessionConfiguration,
                )
                configuredSceneView = newSceneView
                newSceneView.cameraStream?.isDepthOcclusionEnabled = depthSupported

                newSceneView.also { sv ->
                    sceneViewRef.value = sv
                    onSceneViewReady(sv)
                    sv.planeRenderer.isEnabled = true
                    sv.planeRenderer.isVisible = true
                    sv.planeRenderer.isShadowReceiver = true
                    var lastDragAnchorUpdateAt = 0L
                    var lastSurfaceProbeAt = 0L
                    var trackingFailureGeneration = 0
                    sv.onSessionConfigChanged = { _, config ->
                        if (BuildConfig.DEBUG) {
                            Log.d(
                                "FerosaAR",
                                formatArSessionConfigLog(
                                    planeFindingMode = config.planeFindingMode.toString(),
                                    lightEstimationMode = config.lightEstimationMode.toString(),
                                    depthMode = config.depthMode.toString(),
                                    depthOcclusionEnabled =
                                        sv.cameraStream?.isDepthOcclusionEnabled == true,
                                ),
                            )
                        }
                    }
                    sv.onSessionCreated = {
                        arSessionMessage = "Starting camera..."
                        hidePreviewTarget()
                    }
                    sv.onSessionResumed = {
                        arSessionMessage = null
                    }
                    sv.onSessionFailed = { exception ->
                        hidePreviewTarget()
                        arSessionMessage = exception.message
                            ?: "AR camera could not start. Close other apps using the camera, then try again."
                    }
                    sv.onTrackingFailureChanged = { reason ->
                        // Keep the last confirmed preview through transient PAUSED states. The
                        // frame probe owns the bounded 350 ms miss expiry; clearing immediately
                        // here would defeat that smoothing whenever ARCore briefly reports a
                        // recoverable tracking reason. A camera-unavailable state is terminal
                        // enough to clear immediately.
                        val failureGeneration = ++trackingFailureGeneration
                        if (reason != null && reason.name != "CAMERA_UNAVAILABLE") {
                            coroutineScope.launch {
                                delay(PLACEMENT_TARGET_MISS_GRACE_MILLIS)
                                if (trackingFailureGeneration == failureGeneration) {
                                    hidePreviewTarget()
                                }
                            }
                        } else if (reason?.name == "CAMERA_UNAVAILABLE") {
                            hidePreviewTarget()
                        }
                        arSessionMessage = when (reason?.name) {
                            null, "NONE" -> null
                            "BAD_STATE" -> "Move your phone slowly so AR can start tracking."
                            "INSUFFICIENT_LIGHT" -> "More light is needed for AR tracking."
                            "EXCESSIVE_MOTION" -> "Move your phone more slowly."
                            "INSUFFICIENT_FEATURES" -> "Point the camera at a textured floor or garden surface."
                            "CAMERA_UNAVAILABLE" -> "Camera is unavailable. Close other apps using it, then reopen AR."
                            else -> "AR is still trying to find a surface."
                        }
                    }
                    sv.onSessionUpdated = { _, _ ->
                        hasArFrame = true
                        if (arSessionMessage == "Starting camera..." ||
                            arSessionMessage == "Camera is taking too long to start. Check camera permission and Google Play Services for AR."
                        ) {
                            arSessionMessage = null
                        }

                        val now = SystemClock.elapsedRealtime()
                        if (sv.width > 0 && sv.height > 0 && now - lastSurfaceProbeAt >= 150L) {
                            lastSurfaceProbeAt = now
                            val (x, y) = crosshairCoordinates(sv.width, sv.height)
                            val centerHit = findPlacementHit(sv, x, y)
                            applyPlacementTarget(centerHit, now)
                            if (isPlacementSurfaceReady &&
                                placementNotice?.startsWith("No ground found") == true
                            ) {
                                placementNotice = null
                            }
                        }
                    }

                    /**
                     * Finds which placed model (if any) is under a screen coordinate.
                     *
                     * SceneView's collision ray is the authoritative hit test. The projected model
                     * bounds are a forgiving fallback for devices/assets where the collision AABB
                     * is temporarily unavailable while Filament updates a transform. The old
                     * origin-only check was particularly hard to use for tall plants because the
                     * grounded origin is at the pot/base, not in the visible leaves.
                     */
                    fun findPlacedModelAtPosition(x: Float, y: Float): PlacedModel? {
                        val placedModels = placedModelsRef.value
                        if (placedModels.isEmpty()) return null

                        fun hitBelongsToModel(hitNode: Node, placed: PlacedModel): Boolean {
                            var current: Node? = hitNode
                            while (current != null) {
                                if (current === placed.modelNode) return true
                                current = current.parent
                            }
                            return false
                        }

                        // Prefer the nearest SceneView collision hit, which also handles overlap
                        // correctly when several products are placed in front of one another.
                        val collisionHit = runCatching {
                            sv.collisionSystem.hitTest(x, y)
                        }.getOrDefault(emptyList())
                        collisionHit.firstNotNullOfOrNull { hit ->
                            placedModels.firstOrNull { placed ->
                                hitBelongsToModel(hit.node, placed)
                            }
                        }?.let { return it }

                        val camera = sv.cameraNode
                        val touchPaddingPx = 48f * sv.resources.displayMetrics.density

                        // If a collision result is unavailable, use the projected model AABB rather
                        // than the grounded origin. This keeps the entire visible product tappable.
                        return placedModels.mapNotNull { placed ->
                            val modelNode = placed.modelNode
                            val bounds = runCatching {
                                val center = modelNode.center
                                val halfExtent = modelNode.halfExtent
                                val projectedPoints = buildList {
                                    for (xSign in listOf(-1f, 1f)) {
                                        for (ySign in listOf(-1f, 1f)) {
                                            for (zSign in listOf(-1f, 1f)) {
                                                val localPoint = Position(
                                                    x = center.x + xSign * halfExtent.x,
                                                    y = center.y + ySign * halfExtent.y,
                                                    z = center.z + zSign * halfExtent.z,
                                                )
                                                val viewCoords = camera.worldToView(
                                                    modelNode.getWorldPosition(localPoint)
                                                )
                                                add(
                                                    (viewCoords.x * sv.width) to
                                                        ((1f - viewCoords.y) * sv.height)
                                                )
                                            }
                                        }
                                    }
                                }
                                screenHitBoundsFromPoints(projectedPoints)
                            }.getOrNull() ?: return@mapNotNull null

                            if (!containsScreenHitBounds(bounds, x, y, touchPaddingPx)) {
                                return@mapNotNull null
                            }

                            val centerView = camera.worldToView(
                                modelNode.getWorldPosition(modelNode.center)
                            )
                            val centerX = centerView.x * sv.width
                            val centerY = (1f - centerView.y) * sv.height
                            val dx = centerX - x
                            val dy = centerY - y
                            (dx * dx + dy * dy) to placed
                        }.minByOrNull { it.first }?.second
                    }

                    val gestureDetector = GestureDetectorCompat(
                        ctx,
                        object : GestureDetector.SimpleOnGestureListener() {

                            override fun onSingleTapUp(e: MotionEvent): Boolean {
                                if (placementStage != ModelPlacementStage.Idle) return false

                                // If repositioning, don't handle single tap here
                                // (ACTION_UP in onTouchEvent handles finalization)
                                if (repositioningRef.value != null) return false

                                // 1) Check if tap hit a placed model → show info panel
                                val tappedModel = findPlacedModelAtPosition(e.x, e.y)
                                if (tappedModel != null) {
                                    viewModel.showProductInfo(tappedModel)
                                    return true
                                }

                                // 2) Surface taps only aim the crosshair. Placement is committed
                                // by the explicit Place button so an accidental camera tap cannot
                                // create an anchor away from the rendered preview.
                                return true
                            }

                            override fun onLongPress(e: MotionEvent) {
                                if (repositioningRef.value != null) return

                                // Raycast to find which specific model was long-pressed
                                val tappedModel = findPlacedModelAtPosition(e.x, e.y)
                                if (tappedModel != null) {
                                    viewModel.startRepositioning(tappedModel)
                                }
                            }
                        }
                    )

                    sv.setOnTouchListener { touchedView, event ->
                        // SurfaceView receives raw touches even when Compose draws the Place
                        // button above it. Route only the button's published layout bounds to the
                        // same guarded action; all other surface taps remain non-placing.
                        val coachButtonTouch = coachButtonBoundsRef.value
                            ?.contains(Offset(event.x, event.y)) == true
                        if (coachButtonTouch) {
                            if (event.action == MotionEvent.ACTION_UP) {
                                showPlacementCoach = false
                            }
                            return@setOnTouchListener true
                        }

                        val turnPreviewButtonTouch = turnPreviewButtonBoundsRef.value
                            ?.contains(Offset(event.x, event.y)) == true
                        if (turnPreviewButtonTouch) {
                            if (event.action == MotionEvent.ACTION_UP) {
                                turnPreview180()
                            }
                            return@setOnTouchListener true
                        }

                        val placeButtonTouch = placeButtonBoundsRef.value
                            ?.contains(Offset(event.x, event.y)) == true
                        if (placeButtonTouch) {
                            if (event.action == MotionEvent.ACTION_UP) {
                                placePreviewAtCrosshair()
                            }
                            return@setOnTouchListener true
                        }

                        // Handle repositioning drag and drop
                        val currentRepositioning = repositioningRef.value
                        if (currentRepositioning != null) {
                            when (event.action) {
                                MotionEvent.ACTION_MOVE -> {
                                    // AR anchors are expensive. Limit drag updates while still
                                    // keeping motion responsive, then create the precise final
                                    // anchor on ACTION_UP below.
                                    if (event.eventTime - lastDragAnchorUpdateAt < 72L) {
                                        return@setOnTouchListener true
                                    }
                                    lastDragAnchorUpdateAt = event.eventTime

                                    // Real-time drag: move model along surfaces
                                    val hit = findPlacementHit(sv, event.x, event.y)
                                    if (hit != null) {
                                        // Valid surface: move model to the latest hit position.
                                        val newAnchor = hit.createAnchor()
                                        val newAnchorNode = AnchorNode(
                                            engine = sv.engine, anchor = newAnchor
                                        )

                                        val oldAnchorNode = currentRepositioning.anchorNode
                                        val originalAnchorNode = currentRepositioning.originalAnchor
                                        val modelNode = currentRepositioning.modelNode

                                        // Remove model from old parent, add to new
                                        oldAnchorNode.removeChildNode(modelNode)
                                        if (oldAnchorNode !== originalAnchorNode) {
                                            sv.removeChildNode(oldAnchorNode)
                                            oldAnchorNode.anchor.detach()
                                        }

                                        newAnchorNode.addChildNode(modelNode)
                                        sv.addChildNode(newAnchorNode)

                                        // Update the repositioning model reference with new anchor
                                        val updatedModel = currentRepositioning.copy(
                                            anchorNode = newAnchorNode
                                        )
                                        repositioningRef.value = updatedModel
                                        viewModel.updateRepositioningAnchor(updatedModel)
                                    }
                                    return@setOnTouchListener true
                                }
                                MotionEvent.ACTION_UP -> {
                                    // Finalize repositioning
                                    val hit = findPlacementHit(sv, event.x, event.y)
                                    if (hit != null) {
                                        // Over valid surface → finish repositioning with new anchor
                                        val newAnchor = hit.createAnchor()
                                        val newAnchorNode = AnchorNode(
                                            engine = sv.engine, anchor = newAnchor
                                        )

                                        val oldAnchorNode = currentRepositioning.anchorNode
                                        val originalAnchorNode = currentRepositioning.originalAnchor
                                        val modelNode = currentRepositioning.modelNode

                                        // Move model to final position
                                        oldAnchorNode.removeChildNode(modelNode)
                                        if (oldAnchorNode !== originalAnchorNode) {
                                            sv.removeChildNode(oldAnchorNode)
                                            oldAnchorNode.anchor.detach()
                                        }

                                        newAnchorNode.addChildNode(modelNode)
                                        sv.addChildNode(newAnchorNode)

                                        if (originalAnchorNode != null && originalAnchorNode !== newAnchorNode) {
                                            sv.removeChildNode(originalAnchorNode)
                                            originalAnchorNode.anchor.detach()
                                        }

                                        viewModel.finishRepositioning(newAnchorNode)
                                    } else {
                                        // No valid surface → cancel and snap back to original position
                                        val originalAnchorNode = currentRepositioning.originalAnchor
                                        if (originalAnchorNode != null && currentRepositioning.anchorNode !== originalAnchorNode) {
                                            runCatching {
                                                currentRepositioning.anchorNode.removeChildNode(currentRepositioning.modelNode)
                                            }
                                            runCatching { sv.removeChildNode(currentRepositioning.anchorNode) }
                                            runCatching { currentRepositioning.anchorNode.anchor.detach() }
                                            runCatching { originalAnchorNode.addChildNode(currentRepositioning.modelNode) }
                                        }
                                        viewModel.cancelRepositioning()
                                    }
                                    return@setOnTouchListener true
                                }
                            }
                        }

                        // Lifting a finger without an active drag is a tap. Routing
                        // it through performClick keeps accessibility services
                        // (TalkBack, switch access) able to trigger the same action.
                        if (event.action == MotionEvent.ACTION_UP) {
                            touchedView.performClick()
                        }

                        gestureDetector.onTouchEvent(event)
                        true
                    }
                    // Install callbacks before attaching an already-resumed lifecycle: ARSceneView
                    // may create and configure its session synchronously when the observer is added.
                    sv.lifecycle = lifecycleOwner.lifecycle
                }
            }
        )

        // ── Top gradient ────────────────────────────────────────────────
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(140.dp)
                .align(Alignment.TopCenter)
                .background(
                    Brush.verticalGradient(
                        colors = listOf(Color(0xCC000000), Color.Transparent)
                    )
                )
        )

        // ── Top Bar ─────────────────────────────────────────────────────
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .statusBarsPadding()
                .padding(horizontal = 16.dp, vertical = 12.dp)
                .align(Alignment.TopStart),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment     = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(Color(0x44000000))
                    .border(1.dp, Color.White.copy(alpha = 0.16f), CircleShape)
                    .clickable(onClick = onExit),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                    contentDescription = "Close AR",
                    tint = Color.White,
                    modifier = Modifier.size(21.dp)
                )
            }

            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text(
                    "AR Visualizer",
                    color      = Color.White,
                    style      = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    projectType.replaceFirstChar { it.uppercase() } + " · $propertySize sq m · ${placedModels.size}/5",
                    color = Color(0xAAFFFFFF),
                    style = MaterialTheme.typography.bodySmall
                )
            }

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Box(
                    modifier = Modifier
                        .size(44.dp)
                        .clip(CircleShape)
                        .background(Color(0x44000000))
                        .border(1.dp, Color.White.copy(alpha = 0.16f), CircleShape)
                        .clickable { captureScreenshot() },
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.CameraAlt,
                        contentDescription = "Save AR photo",
                        tint = Color.White,
                        modifier = Modifier.size(20.dp)
                    )
                }
                Box(
                    modifier = Modifier
                        .size(44.dp)
                        .clip(CircleShape)
                        .background(Color(0x44000000))
                        .border(1.dp, Color.White.copy(alpha = 0.16f), CircleShape)
                        .clickable { showInfoPanel = !showInfoPanel },
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.Info,
                        contentDescription = if (showInfoPanel) "Hide design info" else "Show design info",
                        tint = if (showInfoPanel) Brand100 else Color.White,
                        modifier = Modifier.size(20.dp)
                    )
                }
            }
        }

        // ── Repositioning mode banner ───────────────────────────────────
        AnimatedVisibility(
            visible = arSessionMessage != null,
            modifier = Modifier
                .align(Alignment.Center)
                .padding(horizontal = 28.dp),
            enter = fadeIn(),
            exit = fadeOut()
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier
                    .background(Color(0xCC000000), RoundedCornerShape(16.dp))
                    .padding(22.dp)
            ) {
                CircularProgressIndicator(
                    color = Color.White,
                    modifier = Modifier.size(32.dp),
                    strokeWidth = 3.dp
                )
                Spacer(Modifier.height(14.dp))
                Text(
                    arSessionMessage ?: "",
                    color = Color.White,
                    fontSize = 13.sp,
                    lineHeight = 18.sp,
                    textAlign = TextAlign.Center
                )
            }
        }

        AnimatedVisibility(
            visible  = repositioningModel != null,
            modifier = Modifier
                .align(Alignment.TopCenter)
                .statusBarsPadding()
                .padding(top = 64.dp),
            enter = fadeIn() + slideInVertically(initialOffsetY = { -it }),
            exit  = fadeOut() + slideOutVertically(targetOffsetY = { -it })
        ) {
            Row(
                modifier = Modifier
                    .padding(horizontal = 12.dp)
                    .background(Brand700.copy(alpha = 0.96f), RoundedCornerShape(18.dp))
                    .border(1.dp, Color.White.copy(alpha = .14f), RoundedCornerShape(18.dp))
                    .padding(start = 14.dp, end = 6.dp, top = 6.dp, bottom = 6.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Icon(
                    imageVector = Icons.Default.TouchApp,
                    contentDescription = null,
                    tint = Color.White,
                    modifier = Modifier.size(18.dp)
                )
                Text(
                    "Drag to move · Release to place",
                    color      = Color.White,
                    fontSize   = 12.sp,
                    fontWeight = FontWeight.Medium,
                    modifier = Modifier.weight(1f, fill = false)
                )
                TextButton(
                    onClick = { cancelMoveMode() },
                    modifier = Modifier.heightIn(min = 44.dp),
                    colors = ButtonDefaults.textButtonColors(contentColor = Color.White)
                ) {
                    Text("Cancel", fontWeight = FontWeight.Bold, fontSize = 12.sp)
                }
            }
        }

        // One prioritized notice prevents offline, screenshot, and error messages
        // from covering each other at the top of the camera view.
        AnimatedVisibility(
            visible  = noticeMessage != null,
            modifier = Modifier
                .align(Alignment.TopCenter)
                .statusBarsPadding()
                .padding(
                    start = 16.dp,
                    end = 16.dp,
                    top = if (repositioningModel != null) 120.dp else 68.dp
                ),
            enter = fadeIn() + slideInVertically(initialOffsetY = { -it }),
            exit  = fadeOut() + slideOutVertically(targetOffsetY = { -it })
        ) {
            Row(
                modifier = Modifier
                    .background(noticeColor.copy(alpha = .96f), RoundedCornerShape(18.dp))
                    .border(1.dp, Color.White.copy(alpha = .16f), RoundedCornerShape(18.dp))
                    .clickable(enabled = error != null) { viewModel.clearError() }
                    .padding(horizontal = 16.dp, vertical = 11.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Icon(
                    imageVector = noticeIcon,
                    contentDescription = null,
                    tint = Color.White,
                    modifier = Modifier.size(18.dp)
                )
                Text(
                    noticeMessage ?: "",
                    color      = Color.White,
                    fontSize   = 12.sp,
                    lineHeight = 17.sp,
                    fontWeight = FontWeight.SemiBold
                )
            }
        }

        // ── Loading spinner ─────────────────────────────────────────────
        AnimatedVisibility(
            visible  = isLoading,
            modifier = Modifier.align(Alignment.Center),
            enter    = fadeIn(),
            exit     = fadeOut()
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier
                    .background(Color(0xAA000000), RoundedCornerShape(16.dp))
                    .padding(28.dp)
            ) {
                CircularProgressIndicator(
                    color       = Color.White,
                    modifier    = Modifier.size(36.dp),
                    strokeWidth = 3.dp
                )
                Spacer(Modifier.height(14.dp))
                Text(
                    if (isViewModelLoading) {
                        "Loading products..."
                    } else {
                        placementStage.progressMessage
                    },
                    color = Color.White,
                    style = MaterialTheme.typography.bodySmall
                )
            }
        }

        // ── Instruction overlay (before first placement) ────────────────
        AnimatedVisibility(
            visible  = coachVisible,
            modifier = Modifier
                .align(Alignment.Center)
                .padding(horizontal = 24.dp, vertical = 150.dp),
            enter = fadeIn(),
            exit  = fadeOut()
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier
                    .widthIn(max = 340.dp)
                    .background(Brand900.copy(alpha = 0.94f), RoundedCornerShape(24.dp))
                    .border(1.dp, Color.White.copy(alpha = 0.14f), RoundedCornerShape(24.dp))
                    .padding(24.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(56.dp)
                        .clip(CircleShape)
                        .background(Color(0x33FFFFFF)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.TouchApp,
                        contentDescription = null,
                        tint = Color.White,
                        modifier = Modifier.size(26.dp)
                    )
                }
                Spacer(Modifier.height(12.dp))
                Text(
                    "Point at your garden floor",
                    color      = Color.White,
                    style      = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold
                )
                Spacer(Modifier.height(4.dp))
                Text(
                    "Choose an element below, aim at a detected surface, then press Place.",
                    color     = Color(0xAAFFFFFF),
                    style     = MaterialTheme.typography.bodySmall,
                    textAlign = TextAlign.Center
                )
                Spacer(Modifier.height(6.dp))
                Text(
                    "Tap a placed item for details. Press and hold it to move.",
                    color     = Color(0x88FFFFFF),
                    style     = MaterialTheme.typography.bodySmall,
                    textAlign = TextAlign.Center
                )
                Spacer(Modifier.height(18.dp))
                Button(
                    onClick = { showPlacementCoach = false },
                    modifier = Modifier
                        .fillMaxWidth()
                        .heightIn(min = 48.dp)
                        .onGloballyPositioned { coordinates ->
                            coachButtonBoundsRef.value = coordinates.boundsInRoot()
                        },
                    shape = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color.White,
                        contentColor = Brand900
                    )
                ) {
                    Text("Start placing", fontWeight = FontWeight.Bold)
                }
            }
        }

        // Show the placement target only after the short coach has been dismissed.
        AnimatedVisibility(
            visible = placeButtonVisible,
            modifier = Modifier.align(Alignment.Center),
            enter = fadeIn(),
            exit = fadeOut()
        ) {
            PlacementReticle(
                productName = selectedProduct?.name,
                isSurfaceReady = isPlacementSurfaceReady,
                isPreviewReady = previewStage == PreviewStage.Ready,
                canPlace = canConfirmPlacement(placementControlState()),
                onPlace = { placePreviewAtCrosshair() },
                onPlaceBoundsChanged = { bounds -> placeButtonBoundsRef.value = bounds },
                onTurnPreview = { turnPreview180() },
                onTurnBoundsChanged = { bounds -> turnPreviewButtonBoundsRef.value = bounds },
            )
        }

        AnimatedVisibility(
            visible = arSessionMessage == null &&
                shouldShowArEmptyState(products, isLoading) &&
                error == null,
            modifier = Modifier
                .align(Alignment.Center)
                .padding(24.dp),
            enter = fadeIn(),
            exit = fadeOut()
        ) {
            EmptyCatalogMessage(
                isOffline = isOffline,
                onBack = onBackToWeb,
            )
        }

        AnimatedVisibility(
            visible  = !canPlace,
            modifier = Modifier
                .align(Alignment.Center)
                .padding(top = 80.dp),
            enter = fadeIn(),
            exit  = fadeOut()
        ) {
            Text(
                "Maximum 5 items placed",
                modifier = Modifier
                    .background(Color(0xE6E19A24), RoundedCornerShape(24.dp))
                    .padding(horizontal = 20.dp, vertical = 10.dp),
                color      = Color.White,
                fontSize   = 12.sp,
                fontWeight = FontWeight.Medium
            )
        }

        // ── Bottom: model picker + info panel ───────────────────────────
        Column(
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .fillMaxWidth()
        ) {
            // Show product catalog drawer from ViewModel products
            if (arSessionMessage == null && products.isNotEmpty()) {
                CatalogDrawer(
                    products       = products,
                    selectedId     = selectedProduct?.id,
                    isOffline      = isOffline,
                    isModelLoading = placementStage != ModelPlacementStage.Idle,
                    onSelect       = { product ->
                        val nextProduct = toggleSelectedProduct(
                            current = selectedProduct,
                            tapped = product,
                        )
                        if (nextProduct == null) viewModel.clearSelectedProduct()
                        else viewModel.selectProduct(nextProduct)
                    }
                )
            }

            AnimatedVisibility(
                visible = showInfoPanel && hasPlacedModels,
                enter   = slideInVertically(initialOffsetY = { it }),
                exit    = slideOutVertically(targetOffsetY = { it })
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(Color.White)
                        .padding(20.dp)
                        .navigationBarsPadding()
                ) {
                    Box(
                        modifier = Modifier
                            .width(36.dp)
                            .height(4.dp)
                            .clip(RoundedCornerShape(2.dp))
                            .background(Surface200)
                            .align(Alignment.CenterHorizontally)
                    )
                    Spacer(Modifier.height(14.dp))

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment     = Alignment.CenterVertically
                    ) {
                        Text(
                            "Design Preview",
                            style      = MaterialTheme.typography.titleMedium,
                            color      = Surface900,
                            fontWeight = FontWeight.SemiBold
                        )
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(
                                "${placedModels.size}/5 placed",
                                style      = MaterialTheme.typography.bodySmall,
                                color      = Surface400,
                                fontWeight = FontWeight.Medium
                            )
                            Spacer(Modifier.width(4.dp))
                            TextButton(
                                onClick = { showInfoPanel = false },
                                modifier = Modifier.heightIn(min = 40.dp),
                                colors = ButtonDefaults.textButtonColors(contentColor = Brand700)
                            ) {
                                Text("Hide", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                    Spacer(Modifier.height(12.dp))

                    Row(
                        modifier              = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        InfoChip(label = "Type",  value = projectType.replaceFirstChar { it.uppercase() }, modifier = Modifier.weight(1f))
                        InfoChip(label = "Area",  value = "$propertySize sq m", modifier = Modifier.weight(1f))
                        if (estimatedCost != null) {
                            InfoChip(label = "Est. Cost", value = "₱$estimatedCost", modifier = Modifier.weight(1f))
                        }
                    }

                    Spacer(Modifier.height(8.dp))
                    Text(
                        "💡 Long press any placed element to drag and reposition it",
                        fontSize = 11.sp,
                        color    = Color(0xFF6B7280)
                    )
                    Spacer(Modifier.height(12.dp))

                    Row(
                        horizontalArrangement = Arrangement.spacedBy(10.dp),
                        modifier              = Modifier.fillMaxWidth()
                    ) {
                        OutlinedButton(
                            onClick        = { resetScene() },
                            modifier       = Modifier.weight(1f),
                            shape          = RoundedCornerShape(12.dp),
                            colors         = ButtonDefaults.outlinedButtonColors(contentColor = Surface700),
                            contentPadding = PaddingValues(vertical = 12.dp)
                        ) {
                            Text("Reset", fontWeight = FontWeight.Medium, fontSize = 13.sp)
                        }
                        Button(
                            onClick        = onBackToWeb,
                            modifier       = Modifier.weight(1f),
                            shape          = RoundedCornerShape(12.dp),
                            colors         = ButtonDefaults.buttonColors(containerColor = Surface900, contentColor = Color.White),
                            contentPadding = PaddingValues(vertical = 12.dp)
                        ) {
                            Text("Back to App", fontWeight = FontWeight.Medium, fontSize = 13.sp)
                        }
                    }
                }
            }

            if (!showInfoPanel || !hasPlacedModels) {
                Spacer(Modifier.navigationBarsPadding())
            }
        }

        // ── Product Info Panel overlay (tap placed model → info → add to cart) ──
        ProductInfoPanel(
            placedModel = selectedInfoModel,
            cartActionState = cartActionState,
            onAddToCart = { product -> viewModel.addToCart(product) },
            onCartActionConsumed = { viewModel.clearCartAction() },
            onMove = { placed -> startMoveMode(placed) },
            onTurn = { placed -> turnPlacedModel180(placed) },
            onDelete = { placed -> deletePlacedModel(placed) },
            onDismiss = { viewModel.dismissProductInfo() }
        )
    }
}



// ─── Info chip ─────────────────────────────────────────────────────────────────

@Composable
private fun InfoChip(label: String, value: String, modifier: Modifier = Modifier) {
    Column(
        modifier = modifier
            .background(Surface50, RoundedCornerShape(10.dp))
            .border(1.dp, Surface100, RoundedCornerShape(10.dp))
            .padding(12.dp)
    ) {
        Text(label, style = MaterialTheme.typography.labelSmall, color = Surface400, fontSize = 10.sp)
        Spacer(Modifier.height(2.dp))
        Text(value, style = MaterialTheme.typography.bodyMedium, color = Surface900, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
    }
}

@Composable
private fun PlacementReticle(
    productName: String?,
    isSurfaceReady: Boolean,
    isPreviewReady: Boolean,
    canPlace: Boolean,
    onPlace: () -> Unit,
    onPlaceBoundsChanged: (Rect?) -> Unit,
    onTurnPreview: () -> Unit,
    onTurnBoundsChanged: (Rect?) -> Unit,
) {
    val reticleColor = if (isSurfaceReady) Color(0xFF4ADE80) else Color(0xFFF5B942)
    val instruction = when {
        productName == null -> "Select an element to place"
        !isPreviewReady -> "Preparing a real-size preview of $productName"
        isSurfaceReady && canPlace -> "Aim ready · Place $productName at the crosshair"
        isSurfaceReady -> "Placement limit reached"
        else -> "Move slowly and aim at the ground"
    }

    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Box(contentAlignment = Alignment.Center) {
            Box(
                modifier = Modifier
                    .size(74.dp)
                    .border(3.dp, reticleColor, CircleShape)
            )
            Box(
                modifier = Modifier
                    .size(12.dp)
                    .clip(CircleShape)
                    .background(reticleColor)
                    .border(2.dp, Color.White, CircleShape)
            )
        }
        Spacer(Modifier.height(12.dp))
        Text(
            text = instruction,
            modifier = Modifier
                .background(Color(0xAA000000), RoundedCornerShape(18.dp))
                .padding(horizontal = 14.dp, vertical = 8.dp),
            color = Color.White,
            fontSize = 12.sp,
            fontWeight = FontWeight.Medium,
            textAlign = TextAlign.Center
        )
        Spacer(Modifier.height(12.dp))
        Button(
            onClick = onPlace,
            enabled = canPlace,
            modifier = Modifier
                .heightIn(min = 48.dp)
                .widthIn(min = 160.dp)
                .onGloballyPositioned { coordinates ->
                    onPlaceBoundsChanged(coordinates.boundsInRoot())
                },
            shape = RoundedCornerShape(14.dp),
            colors = ButtonDefaults.buttonColors(
                containerColor = Brand500,
                contentColor = Color.White,
                disabledContainerColor = Color(0x66000000),
                disabledContentColor = Color(0x99FFFFFF),
            ),
        ) {
            Text("Place", fontWeight = FontWeight.Bold)
        }
        if (isPreviewReady) {
            Spacer(Modifier.height(8.dp))
            OutlinedButton(
                onClick = onTurnPreview,
                modifier = Modifier
                    .heightIn(min = 48.dp)
                    .widthIn(min = 160.dp)
                    .onGloballyPositioned { coordinates ->
                        onTurnBoundsChanged(coordinates.boundsInRoot())
                    },
                shape = RoundedCornerShape(14.dp),
                colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White),
                border = androidx.compose.foundation.BorderStroke(
                    1.dp,
                    Color.White.copy(alpha = 0.72f),
                ),
            ) {
                Text("Turn 180°", fontWeight = FontWeight.SemiBold)
            }
        }
    }
}

@Composable
private fun EmptyCatalogMessage(
    isOffline: Boolean,
    onBack: () -> Unit,
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier
            .background(Color(0xAA000000), RoundedCornerShape(16.dp))
            .padding(22.dp)
    ) {
        Text(
            text = AR_EMPTY_CATALOG_TITLE,
            color = Color.White,
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.SemiBold,
            textAlign = TextAlign.Center
        )
        Spacer(Modifier.height(6.dp))
        Text(
            text = if (isOffline) {
                "Connect to the internet once to download AR products."
            } else {
                "AR products will appear here after the catalog has a 3D model."
            },
            color = Color(0xCCFFFFFF),
            style = MaterialTheme.typography.bodySmall,
            textAlign = TextAlign.Center
        )
        Spacer(Modifier.height(16.dp))
        OutlinedButton(
            onClick = onBack,
            colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White),
        ) {
            Text("Back to App", fontWeight = FontWeight.SemiBold)
        }
    }
}
