<?php
// Session validation with same security headers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Validate session for API endpoint
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'db_trailsandtails_midterm';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT id, pet_name, service, vet, appt_date, appt_time, status FROM tbl_appointments WHERE user_id = ? ORDER BY appt_date ASC, appt_time ASC");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    echo json_encode($rows);
    $stmt->close();
    $conn->close();
    exit();
}

// POST actions: create or cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'create') {
        $pet = isset($_POST['pet_name']) ? trim($_POST['pet_name']) : '';
        $service = isset($_POST['service']) ? trim($_POST['service']) : '';
        $vet = isset($_POST['vet']) ? trim($_POST['vet']) : '';
        $date = isset($_POST['appt_date']) ? $_POST['appt_date'] : '';
        $time = isset($_POST['appt_time']) ? $_POST['appt_time'] : '';

        if ($pet === '' || $service === '' || $date === '' || $time === '') {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit();
        }

        // Validate date is not in the past
        $selectedDate = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        if ($selectedDate < $today) {
            echo json_encode(['success' => false, 'message' => 'Cannot book appointments in the past']);
            exit();
        }

        // If a doctor is selected, validate the appointment against their availability
        if ($vet !== '') {
            // Check if doctor is available on the selected date
            $stmt = $conn->prepare("SELECT available_dates, available_times FROM tbl_vets WHERE name = ? AND status = 'On Duty'");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                exit();
            }
            
            $stmt->bind_param('s', $vet);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Selected doctor is not available']);
                $stmt->close();
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
                exit();
            }
            
            // Check if the specific time slot is already booked
            $checkBooking = $conn->prepare("SELECT id FROM tbl_appointments WHERE vet = ? AND appt_date = ? AND appt_time = ? AND status NOT IN ('cancelled', 'rejected')");
            if (!$checkBooking) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                exit();
            }
            
            $checkBooking->bind_param('sss', $vet, $date, $time);
            $checkBooking->execute();
            $bookingResult = $checkBooking->get_result();
            
            if ($bookingResult->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
                $checkBooking->close();
                exit();
            }
            
            $checkBooking->close();
        }

        // Registered users only (USR or ADM prefix)
        // Guest bookings are done via guest_book.php from homepage
        $booking_type = 'registered';
        $insert_user_id = $user_id;
        $guest_name = null;
        $guest_email = null;
        $guest_contact = null;

        $stmt = $conn->prepare("INSERT INTO tbl_appointments (booking_type, user_id, guest_name, guest_email, guest_contact, pet_name, service, vet, appt_date, appt_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param('ssssssssss', $booking_type, $insert_user_id, $guest_name, $guest_email, $guest_contact, $pet, $service, $vet, $date, $time);
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            $stmt->close();
            $s = $conn->prepare("SELECT id, pet_name, service, vet, appt_date, appt_time, status FROM tbl_appointments WHERE id = ? LIMIT 1");
            $s->bind_param('i', $id);
            $s->execute();
            $res = $s->get_result();
            $row = $res->fetch_assoc();
            echo json_encode(['success' => true, 'appointment' => $row]);
            $s->close();
            $conn->close();
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Insert failed']);
            $stmt->close();
            $conn->close();
            exit();
        }
    } elseif ($action === 'cancel') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit();
        }
        $stmt = $conn->prepare("UPDATE tbl_appointments SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        $stmt->bind_param('is', $id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cancelled']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        $stmt->close();
        $conn->close();
        exit();
    }
}

// default
echo json_encode(['success' => false, 'message' => 'Invalid request']);
$conn->close();
exit();
