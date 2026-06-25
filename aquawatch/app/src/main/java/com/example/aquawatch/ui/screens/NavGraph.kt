package com.example.aquawatch.ui.screens

import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.platform.LocalContext
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.example.aquawatch.data.AppLanguage
import com.example.aquawatch.data.clearAquaSession
import com.example.aquawatch.data.isMonitoringAreaSaved
import com.example.aquawatch.data.isSignedIn
import com.example.aquawatch.data.saveMonitoringArea
import com.example.aquawatch.data.setSignedIn

object Routes {
    const val WELCOME = "welcome"
    const val AUTH = "auth"
    const val LOCATION = "location"
    const val DASHBOARD = "dashboard"
    const val MAP = "map"
    const val ALERTS = "alerts"
    const val DEVICES = "devices"
    const val ADD_DEVICE = "add_device"
    const val PROFILE = "profile"
    const val REPORTS = "reports"
    const val SETTINGS = "settings"
}

@Composable
fun AquaNavHost(
    start: String = Routes.WELCOME,
    controller: NavHostController = rememberNavController(),
    darkMode: Boolean = true,
    onDarkModeChange: (Boolean) -> Unit = {},
    language: AppLanguage = AppLanguage.English,
    onLanguageChange: (AppLanguage) -> Unit = {}
) {
    val context = LocalContext.current
    var signedIn by rememberSaveable { mutableStateOf(context.isSignedIn()) }
    var monitoringAreaSaved by rememberSaveable { mutableStateOf(context.isMonitoringAreaSaved()) }
    val startDestination = when {
        signedIn && monitoringAreaSaved -> Routes.DASHBOARD
        signedIn -> Routes.LOCATION
        else -> start
    }

    NavHost(navController = controller, startDestination = startDestination) {
        composable(Routes.WELCOME) {
            WelcomeScreen(
                onStart = {
                    controller.navigate(Routes.AUTH) {
                        popUpTo(Routes.WELCOME) { inclusive = true }
                    }
                }
            )
        }
        composable(Routes.AUTH) {
            AuthScreen(
                onAuthenticated = {
                    context.setSignedIn(true)
                    signedIn = true
                    controller.navigate(Routes.LOCATION) {
                        popUpTo(Routes.AUTH) { inclusive = true }
                    }
                }
            )
        }
        composable(Routes.LOCATION) {
            LocationSetupScreen(
                onSaved = { area ->
                    context.saveMonitoringArea(area)
                    monitoringAreaSaved = true
                    controller.navigate(Routes.DASHBOARD) {
                        popUpTo(Routes.LOCATION) { inclusive = true }
                    }
                }
            )
        }
        composable(Routes.DASHBOARD) {
            MainNavigationScreen(
                startDestination = Routes.DASHBOARD,
                darkMode = darkMode,
                onDarkModeChange = onDarkModeChange,
                language = language,
                onLanguageChange = onLanguageChange,
                onLogout = {
                    context.clearAquaSession()
                    signedIn = false
                    monitoringAreaSaved = false
                    controller.navigate(Routes.WELCOME) {
                        popUpTo(controller.graph.startDestinationId) { inclusive = true }
                    }
                },
                onMonitoringAreaSaved = { area ->
                    context.saveMonitoringArea(area)
                    monitoringAreaSaved = true
                }
            )
        }
    }
}
