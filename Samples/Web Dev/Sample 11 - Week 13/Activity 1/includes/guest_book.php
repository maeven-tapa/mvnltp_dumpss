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

// Validate date is not in the past
$selectedDate = new DateTime($date);
$today = new DateTime();
$today->setTime(0, 0, 0);
if ($selectedDate < $today) {
    echo json_encode(['success' => false, 'message' => 'Cannot book appointments in the past']);
    $conn->close();
    exit();
}

// If a doctor is selected, validate the appointment against their availability
if ($vet !== '') {
    // Check if doctor is available on the selected date
    $stmt = $conn->prepare("SELECT available_dates, available_times FROM tbl_vets WHERE name = ? AND status = 'On Duty'");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        $conn->close();
        exit();
    }
    
    $stmt->bind_param('s', $vet);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected doctor is not available']);
        $stmt->close();
        $conn->close();
        exit();
    }
    
    $doctorRow = $result->fetch_assoc();
    $stmt->close();
    
    $availableDates = json_decode($doctorRow['available_dates'], true);
    $availableTimes = json_decode($doctorRow['available_times'], true);
    
    if (!is_array($availableDates)) $availableDates = [];
    if (!is_array($availableTimes)) $availableTimes = [];
    
    // Check if the selected date's day of week is in available days
    $dayOfWeek = $selectedDate->format('l'); // e.g., "Monday"
    if (!in_array($dayOfWeek, $availableDates)) {
        echo json_encode(['success' => false, 'message' => 'Doctor is not available on the selected date']);
        $conn->close();
        exit();
    }
    
    // Check if the selected time is within doctor's available hours
    $timeValid = false;
    foreach ($availableTimes as $timeRange) {
        $timeRange = trim($timeRange);
        if (strpos($timeRange, '-') !== false) {
            list($startHour, $endHour) = explode('-', $timeRange);
            $startHour = (int)trim($startHour);
            $endHour = (int)trim($endHour);
            $selectedHour = (int)substr($time, 0, 2);
            
            if ($selectedHour >= $startHour && $selectedHour < $endHour) {
                $timeValid = true;
                break;
            }
        }
    }
    
    if (!$timeValid) {
        echo json_encode(['success' => false, 'message' => 'Selected time is not within doctor\'s available hours']);
        $conn->close();
        exit();
    }
    
    // Check if the specific time slot is already booked
    $checkBooking = $conn->prepare("SELECT id FROM tbl_appointments WHERE vet = ? AND appt_date = ? AND appt_time = ? AND status NOT IN ('cancelled', 'rejected')");
    if (!$checkBooking) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        $conn->close();
        exit();
    }
    
    $checkBooking->bind_param('sss', $vet, $date, $time);
    $checkBooking->execute();
    $bookingResult = $checkBooking->get_result();
    
    if ($bookingResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
        $checkBooking->close();
        $conn->close();
        exit();
    }
    
    $checkBooking->close();
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
