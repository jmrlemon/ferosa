package com.example.ferosa_landscaping

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Calculate
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Construction
import androidx.compose.material.icons.filled.ContentCut
import androidx.compose.material.icons.filled.LocalFlorist
import androidx.compose.material.icons.filled.ViewInAr
import androidx.compose.material.icons.filled.ZoomIn
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CheckboxDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.TransformOrigin
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import coil.compose.AsyncImage
import com.example.ferosa_landscaping.data.api.ApiClient
import com.example.ferosa_landscaping.data.repository.SummaryRepository
import com.example.ferosa_landscaping.ui.estimator.BUNDLED_RATE_CARD
import com.example.ferosa_landscaping.ui.estimator.RateCard
import com.example.ferosa_landscaping.ui.estimator.toRateCard
import com.example.ferosa_landscaping.ui.format.formatPeso
import com.example.ferosa_landscaping.ui.shell.ArLaunchRequest
import com.example.ferosa_landscaping.ui.theme.Brand50
import com.example.ferosa_landscaping.ui.theme.Brand100
import com.example.ferosa_landscaping.ui.theme.Brand600
import com.example.ferosa_landscaping.ui.theme.Brand700
import com.example.ferosa_landscaping.ui.theme.Surface0
import com.example.ferosa_landscaping.ui.theme.Surface100
import com.example.ferosa_landscaping.ui.theme.Surface200
import com.example.ferosa_landscaping.ui.theme.Surface400
import com.example.ferosa_landscaping.ui.theme.Surface500
import com.example.ferosa_landscaping.ui.theme.Surface600
import com.example.ferosa_landscaping.ui.theme.Surface800
import com.example.ferosa_landscaping.ui.theme.Surface900
import java.text.NumberFormat
import kotlin.math.roundToInt

/*
 * The rates, tiers and add-ons that used to be hardcoded here were a second
 * source of truth alongside the web estimator, and the two had already drifted
 * (the web offered a 5,000 sq m quick pick, this screen stopped at 2,000;
 * several descriptions differed word for word). They now come from the server's
 * config/estimator.php over /api/mobile/estimator-rates, with BUNDLED_RATE_CARD
 * as the offline fallback.
 *
 * The aliases keep the existing composable signatures in this file unchanged.
 */
private typealias NativeProjectType = RateCard.ProjectType
private typealias NativeTier = RateCard.Tier
private typealias NativeAddon = RateCard.Addon

private fun peso(value: Double): String = formatPeso(value)

/**
 * Serves the bundled rate card immediately, then swaps in the server's copy if
 * it can be fetched - so the estimator is usable offline and still reflects a
 * price change made on the server without an app release.
 */
@Composable
private fun rememberRateCard(): RateCard {
    var rateCard by remember { mutableStateOf(BUNDLED_RATE_CARD) }
    LaunchedEffect(Unit) {
        SummaryRepository(ApiClient.service).estimatorRates()?.let {
            rateCard = it.toRateCard()
        }
    }
    return rateCard
}

