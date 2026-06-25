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
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.aquawatch.ui.theme.Background
import com.example.aquawatch.ui.theme.Danger
import com.example.aquawatch.ui.theme.Navy900
import com.example.aquawatch.ui.theme.PrimaryActionButton
import com.example.aquawatch.ui.theme.Success
import com.example.aquawatch.ui.theme.Warning

data class ReportItem(
    val id: String,
    val type: String,
    val location: String,
    val severity: String,
    val time: String
)

@Composable
fun ReportsScreen() {
    var description by remember { mutableStateOf("") }
    var location by remember { mutableStateOf("") }
    var severity by remember { mutableStateOf("Medium") }
    var submittedReport by remember { mutableStateOf<ReportItem?>(null) }
    val reports = remember { mutableStateListOf<ReportItem>() }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(Background)
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
        contentPadding = PaddingValues(vertical = 16.dp)
    ) {
        item {
            Column {
                Text(
                    "Incident Reports",
                    fontSize = 28.sp,
                    color = MaterialTheme.colorScheme.onBackground
                )
                Text(
                    "Create and review coastal incident reports",
                    fontSize = 14.sp,
                    color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.68f)
                )
            }
        }

        item {
            IncidentReportWindow(
                description = description,
                location = location,
                severity = severity,
                onDescriptionChange = { description = it },
                onLocationChange = { location = it },
                onSeverityChange = { severity = it },
                onSubmit = {
                    if (description.isNotBlank()) {
                        val report = ReportItem(
                            id = (reports.size + 1).toString(),
                            type = description.trim(),
                            location = location.ifBlank { "No location set" },
                            severity = severity.ifBlank { "Unspecified" },
                            time = "Now"
                        )
                        reports.add(0, report)
                        submittedReport = report
                        description = ""
                        location = ""
                        severity = "Medium"
                    }
                }
            )
        }

        item {
            Text(
                "Report History",
                fontSize = 16.sp,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.onBackground
            )
        }

        if (reports.isEmpty()) {
            item {
                EmptyStateCard(
                    title = "No reports submitted",
                    message = "Submitted incident reports will appear here."
                )
            }
        } else {
            items(reports, key = { it.id }) { report ->
                ReportRow(report)
            }
        }

        item {
            Spacer(Modifier.height(8.dp))
        }
    }

    submittedReport?.let { report ->
        ReportSubmittedDialog(report = report, onDismiss = { submittedReport = null })
    }
}

@Composable
private fun IncidentReportWindow(
    description: String,
    location: String,
    severity: String,
    onDescriptionChange: (String) -> Unit,
    onLocationChange: (String) -> Unit,
    onSeverityChange: (String) -> Unit,
    onSubmit: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 10.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        "Submit Incident Report",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    Text(
                        "Log location, severity, and details for response tracking.",
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.64f)
                    )
                }
                Box(
                    modifier = Modifier
                        .background(Navy900.copy(alpha = 0.10f), RoundedCornerShape(10.dp))
                        .padding(horizontal = 10.dp, vertical = 6.dp)
                ) {
                    Text("New", fontSize = 11.sp, color = Navy900, fontWeight = FontWeight.SemiBold)
                }
            }

            OutlinedTextField(
                value = description,
                onValueChange = onDescriptionChange,
                label = { Text("Description") },
                minLines = 3,
                modifier = Modifier.fillMaxWidth()
            )
            OutlinedTextField(
                value = location,
                onValueChange = onLocationChange,
                label = { Text("Location") },
                modifier = Modifier.fillMaxWidth()
            )

            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text(
                    "Severity",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf("Low", "Medium", "High", "Critical").forEach { option ->
                        SeverityButton(
                            label = option,
                            selected = severity == option,
                            onClick = { onSeverityChange(option) }
                        )
                    }
                }
            }

            PrimaryActionButton(text = "Add Report", onClick = onSubmit)
        }
    }
}

@Composable
private fun SeverityButton(label: String, selected: Boolean, onClick: () -> Unit) {
    val color = severityColor(label)

    if (selected) {
        Button(
            onClick = onClick,
            modifier = Modifier.height(36.dp),
            shape = RoundedCornerShape(10.dp),
            colors = ButtonDefaults.buttonColors(containerColor = color, contentColor = Color.White),
            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 0.dp)
        ) {
            Text(label, fontSize = 12.sp, maxLines = 1)
        }
    } else {
        OutlinedButton(
            onClick = onClick,
            modifier = Modifier.height(36.dp),
            shape = RoundedCornerShape(10.dp),
            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 0.dp)
        ) {
            Text(label, fontSize = 12.sp, color = color, maxLines = 1)
        }
    }
}

@Composable
fun ReportRow(r: ReportItem) {
    val severityColor = severityColor(r.severity)

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 5.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        r.type,
                        fontSize = 15.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurface,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                    Spacer(Modifier.height(4.dp))
                    Text(
                        r.location,
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
                Spacer(Modifier.width(10.dp))
                SeverityBadge(label = r.severity, color = severityColor)
            }

            Text(
                "Submitted ${r.time}",
                fontSize = 11.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.58f)
            )
        }
    }
}

@Composable
private fun SeverityBadge(label: String, color: Color) {
    Box(
        modifier = Modifier
            .background(color, RoundedCornerShape(8.dp))
            .padding(horizontal = 9.dp, vertical = 5.dp)
    ) {
        Text(label, fontSize = 10.sp, color = Color.White, fontWeight = FontWeight.SemiBold)
    }
}

@Composable
private fun ReportSubmittedDialog(report: ReportItem, onDismiss: () -> Unit) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Report Submitted") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("Your incident report was added to the history.")
                Text("Severity: ${report.severity}")
                Text("Location: ${report.location}")
            }
        },
        confirmButton = {
            Button(onClick = onDismiss) {
                Text("Close")
            }
        },
        shape = RoundedCornerShape(14.dp)
    )
}

private fun severityColor(severity: String): Color {
    return when (severity) {
        "Critical" -> Danger
        "High" -> Warning
        "Medium" -> Color(0xFFFFC107)
        else -> Success
    }
}
