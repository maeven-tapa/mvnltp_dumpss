package com.example.aquawatch.ui.screens

import com.google.android.gms.maps.model.LatLng
import com.google.android.gms.maps.model.LatLngBounds
import kotlin.math.PI
import kotlin.math.cos

internal const val MonitoringRadiusMeters = 20_000.0

internal fun monitoringAreaBounds(
    center: LatLng,
    radiusMeters: Double = MonitoringRadiusMeters
): LatLngBounds {
    val latitudeDelta = radiusMeters / 111_320.0
    val longitudeScale = cos(center.latitude * PI / 180.0).coerceAtLeast(0.01)
    val longitudeDelta = radiusMeters / (111_320.0 * longitudeScale)

    return LatLngBounds(
        LatLng(center.latitude - latitudeDelta, center.longitude - longitudeDelta),
        LatLng(center.latitude + latitudeDelta, center.longitude + longitudeDelta)
    )
}