@Composable
fun NativeEstimatorScreen(
    modifier: Modifier = Modifier,
    onBook: () -> Unit,
    onOpenAr: (ArLaunchRequest) -> Unit,
) {
    val rateCard = rememberRateCard()

    var projectKey by rememberSaveable { mutableStateOf(rateCard.defaultProjectType) }
    var sizeText by rememberSaveable { mutableStateOf(rateCard.defaultSize.toString()) }
    var tierKey by rememberSaveable { mutableStateOf(rateCard.defaultTier) }
    var selectedAddons by remember { mutableStateOf(emptySet<String>()) }
    var showZoom by rememberSaveable { mutableStateOf(false) }

    // Falls back to the first entry when a saved key is missing from a rate card
    // the server has since changed.
    val project = rateCard.projectType(projectKey)
    val tier = rateCard.tier(tierKey)
    val area = sizeText.toIntOrNull()?.coerceAtLeast(0) ?: 0
    val base = project.rate * area * tier.multiplier
    val addonsTotal = rateCard.addons.filter { it.key in selectedAddons }.sumOf { it.amount }
    val total = base + addonsTotal

    if (showZoom) {
        PackageZoomDialog(tier = tier, onDismiss = { showZoom = false })
    }

    LazyColumn(
        modifier = modifier
            .fillMaxSize()
            .background(Color(0xFFF8F7F3)),
        contentPadding = PaddingValues(horizontal = 18.dp, vertical = 16.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        item(key = "header") {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        "COST ESTIMATOR",
                        style = MaterialTheme.typography.labelSmall,
                        color = Brand600,
                        fontWeight = FontWeight.Bold,
                        letterSpacing = 1.1.sp,
                    )
                    Spacer(Modifier.height(4.dp))
                    Text(
                        "Plan your project",
                        style = MaterialTheme.typography.headlineSmall,
                        color = Surface900,
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        "Adjust the options and see your estimate instantly.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = Surface500,
                    )
                }
                Surface(color = Brand50, shape = RoundedCornerShape(14.dp)) {
                    Icon(
                        Icons.Default.Calculate,
                        contentDescription = null,
                        tint = Brand700,
                        modifier = Modifier.padding(12.dp).size(24.dp),
                    )
                }
            }
        }

        item(key = "estimate-hero") {
            EstimateHero(
                total = total,
                project = project,
                tier = tier,
                area = area,
                rangeLow = rateCard.rangeLow,
                rangeHigh = rateCard.rangeHigh,
            )
        }

        item(key = "project-type") {
            EstimatorSection(
                step = "1",
                title = "Project type",
                subtitle = "What are you planning?",
            ) {
                Column(verticalArrangement = Arrangement.spacedBy(9.dp)) {
                    rateCard.projectTypes.forEach { option ->
                        ProjectTypeOption(
                            option = option,
                            selected = option.key == projectKey,
                            onClick = { projectKey = option.key },
                        )
                    }
                }
            }
        }

        item(key = "property-size") {
            EstimatorSection(
                step = "2",
                title = "Property size",
                subtitle = "Enter the area in square metres.",
            ) {
                OutlinedTextField(
                    value = sizeText,
                    onValueChange = { value -> sizeText = value.filter(Char::isDigit).take(7) },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Area") },
                    suffix = { Text("sq m") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    shape = RoundedCornerShape(12.dp),
                    isError = area <= 0,
                    supportingText = if (area <= 0) ({ Text("Enter an area greater than zero.") }) else null,
                )
                Spacer(Modifier.height(10.dp))
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    rateCard.quickSizes.forEach { quickSize ->
                    FilterChip(
                        selected = area == quickSize,
                        onClick = { sizeText = quickSize.toString() },
                        label = { Text(NumberFormat.getIntegerInstance().format(quickSize)) },
                        colors = FilterChipDefaults.filterChipColors(
                            containerColor = Surface0,
                            labelColor = Surface600,
                            selectedContainerColor = Brand50,
                            selectedLabelColor = Brand700,
                        ),
                    )
                    }
                }
            }
        }

        item(key = "quality-tier") {
            EstimatorSection(
                step = "3",
                title = "Quality tier",
                subtitle = "Choose the finish level.",
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    rateCard.tiers.forEach { option ->
                        TierOption(
                            tier = option,
                            selected = option.key == tierKey,
                            modifier = Modifier.weight(1f),
                            onClick = { tierKey = option.key },
                        )
                    }
                }
                Spacer(Modifier.height(10.dp))
                Surface(color = Brand50, shape = RoundedCornerShape(10.dp)) {
                    Text(
                        tier.description,
                        modifier = Modifier.fillMaxWidth().padding(12.dp),
                        style = MaterialTheme.typography.bodySmall,
                        color = Brand700,
                    )
                }
            }
        }

        item(key = "addons") {
            EstimatorSection(
                step = "4",
                title = "Extra features",
                subtitle = if (selectedAddons.isEmpty()) "Optional add-ons" else "${selectedAddons.size} selected",
            ) {
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    rateCard.addons.forEach { addon ->
                        AddonOption(
                            addon = addon,
                            selected = addon.key in selectedAddons,
                            onToggle = {
                                selectedAddons = if (addon.key in selectedAddons) {
                                    selectedAddons - addon.key
                                } else {
                                    selectedAddons + addon.key
                                }
                            },
                        )
                    }
                }
            }
        }

        item(key = "generated-package") {
            GeneratedPackageCard(
                tier = tier,
                area = area,
                project = project,
                selectedAddons = rateCard.addons.filter { it.key in selectedAddons },
                onZoom = { showZoom = true },
            )
        }

        item(key = "breakdown") {
            EstimateBreakdown(
                project = project,
                tier = tier,
                area = area,
                base = base,
                addons = rateCard.addons.filter { it.key in selectedAddons },
                total = total,
                onBook = onBook,
                // Every AR entry point used to send a hardcoded
                // `ferosa://ar?designId=demo`, so ArActivity fell back to its
                // defaults and the header read "Design · 100 sq m" whatever the
                // customer had configured. The web estimator has always passed
                // these through; this is the native equivalent.
                onOpenAr = {
                    onOpenAr(
                        ArLaunchRequest(
                            projectType = project.key,
                            propertySize = area,
                            estimatedCost = total.roundToInt().toLong(),
                        )
                    )
                },
            )
        }

        item(key = "disclaimer") {
            Text(
                "Estimates are indicative only. A site visit may be required for an accurate quotation.",
                modifier = Modifier.fillMaxWidth().padding(horizontal = 10.dp, vertical = 6.dp),
                style = MaterialTheme.typography.bodySmall,
                color = Surface400,
            )
        }
    }
}

