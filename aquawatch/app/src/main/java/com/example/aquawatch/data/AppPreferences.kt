package com.example.aquawatch.data

import android.content.Context
import com.google.android.gms.maps.model.LatLng
import java.security.MessageDigest

data class MonitoringArea(
    val latitude: Double,
    val longitude: Double,
    val label: String,
    val imageUri: String = ""
) {
    val latLng: LatLng
        get() = LatLng(latitude, longitude)
}

data class UserAccount(
    val firstName: String = "AquaWatch",
    val lastName: String = "User",
    val email: String = "",
    val phone: String = "",
    val role: String = "Coastal responder",
    val station: String = "",
    val imageUri: String = ""
) {
    val fullName: String
        get() = listOf(firstName, lastName).filter { it.isNotBlank() }.joinToString(" ")

    val initials: String
        get() = listOf(firstName, lastName)
            .mapNotNull { it.trim().firstOrNull()?.uppercase() }
            .joinToString("")
            .take(2)
            .ifBlank { "AW" }
}

enum class AppLanguage(val displayName: String) {
    English("English"),
    Filipino("Filipino"),
    Cebuano("Cebuano");

    companion object {
        fun fromDisplayName(value: String): AppLanguage {
            return values().firstOrNull { it.displayName == value } ?: English
        }
    }
}

private const val SessionPrefsName = "aquawatch_session"
private const val SignedInKey = "signed_in"
private const val MonitoringAreaSavedKey = "monitoring_area_saved"
private const val MonitoringAreaLatKey = "monitoring_area_lat"
private const val MonitoringAreaLngKey = "monitoring_area_lng"
private const val MonitoringAreaLabelKey = "monitoring_area_label"
private const val MonitoringAreaImageUriKey = "monitoring_area_image_uri"
private const val LanguageKey = "language"
private const val AccountFirstNameKey = "account_first_name"
private const val AccountLastNameKey = "account_last_name"
private const val AccountEmailKey = "account_email"
private const val AccountPhoneKey = "account_phone"
private const val AccountRoleKey = "account_role"
private const val AccountStationKey = "account_station"
private const val AccountImageUriKey = "account_image_uri"
private const val AccountPasswordHashKey = "account_password_hash"
private const val DefaultLatitude = 14.5995
private const val DefaultLongitude = 120.9842

fun Context.aquaPrefs() = getSharedPreferences(SessionPrefsName, Context.MODE_PRIVATE)

fun Context.isSignedIn(): Boolean = aquaPrefs().getBoolean(SignedInKey, false)

fun Context.isMonitoringAreaSaved(): Boolean = aquaPrefs().getBoolean(MonitoringAreaSavedKey, false)

fun Context.setSignedIn(value: Boolean) {
    aquaPrefs().edit().putBoolean(SignedInKey, value).apply()
}

fun Context.saveMonitoringArea(area: MonitoringArea) {
    aquaPrefs().edit()
        .putBoolean(MonitoringAreaSavedKey, true)
        .putFloat(MonitoringAreaLatKey, area.latitude.toFloat())
        .putFloat(MonitoringAreaLngKey, area.longitude.toFloat())
        .putString(MonitoringAreaLabelKey, area.label)
        .putString(MonitoringAreaImageUriKey, area.imageUri)
        .apply()
}

fun Context.getMonitoringArea(): MonitoringArea {
    val prefs = aquaPrefs()
    return MonitoringArea(
        latitude = prefs.getFloat(MonitoringAreaLatKey, DefaultLatitude.toFloat()).toDouble(),
        longitude = prefs.getFloat(MonitoringAreaLngKey, DefaultLongitude.toFloat()).toDouble(),
        label = prefs.getString(MonitoringAreaLabelKey, "Quiapo District Coast Watch") ?: "Quiapo District Coast Watch",
        imageUri = prefs.getString(MonitoringAreaImageUriKey, "").orEmpty()
    )
}

fun Context.getAppLanguage(): AppLanguage {
    return AppLanguage.fromDisplayName(aquaPrefs().getString(LanguageKey, AppLanguage.English.displayName).orEmpty())
}

fun Context.setAppLanguage(language: AppLanguage) {
    aquaPrefs().edit().putString(LanguageKey, language.displayName).apply()
}

fun Context.getUserAccount(): UserAccount {
    val prefs = aquaPrefs()
    return UserAccount(
        firstName = prefs.getString(AccountFirstNameKey, "AquaWatch").orEmpty(),
        lastName = prefs.getString(AccountLastNameKey, "User").orEmpty(),
        email = prefs.getString(AccountEmailKey, "").orEmpty(),
        phone = prefs.getString(AccountPhoneKey, "").orEmpty(),
        role = prefs.getString(AccountRoleKey, "Coastal responder").orEmpty(),
        station = prefs.getString(AccountStationKey, "").orEmpty(),
        imageUri = prefs.getString(AccountImageUriKey, "").orEmpty()
    )
}

fun Context.saveUserAccount(account: UserAccount) {
    aquaPrefs().edit()
        .putString(AccountFirstNameKey, account.firstName)
        .putString(AccountLastNameKey, account.lastName)
        .putString(AccountEmailKey, account.email)
        .putString(AccountPhoneKey, account.phone)
        .putString(AccountRoleKey, account.role)
        .putString(AccountStationKey, account.station)
        .putString(AccountImageUriKey, account.imageUri)
        .apply()
}

fun Context.hasAccountPassword(): Boolean = aquaPrefs().getString(AccountPasswordHashKey, "").orEmpty().isNotBlank()

fun Context.accountPasswordMatches(password: String): Boolean {
    val storedHash = aquaPrefs().getString(AccountPasswordHashKey, "").orEmpty()
    return storedHash.isBlank() || storedHash == password.sha256()
}

fun Context.saveAccountPassword(password: String) {
    aquaPrefs().edit().putString(AccountPasswordHashKey, password.sha256()).apply()
}

private fun String.sha256(): String {
    return MessageDigest.getInstance("SHA-256")
        .digest(toByteArray())
        .joinToString("") { "%02x".format(it) }
}

fun Context.clearAquaSession() {
    val language = getAppLanguage()
    val account = getUserAccount()
    val passwordHash = aquaPrefs().getString(AccountPasswordHashKey, "").orEmpty()
    aquaPrefs().edit().clear().apply()
    saveUserAccount(account)
    aquaPrefs().edit()
        .putString(LanguageKey, language.displayName)
        .putString(AccountPasswordHashKey, passwordHash)
        .apply()
}
