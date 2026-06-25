package com.example.aquawatch.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import com.example.aquawatch.ui.theme.AppSurfaceCard
import com.example.aquawatch.ui.theme.Background
import com.example.aquawatch.ui.theme.PrimaryActionButton
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp

data class ReportItem(val id: String, val type: String, val location: String, val severity: String, val time: String)

@Composable
fun ReportsScreen() {
    var description by remember { mutableStateOf("") }
    var location by remember { mutableStateOf("") }
    var severity by remember { mutableStateOf("Medium") }
    val reports = remember { mutableStateListOf<ReportItem>() }

    Column(modifier = Modifier.fillMaxSize().background(Background).padding(16.dp)) {
        AppSurfaceCard(modifier = Modifier.fillMaxWidth()) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text("Submit Incident Report")
                Spacer(Modifier.height(12.dp))
                Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedTextField(value = description, onValueChange = { description = it }, label = { Text("Description") }, modifier = Modifier.fillMaxWidth())
                    OutlinedTextField(value = location, onValueChange = { location = it }, label = { Text("Location") }, modifier = Modifier.fillMaxWidth())
                    OutlinedTextField(value = severity, onValueChange = { severity = it }, label = { Text("Severity") }, modifier = Modifier.fillMaxWidth())
                }
                Spacer(Modifier.height(12.dp))
                PrimaryActionButton(
                    text = "Submit Report",
                    onClick = {
                        if (description.isNotBlank()) {
                            reports.add(
                                ReportItem(
                                    id = (reports.size + 1).toString(),
                                    type = description,
                                    location = location.ifBlank { "No location set" },
                                    severity = severity.ifBlank { "Unspecified" },
                                    time = "Now"
                                )
                            )
                            description = ""
                            location = ""
                            severity = "Medium"
                        }
                    }
                )

                Spacer(Modifier.height(24.dp))
                Text("Report History")
                Spacer(Modifier.height(8.dp))
                if (reports.isEmpty()) {
                    EmptyStateCard(
                        title = "No reports submitted",
                        message = "Submitted incident reports will appear here."
                    )
                } else {
                    LazyColumn(modifier = Modifier.weight(1f)) {
                        items(reports) { r -> ReportRow(r) }
                    }
                }
            }
        }
    }
}

@Composable
fun ReportRow(r: ReportItem) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
        Row(modifier = Modifier.fillMaxWidth().padding(12.dp), horizontalArrangement = Arrangement.SpaceBetween) {
            Column { Text(r.type); Text(r.location) }
            Text(r.severity)
        }
    }
}
