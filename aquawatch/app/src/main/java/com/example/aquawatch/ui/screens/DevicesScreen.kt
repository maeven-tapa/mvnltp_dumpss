package com.example.aquawatch.ui.screens

import android.net.Uri
import android.widget.ImageView
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.GpsFixed
import androidx.compose.material.icons.filled.Router
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Speed
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.TextButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.draw.clip
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.ui.text.input.KeyboardType
import com.example.aquawatch.ui.LocalAppLanguage
import com.example.aquawatch.ui.appCopy
import com.example.aquawatch.ui.theme.Seafoam500

data class DeviceRow(
    val id: String,
    val serial: String,
    val sim: String,
    val driver: String,
    val owner: String,
    val vessel: String,
    val reg: String,
    val contact: String,
    val photoLabel: String,
    val status: String,
    val lastLocation: String,
    val lastUpdated: String,
    val name: String = "Tracking device",
    val type: String = "GPS tracker",
    val imei: String = "",
    val waterLevel: String = "-- cm",
    val gyro: String = "Stable",
    val gsm: String = "-- dBm",
    val gpsLocation: String = "No GPS fix yet"
)

@Composable
fun DevicesScreen(
    devices: List<DeviceRow> = emptyList(),
    onOpenAddDevice: () -> Unit = {},
    onUpdateDevice: (DeviceRow) -> Unit = {}
) {
    val copy = appCopy(LocalAppLanguage.current)
    var selectedDevice by remember { mutableStateOf<DeviceRow?>(null) }
    var editingDevice by remember { mutableStateOf<DeviceRow?>(null) }
    val onlineCount = devices.count { it.status == "Online" }
    val offlineCount = devices.count { it.status != "Online" }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
        contentPadding = PaddingValues(vertical = 8.dp)
    ) {
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text(copy.devices, fontSize = 28.sp, color = MaterialTheme.colorScheme.onBackground)
                    Text(
                        copy.devicesSubtitle,
                        fontSize = 14.sp,
                        color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.68f)
                    )
                }
                FloatingActionButton(
                    onClick = onOpenAddDevice,
                    containerColor = Seafoam500,
                    contentColor = Color.White,
                    modifier = Modifier.size(48.dp),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Icon(Icons.Filled.Add, contentDescription = "Add Device")
                }
            }
        }

        item {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(80.dp),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                StatBox("Registered", "${devices.size}", "Total", Modifier.weight(1f))
                StatBox("Online", "$onlineCount", "Live", Modifier.weight(1f))
                StatBox("Offline", "$offlineCount", "Idle", Modifier.weight(1f))
            }
        }

        if (devices.isEmpty()) {
            item {
                EmptyStateCard(
                    title = copy.noDevices,
                    message = copy.noDevicesMessage
                )
            }
        } else {
            items(devices) { device ->
                DeviceRowViewModern(
                    device = device,
                    onView = { selectedDevice = device },
                    onEdit = { editingDevice = device }
                )
            }
        }

        item {
            Spacer(Modifier.height(8.dp))
        }
    }

    selectedDevice?.let { device ->
        DeviceTrackingDialog(device = device, onDismiss = { selectedDevice = null })
    }

    editingDevice?.let { device ->
        EditDeviceDialog(
            device = device,
            onDismiss = { editingDevice = null },
            onSave = { updated ->
                onUpdateDevice(updated)
                editingDevice = null
            }
        )
    }
}

@Composable
fun StatBox(label: String, value: String, badge: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier.fillMaxHeight(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(12.dp),
            verticalArrangement = Arrangement.Center,
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(badge, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f))
            Text(value, fontSize = 16.sp, color = MaterialTheme.colorScheme.onSurface)
            Text(label, fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f))
        }
    }
}

