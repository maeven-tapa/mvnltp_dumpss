package com.example.aquawatch.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Help
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.automirrored.filled.VolumeUp
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.DarkMode
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Language
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.MyLocation
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.aquawatch.data.AppLanguage
import com.example.aquawatch.data.UserAccount
import com.example.aquawatch.data.getUserAccount
import com.example.aquawatch.ui.theme.Navy900
import com.example.aquawatch.ui.theme.Seafoam500

private enum class SettingsDialog {
    AppVersion,
    HelpSupport,
    Language,
    AlertSound,
    Logout
}

@Composable
fun SettingsScreen(
    onOpenProfile: () -> Unit = {},
    onOpenLocation: () -> Unit = {},
    darkMode: Boolean = true,
    onDarkModeChange: (Boolean) -> Unit = {},
    language: AppLanguage = AppLanguage.English,
    onLanguageChange: (AppLanguage) -> Unit = {},
    onLogout: () -> Unit = {}
) {
    val context = LocalContext.current
    val account = context.getUserAccount()
    var notificationsEnabled by remember { mutableStateOf(true) }
    var shareLocation by remember { mutableStateOf(true) }
    var activeDialog by remember { mutableStateOf<SettingsDialog?>(null) }
    val copy = settingsCopy(language)

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
        contentPadding = PaddingValues(top = 16.dp, bottom = 24.dp)
    ) {
        item {
            Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Text(copy.settings, fontSize = 28.sp, color = MaterialTheme.colorScheme.onBackground)
                Text(copy.settingsSubtitle, fontSize = 14.sp, color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.65f))
            }
        }

        item { SettingsSectionTitle(copy.account) }
        item {
            AccountSummaryCard(
                account = account,
                onClick = onOpenProfile
            )
        }

        item { SettingsSectionTitle(copy.alerts) }
        item {
            SettingsToggleCard(
                icon = Icons.Filled.Notifications,
                title = copy.enableNotifications,
                subtitle = copy.enableNotificationsSubtitle,
                isEnabled = notificationsEnabled,
                onToggle = { notificationsEnabled = it }
            )
        }
        item {
            SettingsOptionCard(
                icon = Icons.AutoMirrored.Filled.VolumeUp,
                title = copy.alertSound,
                subtitle = copy.alertSoundSubtitle,
                onClick = { activeDialog = SettingsDialog.AlertSound }
            )
        }

        item { SettingsSectionTitle(copy.location) }
        item {
            SettingsToggleCard(
                icon = Icons.Filled.MyLocation,
                title = copy.shareLocation,
                subtitle = copy.shareLocationSubtitle,
                isEnabled = shareLocation,
                onToggle = { shareLocation = it }
            )
        }
        item {
            SettingsOptionCard(
                icon = Icons.Filled.LocationOn,
                title = copy.monitoringLocation,
                subtitle = copy.monitoringLocationSubtitle,
                onClick = onOpenLocation
            )
        }

        item { SettingsSectionTitle(copy.display) }
        item {
            SettingsToggleCard(
                icon = Icons.Filled.DarkMode,
                title = copy.darkMode,
                subtitle = if (darkMode) copy.darkEnabled else copy.lightEnabled,
                isEnabled = darkMode,
                onToggle = onDarkModeChange
            )
        }
        item {
            SettingsOptionCard(
                icon = Icons.Filled.Language,
                title = copy.language,
                subtitle = language.displayName,
                onClick = { activeDialog = SettingsDialog.Language }
            )
        }

        item { SettingsSectionTitle(copy.about) }
        item {
            SettingsOptionCard(
                icon = Icons.Filled.Info,
                title = copy.appVersion,
                subtitle = "AquaWatch v1.0.0",
                onClick = { activeDialog = SettingsDialog.AppVersion }
            )
        }
        item {
            SettingsOptionCard(
                icon = Icons.AutoMirrored.Filled.Help,
                title = copy.helpSupport,
                subtitle = copy.helpSupportSubtitle,
                onClick = { activeDialog = SettingsDialog.HelpSupport }
            )
        }

        item { SettingsSectionTitle(copy.dangerZone, color = Color(0xFFE03E3E)) }
        item {
            SettingsOptionCard(
                icon = Icons.AutoMirrored.Filled.Logout,
                title = copy.logout,
                subtitle = copy.logoutSubtitle,
                iconTint = Color(0xFFE03E3E),
                titleColor = Color(0xFFE03E3E),
                onClick = { activeDialog = SettingsDialog.Logout }
            )
        }
    }

    activeDialog?.let { dialog ->
        SettingsInfoDialog(
            dialog = dialog,
            language = language,
            onLanguageSelected = onLanguageChange,
            onLogout = onLogout,
            onDismiss = { activeDialog = null }
        )
    }
}

