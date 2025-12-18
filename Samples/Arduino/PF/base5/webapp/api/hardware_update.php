<?php
header('Content-Type: application/json');
include '../includes/db_config.php';

$input = json_decode(file_get_contents('php://input'), true);

$apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$validApiKey = 'your_secret_hardware_key_12345'; // PA-CHANGE DAW PO

if ($apiKey !== $validApiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$updates = [];

if (isset($input['weight'])) {
    $weight = floatval($input['weight']);
    $sql = "UPDATE device_settings SET setting_value = '$weight' WHERE setting_key = 'current_weight'";
    mysqli_query($conn, $sql);
    $updates[] = 'weight';
}

if (isset($input['wifi_rssi'])) {
    $rssi = intval($input['wifi_rssi']);
    $sql = "UPDATE device_settings SET setting_value = '$rssi' WHERE setting_key = 'wifi_signal'";
    mysqli_query($conn, $sql);
    $updates[] = 'wifi_signal';
}

if (isset($input['device_id'])) {
    $deviceId = mysqli_real_escape_string($conn, $input['device_id']);
    $sql = "UPDATE device_settings SET setting_value = '$deviceId' WHERE setting_key = 'device_id'";
    mysqli_query($conn, $sql);
    $updates[] = 'device_id';
}

if (isset($input['firmware_version'])) {
    $firmwareVersion = mysqli_real_escape_string($conn, $input['firmware_version']);
    $sql = "UPDATE device_settings SET setting_value = '$firmwareVersion' WHERE setting_key = 'firmware_version'";
    mysqli_query($conn, $sql);
    $updates[] = 'firmware_version';
}

$sql = "UPDATE device_settings SET setting_value = '1' WHERE setting_key = 'is_connected'";
mysqli_query($conn, $sql);

$now = date('Y-m-d H:i:s');
$sql = "UPDATE device_settings SET setting_value = '$now' WHERE setting_key = 'last_heartbeat'";
mysqli_query($conn, $sql);

if (isset($input['dispensed'])) {
    $rounds = intval($input['dispensed']);
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $type = isset($input['type']) ? mysqli_real_escape_string($conn, $input['type']) : 'Scheduled';
    $status = isset($input['status']) ? mysqli_real_escape_string($conn, $input['status']) : 'Success';
    
    $sql = "INSERT INTO history (feed_date, feed_time, rounds, type, status) 
            VALUES ('$date', '$time', $rounds, '$type', '$status')";
    mysqli_query($conn, $sql);
    
    $alertMsg = "Device dispensed $rounds rounds - $type ($status)";
    $alertSql = "INSERT INTO alerts (alert_type, message, is_read) 
                 VALUES ('Info', '$alertMsg', 0)";
    mysqli_query($conn, $alertSql);
    $updates[] = 'feed_event';
}

if (isset($input['alert']) && $input['alert'] === true) {
    $alertType = isset($input['alert_type']) ? mysqli_real_escape_string($conn, $input['alert_type']) : 'Info';
    $alertMsg = isset($input['message']) ? mysqli_real_escape_string($conn, $input['message']) : 'Device alert';
    
    $alertSql = "INSERT INTO alerts (alert_type, message, is_read) 
                 VALUES ('$alertType', '$alertMsg', 0)";
    mysqli_query($conn, $alertSql);
    $updates[] = 'alert';
}

echo json_encode([
    'success' => true, 
    'message' => 'Hardware update received',
    'updated' => $updates
]);

mysqli_close($conn);
?>