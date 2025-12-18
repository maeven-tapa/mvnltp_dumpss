<?php
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

$sql = "SELECT * FROM device_settings";
$result = mysqli_query($conn, $sql);

$status = [
    'online' => false,
    'weight' => 0,
    'wifi_signal' => 0,
    'last_heartbeat' => null,
    'firmware_version' => '1.0.0'
];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        
        switch($key) {
            case 'is_connected':
                $status['online'] = $value == '1';
                break;
            case 'current_weight':
                $status['weight'] = intval($value);
                break;
            case 'last_heartbeat':
                $status['last_heartbeat'] = $value;
                break;
            case 'firmware_version':
                $status['firmware_version'] = $value;
                break;
        }
    }
}

if ($status['last_heartbeat']) {
    $lastBeat = strtotime($status['last_heartbeat']);
    $now = time();
    if (($now - $lastBeat) > 30) {
        $status['online'] = false;
    }
}

echo json_encode(['success' => true, 'status' => $status]);
mysqli_close($conn);
?>