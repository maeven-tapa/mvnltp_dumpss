package com.example.aquawatch.ui.screens

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.net.Uri
import android.widget.ImageView
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.luminance
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import com.example.aquawatch.data.getMonitoringArea
import com.example.aquawatch.ui.LocalAppLanguage
import com.example.aquawatch.ui.appCopy
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.maps.CameraUpdateFactory
import com.google.android.gms.maps.model.LatLng
import com.google.android.gms.maps.model.MapStyleOptions
import com.google.android.gms.tasks.CancellationTokenSource
import com.google.maps.android.compose.Circle
import com.google.maps.android.compose.GoogleMap
import com.google.maps.android.compose.MapProperties
import com.google.maps.android.compose.MapUiSettings
import com.google.maps.android.compose.Marker
import com.google.maps.android.compose.rememberCameraPositionState
import com.google.maps.android.compose.rememberMarkerState

@SuppressLint("MissingPermission")
@Composable
fun MapScreen() {
    val context = LocalContext.current
    val copy = appCopy(LocalAppLanguage.current)
    val monitoringArea = remember { context.getMonitoringArea() }
    val isDarkTheme = MaterialTheme.colorScheme.background.luminance() < 0.5f
    var hasLocationPermission by remember { mutableStateOf(context.hasFineLocationPermission()) }
    val monitoringCenter = monitoringArea.latLng
    var cameraTarget by remember { mutableStateOf(monitoringCenter) }
    var showMonitoringRadius by remember { mutableStateOf(true) }
    var mapLoaded by remember { mutableStateOf(false) }
    var cameraRequest by remember { mutableStateOf(0) }
    var showMonitoringDetails by remember { mutableStateOf(false) }
    var locationStatus by remember { mutableStateOf("Tap Show My Location to center the map") }
    val cameraPositionState = rememberCameraPositionState()
    val markerState = rememberMarkerState(position = monitoringCenter)

    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        hasLocationPermission = granted
        if (granted) {
            locationStatus = "Finding current location..."
            fetchCurrentLocation(context) { latLng ->
                if (latLng != null) {
                    cameraTarget = latLng
                    showMonitoringRadius = false
                    cameraRequest++
                    locationStatus = latLng.coordinateText()
                } else {
                    locationStatus = "Location unavailable. Turn on GPS and try again."
                }
            }
        } else {
            locationStatus = "Location permission denied"
        }
    }

    LaunchedEffect(mapLoaded, cameraTarget, showMonitoringRadius, cameraRequest) {
        if (!mapLoaded) return@LaunchedEffect
        markerState.position = monitoringCenter
        val update = if (showMonitoringRadius) {
            CameraUpdateFactory.newLatLngBounds(monitoringAreaBounds(monitoringCenter), 64)
        } else {
            CameraUpdateFactory.newLatLngZoom(cameraTarget, 15f)
        }
        cameraPositionState.animate(update)
    }

    Box(modifier = Modifier.fillMaxSize()) {
        GoogleMap(
            modifier = Modifier.fillMaxSize(),
            cameraPositionState = cameraPositionState,
            onMapLoaded = { mapLoaded = true },
            onMapClick = { showMonitoringDetails = false },
            properties = MapProperties(
                isMyLocationEnabled = hasLocationPermission,
                mapStyleOptions = if (isDarkTheme) MapStyleOptions(DarkMapStyleJson) else null
            ),
            uiSettings = MapUiSettings(myLocationButtonEnabled = false)
        ) {
            Circle(
                center = monitoringCenter,
                radius = MonitoringRadiusMeters,
                fillColor = Color(0x2200A8B5),
                strokeColor = Color(0xAA00A8B5),
                strokeWidth = 4f
            )
            Marker(
                state = markerState,
                title = monitoringArea.label,
                snippet = "20 km monitoring radius",
                onClick = {
                    showMonitoringDetails = true
                    showMonitoringRadius = true
                    cameraRequest++
                    true
                }
            )
        }

        if (showMonitoringDetails) {
            Box(
                modifier = Modifier
                    .align(Alignment.BottomStart)
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
                ) {
                    Column(modifier = Modifier.fillMaxWidth().padding(16.dp)) {
                        Text(
                            monitoringArea.label,
                            fontSize = 16.sp,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        Text(
                            copy.monitoringDetails,
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                        )
                        Spacer(Modifier.height(10.dp))
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Column {
                                Text(copy.coverage, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f))
                                Text("20 km radius", fontSize = 16.sp, color = MaterialTheme.colorScheme.onSurface)
                            }
                            Column(horizontalAlignment = Alignment.End) {
                                Text(copy.devices, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f))
                                Text("0 active", fontSize = 16.sp, color = MaterialTheme.colorScheme.onSurface)
                            }
                        }
                        Spacer(Modifier.height(8.dp))
                        Text(
                            "Coordinates: ${monitoringArea.latLng.coordinateText()}",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                        )
                        if (monitoringArea.imageUri.isNotBlank()) {
                            Spacer(Modifier.height(10.dp))
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(120.dp),
                                shape = RoundedCornerShape(14.dp),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.background)
                            ) {
                                AndroidView(
                                    modifier = Modifier.fillMaxSize(),
                                    factory = { imageContext ->
                                        ImageView(imageContext).apply {
                                            scaleType = ImageView.ScaleType.CENTER_CROP
                                            setImageURI(Uri.parse(monitoringArea.imageUri))
                                        }
                                    },
                                    update = { imageView ->
                                        imageView.setImageURI(Uri.parse(monitoringArea.imageUri))
                                    }
                                )
                            }
                        }
                        Text(
                            "Use Settings > Monitoring Location to move this pin.",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                        )
                    }
                }
            }
        }

        Box(
            modifier = Modifier
                .align(Alignment.TopStart)
                .padding(16.dp)
        ) {
            Button(
                onClick = {
                    if (!hasLocationPermission) {
                        launcher.launch(Manifest.permission.ACCESS_FINE_LOCATION)
                    } else {
                        locationStatus = "Finding current location..."
                        fetchCurrentLocation(context) { latLng ->
                            if (latLng != null) {
                                cameraTarget = latLng
                                showMonitoringRadius = false
                                cameraRequest++
                                locationStatus = latLng.coordinateText()
                            } else {
                                locationStatus = "Location unavailable. Turn on GPS and try again."
                            }
                        }
                    }
                },
                modifier = Modifier.height(48.dp),
                shape = RoundedCornerShape(12.dp)
            ) {
                Text(if (hasLocationPermission) copy.showMyLocation else copy.enableLocation)
            }
        }
    }
}

