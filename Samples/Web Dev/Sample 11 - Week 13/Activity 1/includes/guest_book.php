<?php
header('Content-Type: application/json');

$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'db_trailsandtails_midterm';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit();
}

// Expected POST fields from the index booking form
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$pet_name = isset($_POST['pet_name']) ? trim($_POST['pet_name']) : '';
$service = isset($_POST['service']) ? trim($_POST['service']) : '';
$vet = isset($_POST['doctor']) ? trim($_POST['doctor']) : '';
$date = isset($_POST['date']) ? $_POST['date'] : '';
$time = isset($_POST['time']) ? $_POST['time'] : '';

if ($fullname === '' || $email === '' || $contact === '' || $pet_name === '' || $service === '' || $date === '' || $time === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    $conn->close();
    exit();
}

// Create appointment for guest with booking_type = 'guest'
// guest_* fields populated, user_id = NULL
$booking_type = 'guest';
$null_user_id = null;

$stmt = $conn->prepare("INSERT INTO tbl_appointments (booking_type, user_id, guest_name, guest_email, guest_contact, pet_name, service, vet, appt_date, appt_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed']);
    $conn->close();
    exit();
}

$stmt->bind_param('ssssssssss', $booking_type, $null_user_id, $fullname, $email, $contact, $pet_name, $service, $vet, $date, $time);
if ($stmt->execute()) {
    $apptId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Booked', 'appointment_ref' => 'GUEST-' . $apptId]);
    exit();
} else {
    $err = $stmt->error;
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $err]);
    exit();
}

?>
