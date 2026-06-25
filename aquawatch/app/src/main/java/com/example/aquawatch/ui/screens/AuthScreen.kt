package com.example.aquawatch.ui.screens

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.  padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.aquawatch.R
import com.example.aquawatch.data.UserAccount
import com.example.aquawatch.data.accountPasswordMatches
import com.example.aquawatch.data.getUserAccount
import com.example.aquawatch.data.hasAccountPassword
import com.example.aquawatch.data.saveAccountPassword
import com.example.aquawatch.data.saveUserAccount
import com.example.aquawatch.ui.AppCopy
import com.example.aquawatch.ui.LocalAppLanguage
import com.example.aquawatch.ui.appCopy
import com.example.aquawatch.ui.theme.PrimaryActionButton
import com.example.aquawatch.ui.theme.Seafoam500

@Composable
fun AuthScreen(onAuthenticated: () -> Unit) {
    val context = LocalContext.current
    var isLogin by remember { mutableStateOf(true) }
    var showTerms by remember { mutableStateOf(false) }
    val backgroundColor = MaterialTheme.colorScheme.background
    val copy = appCopy(LocalAppLanguage.current)

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(backgroundColor)
    ) {
        Image(
            painter = painterResource(R.drawable.auth_hero),
            contentDescription = null,
            contentScale = ContentScale.Crop,
            modifier = Modifier
                .fillMaxWidth()
                .height(310.dp)
        )
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(310.dp)
                .background(
                    Brush.verticalGradient(
                        listOf(Color(0x33000000), Color(0xCC071B33), backgroundColor)
                    )
                )
        )

        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 18.dp, vertical = 26.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(42.dp))
            Text(
                text = "AquaWatch",
                color = Color.White,
                fontSize = 34.sp,
                fontWeight = FontWeight.ExtraBold
            )
            Text(
                text = copy.authSubtitle,
                color = Color(0xDDEAF8FF),
                fontSize = 14.sp,
                textAlign = TextAlign.Center
            )
            Spacer(Modifier.height(34.dp))

            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(28.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 12.dp)
            ) {
                Column(
                    modifier = Modifier.padding(18.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    AuthModeSwitch(
                        isLogin = isLogin,
                        onLogin = { isLogin = true },
                        onSignup = { isLogin = false },
                        loginText = copy.login,
                        signUpText = copy.signUp
                    )
                    if (isLogin) {
                        LoginFormModern(
                            onSubmit = { email, password ->
                                val existing = context.getUserAccount()
                                when {
                                    existing.email.isNotBlank() && !existing.email.equals(email.trim(), ignoreCase = true) -> {
                                        "Email does not match this account"
                                    }
                                    context.hasAccountPassword() && !context.accountPasswordMatches(password) -> {
                                        "Incorrect password"
                                    }
                                    else -> {
                                        context.saveUserAccount(existing.copy(email = email.trim()))
                                        onAuthenticated()
                                        null
                                    }
                                }
                            },
                            copy = copy
                        )
                    } else {
                        SignupFormModern(
                            onSubmit = { account, password ->
                                context.saveUserAccount(account)
                                context.saveAccountPassword(password)
                                onAuthenticated()
                            },
                            copy = copy,
                            onOpenTerms = { showTerms = true }
                        )
                    }
                }
            }
        }
        if (showTerms) {
            TermsAndConditionsScreen(onBack = { showTerms = false })
        }
    }
}

@Composable
private fun AuthModeSwitch(
    isLogin: Boolean,
    onLogin: () -> Unit,
    onSignup: () -> Unit,
    loginText: String,
    signUpText: String
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(MaterialTheme.colorScheme.onSurface.copy(alpha = 0.08f), RoundedCornerShape(18.dp))
            .padding(4.dp),
        horizontalArrangement = Arrangement.spacedBy(4.dp)
    ) {
        TabButton(
            text = loginText,
            isActive = isLogin,
            onClick = onLogin,
            modifier = Modifier.weight(1f)
        )
        TabButton(
            text = signUpText,
            isActive = !isLogin,
            onClick = onSignup,
            modifier = Modifier.weight(1f)
        )
    }
}

