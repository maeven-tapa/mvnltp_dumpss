package com.example.aquawatch.ui.screens

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.net.Uri
import android.widget.ImageView
import android.location.Geocoder
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.GpsFixed
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.luminance
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import com.example.aquawatch.data.MonitoringArea
import com.example.aquawatch.data.getMonitoringArea
import com.example.aquawatch.data.isMonitoringAreaSaved
import com.example.aquawatch.ui.LocalAppLanguage
import com.example.aquawatch.ui.appCopy
import com.example.aquawatch.ui.theme.PrimaryActionButton
import com.example.aquawatch.ui.theme.Seafoam500
import com.example.aquawatch.ui.theme.SecondaryActionButton
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
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

@SuppressLint("MissingPermission")
@Composable
fun LocationSetupScreen(onSaved: (MonitoringArea) -> Unit) {
    val context = LocalContext.current
    val copy = appCopy(LocalAppLanguage.current)
    val savedArea = remember { context.getMonitoringArea() }
    val hasSavedArea = remember { context.isMonitoringAreaSaved() }
    val scope = rememberCoroutineScope()
    val isDarkTheme = MaterialTheme.colorScheme.background.luminance() < 0.5f
    var areaName by remember { mutableStateOf(if (hasSavedArea) savedArea.label else "") }
    var address by remember { mutableStateOf("") }
    var photoUri by remember { mutableStateOf(savedArea.imageUri) }
    var selectedLocation by remember { mutableStateOf(savedArea.latLng) }
    var mapLoaded by remember { mutableStateOf(false) }
    var hasLocationPermission by remember { mutableStateOf(context.hasFineLocationPermission()) }
    var coords by remember { mutableStateOf("Selected: ${savedArea.latLng.coordinateText()}") }
    val cameraPositionState = rememberCameraPositionState()
    val markerState = rememberMarkerState(position = selectedLocation)
    val photoPicker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        if (uri != null) {
            runCatching {
                context.contentResolver.takePersistableUriPermission(
                    uri,
                    android.content.Intent.FLAG_GRANT_READ_URI_PERMISSION
                )
            }
            photoUri = uri.toString()
        }
    }

    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        hasLocationPermission = granted
        if (granted) {
            coords = "Finding current location..."
            fetchCurrentLocation(context) { latLng ->
                if (latLng != null) {
                    selectedLocation = latLng
                    coords = "Selected: ${latLng.coordinateText()}"
                } else {
                    coords = "Location unavailable. Turn on GPS and try again."
                }
            }
        } else {
            coords = "Location permission denied"
        }
    }

    LaunchedEffect(mapLoaded, selectedLocation) {
        if (!mapLoaded) return@LaunchedEffect
        markerState.position = selectedLocation
        cameraPositionState.animate(
            CameraUpdateFactory.newLatLngBounds(monitoringAreaBounds(selectedLocation), 48)
        )
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .statusBarsPadding()
            .verticalScroll(rememberScrollState())
            .padding(18.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(
                copy.monitoringArea,
                fontSize = 28.sp,
                color = MaterialTheme.colorScheme.onBackground,
                fontWeight = FontWeight.Bold
            )
            Text(
                copy.monitoringAreaSubtitle,
                fontSize = 14.sp,
                color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.68f)
            )
        }

        Card(
            modifier = Modifier
                .fillMaxWidth()
                .height(320.dp),
            shape = RoundedCornerShape(28.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 12.dp)
        ) {
            Box(modifier = Modifier.fillMaxSize()) {
                GoogleMap(
                    modifier = Modifier.fillMaxSize(),
                    cameraPositionState = cameraPositionState,
                    onMapLoaded = { mapLoaded = true },
                    onMapClick = { latLng ->
                        selectedLocation = latLng
                        coords = "Selected: ${latLng.coordinateText()}"
                    },
                    properties = MapProperties(
                        isMyLocationEnabled = hasLocationPermission,
                        mapStyleOptions = if (isDarkTheme) MapStyleOptions(DarkLocationMapStyleJson) else null
                    ),
                    uiSettings = MapUiSettings(myLocationButtonEnabled = false, zoomControlsEnabled = false)
                ) {
                    Circle(
                        center = selectedLocation,
                        radius = MonitoringRadiusMeters,
                        fillColor = Color(0x2200A8B5),
                        strokeColor = Color(0xAA00A8B5),
                        strokeWidth = 4f
                    )
                    Marker(state = markerState, title = "Monitoring Area")
                }

                Card(
                    modifier = Modifier
                        .align(Alignment.TopStart)
                        .padding(12.dp),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.90f))
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        Icon(Icons.Filled.LocationOn, contentDescription = null, tint = Seafoam500)
                        Text(copy.tapMapPin, color = MaterialTheme.colorScheme.onSurface, fontSize = 12.sp)
                    }
                }
            }
        }

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(24.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Text(
                    copy.searchPlace,
                    fontSize = 16.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                OutlinedTextField(
                    value = address,
                    onValueChange = { address = it },
                    label = { Text(copy.searchPlace) },
                    placeholder = { Text("City, coastline, or address") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    singleLine = true
                )
                SecondaryActionButton(
                    text = copy.searchTypedLocation,
                    onClick = {
                        if (address.isBlank()) {
                            coords = "Type a place name first"
                        } else {
                            coords = "Searching..."
                            scope.launch {
                                val result = searchAddress(context, address)
                                if (result != null) {
                                    selectedLocation = result
                                    coords = "Selected: ${result.coordinateText()}"
                                } else {
                                    coords = "Place not found. Try a more specific address."
                                }
                            }
                        }
                    }
                )
                PrimaryActionButton(
                    text = copy.useGpsLocation,
                    onClick = {
                        if (!hasLocationPermission) {
                            launcher.launch(Manifest.permission.ACCESS_FINE_LOCATION)
                        } else {
                            coords = "Finding current location..."
                            fetchCurrentLocation(context) { latLng ->
                                if (latLng != null) {
                                    selectedLocation = latLng
                                    coords = "Selected: ${latLng.coordinateText()}"
                                } else {
                                    coords = "Location unavailable. Turn on GPS and try again."
                                }
                            }
                        }
                    }
                )
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Icon(Icons.Filled.GpsFixed, contentDescription = null, tint = Seafoam500)
                    Text(coords, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f), fontSize = 13.sp)
                }

                HorizontalDivider(color = MaterialTheme.colorScheme.outline.copy(alpha = 0.25f))

                Text(
                    copy.areaName,
                    fontSize = 16.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                OutlinedTextField(
                    value = areaName,
                    onValueChange = { areaName = it },
                    label = { Text(copy.areaName) },
                    placeholder = { Text("Name this monitoring area") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    singleLine = true
                )
                Text(
                    copy.areaPhoto,
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                )
                if (photoUri.isNotBlank()) {
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(160.dp),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.background)
                    ) {
                        AndroidView(
                            modifier = Modifier.fillMaxSize(),
                            factory = { imageContext ->
                                ImageView(imageContext).apply {
                                    scaleType = ImageView.ScaleType.CENTER_CROP
                                    setImageURI(Uri.parse(photoUri))
                                }
                            },
                            update = { imageView -> imageView.setImageURI(Uri.parse(photoUri)) }
                        )
                    }
                }
                SecondaryActionButton(
                    text = if (photoUri.isBlank()) copy.addPhoto else copy.changePhoto,
                    onClick = { photoPicker.launch(arrayOf("image/*")) }
                )
                Spacer(Modifier.height(2.dp))
                SecondaryActionButton(
                    text = copy.saveMonitoringArea,
                    onClick = {
                        onSaved(
                            MonitoringArea(
                                latitude = selectedLocation.latitude,
                                longitude = selectedLocation.longitude,
                                label = areaName.ifBlank { copy.monitoringArea },
                                imageUri = photoUri
                            )
                        )
                    }
                )
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

@Suppress("DEPRECATION")
private suspend fun searchAddress(context: Context, query: String): LatLng? = withContext(Dispatchers.IO) {
    runCatching {
        val result = Geocoder(context).getFromLocationName(query, 1)?.firstOrNull()
        result?.let { LatLng(it.latitude, it.longitude) }
    }.getOrNull()
}

private fun LatLng.coordinateText(): String {
    return "%.5f, %.5f".format(latitude, longitude)
}

private val DarkLocationMapStyleJson = """
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
