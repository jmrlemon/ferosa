package com.example.ferosa_landscaping.ui.ar.components

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.Category
import androidx.compose.material.icons.outlined.CloudOff
import androidx.compose.material.icons.outlined.ImageNotSupported
import androidx.compose.material.icons.rounded.Check
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil.compose.AsyncImagePainter
import coil.compose.SubcomposeAsyncImage
import coil.compose.SubcomposeAsyncImageContent
import com.example.ferosa_landscaping.ui.ar.ArProduct
import com.example.ferosa_landscaping.ui.theme.Brand100
import com.example.ferosa_landscaping.ui.theme.Brand200
import com.example.ferosa_landscaping.ui.theme.Brand400
import com.example.ferosa_landscaping.ui.theme.Brand600
import com.example.ferosa_landscaping.ui.theme.Brand700
import com.example.ferosa_landscaping.ui.theme.Brand900
import com.example.ferosa_landscaping.ui.theme.Surface50
import com.example.ferosa_landscaping.ui.theme.Surface100
import com.example.ferosa_landscaping.ui.theme.Surface200
import com.example.ferosa_landscaping.ui.theme.Surface300
import com.example.ferosa_landscaping.ui.theme.Surface400
import com.example.ferosa_landscaping.ui.theme.Surface500
import com.example.ferosa_landscaping.ui.theme.Surface600
import com.example.ferosa_landscaping.ui.theme.Surface700
import com.example.ferosa_landscaping.ui.theme.Surface800
import com.example.ferosa_landscaping.ui.theme.Surface900
import java.text.NumberFormat
import java.util.Locale

/**
 * Truncates a product name to 25 characters with ellipsis if longer.
 * Names of 25 characters or fewer are returned unchanged.
 */
fun truncateProductName(name: String): String {
    return if (name.length > 25) {
        name.take(25) + "…"
    } else {
        name
    }
}

/**
 * Formats a price value as Philippine Peso (₱) with two decimal places.
 */
private fun formatPrice(price: Double): String {
    val format = NumberFormat.getNumberInstance(Locale.forLanguageTag("en-PH")).apply {
        minimumFractionDigits = 2
        maximumFractionDigits = 2
    }
    return "₱${format.format(price)}"
}

/**
 * A horizontal product catalog drawer for the AR experience.
 *
 * Displays products in a horizontally scrollable list with clear selection,
 * availability, category, image-loading, and 3D-model-loading states.
 */
@Composable
fun CatalogDrawer(
    products: List<ArProduct>,
    selectedId: Int?,
    isOffline: Boolean,
    isModelLoading: Boolean,
    onSelect: (ArProduct) -> Unit,
) {
    val drawerShape = RoundedCornerShape(topStart = 24.dp, topEnd = 24.dp)
    val inStockCount = products.count { it.inStock }

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .clip(drawerShape)
            .background(Surface900.copy(alpha = 0.96f))
            .border(1.dp, Surface700, drawerShape)
            .padding(top = 10.dp, bottom = 12.dp)
    ) {
        Box(
            modifier = Modifier
                .width(36.dp)
                .height(4.dp)
                .clip(CircleShape)
                .background(Surface500)
                .align(Alignment.CenterHorizontally)
        )

        Spacer(Modifier.height(12.dp))

        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 18.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = "SELECT ELEMENT TO PLACE",
                    color = Surface50,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    letterSpacing = 0.7.sp
                )
                Spacer(Modifier.height(2.dp))
                Text(
                    text = "$inStockCount of ${products.size} in stock",
                    color = Surface300,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Medium
                )
            }

            AnimatedVisibility(
                visible = isOffline,
                enter = fadeIn(),
                exit = fadeOut()
            ) {
                OfflineIndicator()
            }
        }

        Spacer(Modifier.height(10.dp))

        LazyRow(
            horizontalArrangement = Arrangement.spacedBy(10.dp),
            contentPadding = PaddingValues(horizontal = 16.dp)
        ) {
            items(products, key = { it.id }) { product ->
                val selected = product.id == selectedId

                CatalogItem(
                    product = product,
                    selected = selected,
                    isLoading = selected && isModelLoading,
                    onSelect = { onSelect(product) }
                )
            }
        }
    }
}