@Composable
fun DeviceRowViewModern(device: DeviceRow, onView: () -> Unit, onEdit: () -> Unit) {
    val statusColor = if (device.status == "Online") Color(0xFF4CAF50) else Color(0xFFBDBDBD)

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 6.dp)
    ) {
        Column(modifier = Modifier.fillMaxWidth().padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.weight(1f)
                ) {
                    if (device.photoLabel.startsWith("content:") || device.photoLabel.startsWith("file:")) {
                        AndroidView(
                            modifier = Modifier.size(44.dp).clip(RoundedCornerShape(10.dp)),
                            factory = { imageContext ->
                                ImageView(imageContext).apply {
                                    scaleType = ImageView.ScaleType.CENTER_CROP
                                    setImageURI(Uri.parse(device.photoLabel))
                                }
                            },
                            update = { it.setImageURI(Uri.parse(device.photoLabel)) }
                        )
                    } else {
                        Box(
                            modifier = Modifier
                                .size(44.dp)
                                .background(Seafoam500, RoundedCornerShape(10.dp)),
                            contentAlignment = Alignment.Center
                        ) {
                            Text("ID", fontSize = 12.sp, color = Color.White)
                        }
                    }
                    Spacer(Modifier.width(12.dp))
                    Column {
                        Text(device.name, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurface)
                        Text(device.serial, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f))
                        Text(
                            device.owner.ifBlank { "No owner set" },
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                        )
                    }
                }
                Box(
                    modifier = Modifier
                        .background(statusColor, RoundedCornerShape(6.dp))
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                ) {
                    Text(device.status, fontSize = 11.sp, color = Color.White)
                }
            }
            Spacer(Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                DeviceTelemetryChip(
                    icon = Icons.Filled.WaterDrop,
                    label = "Water",
                    value = device.waterLevel,
                    modifier = Modifier.weight(1f)
                )
                DeviceTelemetryChip(
                    icon = Icons.Filled.Speed,
                    label = "Gyro",
                    value = device.gyro,
                    modifier = Modifier.weight(1f)
                )
            }
            Spacer(Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                DeviceTelemetryChip(
                    icon = Icons.Filled.Router,
                    label = "GSM",
                    value = device.gsm,
                    modifier = Modifier.weight(1f)
                )
                DeviceTelemetryChip(
                    icon = Icons.Filled.GpsFixed,
                    label = "GPS",
                    value = device.gpsLocation,
                    modifier = Modifier.weight(1f)
                )
            }
            Spacer(Modifier.height(10.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        "Driver: ${device.driver.ifBlank { "--" }}",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                    )
                    Text(
                        "Vessel: ${device.vessel}",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                    )
                    Text(
                        "Last update: ${device.lastUpdated}",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                    )
                }
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedButton(
                        onClick = onView,
                        modifier = Modifier.height(36.dp),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Icon(Icons.Filled.Visibility, contentDescription = null, modifier = Modifier.size(14.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("View", fontSize = 11.sp)
                    }
                    Button(
                        onClick = onEdit,
                        modifier = Modifier.height(36.dp),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Icon(Icons.Filled.Edit, contentDescription = null, modifier = Modifier.size(14.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("Edit", fontSize = 11.sp)
                    }
                }
            }
        }
    }
}

@Composable
private fun DeviceTelemetryChip(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    label: String,
    value: String,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .background(MaterialTheme.colorScheme.background.copy(alpha = 0.58f), RoundedCornerShape(12.dp))
            .padding(horizontal = 10.dp, vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(icon, contentDescription = null, tint = Seafoam500, modifier = Modifier.size(18.dp))
        Spacer(Modifier.width(8.dp))
        Column {
            Text(label, fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.58f))
            Text(value, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurface)
        }
    }
}

