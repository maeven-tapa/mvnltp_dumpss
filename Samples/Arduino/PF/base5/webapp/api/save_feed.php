<?php
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
include '../includes/db_config.php';

$input = json_decode(file_get_contents('php://input'), true);

$rounds = intval($input['rounds']);
$type = mysqli_real_escape_string($conn, $input['type']);
$weightDispensed = intval($input['weightDispensed']);

$weightResult = mysqli_query($conn, "SELECT setting_value FROM device_settings WHERE setting_key = 'current_weight'");
$weightRow = mysqli_fetch_assoc($weightResult);
$currentWeight = intval($weightRow['setting_value']);

if ($currentWeight < $weightDispensed) {
    $now = date('Y-m-d');
    $time = date('H:i:s');
    $sql = "INSERT INTO history (feed_date, feed_time, rounds, type, status) 
            VALUES ('$now', '$time', $rounds, '$type', 'Failed (Low Feed)')";
    
    if (!mysqli_query($conn, $sql)) {
        error_log("[SAVE_FEED] Failed to insert failed feed into history: " . mysqli_error($conn));
    } else {
        error_log("[SAVE_FEED] Recorded failed feed attempt: insufficient feed");
    }
    
    $alertMsg = "Dispense failed: Insufficient feed for $rounds rounds.";
    $alertSql = "INSERT INTO alerts (alert_type, message, is_read) VALUES ('Error', '$alertMsg', 0)";
    if (!mysqli_query($conn, $alertSql)) {
        error_log("[SAVE_FEED] Failed to insert error alert: " . mysqli_error($conn));
    }
    
    echo json_encode([
        'success' => false, 
        'message' => "Insufficient feed. Only {$currentWeight}g remaining.",
        'currentWeight' => $currentWeight
    ]);
    mysqli_close($conn);
    exit;
}

$newWeight = $currentWeight - $weightDispensed;
$updateWeightSql = "UPDATE device_settings SET setting_value = '$newWeight' WHERE setting_key = 'current_weight'";
mysqli_query($conn, $updateWeightSql);

$now = date('Y-m-d');
$time = date('H:i:s');
$sql = "INSERT INTO history (feed_date, feed_time, rounds, type, status) 
        VALUES ('$now', '$time', $rounds, '$type', 'Success')";

if (!mysqli_query($conn, $sql)) {
    error_log("[SAVE_FEED] Failed to insert feed into history: " . mysqli_error($conn));
} else {
    error_log("[SAVE_FEED] Successfully recorded feed: $rounds rounds, type: $type");
}

$alertMsg = "Successfully dispensed $rounds rounds.";
$alertSql = "INSERT INTO alerts (alert_type, message, is_read) VALUES ('Info', '$alertMsg', 0)";
if (!mysqli_query($conn, $alertSql)) {
    error_log("[SAVE_FEED] Failed to insert alert: " . mysqli_error($conn));
}

if ($newWeight < 100) {
    $warningMsg = "Feed supply critically low (<100g).";
    $warningSql = "INSERT INTO alerts (alert_type, message, is_read) VALUES ('Warning', '$warningMsg', 0)";
    if (!mysqli_query($conn, $warningSql)) {
        error_log("[SAVE_FEED] Failed to insert warning alert: " . mysqli_error($conn));
    }
}

echo json_encode([
    'success' => true, 
    'message' => "Successfully dispensed $rounds rounds ({$weightDispensed}g).",
    'currentWeight' => $newWeight
]);

mysqli_close($conn);
?>