package com.example.ferosa_landscaping

import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.graphics.Bitmap
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.provider.MediaStore
import android.view.MotionEvent
import android.view.PixelCopy
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.animation.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import com.example.ferosa_landscaping.ui.theme.*
import io.github.sceneview.ar.ARSceneView
import io.github.sceneview.ar.node.AnchorNode
import io.github.sceneview.node.ModelNode
import kotlinx.coroutines.delay

// ─── Garden model catalogue ────────────────────────────────────────────────────
// Each entry describes a placeable 3D element.
// Replace the URLs with your own .glb files for a production demo.

data class GardenModel(
    val id: String,
    val name: String,
    val emoji: String,
    val url: String,
    val scale: Float,
)

private val GARDEN_MODELS = listOf(
    GardenModel(
        id    = "shrub",
        name  = "Shrub",
        emoji = "🌿",
        url   = "https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/main/2.0/Avocado/glTF-Binary/Avocado.glb",
        scale = 0.5f    // 50 cm
    ),
    GardenModel(
        id    = "tree",
        name  = "Tree",
        emoji = "🌳",
        url   = "https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/main/2.0/Avocado/glTF-Binary/Avocado.glb",
        scale = 1.2f    // 1.2 m
    ),
    GardenModel(
        id    = "patio",
        name  = "Patio",
        emoji = "🧱",
        url   = "https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/main/2.0/BoxTextured/glTF-Binary/BoxTextured.glb",
        scale = 0.5f
    ),
    GardenModel(
        id    = "fountain",
        name  = "Fountain",
        emoji = "\u26F2",
        url   = "https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/main/2.0/Duck/glTF-Binary/Duck.glb",
        scale = 0.5f
    ),
    GardenModel(
        id    = "statue",
        name  = "Statue",
        emoji = "🦊",
        url   = "https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/main/2.0/Fox/glTF-Binary/Fox.glb",
        scale = 0.5f
    ),
)

// ─── Screenshot helper ─────────────────────────────────────────────────────────

private fun saveBitmapToGallery(context: Context, bitmap: Bitmap): Boolean {
    return try {
        val values = ContentValues().apply {
            put(MediaStore.Images.Media.DISPLAY_NAME, "Ferosa_AR_${System.currentTimeMillis()}.jpg")
            put(MediaStore.Images.Media.MIME_TYPE, "image/jpeg")
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                put(MediaStore.Images.Media.RELATIVE_PATH, "Pictures/Ferosa")
                put(MediaStore.Images.Media.IS_PENDING, 1)
            }
        }
        val uri = context.contentResolver.insert(
            MediaStore.Images.Media.EXTERNAL_CONTENT_URI, values
        ) ?: return false

        context.contentResolver.openOutputStream(uri)?.use { out ->
            bitmap.compress(Bitmap.CompressFormat.JPEG, 95, out)
        }
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            values.clear()
            values.put(MediaStore.Images.Media.IS_PENDING, 0)
            context.contentResolver.update(uri, values, null, null)
        }
        true
    } catch (_: Exception) {
        false
    }
}

// ─── Activity ──────────────────────────────────────────────────────────────────

class ArActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        val data: Uri? = intent?.data
        val projectType   = data?.getQueryParameter("type") ?: "design"
        val propertySize  = data?.getQueryParameter("size") ?: "100"
        val estimatedCost = data?.getQueryParameter("cost")

        setContent {
            Ferosa_landscapingTheme(darkTheme = false) {
                ArScreen(
                    projectType   = projectType,
                    propertySize  = propertySize,
                    estimatedCost = estimatedCost,
                    onExit        = { finish() },
                    onBackToWeb   = {
                        startActivity(
                            Intent(Intent.ACTION_VIEW, Uri.parse("$SERVER_URL/estimator"))
                        )
                        finish()
                    }
                )
            }
        }
    }
}

// ─── AR Screen ─────────────────────────────────────────────────────────────────

