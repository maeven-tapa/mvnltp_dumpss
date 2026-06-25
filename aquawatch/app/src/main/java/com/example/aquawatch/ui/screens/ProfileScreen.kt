package com.example.aquawatch.ui.screens

import android.content.Intent
import android.net.Uri
import android.widget.ImageView
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import com.example.aquawatch.data.UserAccount
import com.example.aquawatch.data.accountPasswordMatches
import com.example.aquawatch.data.getUserAccount
import com.example.aquawatch.data.hasAccountPassword
import com.example.aquawatch.data.saveAccountPassword
import com.example.aquawatch.data.saveUserAccount
import com.example.aquawatch.ui.theme.PrimaryActionButton
import com.example.aquawatch.ui.theme.Seafoam500
import com.example.aquawatch.ui.theme.SecondaryActionButton

@Composable
fun ProfileScreen(onBack: () -> Unit = {}) {
    val context = LocalContext.current
    var account by remember { mutableStateOf(context.getUserAccount()) }
    var firstName by remember { mutableStateOf(account.firstName) }
    var lastName by remember { mutableStateOf(account.lastName) }
    var email by remember { mutableStateOf(account.email) }
    var phone by remember { mutableStateOf(account.phone) }
    var role by remember { mutableStateOf(account.role) }
    var station by remember { mutableStateOf(account.station) }
    var profileMessage by remember { mutableStateOf("") }
    var currentPassword by remember { mutableStateOf("") }
    var newPassword by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var passwordMessage by remember { mutableStateOf("") }
    var passwordError by remember { mutableStateOf(false) }

    val photoPicker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        if (uri != null) {
            runCatching {
                context.contentResolver.takePersistableUriPermission(uri, Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            account = account.copy(imageUri = uri.toString())
            context.saveUserAccount(account)
        }
    }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
        contentPadding = PaddingValues(top = 12.dp, bottom = 28.dp)
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
                    Text("Profile", fontSize = 26.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
                    Text("Account and security", fontSize = 13.sp, color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.65f))
                }
            }
        }

        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(20.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 6.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(18.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    AccountAvatar(account = account, size = 76.dp)
                    Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(3.dp)) {
                        Text(account.fullName, fontSize = 19.sp, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.onSurface)
                        Text(account.role, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f))
                        Text(account.email.ifBlank { "Email not added" }, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.55f))
                    }
                }
                SecondaryActionButton(
                    text = if (account.imageUri.isBlank()) "Add profile picture" else "Change profile picture",
                    modifier = Modifier.padding(start = 18.dp, end = 18.dp, bottom = 18.dp),
                    onClick = { photoPicker.launch(arrayOf("image/*")) }
                )
            }
        }

        item {
            ProfileFormCard(title = "Personal and work details") {
                Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    ProfileField(firstName, { firstName = it }, "First name", Modifier.weight(1f))
                    ProfileField(lastName, { lastName = it }, "Last name", Modifier.weight(1f))
                }
                ProfileField(email, { email = it }, "Email address", keyboardType = KeyboardType.Email)
                ProfileField(phone, { phone = it }, "Phone number", keyboardType = KeyboardType.Phone)
                ProfileField(role, { role = it }, "Role / position")
                ProfileField(station, { station = it }, "Station / office")
                if (profileMessage.isNotBlank()) {
                    Text(profileMessage, color = Seafoam500, fontSize = 12.sp)
                }
                PrimaryActionButton(
                    text = "Save profile",
                    onClick = {
                        account = UserAccount(
                            firstName = firstName.trim().ifBlank { "AquaWatch" },
                            lastName = lastName.trim().ifBlank { "User" },
                            email = email.trim(),
                            phone = phone.trim(),
                            role = role.trim().ifBlank { "Coastal responder" },
                            station = station.trim(),
                            imageUri = account.imageUri
                        )
                        context.saveUserAccount(account)
                        profileMessage = "Profile saved"
                    }
                )
            }
        }

        item {
            ProfileFormCard(title = "Change password") {
                if (context.hasAccountPassword()) {
                    PasswordField(currentPassword, { currentPassword = it }, "Current password")
                }
                PasswordField(newPassword, { newPassword = it }, "New password")
                PasswordField(confirmPassword, { confirmPassword = it }, "Confirm new password")
                Text(
                    "Use at least 8 characters.",
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                    fontSize = 12.sp
                )
                if (passwordMessage.isNotBlank()) {
                    Text(
                        passwordMessage,
                        color = if (passwordError) MaterialTheme.colorScheme.error else Seafoam500,
                        fontSize = 12.sp
                    )
                }
                PrimaryActionButton(
                    text = "Update password",
                    onClick = {
                        passwordError = true
                        passwordMessage = when {
                            !context.accountPasswordMatches(currentPassword) -> "Current password is incorrect"
                            newPassword.length < 8 -> "New password must be at least 8 characters"
                            newPassword != confirmPassword -> "New passwords do not match"
                            else -> {
                                context.saveAccountPassword(newPassword)
                                currentPassword = ""
                                newPassword = ""
                                confirmPassword = ""
                                passwordError = false
                                "Password updated"
                            }
                        }
                    }
                )
            }
        }
    }
}

@Composable
fun AccountAvatar(account: UserAccount, size: Dp) {
    if (account.imageUri.isNotBlank()) {
        AndroidView(
            modifier = Modifier.size(size).clip(CircleShape),
            factory = { imageContext ->
                ImageView(imageContext).apply {
                    scaleType = ImageView.ScaleType.CENTER_CROP
                    setImageURI(Uri.parse(account.imageUri))
                }
            },
            update = { it.setImageURI(Uri.parse(account.imageUri)) }
        )
    } else {
        Box(
            modifier = Modifier.size(size).background(Seafoam500, CircleShape),
            contentAlignment = Alignment.Center
        ) {
            Text(account.initials, color = Color.White, fontSize = (size.value * 0.3f).sp, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
private fun ProfileFormCard(title: String, content: @Composable ColumnScope.() -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(title, fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.onSurface)
            content()
        }
    }
}

@Composable
private fun ProfileField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier.fillMaxWidth(),
    keyboardType: KeyboardType = KeyboardType.Text
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = keyboardType)
    )
}

@Composable
private fun PasswordField(value: String, onValueChange: (String) -> Unit, label: String) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        singleLine = true,
        visualTransformation = PasswordVisualTransformation(),
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password)
    )
}
