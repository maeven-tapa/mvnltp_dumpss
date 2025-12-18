<?php
header('Content-Type: application/json');
include '../includes/db_config.php';

// Send recalibrate command to device
$sql = "INSERT INTO command_queue (command_type, command_data, status, created_at) VALUES ('recalibrate', '', 'pending', NOW())";
mysqli_query($conn, $sql);
$commandId = mysqli_insert_id($conn);

echo json_encode([
    'success' => true, 
    'command_id' => $commandId,
    'message' => 'Recalibration command sent to device.'
]);
mysqli_close($conn);
?>