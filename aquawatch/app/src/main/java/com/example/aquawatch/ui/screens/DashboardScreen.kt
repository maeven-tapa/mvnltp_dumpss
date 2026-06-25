package com.example.aquawatch.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Air
import androidx.compose.material.icons.filled.Cloud
import androidx.compose.material.icons.filled.Grain
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Router
import androidx.compose.material.icons.filled.Speed
import androidx.compose.material.icons.filled.Thunderstorm
import androidx.compose.material.icons.filled.Thermostat
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.WbSunny
import androidx.compose.material.icons.filled.Waves
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.aquawatch.R
import com.example.aquawatch.data.getMonitoringArea
import com.example.aquawatch.data.network.OpenWeatherClient
import com.example.aquawatch.data.network.WeatherCondition
import com.example.aquawatch.data.network.WeatherSnapshot
import com.example.aquawatch.ui.LocalAppLanguage
import com.example.aquawatch.ui.appCopy
import com.example.aquawatch.ui.theme.PrimaryActionButton
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

@Composable
fun DashboardScreen(onOpenAlerts: () -> Unit) {
    val context = LocalContext.current
    val copy = appCopy(LocalAppLanguage.current)
    val monitoringArea = remember { context.getMonitoringArea() }
    var weather by remember { mutableStateOf(fallbackWeather()) }
    var weatherStatus by remember { mutableStateOf("Updating live conditions") }

    LaunchedEffect(monitoringArea.latitude, monitoringArea.longitude) {
        weatherStatus = runCatching {
            withContext(Dispatchers.IO) {
                OpenWeatherClient.fetchWeather(
                    latitude = monitoringArea.latitude,
                    longitude = monitoringArea.longitude
                )
            }
        }.fold(
            onSuccess = {
                weather = it
                "${monitoringArea.label} - OpenWeather: ${it.location}"
            },
            onFailure = { "Live weather unavailable" }
        )
    }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
        contentPadding = PaddingValues(vertical = 16.dp)
    ) {
        item {
            Column {
                Text(copy.dashboard, fontSize = 28.sp, color = MaterialTheme.colorScheme.onBackground)
                Text(
                    copy.dashboardSubtitle,
                    fontSize = 14.sp,
                    color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.68f)
                )
            }
        }

        item {
            DashboardHeroCard(
                weather = weather,
                monitoringAreaName = monitoringArea.label
            )
        }

        item {
            Text(copy.recentAlerts, fontSize = 16.sp, color = MaterialTheme.colorScheme.onBackground)
        }

        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
            ) {
                Column(modifier = Modifier.fillMaxWidth().padding(16.dp)) {
                    Text(copy.noRecentAlerts, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurface)
                    Spacer(Modifier.height(4.dp))
                    Text(
                        copy.noRecentAlertsMessage,
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.68f)
                    )
                    Spacer(Modifier.height(12.dp))
                    PrimaryActionButton(text = copy.viewAllAlerts, onClick = onOpenAlerts)
                }
            }
        }

        item {
            Text(copy.weatherForecast, fontSize = 16.sp, color = MaterialTheme.colorScheme.onBackground)
        }

        item {
            ForecastWidget(
                title = copy.fiveDayForecast,
                weather = weather,
                weatherStatus = weatherStatus
            )
        }

        item {
            Spacer(Modifier.height(8.dp))
        }
    }
}

