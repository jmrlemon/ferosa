package com.example.ferosa_landscaping.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

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

/**
 * The app is deliberately light-only.
 *
 * Most of what the user sees is the Laravel site rendered in a WebView, and
 * that UI is light-only Tailwind. A dark native shell wrapping white web pages
 * looked broken, so the dark scheme that used to sit here unused - every call
 * site passed `darkTheme = false` - has been removed rather than left as a
 * decoration. Reinstating it means doing the web side too.
 *
 * [darkTheme] is kept so existing call sites (including the AR screens) compile
 * unchanged; it currently selects nothing.
 */
@Composable
fun Ferosa_landscapingTheme(
    @Suppress("UNUSED_PARAMETER") darkTheme: Boolean = false,
    content: @Composable () -> Unit
) {
    MaterialTheme(
        colorScheme = LightColorScheme,
        typography = Typography,
        content = content
    )
}
