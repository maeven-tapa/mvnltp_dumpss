package com.example.aquawatch.data.network

import com.example.aquawatch.BuildConfig
import java.net.URL
import java.text.SimpleDateFormat
import java.util.Locale
import javax.net.ssl.HttpsURLConnection
import org.json.JSONObject

data class WeatherCondition(
    val label: String,
    val temperature: Int,
    val description: String,
    val icon: String,
    val isNight: Boolean = false
)

data class WeatherSnapshot(
    val location: String,
    val current: WeatherCondition,
    val forecast: List<WeatherCondition>,
    val windSpeed: Double,
    val windGust: Double,
    val visibilityMeters: Int,
    val humidity: Int
)

object OpenWeatherClient {
    suspend fun fetchWeather(
        latitude: Double,
        longitude: Double
    ): WeatherSnapshot {
        val key = BuildConfig.OPEN_WEATHER_API_KEY
        require(key.isNotBlank()) { "OpenWeather API key is missing." }

        val weatherJson = getJson(
            "https://api.openweathermap.org/data/2.5/weather?lat=$latitude&lon=$longitude&appid=$key&units=metric"
        )
        val forecastJson = getJson(
            "https://api.openweathermap.org/data/2.5/forecast?lat=$latitude&lon=$longitude&appid=$key&units=metric"
        )

        val location = weatherJson.optString("name", "Monitoring area")
        val current = weatherJson.toCondition("Now")
        val main = weatherJson.getJSONObject("main")
        val wind = weatherJson.optJSONObject("wind")

        return WeatherSnapshot(
            location = location,
            current = current,
            forecast = forecastJson.toDailyForecast(),
            windSpeed = wind?.optDouble("speed", 0.0) ?: 0.0,
            windGust = wind?.optDouble("gust", 0.0) ?: 0.0,
            visibilityMeters = weatherJson.optInt("visibility", 0),
            humidity = main.optInt("humidity", 0)
        )
    }

    private fun getJson(url: String): JSONObject {
        val connection = URL(url).openConnection() as HttpsURLConnection
        connection.connectTimeout = 10_000
        connection.readTimeout = 10_000
        connection.requestMethod = "GET"

        return connection.inputStream.bufferedReader().use { reader ->
            JSONObject(reader.readText())
        }.also {
            connection.disconnect()
        }
    }

    private fun JSONObject.toDailyForecast(): List<WeatherCondition> {
        val list = getJSONArray("list")
        val selected = mutableListOf<WeatherCondition>()
        val seenDates = mutableSetOf<String>()

        for (index in 0 until list.length()) {
            val item = list.getJSONObject(index)
            val timestamp = item.optString("dt_txt")
            val date = timestamp.substringBefore(" ")
            val hour = timestamp.substringAfter(" ", "")

            if (date !in seenDates && (hour.startsWith("12:") || selected.isEmpty())) {
                val dayLabel = if (selected.isEmpty()) "Today" else date.toForecastDayLabel()
                selected += item.toCondition(dayLabel)
                seenDates += date
            }

            if (selected.size == 5) break
        }

        return selected
    }

    private fun JSONObject.toCondition(label: String): WeatherCondition {
        val main = getJSONObject("main")
        val weather = getJSONArray("weather").getJSONObject(0)
        val condition = weather.optString("main", "")
        val iconCode = weather.optString("icon", "")
        val description = weather.optString("description", condition).replaceFirstChar { it.uppercase() }

        return WeatherCondition(
            label = label,
            temperature = main.optDouble("temp", 0.0).toInt(),
            description = description,
            icon = condition.toWeatherIcon(),
            isNight = iconCode.endsWith("n")
        )
    }

    private fun String.toWeatherIcon(): String = when (lowercase()) {
        "clear" -> "Sunny"
        "clouds" -> "Cloudy"
        "rain", "drizzle" -> "Rain"
        "thunderstorm" -> "Storm"
        "mist", "fog" -> "Fog"
        "haze", "smoke", "dust", "sand", "ash" -> "Haze"
        else -> "Marine"
    }

    private fun String.toForecastDayLabel(): String {
        return runCatching {
            val date = SimpleDateFormat("yyyy-MM-dd", Locale.US).parse(this)
            SimpleDateFormat("EEE", Locale.getDefault()).format(requireNotNull(date))
        }.getOrDefault(this)
    }
}