private fun Context.hasFineLocationPermission(): Boolean {
    return ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) ==
        PackageManager.PERMISSION_GRANTED
}

@SuppressLint("MissingPermission")
private fun fetchCurrentLocation(context: Context, callback: (LatLng?) -> Unit) {
    val client = LocationServices.getFusedLocationProviderClient(context)
    val cancellationTokenSource = CancellationTokenSource()
    client.getCurrentLocation(Priority.PRIORITY_HIGH_ACCURACY, cancellationTokenSource.token)
        .addOnSuccessListener { location ->
            if (location != null) {
                callback(LatLng(location.latitude, location.longitude))
            } else {
                client.lastLocation
                    .addOnSuccessListener { lastLocation ->
                        callback(lastLocation?.let { LatLng(it.latitude, it.longitude) })
                    }
                    .addOnFailureListener { callback(null) }
            }
        }
        .addOnFailureListener { callback(null) }
}

private fun LatLng.coordinateText(): String {
    return "%.5f, %.5f".format(latitude, longitude)
}

private val DarkMapStyleJson = """
[
  {"elementType":"geometry","stylers":[{"color":"#102338"}]},
  {"elementType":"labels.text.fill","stylers":[{"color":"#d7eef6"}]},
  {"elementType":"labels.text.stroke","stylers":[{"color":"#07111f"}]},
  {"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#2b5365"}]},
  {"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#12283b"}]},
  {"featureType":"poi","stylers":[{"visibility":"off"}]},
  {"featureType":"road","elementType":"geometry","stylers":[{"color":"#1c3b52"}]},
  {"featureType":"road","elementType":"labels.text.fill","stylers":[{"color":"#9ec4d1"}]},
  {"featureType":"transit","stylers":[{"visibility":"off"}]},
  {"featureType":"water","elementType":"geometry","stylers":[{"color":"#061a2c"}]},
  {"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#77a9ba"}]}
]
""".trimIndent()