@Composable
private fun AccountSummaryCard(account: UserAccount, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(18.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Text(
                    account.fullName,
                    fontSize = 19.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Text(
                    "${account.role} | ${account.email.maskedEmail()}",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.58f),
                    maxLines = 2
                )
            }
            AccountAvatar(account = account, size = 62.dp)
        }
    }
}

private fun String.maskedEmail(): String {
    if (isBlank()) return "Email not added"
    val name = substringBefore("@")
    val domain = substringAfter("@", "")
    val visible = name.take(3)
    val hidden = "*".repeat((name.length - visible.length).coerceAtLeast(3))
    return if (domain.isBlank()) "$visible$hidden" else "$visible$hidden@$domain"
}

private data class SettingsCopy(
    val settings: String,
    val settingsSubtitle: String,
    val account: String,
    val officerProfile: String,
    val officerProfileSubtitle: String,
    val alerts: String,
    val enableNotifications: String,
    val enableNotificationsSubtitle: String,
    val alertSound: String,
    val alertSoundSubtitle: String,
    val location: String,
    val shareLocation: String,
    val shareLocationSubtitle: String,
    val monitoringLocation: String,
    val monitoringLocationSubtitle: String,
    val display: String,
    val darkMode: String,
    val darkEnabled: String,
    val lightEnabled: String,
    val language: String,
    val about: String,
    val appVersion: String,
    val helpSupport: String,
    val helpSupportSubtitle: String,
    val dangerZone: String,
    val logout: String,
    val logoutSubtitle: String
)

private fun settingsCopy(language: AppLanguage): SettingsCopy {
    return when (language) {
        AppLanguage.English -> SettingsCopy(
            settings = "Settings",
            settingsSubtitle = "Manage your AquaWatch workspace",
            account = "Account",
            officerProfile = "Officer Profile",
            officerProfileSubtitle = "Edit name, role, contact, and station details",
            alerts = "Alerts",
            enableNotifications = "Enable Notifications",
            enableNotificationsSubtitle = "Receive incident and device updates",
            alertSound = "Alert Sound",
            alertSoundSubtitle = "Standard Marine Tone",
            location = "Location",
            shareLocation = "Share Location",
            shareLocationSubtitle = "Allow live location use for monitoring",
            monitoringLocation = "Monitoring Location",
            monitoringLocationSubtitle = "Change the coastal area used by the app",
            display = "Display",
            darkMode = "Dark Mode",
            darkEnabled = "Dark appearance enabled",
            lightEnabled = "Light appearance enabled",
            language = "Language",
            about = "About",
            appVersion = "App Version",
            helpSupport = "Help & Support",
            helpSupportSubtitle = "Contact support and view response guidance",
            dangerZone = "Danger Zone",
            logout = "Logout",
            logoutSubtitle = "End this AquaWatch session"
        )
        AppLanguage.Filipino -> SettingsCopy(
            settings = "Mga Setting",
            settingsSubtitle = "Pamahalaan ang AquaWatch workspace",
            account = "Account",
            officerProfile = "Profile ng Officer",
            officerProfileSubtitle = "Baguhin ang pangalan, tungkulin, kontak, at istasyon",
            alerts = "Mga Alerto",
            enableNotifications = "I-on ang Notifications",
            enableNotificationsSubtitle = "Tumanggap ng insidente at device updates",
            alertSound = "Tunog ng Alerto",
            alertSoundSubtitle = "Standard Marine Tone",
            location = "Lokasyon",
            shareLocation = "Ibahagi ang Lokasyon",
            shareLocationSubtitle = "Payagan ang live location para sa monitoring",
            monitoringLocation = "Monitoring Location",
            monitoringLocationSubtitle = "Baguhin ang coastal area ng app",
            display = "Display",
            darkMode = "Dark Mode",
            darkEnabled = "Naka-enable ang dark appearance",
            lightEnabled = "Naka-enable ang light appearance",
            language = "Wika",
            about = "Tungkol",
            appVersion = "Bersyon ng App",
            helpSupport = "Tulong at Support",
            helpSupportSubtitle = "Makipag-ugnayan sa support at response guidance",
            dangerZone = "Danger Zone",
            logout = "Logout",
            logoutSubtitle = "Tapusin ang AquaWatch session"
        )
        AppLanguage.Cebuano -> SettingsCopy(
            settings = "Mga Setting",
            settingsSubtitle = "Dumala sa imong AquaWatch workspace",
            account = "Account",
            officerProfile = "Profile sa Officer",
            officerProfileSubtitle = "Usba ang ngalan, role, kontak, ug estasyon",
            alerts = "Mga Alerto",
            enableNotifications = "I-on ang Notifications",
            enableNotificationsSubtitle = "Dawata ang incident ug device updates",
            alertSound = "Tingog sa Alerto",
            alertSoundSubtitle = "Standard Marine Tone",
            location = "Lokasyon",
            shareLocation = "I-share ang Lokasyon",
            shareLocationSubtitle = "Tugoti ang live location para sa monitoring",
            monitoringLocation = "Monitoring Location",
            monitoringLocationSubtitle = "Usba ang coastal area nga gamiton sa app",
            display = "Display",
            darkMode = "Dark Mode",
            darkEnabled = "Naka-enable ang dark appearance",
            lightEnabled = "Naka-enable ang light appearance",
            language = "Pinulongan",
            about = "Mahitungod",
            appVersion = "Bersyon sa App",
            helpSupport = "Tabang ug Support",
            helpSupportSubtitle = "Kontaka ang support ug tan-awa ang response guidance",
            dangerZone = "Danger Zone",
            logout = "Logout",
            logoutSubtitle = "Tapusa ang AquaWatch session"
        )
    }
}

