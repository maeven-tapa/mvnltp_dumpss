<?php
header('Content-Type: application/json');
include '../includes/db_config.php';

// Get pending commands for the device
$sql = "SELECT * FROM command_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $command = mysqli_fetch_assoc($result);
    
    // Mark command as processing
    $commandId = $command['id'];
    mysqli_query($conn, "UPDATE command_queue SET status = 'processing' WHERE id = $commandId");
    
    echo json_encode([
        'success' => true,
        'has_command' => true,
        'command' => [
            'id' => $command['id'],
            'type' => $command['command_type'],
            'data' => $command['command_data']
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'has_command' => false
    ]);
}

mysqli_close($conn);
?>
