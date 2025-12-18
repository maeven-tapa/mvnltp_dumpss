<?php
header('Content-Type: application/json');
include '../includes/db_config.php';

// Delete all data from tables
mysqli_query($conn, "TRUNCATE TABLE schedules");
mysqli_query($conn, "TRUNCATE TABLE history");
mysqli_query($conn, "TRUNCATE TABLE alerts");
mysqli_query($conn, "TRUNCATE TABLE command_queue");

// Reset device settings to defaults
mysqli_query($conn, "UPDATE device_settings SET setting_value = '0' WHERE setting_key = 'current_weight'");
mysqli_query($conn, "UPDATE device_settings SET setting_value = 'Not Set' WHERE setting_key = 'wifi_ssid'");
mysqli_query($conn, "UPDATE device_settings SET setting_value = '0' WHERE setting_key = 'wifi_rssi'");
mysqli_query($conn, "UPDATE device_settings SET setting_value = '0' WHERE setting_key = 'is_connected'");
mysqli_query($conn, "UPDATE device_settings SET setting_value = '1.0.0' WHERE setting_key = 'firmware_version'");
mysqli_query($conn, "UPDATE device_settings SET setting_value = '80.0' WHERE setting_key = 'calibration_factor'");

// Reset admin password to default: admin123
// Bcrypt hash for 'admin123'
$defaultPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
mysqli_query($conn, "UPDATE users SET password = '$defaultPassword' WHERE username = 'admin'");

// Send factory reset command to ESP32
mysqli_query($conn, "INSERT INTO command_queue (command_type, command_data, status, created_at) VALUES ('factory_reset', '', 'pending', NOW())");
$commandId = mysqli_insert_id($conn);

echo json_encode([
    'success' => true, 
    'command_id' => $commandId,
    'message' => 'Factory reset command sent to device.'
]);

mysqli_close($conn);
?>