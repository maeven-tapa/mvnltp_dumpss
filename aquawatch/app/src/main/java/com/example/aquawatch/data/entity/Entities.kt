package com.example.aquawatch.data.entity

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "devices")
data class DeviceEntity(
    @PrimaryKey
    val id: String,
    val serial: String,
    val sim: String,
    val owner: String,
    val vessel: String,
    val registration: String,
    val contact: String,
    val status: String,
    val lastLocation: String,
    val lastUpdated: String
)

@Entity(tableName = "alerts")
data class AlertEntity(
    @PrimaryKey
    val id: String,
    val type: String,
    val location: String,
    val time: String,
    val severity: String,
    val description: String
)

@Entity(tableName = "reports")
data class ReportEntity(
    @PrimaryKey
    val id: String,
    val type: String,
    val location: String,
    val severity: String,
    val time: String,
    val description: String
)

@Entity(tableName = "user_profile")
data class UserProfileEntity(
    @PrimaryKey
    val id: Int = 1,
    val name: String,
    val email: String,
    val rank: String,
    val station: String,
    val phone: String
)
