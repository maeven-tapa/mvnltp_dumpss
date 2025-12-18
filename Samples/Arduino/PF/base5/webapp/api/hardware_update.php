<?php
// Set timezone to Philippine Time
date_default_timezone_set('Asia/Manila');

// Allow requests from Arduino device
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../includes/db_config.php';

// Log incoming data for debugging
$rawInput = file_get_contents('php://input');
error_log("[HARDWARE_UPDATE] Received request. Raw data: " . $rawInput);

$input = json_decode($rawInput, true);
error_log("[HARDWARE_UPDATE] Parsed input: " . json_encode($input));

$apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$validApiKey = 'your_secret_hardware_key_12345'; // PA-CHANGE DAW PO

if ($apiKey !== $validApiKey) {
    error_log("[HARDWARE_UPDATE] Unauthorized request - invalid API key");
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
    error_log("[HARDWARE_UPDATE] Feed event detected! Input data: " . json_encode($input));
    
    $rounds = intval($input['dispensed']);
    // Use device timestamp if provided, otherwise use server time
    $date = isset($input['feed_date']) ? mysqli_real_escape_string($conn, $input['feed_date']) : date('Y-m-d');
    $time = isset($input['feed_time']) ? mysqli_real_escape_string($conn, $input['feed_time']) : date('H:i:s');
    $type = isset($input['type']) ? mysqli_real_escape_string($conn, $input['type']) : 'Scheduled';
    $status = isset($input['status']) ? mysqli_real_escape_string($conn, $input['status']) : 'Success';
    
    error_log("[HARDWARE_UPDATE] Parsed values - Rounds: $rounds, Date: $date, Time: $time, Type: $type, Status: $status");
    
    $sql = "INSERT INTO history (feed_date, feed_time, rounds, type, status) 
            VALUES ('$date', '$time', $rounds, '$type', '$status')";
    
    error_log("[HARDWARE_UPDATE] Executing SQL: " . $sql);
    
    if (mysqli_query($conn, $sql)) {
        $insertId = mysqli_insert_id($conn);
        $updates[] = 'feed_event';
        error_log("[HARDWARE_UPDATE] Successfully recorded feed event with ID: $insertId - $rounds rounds, type: $type, status: $status");
        
        $alertMsg = "Device dispensed $rounds rounds - $type ($status)";
        $alertSql = "INSERT INTO alerts (alert_type, message, is_read) 
                     VALUES ('Info', '$alertMsg', 0)";
        if (!mysqli_query($conn, $alertSql)) {
            error_log("[HARDWARE_UPDATE] Failed to insert alert: " . mysqli_error($conn));
        } else {
            error_log("[HARDWARE_UPDATE] Alert created successfully");
        }
    } else {
        error_log("[HARDWARE_UPDATE] FAILED to insert feed event into history!");
        error_log("[HARDWARE_UPDATE] MySQL Error: " . mysqli_error($conn));
        error_log("[HARDWARE_UPDATE] MySQL Error Code: " . mysqli_errno($conn));
        error_log("[HARDWARE_UPDATE] SQL: " . $sql);
        $updates[] = 'feed_event_failed';
    }
} else {
    error_log("[HARDWARE_UPDATE] No 'dispensed' field in input data");
}

if (isset($input['alert']) && $input['alert'] === true) {
    $alertType = isset($input['alert_type']) ? mysqli_real_escape_string($conn, $input['alert_type']) : 'Info';
    $alertMsg = isset($input['message']) ? mysqli_real_escape_string($conn, $input['message']) : 'Device alert';
    
    $alertSql = "INSERT INTO alerts (alert_type, message, is_read) 
                 VALUES ('$alertType', '$alertMsg', 0)";
    
    if (mysqli_query($conn, $alertSql)) {
        $updates[] = 'alert';
        error_log("[HARDWARE_UPDATE] Successfully recorded alert: $alertType - $alertMsg");
    } else {
        error_log("[HARDWARE_UPDATE] Failed to insert alert: " . mysqli_error($conn));
        $updates[] = 'alert_failed';
    }
}

// Log all updates for debugging
error_log("[HARDWARE_UPDATE] Processing complete. Updates: " . implode(', ', $updates));

echo json_encode([
    'success' => true, 
    'message' => 'Hardware update received',
    'updated' => $updates
]);

mysqli_close($conn);
?>