@Composable
private fun ArScreen(
    projectType: String,
    propertySize: String,
    estimatedCost: String?,
    onExit: () -> Unit,
    onBackToWeb: () -> Unit,
) {
    val context = LocalContext.current

    // Currently selected garden element
    var selectedModel by remember { mutableStateOf(GARDEN_MODELS[0]) }

    val modelPlaced   = remember { mutableStateOf(false) }
    val isLoading     = remember { mutableStateOf(false) }
    var showInfoPanel by remember { mutableStateOf(true) }
    val placedCount   = remember { mutableIntStateOf(0) }

    val sceneViewRef  = remember { mutableStateOf<ARSceneView?>(null) }
    val placedNodes   = remember { mutableListOf<AnchorNode>() }

    // Screenshot feedback toast
    var screenshotMsg by remember { mutableStateOf<String?>(null) }
    LaunchedEffect(screenshotMsg) {
        if (screenshotMsg != null) { delay(2500); screenshotMsg = null }
    }

    // Holds the currently selected model URL+scale so the touch listener
    // always reads the latest value (captured by reference via State).
    val selectedModelState = remember { mutableStateOf(GARDEN_MODELS[0]) }
    selectedModelState.value = selectedModel

    fun resetScene() {
        sceneViewRef.value?.let { sv ->
            placedNodes.forEach { node ->
                sv.removeChildNode(node)
                node.anchor.detach()
            }
            placedNodes.clear()
        }
        placedCount.intValue = 0
        modelPlaced.value = false
    }

    fun captureScreenshot() {
        val sv = sceneViewRef.value ?: return
        val bitmap = Bitmap.createBitmap(sv.width, sv.height, Bitmap.Config.ARGB_8888)
        PixelCopy.request(sv, bitmap, { result ->
            screenshotMsg = if (result == PixelCopy.SUCCESS && saveBitmapToGallery(context, bitmap))
                "Screenshot saved to gallery" else "Failed to capture"
        }, Handler(Looper.getMainLooper()))
    }

    Box(modifier = Modifier.fillMaxSize()) {

        // ── AR Camera View ──────────────────────────────────────────────
        AndroidView(
            modifier = Modifier.fillMaxSize(),
            factory = { ctx ->
                ARSceneView(ctx).also { sv ->
                    sceneViewRef.value = sv
                    sv.planeRenderer.isEnabled = true

                    sv.setOnTouchListener { _, event ->
                        if (event.action == MotionEvent.ACTION_UP && !isLoading.value) {
                            val hit = sv.hitTestAR(event.x, event.y)
                                ?: return@setOnTouchListener false
                            val anchor = hit.createAnchor()
                            isLoading.value = true

                            // Read the currently selected model
                            val model = selectedModelState.value

                            sv.modelLoader.loadModelInstanceAsync(
                                fileLocation = model.url
                            ) { modelInstance ->
                                isLoading.value = false
                                modelInstance?.let { instance ->
                                    val anchorNode = AnchorNode(
                                        engine = sv.engine,
                                        anchor = anchor
                                    )
                                    anchorNode.addChildNode(
                                        ModelNode(
                                            modelInstance = instance,
                                            scaleToUnits  = model.scale
                                        )
                                    )
                                    sv.addChildNode(anchorNode)
                                    placedNodes.add(anchorNode)
                                    placedCount.intValue++
                                    modelPlaced.value = true
                                }
                            }
                        }
                        false
                    }
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
                        colors = listOf(Color(0xAA000000), Color.Transparent)
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
            // Back
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .clip(CircleShape)
                    .background(Color(0x33FFFFFF))
                    .clickable(onClick = onExit),
                contentAlignment = Alignment.Center
            ) {
                Text("←", color = Color.White, fontSize = 18.sp, fontWeight = FontWeight.Bold)
            }

            // Title
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text(
                    "AR Visualizer",
                    color      = Color.White,
                    style      = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    projectType.replaceFirstChar { it.uppercase() } + " · $propertySize sq m",
                    color = Color(0xAAFFFFFF),
                    style = MaterialTheme.typography.bodySmall
                )
            }

            // Right buttons: screenshot + info toggle
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                // Screenshot
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(Color(0x33FFFFFF))
                        .clickable { captureScreenshot() },
                    contentAlignment = Alignment.Center
                ) {
                    Text("📷", fontSize = 16.sp)
                }
                // Info toggle
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(Color(0x33FFFFFF))
                        .clickable { showInfoPanel = !showInfoPanel },
                    contentAlignment = Alignment.Center
                ) {
                    Text("i", color = Color.White, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                }
            }
        }

        // ── Screenshot toast ────────────────────────────────────────────
        AnimatedVisibility(
            visible  = screenshotMsg != null,
            modifier = Modifier
                .align(Alignment.TopCenter)
                .statusBarsPadding()
                .padding(top = 64.dp),
            enter = fadeIn() + slideInVertically(initialOffsetY = { -it }),
            exit  = fadeOut()
        ) {
            Text(
                screenshotMsg ?: "",
                modifier = Modifier
                    .background(Color(0xDD000000), RoundedCornerShape(24.dp))
                    .padding(horizontal = 20.dp, vertical = 10.dp),
                color      = Color.White,
                fontSize   = 12.sp,
                fontWeight = FontWeight.Medium
            )
        }

        // ── Loading spinner ─────────────────────────────────────────────
        AnimatedVisibility(
            visible  = isLoading.value,
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
                Text("Loading model…", color = Color.White, style = MaterialTheme.typography.bodySmall)
            }
        }

        // ── Instruction overlay (before first placement) ────────────────
        AnimatedVisibility(
            visible  = !modelPlaced.value && !isLoading.value,
            modifier = Modifier
                .align(Alignment.Center)
                .padding(bottom = 100.dp),       // leave room for model picker
            enter = fadeIn(),
            exit  = fadeOut()
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier
                    .background(Color(0xAA000000), RoundedCornerShape(16.dp))
                    .padding(24.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(56.dp)
                        .clip(CircleShape)
                        .background(Color(0x33FFFFFF)),
                    contentAlignment = Alignment.Center
                ) {
                    Text("👆", fontSize = 24.sp)
                }
                Spacer(Modifier.height(12.dp))
                Text(
                    "Point at a flat surface",
                    color      = Color.White,
                    style      = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold
                )
                Spacer(Modifier.height(4.dp))
                Text(
                    "Select an element below, then tap to place",
                    color     = Color(0xAAFFFFFF),
                    style     = MaterialTheme.typography.bodySmall,
                    textAlign = TextAlign.Center
                )
            }
        }

        // ── Bottom panel: model picker + info ───────────────────────────
        Column(
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .fillMaxWidth()
        ) {
            // Model picker strip (always visible)
            ModelPickerStrip(
                models     = GARDEN_MODELS,
                selectedId = selectedModel.id,
                onSelect   = { selectedModel = it }
            )

            // Info panel (visible after placement, togglable)
            AnimatedVisibility(
                visible = showInfoPanel && modelPlaced.value,
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
                    // Drag handle
                    Box(
                        modifier = Modifier
                            .width(36.dp)
                            .height(4.dp)
                            .clip(RoundedCornerShape(2.dp))
                            .background(Surface200)
                            .align(Alignment.CenterHorizontally)
                    )
                    Spacer(Modifier.height(14.dp))

                    // Header row
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
                        Text(
                            "${placedCount.intValue} placed",
                            style      = MaterialTheme.typography.bodySmall,
                            color      = Surface400,
                            fontWeight = FontWeight.Medium
                        )
                    }
                    Spacer(Modifier.height(12.dp))

                    // Info chips
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
                    Spacer(Modifier.height(16.dp))

                    // Action buttons
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
                            Text("Back to Website", fontWeight = FontWeight.Medium, fontSize = 13.sp)
                        }
                    }
                }
            }

            // Nav-bar spacer when info panel is hidden
            if (!showInfoPanel || !modelPlaced.value) {
                Spacer(Modifier.navigationBarsPadding())
            }
        }
    }
}

