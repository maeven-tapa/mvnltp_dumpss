package com.example.aquawatch.ui.screens

import android.net.Uri
import android.widget.ImageView
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import com.example.aquawatch.ui.theme.PrimaryActionButton
import com.example.aquawatch.ui.theme.SecondaryActionButton

@Composable
fun AddDeviceScreen(
    onBack: () -> Unit,
    onSave: (DeviceRow) -> Unit
) {
    var name by remember { mutableStateOf("") }
    var type by remember { mutableStateOf("GPS tracker") }
    var serial by remember { mutableStateOf("") }
    var imei by remember { mutableStateOf("") }
    var sim by remember { mutableStateOf("") }
    var owner by remember { mutableStateOf("") }
    var driver by remember { mutableStateOf("") }
    var vessel by remember { mutableStateOf("") }
    var registration by remember { mutableStateOf("") }
    var contact by remember { mutableStateOf("") }
    var photoUri by remember { mutableStateOf("") }
    var triedSave by remember { mutableStateOf(false) }

    val photoPicker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        photoUri = uri?.toString().orEmpty()
    }
    val missingRequired = name.isBlank() || serial.isBlank() || owner.isBlank() || vessel.isBlank()

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
        contentPadding = PaddingValues(top = 8.dp, bottom = 28.dp)
    ) {
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically
            ) {
                IconButton(onClick = onBack) {
                    Icon(
                        Icons.AutoMirrored.Filled.ArrowBack,
                        contentDescription = "Back",
                        tint = MaterialTheme.colorScheme.onBackground
                    )
                }
                Column {
                    Text("Add device", fontSize = 26.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
                    Text("Register a tracker for coastal monitoring", fontSize = 13.sp, color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.65f))
                }
            }
        }

        item {
            DeviceFormSection("Device identity") {
                DeviceField(name, { name = it }, "Device name", isError = triedSave && name.isBlank())
                DeviceField(type, { type = it }, "Device type")
                DeviceField(serial, { serial = it }, "Serial number", isError = triedSave && serial.isBlank())
                DeviceField(imei, { imei = it.filter(Char::isDigit) }, "IMEI / hardware ID", KeyboardType.Number)
            }
        }

        item {
            DeviceFormSection("Connectivity") {
                DeviceField(sim, { sim = it }, "SIM number", KeyboardType.Phone)
                Text(
                    "Used by the tracker to send live location and emergency updates.",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f)
                )
            }
        }

        item {
            DeviceFormSection("Assignment and vessel") {
                DeviceField(owner, { owner = it }, "Owner full name", isError = triedSave && owner.isBlank())
                DeviceField(driver, { driver = it }, "Driver / operator")
                DeviceField(vessel, { vessel = it }, "Vessel name", isError = triedSave && vessel.isBlank())
                DeviceField(registration, { registration = it }, "Vessel registration number")
                DeviceField(contact, { contact = it }, "Emergency contact number", KeyboardType.Phone)
            }
        }

        item {
            DeviceFormSection("Device picture") {
                if (photoUri.isNotBlank()) {
                    Card(
                        modifier = Modifier.fillMaxWidth().height(180.dp),
                        shape = RoundedCornerShape(14.dp),
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
                            update = { it.setImageURI(Uri.parse(photoUri)) }
                        )
                    }
                }
                SecondaryActionButton(
                    text = if (photoUri.isBlank()) "Add picture" else "Change picture",
                    onClick = { photoPicker.launch("image/*") }
                )
            }
        }

        if (triedSave && missingRequired) {
            item {
                Text(
                    "Device name, serial number, owner, and vessel are required.",
                    color = MaterialTheme.colorScheme.error,
                    fontSize = 12.sp
                )
            }
        }

        item {
            PrimaryActionButton(
                text = "Register device",
                onClick = {
                    triedSave = true
                    if (!missingRequired) {
                        onSave(
                            DeviceRow(
                                id = System.currentTimeMillis().toString(),
                                serial = serial.trim(),
                                sim = sim.trim(),
                                driver = driver.trim(),
                                owner = owner.trim(),
                                vessel = vessel.trim(),
                                reg = registration.trim(),
                                contact = contact.trim(),
                                photoLabel = photoUri,
                                status = "Online",
                                lastLocation = "Awaiting first GPS update",
                                lastUpdated = "Just registered",
                                name = name.trim(),
                                type = type.trim(),
                                imei = imei.trim(),
                                waterLevel = "Awaiting sensor",
                                gyro = "Calibrating",
                                gsm = "Searching",
                                gpsLocation = "Awaiting GPS fix"
                            )
                        )
                    }
                }
            )
        }
    }
}

@Composable
private fun DeviceFormSection(title: String, content: @Composable androidx.compose.foundation.layout.ColumnScope.() -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Text(title, fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.onSurface)
            content()
        }
    }
}

@Composable
private fun DeviceField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    keyboardType: KeyboardType = KeyboardType.Text,
    isError: Boolean = false
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        singleLine = true,
        isError = isError,
        keyboardOptions = KeyboardOptions(keyboardType = keyboardType)
    )
}