@Composable
private fun DashboardHeroCard(
    weather: WeatherSnapshot,
    monitoringAreaName: String
) {
    val recommendation = weather.sailingRecommendation()
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(24.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 10.dp)
    ) {
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(516.dp)
        ) {
            androidx.compose.foundation.Image(
                painter = painterResource(weather.backgroundRes()),
                contentDescription = null,
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxSize()
            )
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(
                        Brush.verticalGradient(
                            listOf(Color(0x3302172A), Color(0xC4071B33))
                        )
                    )
            )
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(18.dp),
                verticalArrangement = Arrangement.SpaceBetween
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            "LIVE CONDITIONS",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White.copy(alpha = 0.74f)
                        )
                        Text(
                            monitoringAreaName,
                            fontSize = 20.sp,
                            fontWeight = FontWeight.SemiBold,
                            color = Color.White,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                        Text(
                            "${weather.current.description} | OpenWeather: ${weather.location}",
                            fontSize = 12.sp,
                            color = Color.White.copy(alpha = 0.72f),
                            maxLines = 1
                        )
                    }
                    Box(
                        modifier = Modifier
                            .size(54.dp)
                            .background(Color(0x26FFFFFF), RoundedCornerShape(17.dp)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = weather.current.icon.forecastIcon(),
                            contentDescription = weather.current.description,
                            modifier = Modifier.size(32.dp),
                            tint = weather.current.icon.weatherIconTint()
                        )
                    }
                }

                androidx.compose.material3.Surface(
                    modifier = Modifier.fillMaxWidth().height(66.dp),
                    color = recommendation.accent.copy(alpha = 0.17f),
                    shape = RoundedCornerShape(16.dp),
                    tonalElevation = 0.dp
                ) {
                    Row(
                        modifier = Modifier.fillMaxSize().padding(horizontal = 13.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(11.dp)
                    ) {
                        Box(
                            modifier = Modifier
                                .size(38.dp)
                                .background(recommendation.accent.copy(alpha = 0.22f), RoundedCornerShape(12.dp)),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                Icons.Filled.Visibility,
                                contentDescription = "Sailing recommendation",
                                modifier = Modifier.size(22.dp),
                                tint = recommendation.accent
                            )
                        }
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                recommendation.title,
                                color = Color.White,
                                fontSize = 13.sp,
                                fontWeight = FontWeight.SemiBold
                            )
                            Text(
                                recommendation.message,
                                color = Color.White.copy(alpha = 0.68f),
                                fontSize = 10.sp,
                                maxLines = 2,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }
                }

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    WeatherMetricCard(
                        title = "Wind",
                        value = weather.windText(),
                        label = "Sustained speed",
                        icon = Icons.Filled.Air,
                        accent = Color(0xFF76D7FF),
                        modifier = Modifier.weight(1f)
                    )
                    WeatherMetricCard(
                        title = "Air Temp",
                        value = weather.current.widgetTemperature(),
                        label = "Current temperature",
                        icon = Icons.Filled.Thermostat,
                        accent = Color(0xFFFFD45A),
                        modifier = Modifier.weight(1f)
                    )
                }
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    WeatherMetricCard(
                        title = "Wind Gust",
                        value = weather.windGustText(),
                        label = "Peak speed",
                        icon = Icons.Filled.Speed,
                        accent = Color(0xFF9DBBFF),
                        modifier = Modifier.weight(1f)
                    )
                    WeatherMetricCard(
                        title = "Visibility",
                        value = weather.visibilityText(),
                        label = "Viewing range",
                        icon = Icons.Filled.Visibility,
                        accent = Color(0xFF7DE3E8),
                        modifier = Modifier.weight(1f)
                    )
                }
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    WeatherMetricCard(
                        title = "Alerts",
                        value = "0",
                        label = "Active incidents",
                        icon = Icons.Filled.Notifications,
                        accent = Color(0xFFFF9C8D),
                        modifier = Modifier.weight(1f)
                    )
                    WeatherMetricCard(
                        title = "Devices",
                        value = "0",
                        label = "Online sensors",
                        icon = Icons.Filled.Router,
                        accent = Color(0xFF78E0AE),
                        modifier = Modifier.weight(1f)
                    )
                }
            }
        }
    }
}

@Composable
private fun WeatherMetricCard(
    title: String,
    value: String,
    label: String,
    icon: ImageVector,
    accent: Color,
    modifier: Modifier = Modifier
) {
    androidx.compose.material3.Surface(
        modifier = modifier.height(100.dp),
        color = Color(0x24FFFFFF),
        shape = RoundedCornerShape(16.dp),
        tonalElevation = 0.dp
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.SpaceBetween
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier
                        .size(30.dp)
                        .background(accent.copy(alpha = 0.18f), RoundedCornerShape(9.dp)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        icon,
                        contentDescription = null,
                        modifier = Modifier.size(18.dp),
                        tint = accent
                    )
                }
                Spacer(Modifier.width(7.dp))
                Text(
                    title,
                    color = Color.White.copy(alpha = 0.76f),
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Medium
                )
            }
            Column {
                Text(value, color = Color.White, fontSize = 19.sp, fontWeight = FontWeight.SemiBold)
                Text(
                    label,
                    color = Color.White.copy(alpha = 0.58f),
                    fontSize = 9.sp,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
    }
}

