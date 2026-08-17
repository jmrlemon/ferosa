package com.example.ferosa_landscaping.ui.ar.components

import androidx.compose.animation.*
import androidx.activity.compose.BackHandler
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.focusable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.DeleteOutline
import androidx.compose.material.icons.filled.OpenWith
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.focus.FocusRequester
import androidx.compose.ui.focus.focusRequester
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.dialog
import androidx.compose.ui.semantics.heading
import androidx.compose.ui.semantics.role
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.ferosa_landscaping.ui.ar.ArProduct
import com.example.ferosa_landscaping.ui.ar.CartActionState
import com.example.ferosa_landscaping.ui.ar.PlacedModel
import com.example.ferosa_landscaping.ui.theme.*
import kotlinx.coroutines.delay
import java.text.NumberFormat
import java.util.Locale

/**
 * Modal overlay panel displaying product information and "Add to Cart" action.
 *
 * Appears as a bottom-aligned card with a semi-transparent scrim backdrop.
 * Dismisses when the user taps outside the panel (on the scrim).
 *
 * @param placedModel The placed model whose product info to display, or null to hide
 * @param cartActionState Explicit progress/result state from the ViewModel
 * @param onAddToCart Callback when "Add to Cart" is tapped
 * @param onDismiss Callback when the panel should be dismissed (tap outside)
 */
