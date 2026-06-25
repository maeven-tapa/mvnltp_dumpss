package com.example.aquawatch.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.example.aquawatch.data.AppLanguage
import com.example.aquawatch.data.MonitoringArea
import com.example.aquawatch.ui.theme.Seafoam500

data class NavigationItem(
    val route: String,
    val label: String,
    val selectedIcon: ImageVector,
    val unselectedIcon: ImageVector
)

val navigationItems = listOf(
    NavigationItem(
        route = Routes.DASHBOARD,
        label = "Dashboard",
        selectedIcon = Icons.Filled.Dashboard,
        unselectedIcon = Icons.Outlined.Dashboard
    ),
    NavigationItem(
        route = Routes.MAP,
        label = "Map",
        selectedIcon = Icons.Filled.Map,
        unselectedIcon = Icons.Outlined.Map
    ),
    NavigationItem(
        route = Routes.ALERTS,
        label = "Alerts",
        selectedIcon = Icons.Filled.Notifications,
        unselectedIcon = Icons.Outlined.Notifications
    ),
    NavigationItem(
        route = Routes.DEVICES,
        label = "Devices",
        selectedIcon = Icons.Filled.Router,
        unselectedIcon = Icons.Outlined.Router
    ),
    NavigationItem(
        route = Routes.SETTINGS,
        label = "Settings",
        selectedIcon = Icons.Filled.Settings,
        unselectedIcon = Icons.Outlined.Settings
    )
)

@Composable
fun MainNavigationScreen(
    startDestination: String = Routes.DASHBOARD,
    controller: NavHostController = rememberNavController(),
    darkMode: Boolean = true,
    onDarkModeChange: (Boolean) -> Unit = {},
    language: AppLanguage = AppLanguage.English,
    onLanguageChange: (AppLanguage) -> Unit = {},
    onLogout: () -> Unit = {},
    onMonitoringAreaSaved: (MonitoringArea) -> Unit = {}
) {
    val currentBackStack by controller.currentBackStackEntryAsState()
    val currentDestination = currentBackStack?.destination?.route
    var selectedItem by remember { mutableStateOf(startDestination) }
    val devices = remember { mutableStateListOf<DeviceRow>() }
    val detailRoutes = setOf(Routes.PROFILE, Routes.LOCATION, Routes.ADD_DEVICE)

    LaunchedEffect(currentDestination) {
        if (currentDestination != null) {
            selectedItem = currentDestination
        }
    }

    Scaffold(
        modifier = Modifier.fillMaxSize(),
        bottomBar = {
            if (currentDestination !in detailRoutes) {
                BottomNavigationBar(
                    selectedRoute = selectedItem,
                    language = language,
                    onNavigate = { route ->
                        if (route != selectedItem) {
                            selectedItem = route
                            controller.navigate(route) {
                                popUpTo(controller.graph.startDestinationId) { saveState = true }
                                restoreState = true
                            }
                        }
                    }
                )
            }
        }
    ) { paddingValues ->
        NavHost(
            navController = controller,
            startDestination = startDestination,
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            composable(Routes.DASHBOARD) {
                DashboardScreen(
                    onOpenAlerts = { controller.navigate(Routes.ALERTS) },
                    onOpenReports = { controller.navigate(Routes.REPORTS) }
                )
            }
            composable(Routes.MAP) { MapScreen() }
            composable(Routes.ALERTS) { AlertsScreen() }
            composable(Routes.REPORTS) { ReportsScreen() }
            composable(Routes.DEVICES) {
                DevicesScreen(
                    devices = devices,
                    onOpenAddDevice = { controller.navigate(Routes.ADD_DEVICE) },
                    onUpdateDevice = { updatedDevice ->
                        val index = devices.indexOfFirst { it.id == updatedDevice.id }
                        if (index >= 0) {
                            devices[index] = updatedDevice
                        }
                    }
                )
            }
            composable(Routes.ADD_DEVICE) {
                AddDeviceScreen(
                    onBack = { controller.popBackStack() },
                    onSave = { device ->
                        devices.add(device)
                        controller.popBackStack()
                    }
                )
            }
            composable(Routes.SETTINGS) {
                SettingsScreen(
                    onOpenProfile = { controller.navigate(Routes.PROFILE) },
                    onOpenLocation = { controller.navigate(Routes.LOCATION) },
                    darkMode = darkMode,
                    onDarkModeChange = onDarkModeChange,
                    language = language,
                    onLanguageChange = onLanguageChange,
                    onLogout = onLogout
                )
            }
            composable(Routes.PROFILE) { ProfileScreen(onBack = { controller.popBackStack() }) }
            composable(Routes.LOCATION) {
                LocationSetupScreen(
                    onSaved = { area ->
                        onMonitoringAreaSaved(area)
                        controller.navigate(Routes.DASHBOARD) {
                            popUpTo(Routes.LOCATION) { inclusive = true }
                        }
                    }
                )
            }
        }
    }
}

@Composable
fun BottomNavigationBar(
    selectedRoute: String,
    language: AppLanguage = AppLanguage.English,
    onNavigate: (String) -> Unit
) {
    NavigationBar(
        modifier = Modifier
            .fillMaxWidth()
            .height(80.dp),
        containerColor = Color(0xFF071B33),
        tonalElevation = 8.dp
    ) {
        navigationItems.forEach { item ->
            NavigationBarItem(
                selected = selectedRoute == item.route,
                onClick = { onNavigate(item.route) },
                icon = {
                    Icon(
                        imageVector = if (selectedRoute == item.route) item.selectedIcon else item.unselectedIcon,
                        contentDescription = item.label,
                        modifier = Modifier.size(24.dp)
                    )
                },
                label = {
                    Text(
                        text = item.route.navLabel(language),
                        fontSize = 11.sp,
                        maxLines = 1
                    )
                },
                colors = NavigationBarItemDefaults.colors(
                    selectedIconColor = Seafoam500,
                    selectedTextColor = Seafoam500,
                    indicatorColor = Color.Transparent,
                    unselectedIconColor = Color(0xCCFFFFFF),
                    unselectedTextColor = Color(0xCCFFFFFF)
                )
            )
        }
    }
}

private fun String.navLabel(language: AppLanguage): String {
    return when (language) {
        AppLanguage.English -> when (this) {
            Routes.DASHBOARD -> "Dashboard"
            Routes.MAP -> "Map"
            Routes.ALERTS -> "Alerts"
            Routes.DEVICES -> "Devices"
            Routes.SETTINGS -> "Settings"
            else -> this
        }
        AppLanguage.Filipino -> when (this) {
            Routes.DASHBOARD -> "Dashboard"
            Routes.MAP -> "Mapa"
            Routes.ALERTS -> "Alerto"
            Routes.DEVICES -> "Device"
            Routes.SETTINGS -> "Setting"
            else -> this
        }
        AppLanguage.Cebuano -> when (this) {
            Routes.DASHBOARD -> "Dashboard"
            Routes.MAP -> "Mapa"
            Routes.ALERTS -> "Alerto"
            Routes.DEVICES -> "Device"
            Routes.SETTINGS -> "Setting"
            else -> this
        }
    }
}
