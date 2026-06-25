package com.example.aquawatch.data.dao

import androidx.room.Dao
import androidx.room.Delete
import androidx.room.Insert
import androidx.room.Query
import androidx.room.Update
import com.example.aquawatch.data.entity.DeviceEntity
import com.example.aquawatch.data.entity.AlertEntity
import com.example.aquawatch.data.entity.ReportEntity
import com.example.aquawatch.data.entity.UserProfileEntity
import kotlinx.coroutines.flow.Flow

@Dao
interface DeviceDao {
    @Insert
    suspend fun insertDevice(device: DeviceEntity)

    @Update
    suspend fun updateDevice(device: DeviceEntity)

    @Delete
    suspend fun deleteDevice(device: DeviceEntity)

    @Query("SELECT * FROM devices")
    fun getAllDevices(): Flow<List<DeviceEntity>>

    @Query("SELECT * FROM devices WHERE id = :id")
    suspend fun getDeviceById(id: String): DeviceEntity?

    @Query("SELECT * FROM devices WHERE status = :status")
    fun getDevicesByStatus(status: String): Flow<List<DeviceEntity>>
}

@Dao
interface AlertDao {
    @Insert
    suspend fun insertAlert(alert: AlertEntity)

    @Update
    suspend fun updateAlert(alert: AlertEntity)

    @Delete
    suspend fun deleteAlert(alert: AlertEntity)

    @Query("SELECT * FROM alerts ORDER BY time DESC")
    fun getAllAlerts(): Flow<List<AlertEntity>>

    @Query("SELECT * FROM alerts WHERE id = :id")
    suspend fun getAlertById(id: String): AlertEntity?

    @Query("SELECT * FROM alerts WHERE severity = :severity ORDER BY time DESC")
    fun getAlertsBySeverity(severity: String): Flow<List<AlertEntity>>
}

@Dao
interface ReportDao {
    @Insert
    suspend fun insertReport(report: ReportEntity)

    @Update
    suspend fun updateReport(report: ReportEntity)

    @Delete
    suspend fun deleteReport(report: ReportEntity)

    @Query("SELECT * FROM reports ORDER BY time DESC")
    fun getAllReports(): Flow<List<ReportEntity>>

    @Query("SELECT * FROM reports WHERE id = :id")
    suspend fun getReportById(id: String): ReportEntity?

    @Query("SELECT * FROM reports WHERE severity = :severity ORDER BY time DESC")
    fun getReportsBySeverity(severity: String): Flow<List<ReportEntity>>
}

@Dao
interface UserProfileDao {
    @Insert
    suspend fun insertProfile(profile: UserProfileEntity)

    @Update
    suspend fun updateProfile(profile: UserProfileEntity)

    @Query("SELECT * FROM user_profile WHERE id = 1")
    fun getProfile(): Flow<UserProfileEntity?>

    @Delete
    suspend fun deleteProfile(profile: UserProfileEntity)
}