@Composable
private fun OfflineIndicator() {
    Row(
        modifier = Modifier
            .clip(CircleShape)
            .background(Brand900)
            .border(1.dp, Brand400.copy(alpha = 0.6f), CircleShape)
            .padding(horizontal = 8.dp, vertical = 5.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(5.dp)
    ) {
        Icon(
            imageVector = Icons.Outlined.CloudOff,
            contentDescription = null,
            tint = Brand200,
            modifier = Modifier.size(14.dp)
        )
        Text(
            text = "Cached only",
            color = Surface100,
            fontSize = 11.sp,
            fontWeight = FontWeight.SemiBold,
            maxLines = 1
        )
    }
}

/**
 * Fixed-size catalog card. Its full surface is a selectable touch target well
 * above the 48 dp accessibility minimum.
 */
@Composable
private fun CatalogItem(
    product: ArProduct,
    selected: Boolean,
    isLoading: Boolean,
    onSelect: () -> Unit,
) {
    val cardShape = RoundedCornerShape(16.dp)
    val categoryLabel = product.category.trim().ifBlank { "General" }

    Column(
        horizontalAlignment = Alignment.Start,
        modifier = Modifier
            .width(120.dp)
            .height(184.dp)
            .clip(cardShape)
            .background(if (selected) Brand900 else Surface800)
            .border(
                width = if (selected) 2.dp else 1.dp,
                color = if (selected) Brand400 else Surface700,
                shape = cardShape
            )
            .selectable(
                selected = selected,
                onClick = onSelect,
                role = Role.RadioButton
            )
            .padding(8.dp)
    ) {
        ProductThumbnail(
            product = product,
            selected = selected,
            isLoading = isLoading
        )

        Spacer(Modifier.height(8.dp))

        CategoryLabel(
            label = categoryLabel,
            selected = selected
        )

        Spacer(Modifier.height(6.dp))

        Text(
            text = truncateProductName(product.name),
            color = if (selected) Surface50 else Surface100,
            fontSize = 12.sp,
            lineHeight = 15.sp,
            fontWeight = if (selected) FontWeight.SemiBold else FontWeight.Medium,
            maxLines = 2,
            overflow = TextOverflow.Ellipsis,
            modifier = Modifier.heightIn(min = 30.dp)
        )

        Spacer(Modifier.weight(1f))

        Text(
            text = formatPrice(product.price),
            color = if (selected) Brand100 else Surface200,
            fontSize = 12.sp,
            lineHeight = 15.sp,
            fontWeight = FontWeight.Bold,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}

@Composable
private fun ProductThumbnail(
    product: ArProduct,
    selected: Boolean,
    isLoading: Boolean,
) {
    val imageShape = RoundedCornerShape(11.dp)

    Box(
        modifier = Modifier
            .fillMaxWidth()
            .height(72.dp)
            .clip(imageShape)
            .background(Surface700)
            .border(
                width = 1.dp,
                color = if (selected) Brand400 else Surface600,
                shape = imageShape
            )
    ) {
        SubcomposeAsyncImage(
            model = product.thumbnailUrl,
            contentDescription = product.name,
            contentScale = ContentScale.Crop,
            modifier = Modifier.fillMaxSize()
        ) {
            when (painter.state) {
                is AsyncImagePainter.State.Success -> SubcomposeAsyncImageContent()
                is AsyncImagePainter.State.Loading -> ThumbnailLoadingPlaceholder()
                else -> ThumbnailErrorPlaceholder()
            }
        }

        StockIndicator(
            inStock = product.inStock,
            modifier = Modifier
                .align(Alignment.TopStart)
                .padding(6.dp)
        )

        if (selected && !isLoading) {
            Box(
                contentAlignment = Alignment.Center,
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .padding(6.dp)
                    .size(24.dp)
                    .clip(CircleShape)
                    .background(Brand600)
                    .border(1.dp, Brand100, CircleShape)
            ) {
                Icon(
                    imageVector = Icons.Rounded.Check,
                    contentDescription = "Selected",
                    tint = Surface50,
                    modifier = Modifier.size(16.dp)
                )
            }
        }

        if (isLoading) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.Center,
                modifier = Modifier
                    .fillMaxSize()
                    .background(Surface900.copy(alpha = 0.88f))
            ) {
                CircularProgressIndicator(
                    color = Brand200,
                    modifier = Modifier.size(20.dp),
                    strokeWidth = 2.dp
                )
                Spacer(Modifier.height(4.dp))
                Text(
                    text = "Loading 3D",
                    color = Surface50,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.SemiBold
                )
            }
        }
    }
}

@Composable
private fun ThumbnailLoadingPlaceholder() {
    Box(
        contentAlignment = Alignment.Center,
        modifier = Modifier
            .fillMaxSize()
            .background(Surface700)
    ) {
        CircularProgressIndicator(
            color = Brand200,
            modifier = Modifier.size(18.dp),
            strokeWidth = 2.dp
        )
    }
}

@Composable
private fun ThumbnailErrorPlaceholder() {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
        modifier = Modifier
            .fillMaxSize()
            .background(Surface800)
    ) {
        Icon(
            imageVector = Icons.Outlined.ImageNotSupported,
            contentDescription = null,
            tint = Surface400,
            modifier = Modifier.size(22.dp)
        )
        Spacer(Modifier.height(2.dp))
        Text(
            text = "No image",
            color = Surface300,
            fontSize = 11.sp,
            fontWeight = FontWeight.Medium
        )
    }
}

@Composable
private fun CategoryLabel(
    label: String,
    selected: Boolean,
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .heightIn(min = 24.dp)
            .clip(RoundedCornerShape(7.dp))
            .background(if (selected) Brand700 else Surface700)
            .padding(horizontal = 6.dp, vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(4.dp)
    ) {
        Icon(
            imageVector = Icons.Outlined.Category,
            contentDescription = null,
            tint = if (selected) Brand100 else Surface300,
            modifier = Modifier.size(13.dp)
        )
        Text(
            text = label,
            color = if (selected) Brand100 else Surface200,
            fontSize = 11.sp,
            lineHeight = 13.sp,
            fontWeight = FontWeight.Medium,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}

@Composable
private fun StockIndicator(
    inStock: Boolean,
    modifier: Modifier = Modifier,
) {
    val indicatorColor = if (inStock) Brand400 else Surface400

    Row(
        modifier = modifier
            .clip(CircleShape)
            .background(Surface900.copy(alpha = 0.9f))
            .border(1.dp, indicatorColor.copy(alpha = 0.8f), CircleShape)
            .padding(horizontal = 6.dp, vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(4.dp)
    ) {
        Box(
            modifier = Modifier
                .size(6.dp)
                .clip(CircleShape)
                .background(indicatorColor)
        )
        Text(
            text = if (inStock) "In stock" else "Out",
            color = if (inStock) Brand100 else Surface200,
            fontSize = 11.sp,
            lineHeight = 13.sp,
            fontWeight = FontWeight.SemiBold,
            maxLines = 1
        )
    }
}