@Composable
fun ProductInfoPanel(
    placedModel: PlacedModel?,
    cartActionState: CartActionState,
    onAddToCart: (ArProduct) -> Unit,
    onCartActionConsumed: () -> Unit,
    onMove: (PlacedModel) -> Unit,
    onTurn: (PlacedModel) -> Unit,
    onDelete: (PlacedModel) -> Unit,
    onDismiss: () -> Unit
) {
    LaunchedEffect(placedModel?.id) {
        onCartActionConsumed()
    }

    val activeProductId = placedModel?.product?.id
    // A cart request is global to the sheet. Keep every product action disabled
    // until it completes so dismissing/reopening cannot submit a duplicate request.
    val isAddingToCart = cartActionState is CartActionState.Adding
    val showSuccess = cartActionState is CartActionState.Added &&
        cartActionState.productId == activeProductId
    val cartError = (cartActionState as? CartActionState.Failed)
        ?.takeIf { it.productId == activeProductId }

    LaunchedEffect(cartActionState) {
        if (showSuccess) {
            delay(1600L)
            onCartActionConsumed()
            onDismiss()
        }
    }

    if (placedModel != null) {
        val model = placedModel
        val titleFocusRequester = remember(model.id) { FocusRequester() }

        Dialog(
            onDismissRequest = onDismiss,
            properties = DialogProperties(
                dismissOnBackPress = true,
                dismissOnClickOutside = true,
                usePlatformDefaultWidth = false,
            ),
        ) {
            BackHandler(onBack = onDismiss)
            LaunchedEffect(model.id) {
                titleFocusRequester.requestFocus()
            }

            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Color.Black.copy(alpha = 0.46f))
                    .semantics { dialog() }
            ) {
                Column(modifier = Modifier.fillMaxSize()) {
                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxWidth()
                            .clickable(
                                indication = null,
                                interactionSource = remember { MutableInteractionSource() }
                            ) {
                                onDismiss()
                            }
                            .semantics {
                                role = Role.Button
                                contentDescription = "Close product details"
                            }
                    )

                    Card(
                        modifier = Modifier
                            .align(Alignment.CenterHorizontally)
                            .fillMaxWidth()
                            .widthIn(max = 560.dp)
                            .padding(horizontal = 12.dp, vertical = 10.dp),
                        shape = RoundedCornerShape(28.dp),
                        colors = CardDefaults.cardColors(
                            containerColor = Surface0
                        ),
                        elevation = CardDefaults.cardElevation(defaultElevation = 14.dp)
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .navigationBarsPadding()
                                .verticalScroll(rememberScrollState())
                                .padding(horizontal = 20.dp, vertical = 16.dp)
                        ) {
                            Box(
                                modifier = Modifier
                                    .width(42.dp)
                                    .height(4.dp)
                                    .clip(RoundedCornerShape(99.dp))
                                    .background(Surface200)
                                    .align(Alignment.CenterHorizontally)
                            )
                            Spacer(Modifier.height(14.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.Top
                        ) {
                            Surface(
                                color = Brand50,
                                shape = RoundedCornerShape(99.dp),
                                border = androidx.compose.foundation.BorderStroke(1.dp, Brand100)
                            ) {
                                Text(
                                    text = model.product.category.ifBlank { "AR item" }.uppercase(),
                                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
                                    color = Brand700,
                                    fontSize = 10.sp,
                                    fontWeight = FontWeight.Bold,
                                    letterSpacing = .7.sp
                                )
                            }
                            IconButton(
                                onClick = onDismiss,
                                modifier = Modifier.size(48.dp)
                            ) {
                                Icon(
                                    imageVector = Icons.Filled.Close,
                                    contentDescription = "Close product details",
                                    tint = Surface500
                                )
                            }
                        }

                        Text(
                            text = model.product.name,
                            modifier = Modifier
                                .focusRequester(titleFocusRequester)
                                .focusable()
                                .semantics { heading() },
                            style = MaterialTheme.typography.headlineMedium,
                            fontWeight = FontWeight.Bold,
                            color = Surface900,
                            maxLines = 2,
                            overflow = TextOverflow.Ellipsis
                        )
                        Spacer(Modifier.height(4.dp))

                        Text(
                            text = formatPricePeso(model.product.price),
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = Brand700
                        )
                        Spacer(Modifier.height(14.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            ProductFact(
                                label = "REAL HEIGHT",
                                value = "${model.product.heightCm.toInt()} cm",
                                modifier = Modifier.weight(1f)
                            )
                            ProductFact(
                                label = "AVAILABILITY",
                                value = if (model.product.inStock) "In stock" else "Out of stock",
                                valueColor = if (model.product.inStock) Brand700 else Color(0xFFB42318),
                                modifier = Modifier.weight(1f)
                            )
                        }

                        if (model.product.description.isNotBlank()) {
                            Spacer(Modifier.height(14.dp))
                            Text(
                                text = model.product.description,
                                color = Surface600,
                                style = MaterialTheme.typography.bodyMedium,
                                maxLines = 3,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                        Spacer(Modifier.height(16.dp))

                        AnimatedVisibility(visible = cartError != null) {
                            cartError?.let {
                                Surface(
                                    color = Color(0xFFFEE2E2),
                                    shape = RoundedCornerShape(12.dp)
                                ) {
                                    Text(
                                        text = it.message,
                                        color = Color(0xFFDC2626),
                                        style = MaterialTheme.typography.bodyMedium,
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(12.dp)
                                    )
                                }
                            }
                        }

                        AnimatedVisibility(visible = showSuccess) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clip(RoundedCornerShape(12.dp))
                                    .background(Brand50)
                                    .padding(12.dp)
                            ) {
                                Icon(
                                    imageVector = Icons.Filled.CheckCircle,
                                    contentDescription = "Success",
                                    tint = Brand600,
                                    modifier = Modifier.size(24.dp)
                                )
                                Text(
                                    text = "Added to your cart",
                                    color = Brand700,
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Medium
                                )
                            }
                        }
                        if (cartError != null || showSuccess) Spacer(Modifier.height(12.dp))

                        Button(
                            onClick = {
                                onCartActionConsumed()
                                onAddToCart(model.product)
                            },
                            enabled = model.product.inStock && !isAddingToCart && !showSuccess,
                            modifier = Modifier
                                .fillMaxWidth()
                                .heightIn(min = 52.dp),
                            shape = RoundedCornerShape(14.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = Brand600,
                                contentColor = Color.White,
                                disabledContainerColor = Brand200,
                                disabledContentColor = Color.White.copy(alpha = 0.7f)
                            )
                        ) {
                            if (isAddingToCart) {
                                CircularProgressIndicator(
                                    modifier = Modifier.size(20.dp),
                                    color = Color.White,
                                    strokeWidth = 2.dp
                                )
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Adding to cart…", fontWeight = FontWeight.SemiBold)
                            } else {
                                Icon(
                                    imageVector = Icons.Filled.ShoppingCart,
                                    contentDescription = null,
                                    modifier = Modifier.size(20.dp)
                                )
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(
                                    text = if (model.product.inStock) "Add to cart" else "Currently out of stock",
                                    style = MaterialTheme.typography.labelLarge,
                                    fontSize = 15.sp,
                                    fontWeight = FontWeight.Bold
                                )
                            }
                        }
                        Spacer(Modifier.height(10.dp))

                        OutlinedButton(
                            onClick = { onTurn(model) },
                            modifier = Modifier
                                .fillMaxWidth()
                                .heightIn(min = 48.dp),
                            shape = RoundedCornerShape(14.dp),
                            enabled = !isAddingToCart && !showSuccess,
                        ) {
                            Icon(
                                Icons.Filled.Refresh,
                                contentDescription = null,
                                modifier = Modifier.size(18.dp),
                            )
                            Spacer(Modifier.width(7.dp))
                            Text("Turn 180°", fontWeight = FontWeight.Medium)
                        }
                        Spacer(Modifier.height(10.dp))

                        Row(
                            horizontalArrangement = Arrangement.spacedBy(10.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            OutlinedButton(
                                onClick = {
                                    onMove(model)
                                    onDismiss()
                                },
                                modifier = Modifier
                                    .weight(1f)
                                    .heightIn(min = 48.dp),
                                shape = RoundedCornerShape(14.dp),
                                enabled = !isAddingToCart && !showSuccess
                            ) {
                                Icon(Icons.Filled.OpenWith, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(Modifier.width(7.dp))
                                Text("Move", fontWeight = FontWeight.Medium)
                            }

                            OutlinedButton(
                                onClick = {
                                    onDelete(model)
                                    onDismiss()
                                },
                                modifier = Modifier
                                    .weight(1f)
                                    .heightIn(min = 48.dp),
                                shape = RoundedCornerShape(14.dp),
                                enabled = !isAddingToCart && !showSuccess,
                                colors = ButtonDefaults.outlinedButtonColors(
                                    contentColor = Color(0xFFDC2626)
                                )
                            ) {
                                Icon(Icons.Filled.DeleteOutline, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(Modifier.width(7.dp))
                                Text("Remove", fontWeight = FontWeight.Medium)
                            }
                        }
                    }
                }
            }
        }
    }
}
}

@Composable
private fun ProductFact(
    label: String,
    value: String,
    modifier: Modifier = Modifier,
    valueColor: Color = Surface800,
) {
    Column(
        modifier = modifier
            .clip(RoundedCornerShape(12.dp))
            .background(Surface50)
            .border(1.dp, Surface100, RoundedCornerShape(12.dp))
            .padding(horizontal = 12.dp, vertical = 10.dp)
    ) {
        Text(
            text = label,
            color = Surface500,
            fontSize = 9.sp,
            fontWeight = FontWeight.Bold,
            letterSpacing = .6.sp
        )
        Spacer(Modifier.height(3.dp))
        Text(
            text = value,
            color = valueColor,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold
        )
    }
}

/**
 * Formats a price as Philippine Peso currency string.
 * Example: 1299.0 → "₱1,299.00"
 */
private fun formatPricePeso(price: Double): String {
    // Locale("en","PH") is deprecated in favour of forLanguageTag.
    val format = NumberFormat.getNumberInstance(Locale.forLanguageTag("en-PH")).apply {
        minimumFractionDigits = 2
        maximumFractionDigits = 2
    }
    return "₱${format.format(price)}"
}
