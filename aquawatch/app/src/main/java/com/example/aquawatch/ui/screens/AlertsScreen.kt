package com.example.aquawatch.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.aquawatch.ui.LocalAppLanguage
import com.example.aquawatch.ui.appCopy

data class AlertItem(
    val id: String,
    val type: String,
    val location: String,
    val time: String,
    val severity: String
)

@Composable
fun AlertsScreen() {
    val copy = appCopy(LocalAppLanguage.current)
    var selectedAlert by remember { mutableStateOf<AlertItem?>(null) }
    val alerts = remember { emptyList<AlertItem>() }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
        contentPadding = PaddingValues(vertical = 8.dp)
    ) {
        item {
            Column {
                Text(copy.alerts, fontSize = 28.sp, color = MaterialTheme.colorScheme.onBackground)
                Text(
                    copy.alertsSubtitle,
                    fontSize = 14.sp,
                    color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.68f)
                )
            }
        }

        if (alerts.isEmpty()) {
            item {
                EmptyStateCard(
                    title = copy.noActiveAlerts,
                    message = copy.noActiveAlertsMessage
                )
            }
        } else {
            items(alerts) { alert ->
                AlertCardModern(alert, onDetails = { selectedAlert = alert })
            }
        }

        item {
            Spacer(Modifier.height(8.dp))
        }
    }

    selectedAlert?.let { alert ->
        AlertDetailsDialog(item = alert, onDismiss = { selectedAlert = null })
    }
}

@Composable
fun AlertCardModern(item: AlertItem, onDetails: () -> Unit) {
    val severityColor = when (item.severity) {
        "Critical" -> Color(0xFFE03E3E)
        "High" -> Color(0xFFFFA726)
        "Medium" -> Color(0xFFFFC107)
        else -> Color(0xFF4CAF50)
    }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 6.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(item.type, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurface)
                    Box(
                        modifier = Modifier
                            .background(severityColor, RoundedCornerShape(6.dp))
                            .padding(horizontal = 8.dp, vertical = 4.dp)
                    ) {
                        Text(item.severity, fontSize = 10.sp, color = Color.White)
                    }
                }
                Spacer(Modifier.height(4.dp))
                Text(
                    "Location: ${item.location}",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                )
                Spacer(Modifier.height(4.dp))
                Text(item.time, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f))
            }
            Spacer(Modifier.width(8.dp))
            Button(
                onClick = onDetails,
                modifier = Modifier.height(36.dp),
                shape = RoundedCornerShape(8.dp)
            ) {
                Text("Details", fontSize = 11.sp)
            }
        }
    }
}

@Composable
fun AlertDetailsDialog(item: AlertItem, onDismiss: () -> Unit) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("${item.type} Alert", color = MaterialTheme.colorScheme.onSurface) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("Severity: ${item.severity}")
                Text("Location: ${item.location}")
                Text("Reported: ${item.time} ago")
                Text("Recommended action: review the live map and contact nearby response teams if this alert remains active.")
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
fun EmptyStateCard(title: String, message: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Column(
            modifier = Modifier.padding(18.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Text(title, fontSize = 15.sp, color = MaterialTheme.colorScheme.onSurface)
            Text(message, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f))
        }
    }
}