@Composable
private fun EstimateHero(
    total: Double,
    project: NativeProjectType,
    tier: NativeTier,
    area: Int,
    rangeLow: Double,
    rangeHigh: Double,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Surface900),
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .background(Brush.linearGradient(listOf(Surface900, Color(0xFF123C29), Brand700)))
                .padding(20.dp),
        ) {
            Text("YOUR ESTIMATE", color = Color.White.copy(alpha = .6f), fontSize = 11.sp, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(5.dp))
            Text(peso(total), color = Color.White, fontSize = 34.sp, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(8.dp))
            Text(
                "${project.label}  |  $area sq m  |  ${tier.label}",
                color = Color.White.copy(alpha = .82f),
                style = MaterialTheme.typography.bodySmall,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Spacer(Modifier.height(10.dp))
            Text(
                "Typical range ${peso(total * rangeLow)} - ${peso(total * rangeHigh)}",
                color = Color.White.copy(alpha = .62f),
                style = MaterialTheme.typography.labelMedium,
            )
        }
    }
}

@Composable
private fun EstimatorSection(
    step: String,
    title: String,
    subtitle: String,
    content: @Composable () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = Surface0),
        border = BorderStroke(1.dp, Surface100),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Surface(color = Brand600, shape = CircleShape) {
                    Text(
                        step,
                        modifier = Modifier.padding(horizontal = 9.dp, vertical = 5.dp),
                        color = Color.White,
                        fontWeight = FontWeight.Bold,
                        fontSize = 11.sp,
                    )
                }
                Spacer(Modifier.width(10.dp))
                Column {
                    Text(title, style = MaterialTheme.typography.titleMedium, color = Surface900, fontWeight = FontWeight.Bold)
                    Text(subtitle, style = MaterialTheme.typography.bodySmall, color = Surface400)
                }
            }
            Spacer(Modifier.height(14.dp))
            content()
        }
    }
}