@Composable
fun TabButton(
    text: String,
    isActive: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Button(
        onClick = onClick,
        modifier = modifier.height(44.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = if (isActive) MaterialTheme.colorScheme.primary else Color.Transparent,
            contentColor = if (isActive) {
                MaterialTheme.colorScheme.onPrimary
            } else {
                MaterialTheme.colorScheme.onSurface
            }
        ),
        elevation = ButtonDefaults.buttonElevation(defaultElevation = 0.dp),
        shape = RoundedCornerShape(14.dp)
    ) {
        Text(text, fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
    }
}

@Composable
fun LoginFormModern(onSubmit: (String, String) -> String?, copy: AppCopy) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var recoveryMessage by remember { mutableStateOf("") }
    var signInError by remember { mutableStateOf("") }

    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text(
            copy.welcomeBack,
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface
        )
        Text(
            copy.signInSubtitle,
            fontSize = 13.sp,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
        )

        OutlinedTextField(
            value = email,
            onValueChange = { email = it },
            label = { Text(copy.email) },
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(16.dp),
            singleLine = true
        )

        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text(copy.password) },
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(16.dp),
            singleLine = true
        )

        TextButton(
            onClick = { recoveryMessage = "Password recovery link prepared for ${email.ifBlank { "your email" }}" },
            modifier = Modifier.align(Alignment.End)
        ) {
            Text(copy.forgotPassword, color = Seafoam500, fontSize = 12.sp)
        }
        if (recoveryMessage.isNotBlank()) {
            Text(recoveryMessage, color = MaterialTheme.colorScheme.onSurface, fontSize = 12.sp)
        }

        if (signInError.isNotBlank()) {
            ValidationMessage(signInError)
        }
        PrimaryActionButton(
            text = copy.signIn,
            onClick = {
                signInError = when {
                    !email.isValidEmail() -> "Enter a valid email address"
                    password.isBlank() -> "Enter your password"
                    else -> onSubmit(email, password).orEmpty()
                }
            }
        )
    }
}

