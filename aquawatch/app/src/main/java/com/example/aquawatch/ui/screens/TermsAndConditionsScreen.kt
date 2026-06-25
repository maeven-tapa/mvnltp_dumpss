package com.example.aquawatch.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@Composable
fun TermsAndConditionsScreen(onBack: () -> Unit) {
    Surface(
        modifier = Modifier.fillMaxSize(),
        color = MaterialTheme.colorScheme.background
    ) {
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.background)
                .statusBarsPadding()
                .navigationBarsPadding()
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
                        Text(
                            "Terms and Conditions",
                            fontSize = 25.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onBackground
                        )
                        Text(
                            "Effective June 18, 2026",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.62f)
                        )
                    }
                }
            }

            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(18.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
                ) {
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(18.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        Text(
                            "Please read these terms before creating an AquaWatch account. By checking the agreement box, you accept these terms.",
                            fontSize = 14.sp,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        HorizontalDivider(color = MaterialTheme.colorScheme.outline.copy(alpha = 0.25f))
                        TermsSection(
                            "1. Service purpose",
                            "AquaWatch supports coastal monitoring, weather awareness, device tracking, and incident response. It does not replace official emergency services, maritime authorities, or professional safety advice."
                        )
                        TermsSection(
                            "2. Account responsibility",
                            "You must provide accurate account and station details, protect your password, and promptly report unauthorized access. Activity performed through your account is your responsibility."
                        )
                        TermsSection(
                            "3. Location and monitoring data",
                            "The app may process monitoring-area coordinates, device locations, uploaded images, and GPS data to provide maps and safety features. Only add locations and devices you are authorized to monitor."
                        )
                        TermsSection(
                            "4. Weather information",
                            "Weather and forecast information is supplied by third-party services and may be delayed or inaccurate. Confirm critical decisions with official local weather and maritime advisories."
                        )
                        TermsSection(
                            "5. Acceptable use",
                            "Do not misuse the app, submit unlawful or misleading information, interfere with its operation, or use tracking features to monitor people or property without permission."
                        )
                        TermsSection(
                            "6. Photos and device records",
                            "You are responsible for having permission to upload profile, monitoring-area, vessel, and device photos. Avoid uploading confidential information that is not needed for monitoring."
                        )
                        TermsSection(
                            "7. Availability and liability",
                            "The service may be changed, interrupted, or unavailable. To the extent allowed by law, AquaWatch is not liable for losses caused by unavailable services, inaccurate third-party data, or misuse of the app."
                        )
                        TermsSection(
                            "8. Changes and contact",
                            "These terms may be updated as the service develops. Continued use after an update means you accept the revised terms. Contact your AquaWatch administrator for account, privacy, or support concerns."
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun TermsSection(title: String, body: String) {
    Column(verticalArrangement = Arrangement.spacedBy(5.dp)) {
        Text(
            title,
            fontSize = 15.sp,
            fontWeight = FontWeight.SemiBold,
            color = MaterialTheme.colorScheme.onSurface
        )
        Text(
            body,
            fontSize = 13.sp,
            lineHeight = 19.sp,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.72f)
        )
    }
}
