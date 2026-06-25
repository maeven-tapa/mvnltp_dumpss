package com.example.aquawatch.ui.theme

import android.os.Build
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.dynamicDarkColorScheme
import androidx.compose.material3.dynamicLightColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext

private val DarkColorScheme = darkColorScheme(
    primary = Seafoam500,
    secondary = Ocean700,
    tertiary = Color(0xFFB8F3F2),
    surface = Color(0xFF102338),
    background = Color(0xFF07111F),
    onPrimary = Color(0xFF06151F),
    onSurface = Color(0xFFEAF8FF),
    onBackground = Color(0xFFEAF8FF)
)

private val LightColorScheme = lightColorScheme(
    primary = Navy900,
    secondary = Ocean700,
    tertiary = Seafoam500,
    surface = Surface,
    background = Background,
    onPrimary = Surface,
    onBackground = Gray900

    /* Other default colors to override
    background = Color(0xFFFFFBFE),
    surface = Color(0xFFFFFBFE),
    onPrimary = Color.White,
    onSecondary = Color.White,
    onTertiary = Color.White,
    onBackground = Color(0xFF1C1B1F),
    onSurface = Color(0xFF1C1B1F),
    */
)

@Composable
fun AquaWatchTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    // Ignore dynamic color to preserve the AquaWatch palette
    dynamicColor: Boolean = false,
    content: @Composable () -> Unit
) {
    val colorScheme = when {
        dynamicColor && Build.VERSION.SDK_INT >= Build.VERSION_CODES.S -> {
            val context = LocalContext.current
            if (darkTheme) dynamicDarkColorScheme(context) else dynamicLightColorScheme(context)
        }

        darkTheme -> DarkColorScheme
        else -> LightColorScheme
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