@Composable
fun SignupFormModern(
    onSubmit: (UserAccount, String) -> Unit,
    copy: AppCopy,
    onOpenTerms: () -> Unit = {}
) {
    var step by remember { mutableStateOf(1) }
    var firstName by remember { mutableStateOf("") }
    var lastName by remember { mutableStateOf("") }
    var countryExpanded by remember { mutableStateOf(false) }
    var country by remember { mutableStateOf(countryCodes.first()) }
    var phone by remember { mutableStateOf("") }
    var role by remember { mutableStateOf("") }
    var station by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var acceptedTerms by remember { mutableStateOf(false) }
    var triedNext by remember { mutableStateOf(false) }
    var triedSubmit by remember { mutableStateOf(false) }

    val firstNameError = triedNext && firstName.isBlank()
    val lastNameError = triedNext && lastName.isBlank()
    val phoneError = triedNext && phone.filter(Char::isDigit).length < 7
    val roleError = triedNext && role.isBlank()
    val stationError = triedNext && station.isBlank()
    val emailError = triedSubmit && !email.isValidEmail()
    val passwordError = triedSubmit && password.length < 8
    val confirmPasswordError = triedSubmit && confirmPassword != password
    val termsError = triedSubmit && !acceptedTerms

    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text(
            copy.createAccount,
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface
        )
        Text(
            copy.createAccountSubtitle,
            fontSize = 13.sp,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
        )
        Text(
            "Step $step of 2",
            fontSize = 12.sp,
            color = Seafoam500,
            fontWeight = FontWeight.SemiBold
        )

        if (step == 1) {
            OutlinedTextField(
                value = firstName,
                onValueChange = { firstName = it },
                label = { Text("First Name") },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                singleLine = true,
                isError = firstNameError
            )
            if (firstNameError) ValidationMessage("First name is required")

            OutlinedTextField(
                value = lastName,
                onValueChange = { lastName = it },
                label = { Text("Last Name") },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                singleLine = true,
                isError = lastNameError
            )
            if (lastNameError) ValidationMessage("Last name is required")

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.Top
            ) {
                Box {
                    OutlinedTextField(
                        value = country.dialCode,
                        onValueChange = {},
                        label = { Text("Code") },
                        modifier = Modifier.width(96.dp),
                        shape = RoundedCornerShape(16.dp),
                        singleLine = true,
                        readOnly = true
                    )
                    Box(
                        modifier = Modifier
                            .matchParentSize()
                            .clickable { countryExpanded = true }
                    )
                    DropdownMenu(
                        expanded = countryExpanded,
                        onDismissRequest = { countryExpanded = false },
                        modifier = Modifier.width(196.dp)
                    ) {
                        countryCodes.forEach { option ->
                            DropdownMenuItem(
                                text = { Text("${option.name} (${option.dialCode})") },
                                onClick = {
                                    country = option
                                    countryExpanded = false
                                }
                            )
                        }
                    }
                }
                OutlinedTextField(
                    value = phone,
                    onValueChange = { phone = it },
                    label = { Text("Phone Number") },
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(16.dp),
                    singleLine = true,
                    isError = phoneError,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone)
                )
            }
            Text(
                country.name,
                fontSize = 11.sp,
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.62f)
            )
            if (phoneError) ValidationMessage("Enter a valid phone number")

            OutlinedTextField(
                value = role,
                onValueChange = { role = it },
                label = { Text("Role / Position") },
                placeholder = { Text("e.g. Coastal Safety Officer") },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                singleLine = true,
                isError = roleError
            )
            if (roleError) ValidationMessage("Role or position is required")

            OutlinedTextField(
                value = station,
                onValueChange = { station = it },
                label = { Text("Station / Office") },
                placeholder = { Text("e.g. Manila Bay Operations Center") },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                singleLine = true,
                isError = stationError
            )
            if (stationError) ValidationMessage("Station or office is required")

            Button(
                onClick = {
                    triedNext = true
                    if (!firstNameError && !lastNameError && !phoneError && !roleError && !stationError &&
                        firstName.isNotBlank() && lastName.isNotBlank() &&
                        phone.filter(Char::isDigit).length >= 7 && role.isNotBlank() && station.isNotBlank()
                    ) {
                        step = 2
                    }
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(50.dp),
                shape = RoundedCornerShape(16.dp)
            ) {
                Text("Next")
            }
        } else {
            OutlinedTextField(
                value = email,
                onValueChange = { email = it },
                label = { Text(copy.email) },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                singleLine = true,
                isError = emailError,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email)
            )
            if (emailError) ValidationMessage("Enter a valid email address")

            OutlinedTextField(
                value = password,
                onValueChange = { password = it },
                label = { Text("${copy.password} (minimum 8)") },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                singleLine = true,
                isError = passwordError,
                visualTransformation = PasswordVisualTransformation()
            )
            if (passwordError) ValidationMessage("Password must be at least 8 characters")

            OutlinedTextField(
                value = confirmPassword,
                onValueChange = { confirmPassword = it },
                label = { Text(copy.confirmPassword) },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                singleLine = true,
                isError = confirmPasswordError,
                visualTransformation = PasswordVisualTransformation()
            )
            if (confirmPasswordError) ValidationMessage("Passwords do not match")

            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier.fillMaxWidth()
            ) {
                Checkbox(
                    checked = acceptedTerms,
                    onCheckedChange = { acceptedTerms = it }
                )
                Text(
                    copy.terms,
                    fontSize = 12.sp,
                    color = if (termsError) MaterialTheme.colorScheme.error else Seafoam500,
                    textDecoration = TextDecoration.Underline,
                    modifier = Modifier
                        .weight(1f)
                        .clickable(onClick = onOpenTerms)
                )
            }
            if (termsError) ValidationMessage("You must agree before creating an account")

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(
                    onClick = { step = 1 },
                    modifier = Modifier
                        .weight(1f)
                        .height(50.dp),
                    shape = RoundedCornerShape(16.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.12f))
                ) {
                    Text("Back", color = MaterialTheme.colorScheme.onSurface)
                }
                Button(
                    onClick = {
                        triedSubmit = true
                        if (email.isValidEmail() && password.length >= 8 &&
                            confirmPassword == password && acceptedTerms
                        ) {
                            onSubmit(
                                UserAccount(
                                    firstName = firstName.trim(),
                                    lastName = lastName.trim(),
                                    email = email.trim(),
                                    phone = "${country.dialCode} ${phone.trim()}",
                                    role = role.trim(),
                                    station = station.trim()
                                ),
                                password
                            )
                        }
                    },
                    modifier = Modifier
                        .weight(1f)
                        .height(50.dp),
                    shape = RoundedCornerShape(16.dp)
                ) {
                    Text(copy.createAccount)
                }
            }
        }
    }
}

@Composable
private fun ValidationMessage(text: String) {
    Text(
        text = text,
        color = MaterialTheme.colorScheme.error,
        fontSize = 11.sp,
        modifier = Modifier.padding(start = 4.dp)
    )
}

private data class CountryCode(
    val name: String,
    val dialCode: String
)

private val countryCodes = listOf(
    CountryCode("Philippines", "+63"),
    CountryCode("United States", "+1"),
    CountryCode("Canada", "+1"),
    CountryCode("United Kingdom", "+44"),
    CountryCode("Australia", "+61"),
    CountryCode("Japan", "+81"),
    CountryCode("Singapore", "+65"),
    CountryCode("Indonesia", "+62"),
    CountryCode("Malaysia", "+60")
)

private fun String.isValidEmail(): Boolean {
    return matches(Regex("^[A-Za-z0-9+_.-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$"))
}