@Composable
private fun ProjectTypeOption(
    option: NativeProjectType,
    selected: Boolean,
    onClick: () -> Unit,
) {
    Surface(
        modifier = Modifier.fillMaxWidth().clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        color = if (selected) Brand50 else Surface0,
        border = BorderStroke(if (selected) 2.dp else 1.dp, if (selected) Brand600 else Surface200),
    ) {
        Row(
            modifier = Modifier.padding(13.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(color = if (selected) Brand600 else Surface100, shape = RoundedCornerShape(9.dp)) {
                Icon(
                    // Every option used to draw the same calculator, which made the
                    // three project types read as one repeated row.
                    when (option.key) {
                        "design" -> Icons.Default.LocalFlorist
                        "maintenance" -> Icons.Default.ContentCut
                        "hardscaping" -> Icons.Default.Construction
                        else -> Icons.Default.Calculate
                    },
                    contentDescription = null,
                    tint = if (selected) Color.White else Surface600,
                    modifier = Modifier.padding(8.dp).size(18.dp),
                )
            }
            Spacer(Modifier.width(11.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(option.label, fontWeight = FontWeight.SemiBold, color = Surface900)
                Text(option.description, style = MaterialTheme.typography.bodySmall, color = Surface500, maxLines = 2)
            }
            Spacer(Modifier.width(8.dp))
            Column(horizontalAlignment = Alignment.End) {
                Text("${peso(option.rate.toDouble())}/m\u00B2", fontWeight = FontWeight.Bold, color = Brand700, fontSize = 12.sp)
                if (selected) Icon(Icons.Default.Check, contentDescription = "Selected", tint = Brand600, modifier = Modifier.size(18.dp))
            }
        }
    }
}

@Composable
private fun TierOption(
    tier: NativeTier,
    selected: Boolean,
    modifier: Modifier = Modifier,
    onClick: () -> Unit,
) {
    Surface(
        modifier = modifier.clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        color = if (selected) Brand50 else Surface0,
        border = BorderStroke(if (selected) 2.dp else 1.dp, if (selected) Brand600 else Surface200),
    ) {
        Column(
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 12.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            if (selected) {
                Icon(Icons.Default.Check, contentDescription = null, tint = Brand600, modifier = Modifier.size(17.dp))
            } else {
                Box(Modifier.size(17.dp).border(2.dp, Surface200, CircleShape))
            }
            Spacer(Modifier.height(6.dp))
            Text(tier.label, fontWeight = FontWeight.SemiBold, fontSize = 12.sp, maxLines = 1)
            Text("${tier.multiplier}x", color = if (selected) Brand700 else Surface500, fontSize = 11.sp, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
private fun AddonOption(
    addon: NativeAddon,
    selected: Boolean,
    onToggle: () -> Unit,
) {
    Row(
        modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(10.dp)).clickable(onClick = onToggle).padding(vertical = 7.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Checkbox(
            checked = selected,
            onCheckedChange = { onToggle() },
            colors = CheckboxDefaults.colors(checkedColor = Brand600),
        )
        Column(modifier = Modifier.weight(1f)) {
            Text(addon.label, fontWeight = FontWeight.Medium, color = Surface800, fontSize = 14.sp)
            Text(addon.description, style = MaterialTheme.typography.bodySmall, color = Surface400, maxLines = 1)
        }
        Spacer(Modifier.width(8.dp))
        Text("+${peso(addon.amount.toDouble())}", color = Brand700, fontWeight = FontWeight.Bold, fontSize = 12.sp)
    }
}

@Composable
private fun GeneratedPackageCard(
    tier: NativeTier,
    area: Int,
    project: NativeProjectType,
    selectedAddons: List<NativeAddon>,
    onZoom: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = Surface0),
        border = BorderStroke(1.dp, Surface100),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Column {
                    Text("GENERATED PACKAGE", color = Surface400, fontSize = 10.sp, fontWeight = FontWeight.Bold)
                    Text("${project.label} Package", color = Surface900, fontWeight = FontWeight.Bold)
                }
                Surface(color = Brand50, shape = CircleShape) {
                    Text(tier.label, modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp), color = Brand700, fontSize = 11.sp, fontWeight = FontWeight.Bold)
                }
            }
            Spacer(Modifier.height(12.dp))
            PackageImage(tier = tier, modifier = Modifier.fillMaxWidth().aspectRatio(3f / 4f), onClick = onZoom)
            Spacer(Modifier.height(13.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                PackageStat("Area", "$area sq m", Modifier.weight(1f))
                PackageStat("Features", "${1 + selectedAddons.size} items", Modifier.weight(1f))
            }
            Spacer(Modifier.height(13.dp))
            Text("INCLUDES", color = Surface400, fontSize = 10.sp, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(7.dp))
            tier.examples.forEach { example -> PackageLine(example) }
            selectedAddons.forEach { addon -> PackageLine(addon.label) }
        }
    }
}

@Composable
private fun PackageImage(
    tier: NativeTier,
    modifier: Modifier = Modifier,
    onClick: () -> Unit,
    showZoomButton: Boolean = true,
) {
    Box(
        modifier = modifier.clip(RoundedCornerShape(16.dp)).background(Surface100).clickable(onClick = onClick),
    ) {
        AsyncImage(
            model = "$SERVER_URL/images/tier-package-visuals.png",
            contentDescription = "${tier.label} package visualization",
            contentScale = ContentScale.Fit,
            modifier = Modifier
                .fillMaxSize()
                .graphicsLayer {
                    scaleX = 3f
                    scaleY = 3f
                    // Derived from the tier's panel in the shared sprite rather
                    // than its key, so reordering the sprite is a config change.
                    transformOrigin = TransformOrigin(
                        pivotFractionX = tier.imagePivotFraction,
                        pivotFractionY = .5f,
                    )
                },
        )
        Box(
            modifier = Modifier.fillMaxSize().background(
                Brush.verticalGradient(listOf(Color.Transparent, Color.Transparent, Color.Black.copy(alpha = .78f)))
            )
        )
        if (showZoomButton) {
            Surface(
                modifier = Modifier.align(Alignment.TopEnd).padding(10.dp),
                color = Color.Black.copy(alpha = .58f),
                shape = CircleShape,
            ) {
                Icon(Icons.Default.ZoomIn, contentDescription = "Enlarge image", tint = Color.White, modifier = Modifier.padding(9.dp).size(19.dp))
            }
        }
        Column(modifier = Modifier.align(Alignment.BottomStart).padding(15.dp)) {
            Text("EXAMPLE CREATION", color = Color.White.copy(alpha = .72f), fontSize = 10.sp, fontWeight = FontWeight.Bold)
            Text(tier.packageTitle, color = Color.White, fontWeight = FontWeight.Bold, fontSize = 18.sp)
            Text(tier.caption, color = Color.White.copy(alpha = .88f), fontSize = 12.sp, lineHeight = 16.sp)
        }
    }
}

@Composable
private fun PackageZoomDialog(tier: NativeTier, onDismiss: () -> Unit) {
    Dialog(onDismissRequest = onDismiss) {
        Box(
            modifier = Modifier.fillMaxWidth().aspectRatio(3f / 4f).clip(RoundedCornerShape(20.dp)),
        ) {
            PackageImage(
                tier = tier,
                modifier = Modifier.fillMaxSize(),
                onClick = {},
                showZoomButton = false,
            )
            Surface(
                modifier = Modifier.align(Alignment.TopStart).padding(10.dp).clickable(onClick = onDismiss),
                color = Color.Black.copy(alpha = .64f),
                shape = CircleShape,
            ) {
                Icon(Icons.Default.Close, contentDescription = "Close", tint = Color.White, modifier = Modifier.padding(10.dp).size(20.dp))
            }
        }
    }
}

@Composable
private fun PackageStat(label: String, value: String, modifier: Modifier = Modifier) {
    Surface(modifier = modifier, color = Color(0xFFF8F7F3), shape = RoundedCornerShape(10.dp)) {
        Column(modifier = Modifier.padding(11.dp)) {
            Text(label, color = Surface400, fontSize = 10.sp)
            Text(value, color = Surface800, fontWeight = FontWeight.Bold, fontSize = 13.sp)
        }
    }
}

@Composable
private fun PackageLine(text: String) {
    Row(modifier = Modifier.padding(vertical = 3.dp), verticalAlignment = Alignment.Top) {
        Icon(Icons.Default.Check, contentDescription = null, tint = Brand600, modifier = Modifier.size(16.dp))
        Spacer(Modifier.width(7.dp))
        Text(text, color = Surface600, style = MaterialTheme.typography.bodySmall)
    }
}

@Composable
private fun EstimateBreakdown(
    project: NativeProjectType,
    tier: NativeTier,
    area: Int,
    base: Double,
    addons: List<NativeAddon>,
    total: Double,
    onBook: () -> Unit,
    onOpenAr: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = Surface0),
        border = BorderStroke(1.dp, Surface100),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text("Estimate breakdown", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = Surface900)
            Spacer(Modifier.height(10.dp))
            BreakdownLine("${project.label} - $area sq m", peso(base))
            BreakdownLine("${tier.label} tier", "${tier.multiplier}x")
            addons.forEach { addon -> BreakdownLine(addon.label, peso(addon.amount.toDouble())) }
            HorizontalDivider(modifier = Modifier.padding(vertical = 10.dp), color = Surface100)
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Estimated total", fontWeight = FontWeight.Bold, color = Surface900)
                Text(peso(total), fontWeight = FontWeight.Bold, color = Brand700, fontSize = 18.sp)
            }
            Spacer(Modifier.height(15.dp))
            Button(
                onClick = onBook,
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = ButtonDefaults.buttonColors(containerColor = Brand700),
                contentPadding = PaddingValues(vertical = 13.dp),
            ) {
                Icon(Icons.Default.CalendarMonth, contentDescription = null, modifier = Modifier.size(18.dp))
                Spacer(Modifier.width(8.dp))
                Text("Book consultation", fontWeight = FontWeight.Bold)
            }
            Spacer(Modifier.height(8.dp))
            OutlinedButton(
                onClick = onOpenAr,
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                border = BorderStroke(1.dp, Surface200),
                contentPadding = PaddingValues(vertical = 12.dp),
            ) {
                Icon(Icons.Default.ViewInAr, contentDescription = null, tint = Brand700, modifier = Modifier.size(18.dp))
                Spacer(Modifier.width(8.dp))
                Text("Visualize in AR", color = Brand700, fontWeight = FontWeight.SemiBold)
            }
        }
    }
}

@Composable
private fun BreakdownLine(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(vertical = 5.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.Top,
    ) {
        Text(label, modifier = Modifier.weight(1f), color = Surface500, style = MaterialTheme.typography.bodySmall)
        Spacer(Modifier.width(10.dp))
        Text(value, color = Surface800, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
    }
}