@Composable
fun DeviceTrackingDialog(device: DeviceRow, onDismiss: () -> Unit) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Tracking ${device.serial}", color = MaterialTheme.colorScheme.onSurface) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("Owner: ${device.owner}")
                Text("Type: ${device.type}")
                Text("Serial: ${device.serial}")
                Text("IMEI: ${device.imei.ifBlank { "--" }}")
                Text("Driver: ${device.driver.ifBlank { "--" }}")
                Text("Vessel: ${device.vessel}")
                Text("Picture: ${device.photoLabel}")
                Text("Status: ${device.status}")
                Text("Water level: ${device.waterLevel}")
                Text("Gyro: ${device.gyro}")
                Text("GSM: ${device.gsm}")
                Text("GPS location: ${device.gpsLocation}")
                Text("Last location: ${device.lastLocation}")
                Text("Last update: ${device.lastUpdated}")
            }
        },
        confirmButton = {
            Button(onClick = onDismiss) {
                Text("Close")
            }
        },
        shape = RoundedCornerShape(12.dp)
    )
}

@Composable
fun EditDeviceDialog(
    device: DeviceRow,
    onDismiss: () -> Unit,
    onSave: (DeviceRow) -> Unit
) {
    var name by remember(device.id) { mutableStateOf(device.name) }
    var serial by remember(device.id) { mutableStateOf(device.serial) }
    var sim by remember(device.id) { mutableStateOf(device.sim) }
    var driver by remember(device.id) { mutableStateOf(device.driver) }
    var owner by remember(device.id) { mutableStateOf(device.owner) }
    var vessel by remember(device.id) { mutableStateOf(device.vessel) }
    var waterLevel by remember(device.id) { mutableStateOf(device.waterLevel) }
    var gyro by remember(device.id) { mutableStateOf(device.gyro) }
    var gsm by remember(device.id) { mutableStateOf(device.gsm) }
    var gpsLocation by remember(device.id) { mutableStateOf(device.gpsLocation) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text("Edit device", modifier = Modifier.weight(1f))
                IconButton(onClick = onDismiss) {
                    Icon(Icons.Filled.Close, contentDescription = "Close")
                }
            }
        },
        text = {
            LazyColumn(
                verticalArrangement = Arrangement.spacedBy(10.dp),
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 420.dp)
            ) {
                item { EditDeviceField(name, { name = it }, "Device name") }
                item { EditDeviceField(serial, { serial = it }, "Serial number") }
                item { EditDeviceField(sim, { sim = it }, "SIM number", KeyboardType.Phone) }
                item { EditDeviceField(driver, { driver = it }, "Driver / operator") }
                item { EditDeviceField(owner, { owner = it }, "Owner full name") }
                item { EditDeviceField(vessel, { vessel = it }, "Vessel name") }
                item { EditDeviceField(waterLevel, { waterLevel = it }, "Water level placeholder") }
                item { EditDeviceField(gyro, { gyro = it }, "Gyro placeholder") }
                item { EditDeviceField(gsm, { gsm = it }, "GSM placeholder") }
                item { EditDeviceField(gpsLocation, { gpsLocation = it }, "GPS location placeholder") }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    onSave(
                        device.copy(
                            name = name.trim().ifBlank { device.name },
                            serial = serial.trim().ifBlank { device.serial },
                            sim = sim.trim(),
                            driver = driver.trim(),
                            owner = owner.trim(),
                            vessel = vessel.trim().ifBlank { device.vessel },
                            waterLevel = waterLevel.trim().ifBlank { "-- cm" },
                            gyro = gyro.trim().ifBlank { "Stable" },
                            gsm = gsm.trim().ifBlank { "-- dBm" },
                            gpsLocation = gpsLocation.trim().ifBlank { "No GPS fix yet" },
                            lastUpdated = "Edited just now"
                        )
                    )
                }
            ) {
                Icon(Icons.Filled.Save, contentDescription = null, modifier = Modifier.size(16.dp))
                Spacer(Modifier.width(6.dp))
                Text("Save")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        },
        shape = RoundedCornerShape(18.dp)
    )
}

@Composable
private fun EditDeviceField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    keyboardType: KeyboardType = KeyboardType.Text
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = keyboardType)
    )
}
