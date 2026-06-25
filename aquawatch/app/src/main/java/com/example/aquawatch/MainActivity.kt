package com.example.aquawatch

import android.graphics.Color
import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.core.view.WindowInsetsControllerCompat
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.tooling.preview.Preview
import com.example.aquawatch.data.AppLanguage
import com.example.aquawatch.data.getAppLanguage
import com.example.aquawatch.data.setAppLanguage
import com.example.aquawatch.ui.LocalAppLanguage
import com.example.aquawatch.ui.screens.AquaNavHost
import com.example.aquawatch.ui.theme.AquaWatchTheme
import androidx.compose.runtime.CompositionLocalProvider

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        applySystemBarColors(darkMode = true)
        setContent {
            var darkMode by rememberSaveable { mutableStateOf(true) }
            var language by rememberSaveable { mutableStateOf(getAppLanguage()) }

            SideEffect {
                applySystemBarColors(darkMode = darkMode)
            }

            CompositionLocalProvider(LocalAppLanguage provides language) {
                AquaWatchTheme(darkTheme = darkMode) {
                    AquaNavHost(
                        darkMode = darkMode,
                        onDarkModeChange = { darkMode = it },
                        language = language,
                        onLanguageChange = {
                            language = it
                            setAppLanguage(it)
                        }
                    )
                }
            }
        }
    }

    private fun applySystemBarColors(darkMode: Boolean) {
        window.statusBarColor = Color.TRANSPARENT
        window.navigationBarColor = Color.rgb(7, 27, 51)
        WindowInsetsControllerCompat(window, window.decorView).apply {
            isAppearanceLightStatusBars = !darkMode
            isAppearanceLightNavigationBars = false
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            window.isNavigationBarContrastEnforced = false
        }
    }
}
@Preview(showBackground = true)
@Composable
fun PreviewApp() {
    var darkMode by remember { mutableStateOf(true) }
    var language by remember { mutableStateOf(AppLanguage.English) }

    CompositionLocalProvider(LocalAppLanguage provides language) {
        AquaWatchTheme(darkTheme = darkMode) {
            AquaNavHost(
                darkMode = darkMode,
                onDarkModeChange = { darkMode = it },
                language = language,
                onLanguageChange = { language = it }
            )
        }
    }
}