// ─── Model picker strip ────────────────────────────────────────────────────────

@Composable
private fun ModelPickerStrip(
    models: List<GardenModel>,
    selectedId: String,
    onSelect: (GardenModel) -> Unit,
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .background(
                Color(0xDD1a1a1a),
                RoundedCornerShape(topStart = 20.dp, topEnd = 20.dp)
            )
            .padding(top = 14.dp, bottom = 10.dp)
    ) {
        Text(
            "Select element to place",
            color    = Color(0x99FFFFFF),
            fontSize = 10.sp,
            fontWeight = FontWeight.Medium,
            letterSpacing = 0.5.sp,
            modifier = Modifier.padding(horizontal = 20.dp)
        )
        Spacer(Modifier.height(10.dp))

        LazyRow(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            contentPadding        = PaddingValues(horizontal = 16.dp)
        ) {
            items(models, key = { it.id }) { model ->
                val selected = model.id == selectedId
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    modifier = Modifier
                        .clip(RoundedCornerShape(14.dp))
                        .background(if (selected) Brand600 else Color(0x22FFFFFF))
                        .then(
                            if (selected) Modifier.border(
                                2.dp, Brand400, RoundedCornerShape(14.dp)
                            ) else Modifier
                        )
                        .clickable { onSelect(model) }
                        .padding(horizontal = 18.dp, vertical = 12.dp)
                        .widthIn(min = 68.dp)
                ) {
                    Text(model.emoji, fontSize = 24.sp)
                    Spacer(Modifier.height(4.dp))
                    Text(
                        model.name,
                        color      = if (selected) Color.White else Color(0xAAFFFFFF),
                        fontSize   = 10.sp,
                        fontWeight = if (selected) FontWeight.SemiBold else FontWeight.Normal,
                        textAlign  = TextAlign.Center
                    )
                }
            }
        }
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