@Composable
private fun ForecastWidget(
    title: String,
    weather: WeatherSnapshot,
    weatherStatus: String
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(24.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 10.dp)
    ) {
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(338.dp)
                .background(weather.forecastWidgetBrush())
        ) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(
                        Brush.verticalGradient(
                            listOf(Color.Transparent, Color(0x52030E1B))
                        )
                    )
            )
            Column(
                modifier = Modifier.fillMaxSize().padding(18.dp),
                verticalArrangement = Arrangement.SpaceBetween
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.Top
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            title.uppercase(),
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White.copy(alpha = 0.88f)
                        )
                        Text(
                            weatherStatus,
                            fontSize = 11.sp,
                            color = Color.White.copy(alpha = 0.68f),
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                    Icon(
                        imageVector = weather.current.icon.forecastIcon(),
                        contentDescription = weather.current.description,
                        modifier = Modifier.size(48.dp),
                        tint = weather.current.icon.weatherIconTint()
                    )
                }

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.Bottom
                ) {
                    Column {
                        Text(
                            weather.current.widgetTemperature(),
                            fontSize = 44.sp,
                            fontWeight = FontWeight.Light,
                            color = Color.White
                        )
                        Text(
                            weather.current.description,
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Medium,
                            color = Color.White
                        )
                    }
                    Column(
                        horizontalAlignment = Alignment.End,
                        verticalArrangement = Arrangement.spacedBy(5.dp)
                    ) {
                        WeatherWidgetMetric(Icons.Filled.Air, weather.windText())
                        WeatherWidgetMetric(Icons.Filled.WaterDrop, weather.humidityText())
                    }
                }

                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(Color(0x2EFFFFFF), RoundedCornerShape(18.dp))
                        .padding(horizontal = 5.dp, vertical = 9.dp),
                    horizontalArrangement = Arrangement.spacedBy(2.dp)
                ) {
                    weather.forecast.take(5).forEachIndexed { index, condition ->
                        ForecastWidgetDay(
                            condition = condition,
                            isToday = index == 0,
                            modifier = Modifier.weight(1f)
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun WeatherWidgetMetric(icon: ImageVector, value: String) {
    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(5.dp)) {
        Icon(icon, contentDescription = null, modifier = Modifier.size(15.dp), tint = Color.White.copy(alpha = 0.78f))
        Text(value, fontSize = 11.sp, color = Color.White.copy(alpha = 0.82f))
    }
}

@Composable
private fun ForecastWidgetDay(
    condition: WeatherCondition,
    isToday: Boolean,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .height(102.dp)
            .background(
                if (isToday) Color(0x26FFFFFF) else Color.Transparent,
                RoundedCornerShape(13.dp)
            )
            .padding(horizontal = 2.dp, vertical = 8.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.SpaceBetween
    ) {
        Text(
            condition.label,
            fontSize = 10.sp,
            fontWeight = if (isToday) FontWeight.Bold else FontWeight.Medium,
            color = Color.White.copy(alpha = if (isToday) 1f else 0.72f),
            maxLines = 1
        )
        Icon(
            imageVector = condition.icon.forecastIcon(),
            contentDescription = condition.description,
            modifier = Modifier.size(25.dp),
            tint = condition.icon.weatherIconTint()
        )
        Text(
            condition.widgetTemperature(),
            fontSize = 13.sp,
            fontWeight = FontWeight.SemiBold,
            color = Color.White
        )
        Text(
            condition.description,
            fontSize = 8.sp,
            color = Color.White.copy(alpha = 0.68f),
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}

@Composable
fun AlertRowItem(title: String, time: String, isUrgent: Boolean = false) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            title,
            fontSize = 13.sp,
            color = if (isUrgent) Color(0xFFE03E3E) else MaterialTheme.colorScheme.onSurface,
            modifier = Modifier.weight(1f)
        )
        Text(time, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.64f))
    }
}

private fun fallbackWeather() = WeatherSnapshot(
    location = "Monitoring area",
    current = WeatherCondition("Now", 0, "Waiting for weather", "Pending"),
    forecast = listOf(
        WeatherCondition("Today", 0, "Waiting", "Pending"),
        WeatherCondition("+1d", 0, "Waiting", "Pending"),
        WeatherCondition("+2d", 0, "Waiting", "Pending"),
        WeatherCondition("+3d", 0, "Waiting", "Pending"),
        WeatherCondition("+4d", 0, "Waiting", "Pending"),
        WeatherCondition("+5d", 0, "Waiting", "Pending")
    ),
    windSpeed = 0.0,
    windGust = 0.0,
    visibilityMeters = 0,
    humidity = 0
)

private fun WeatherCondition.temperatureText(): String {
    return if (description == "Waiting" || description == "Waiting for weather") "--" else "${temperature}C"
}

private fun WeatherCondition.widgetTemperature(): String {
    return if (description == "Waiting" || description == "Waiting for weather") {
        "--"
    } else {
        "$temperature\u00B0"
    }
}

private fun WeatherSnapshot.windText(): String {
    return if (current.description == "Waiting for weather") "--" else "$windSpeed m/s"
}

private fun WeatherSnapshot.windGustText(): String {
    return if (current.description == "Waiting for weather" || windGust == 0.0) "--" else "$windGust m/s"
}

private fun WeatherSnapshot.humidityText(): String {
    return if (current.description == "Waiting for weather") "--" else "$humidity%"
}

private data class SailingRecommendation(
    val title: String,
    val message: String,
    val accent: Color
)

