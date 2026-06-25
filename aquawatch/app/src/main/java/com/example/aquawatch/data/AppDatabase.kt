package com.example.aquawatch.data

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase
import com.example.aquawatch.data.dao.AlertDao
import com.example.aquawatch.data.dao.DeviceDao
import com.example.aquawatch.data.dao.ReportDao
import com.example.aquawatch.data.dao.UserProfileDao
import com.example.aquawatch.data.entity.AlertEntity
import com.example.aquawatch.data.entity.DeviceEntity
import com.example.aquawatch.data.entity.ReportEntity
import com.example.aquawatch.data.entity.UserProfileEntity

@Database(
    entities = [
        DeviceEntity::class,
        AlertEntity::class,
        ReportEntity::class,
        UserProfileEntity::class
    ],
    version = 1,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun deviceDao(): DeviceDao
    abstract fun alertDao(): AlertDao
    abstract fun reportDao(): ReportDao
    abstract fun userProfileDao(): UserProfileDao

    companion object {
        @Volatile
        private var instance: AppDatabase? = null

        fun getInstance(context: Context): AppDatabase {
            return instance ?: synchronized(this) {
                val db = Room.databaseBuilder(
                    context.applicationContext,
                    AppDatabase::class.java,
                    "aquawatch_database"
                ).build()
                instance = db
                db
            }
        }
    }
}
