<?php
header('Content-Type: application/json');
include '../includes/db_config.php';

// Get the command ID from request
$commandId = isset($_GET['command_id']) ? intval($_GET['command_id']) : 0;

if ($commandId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid command ID']);
    exit;
}

// Query the command status from database
$sql = "SELECT id, command_type, status, completed_at, response_message 
        FROM command_queue 
        WHERE id = $commandId";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $command = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true,
        'command' => [
            'id' => $command['id'],
            'type' => $command['command_type'],
            'status' => $command['status'],
            'completed_at' => $command['completed_at'],
            'message' => $command['response_message']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Command not found'
    ]);
}

mysqli_close($conn);
?>