private fun WeatherSnapshot.sailingRecommendation(): SailingRecommendation {
    if (current.description == "Waiting for weather") {
        return SailingRecommendation(
            title = "Checking sailing conditions",
            message = "Waiting for live wind, visibility, and weather data.",
            accent = Color(0xFF9DBBFF)
        )
    }

    val dangerousWeather = current.icon == "Storm"
    val dangerousWind = windSpeed >= 10.0 || windGust >= 14.0
    val poorVisibility = visibilityMeters in 1 until 3_000
    if (dangerousWeather || dangerousWind || poorVisibility) {
        val reason = when {
            dangerousWeather -> "Thunderstorms are present near the monitoring area."
            dangerousWind -> "Strong wind or gusts may make navigation unsafe."
            else -> "Visibility is too low for safe coastal navigation."
        }
        return SailingRecommendation(
            title = "Do not sail",
            message = reason,
            accent = Color(0xFFFF8A80)
        )
    }

    val cautionWeather = current.icon in setOf("Rain", "Fog", "Haze")
    val cautionWind = windSpeed >= 7.0 || windGust >= 10.0
    val reducedVisibility = visibilityMeters in 3_000 until 6_000
    val visibilityUnavailable = visibilityMeters <= 0
    if (cautionWeather || cautionWind || reducedVisibility || visibilityUnavailable) {
        val reason = when {
            cautionWeather -> "${current.description}; check local marine advisories before departure."
            cautionWind -> "Moderate wind and gusts require extra care."
            visibilityUnavailable -> "Visibility data is unavailable; verify conditions before departure."
            else -> "Visibility is reduced; proceed only with proper navigation equipment."
        }
        return SailingRecommendation(
            title = "Sail with caution",
            message = reason,
            accent = Color(0xFFFFD45A)
        )
    }

    return SailingRecommendation(
        title = "Safe to sail",
        message = "Wind and visibility are within normal coastal operating conditions.",
        accent = Color(0xFF78E0AE)
    )
}

private fun WeatherSnapshot.visibilityText(): String {
    return if (current.description == "Waiting for weather" || visibilityMeters == 0) "--" else "${visibilityMeters / 1000.0} km"
}

private fun WeatherSnapshot.backgroundRes(): Int {
    val isWindy = windSpeed >= 8.0 || windGust >= 10.0

    return when (current.icon) {
        "Sunny" -> if (current.isNight) {
            R.drawable.weather_bg_clear_night
        } else if (isWindy) {
            R.drawable.weather_bg_windy_day
        } else {
            R.drawable.weather_bg_sunny_day
        }
        "Rain" -> R.drawable.weather_bg_rain_night
        "Storm" -> R.drawable.weather_bg_storm_night
        "Cloudy" -> if (isWindy) R.drawable.weather_bg_windy_day else R.drawable.weather_bg_cloudy_day
        "Fog" -> R.drawable.weather_bg_fog_morning
        "Haze" -> R.drawable.weather_bg_haze_sunset
        else -> R.drawable.weather_bg_marine_default
    }
}

private fun WeatherSnapshot.forecastWidgetBrush(): Brush {
    val colors = when (current.icon) {
        "Sunny" -> if (current.isNight) {
            listOf(Color(0xFF18295A), Color(0xFF09162F))
        } else {
            listOf(Color(0xFF168AAD), Color(0xFFF0A85A))
        }
        "Rain" -> listOf(Color(0xFF315B78), Color(0xFF14293D))
        "Storm" -> listOf(Color(0xFF414067), Color(0xFF15182C))
        "Cloudy" -> listOf(Color(0xFF557586), Color(0xFF263E50))
        "Fog", "Haze" -> listOf(Color(0xFF6F7E82), Color(0xFF33464D))
        else -> listOf(Color(0xFF087F8C), Color(0xFF123B59))
    }
    return Brush.linearGradient(colors)
}

private fun String.forecastIcon(): ImageVector = when (this) {
    "Sunny" -> Icons.Filled.WbSunny
    "Rain" -> Icons.Filled.WaterDrop
    "Storm" -> Icons.Filled.Thunderstorm
    "Fog", "Haze" -> Icons.Filled.Grain
    "Marine" -> Icons.Filled.Waves
    "Pending" -> Icons.Filled.Cloud
    else -> Icons.Filled.Cloud
}

private fun String.weatherIconTint(): Color = when (this) {
    "Sunny" -> Color(0xFFFFD45A)
    "Rain" -> Color(0xFF76D7FF)
    "Storm" -> Color(0xFFC7B8FF)
    "Fog", "Haze" -> Color(0xFFE5EDF2)
    "Marine" -> Color(0xFF7DE3E8)
    else -> Color.White
}
