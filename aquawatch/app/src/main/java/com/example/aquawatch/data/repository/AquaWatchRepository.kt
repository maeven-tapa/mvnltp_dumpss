package com.example.aquawatch.data.repository

import com.example.aquawatch.data.AppDatabase
import com.example.aquawatch.data.entity.AlertEntity
import com.example.aquawatch.data.entity.DeviceEntity
import com.example.aquawatch.data.entity.ReportEntity
import com.example.aquawatch.data.entity.UserProfileEntity
import kotlinx.coroutines.flow.Flow

class AquaWatchRepository(private val database: AppDatabase) {

    // Device operations
    suspend fun addDevice(device: DeviceEntity) {
        database.deviceDao().insertDevice(device)
    }

    suspend fun updateDevice(device: DeviceEntity) {
        database.deviceDao().updateDevice(device)
    }

    suspend fun deleteDevice(device: DeviceEntity) {
        database.deviceDao().deleteDevice(device)
    }

    fun getAllDevices(): Flow<List<DeviceEntity>> {
        return database.deviceDao().getAllDevices()
    }

    suspend fun getDeviceById(id: String): DeviceEntity? {
        return database.deviceDao().getDeviceById(id)
    }

    fun getDevicesByStatus(status: String): Flow<List<DeviceEntity>> {
        return database.deviceDao().getDevicesByStatus(status)
    }

    // Alert operations
    suspend fun addAlert(alert: AlertEntity) {
        database.alertDao().insertAlert(alert)
    }

    suspend fun updateAlert(alert: AlertEntity) {
        database.alertDao().updateAlert(alert)
    }

    suspend fun deleteAlert(alert: AlertEntity) {
        database.alertDao().deleteAlert(alert)
    }

    fun getAllAlerts(): Flow<List<AlertEntity>> {
        return database.alertDao().getAllAlerts()
    }

    suspend fun getAlertById(id: String): AlertEntity? {
        return database.alertDao().getAlertById(id)
    }

    fun getAlertsBySeverity(severity: String): Flow<List<AlertEntity>> {
        return database.alertDao().getAlertsBySeverity(severity)
    }

    // Report operations
    suspend fun addReport(report: ReportEntity) {
        database.reportDao().insertReport(report)
    }

    suspend fun updateReport(report: ReportEntity) {
        database.reportDao().updateReport(report)
    }

    suspend fun deleteReport(report: ReportEntity) {
        database.reportDao().deleteReport(report)
    }

    fun getAllReports(): Flow<List<ReportEntity>> {
        return database.reportDao().getAllReports()
    }

    suspend fun getReportById(id: String): ReportEntity? {
        return database.reportDao().getReportById(id)
    }

    fun getReportsBySeverity(severity: String): Flow<List<ReportEntity>> {
        return database.reportDao().getReportsBySeverity(severity)
    }

    // User profile operations
    suspend fun saveUserProfile(profile: UserProfileEntity) {
        database.userProfileDao().insertProfile(profile)
    }

    suspend fun updateUserProfile(profile: UserProfileEntity) {
        database.userProfileDao().updateProfile(profile)
    }

    fun getUserProfile(): Flow<UserProfileEntity?> {
        return database.userProfileDao().getProfile()
    }

    suspend fun deleteUserProfile(profile: UserProfileEntity) {
        database.userProfileDao().deleteProfile(profile)
    }
}
