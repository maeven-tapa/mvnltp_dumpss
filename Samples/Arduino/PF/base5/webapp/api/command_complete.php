<?php
date_default_timezone_set('Asia/Manila');
// Allow requests from Arduino device
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../includes/db_config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify API key
$apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$validApiKey = 'your_secret_hardware_key_12345'; // Should match the key in config

if ($apiKey !== $validApiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate required fields
if (!isset($input['command_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing command_id']);
    exit;
}

$commandId = intval($input['command_id']);
$success = isset($input['success']) && $input['success'] === true;
$message = isset($input['message']) ? mysqli_real_escape_string($conn, $input['message']) : '';

// Update command status in database
$status = $success ? 'completed' : 'failed';
$completedAt = date('Y-m-d H:i:s');

$sql = "UPDATE command_queue 
        SET status = '$status', 
            completed_at = '$completedAt'";

if ($message) {
    $sql .= ", response_message = '$message'";
}

$sql .= " WHERE id = $commandId";

if (mysqli_query($conn, $sql)) {
    // Create alert for command completion
    $alertType = $success ? 'Success' : 'Error';
    $commandInfo = mysqli_query($conn, "SELECT command_type FROM command_queue WHERE id = $commandId");
    $commandType = 'Command';
    
    if ($commandInfo && mysqli_num_rows($commandInfo) > 0) {
        $row = mysqli_fetch_assoc($commandInfo);
        $commandType = ucfirst(str_replace('_', ' ', $row['command_type']));
    }
    
    $alertMsg = $message ? $message : "$commandType " . ($success ? "completed successfully" : "failed");
    $alertSql = "INSERT INTO alerts (alert_type, message, is_read) 
                 VALUES ('$alertType', '$alertMsg', 0)";
    mysqli_query($conn, $alertSql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Command status updated',
        'command_id' => $commandId,
        'status' => $status
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
