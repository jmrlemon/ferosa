package com.example.ferosa_landscaping.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

private val DarkColorScheme = darkColorScheme(
    primary = Brand500,
    onPrimary = Surface0,
    primaryContainer = Brand900,
    onPrimaryContainer = Brand100,
    secondary = Surface600,
    onSecondary = Surface0,
    background = Surface900,
    onBackground = Surface100,
    surface = Surface800,
    onSurface = Surface100,
    surfaceVariant = Surface700,
    onSurfaceVariant = Surface300,
)

private val LightColorScheme = lightColorScheme(
    primary = Brand600,
    onPrimary = Surface0,
    primaryContainer = Brand50,
    onPrimaryContainer = Brand900,
    secondary = Surface600,
    onSecondary = Surface0,
    background = Surface50,
    onBackground = Surface900,
    surface = Surface0,
    onSurface = Surface900,
    surfaceVariant = Surface100,
    onSurfaceVariant = Surface600,
    outline = Surface200,
    outlineVariant = Surface100,
)

@Composable
fun Ferosa_landscapingTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colorScheme = if (darkTheme) DarkColorScheme else LightColorScheme

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