@Composable
private fun SettingsSectionTitle(text: String, color: Color = Navy900) {
    val resolvedColor = if (color == Navy900) MaterialTheme.colorScheme.onBackground else color
    Text(text, fontSize = 15.sp, color = resolvedColor)
}

@Composable
fun SettingsOptionCard(
    icon: ImageVector,
    title: String,
    subtitle: String,
    onClick: () -> Unit,
    iconTint: Color = Seafoam500,
    titleColor: Color = Navy900
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .defaultMinSize(minHeight = 76.dp)
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier.weight(1f)
            ) {
                Icon(icon, contentDescription = title, modifier = Modifier.size(24.dp), tint = iconTint)
                Spacer(Modifier.width(12.dp))
                Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                    Text(
                        title,
                        fontSize = 15.sp,
                        color = if (titleColor == Navy900) MaterialTheme.colorScheme.onSurface else titleColor
                    )
                    Text(subtitle, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.62f))
                }
            }
            Icon(
                Icons.Filled.ChevronRight,
                contentDescription = null,
                modifier = Modifier.size(24.dp),
                tint = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f)
            )
        }
    }
}

@Composable
fun SettingsToggleCard(
    icon: ImageVector,
    title: String,
    subtitle: String,
    isEnabled: Boolean,
    onToggle: (Boolean) -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .defaultMinSize(minHeight = 76.dp),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier.weight(1f)
            ) {
                Icon(icon, contentDescription = title, modifier = Modifier.size(24.dp), tint = Seafoam500)
                Spacer(Modifier.width(12.dp))
                Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                    Text(title, fontSize = 15.sp, color = MaterialTheme.colorScheme.onSurface)
                    Text(subtitle, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.62f))
                }
            }
            Switch(
                checked = isEnabled,
                onCheckedChange = onToggle,
                colors = SwitchDefaults.colors(
                    checkedTrackColor = Seafoam500,
                    uncheckedTrackColor = Color.Gray
                )
            )
        }
    }
}

@Composable
private fun SettingsInfoDialog(
    dialog: SettingsDialog,
    language: AppLanguage,
    onLanguageSelected: (AppLanguage) -> Unit,
    onLogout: () -> Unit,
    onDismiss: () -> Unit
) {
    val title = when (dialog) {
        SettingsDialog.AppVersion -> "App Version"
        SettingsDialog.HelpSupport -> "Help & Support"
        SettingsDialog.Language -> "Language"
        SettingsDialog.AlertSound -> "Alert Sound"
        SettingsDialog.Logout -> "Logout"
    }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(title, color = MaterialTheme.colorScheme.onSurface) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                when (dialog) {
                    SettingsDialog.AppVersion -> {
                        Text("AquaWatch v1.0.0")
                        Text("Coastal safety monitoring prototype for alerts, devices, weather, and maps.")
                    }
                    SettingsDialog.HelpSupport -> {
                        Text("For urgent coastal incidents, contact your local response center first.")
                        Text("Support: support@aquawatch.local")
                        Text("Use Alerts for incidents and Devices for tracker registration.")
                    }
                    SettingsDialog.Language -> {
                        AppLanguage.values().forEach { option ->
                            Button(
                                onClick = {
                                    onLanguageSelected(option)
                                    onDismiss()
                                },
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Text(if (option == language) "${option.displayName} selected" else option.displayName)
                            }
                        }
                    }
                    SettingsDialog.AlertSound -> {
                        Text("Current sound: Standard Marine Tone")
                        Text("This can be connected to Android notification channels when push alerts are added.")
                    }
                    SettingsDialog.Logout -> {
                        Text("Sign out and return to the start screen.")
                    }
                }
            }
        },
        confirmButton = {
            when (dialog) {
                SettingsDialog.Language -> Unit
                SettingsDialog.Logout -> {
                    Button(onClick = onLogout) {
                        Text("Logout")
                    }
                }
                else -> {
                    Button(onClick = onDismiss) {
                        Text("Close")
                    }
                }
            }
        },
        dismissButton = {
            if (dialog == SettingsDialog.Logout) {
                Button(onClick = onDismiss) {
                    Text("Cancel")
                }
            }
        },
        shape = RoundedCornerShape(16.dp)
    )
}
