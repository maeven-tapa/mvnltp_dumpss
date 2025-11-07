<?php
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'db_trailsandtails_midterm';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit();
}

// Get available doctors (for guest and registered user appointment booking)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getAvailableDoctors') {
    $appt_date = isset($_GET['date']) ? trim($_GET['date']) : '';

    $query = "SELECT vet_id, name, status, available_dates, available_times FROM tbl_vets WHERE status = 'On Duty'";

    $result = $conn->query($query);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Query failed']);
        $conn->close();
        exit();
    }

    $available_doctors = [];
    while ($row = $result->fetch_assoc()) {
        // Handle both JSON and comma-separated formats
        if ($row['available_dates']) {
            $decoded = json_decode($row['available_dates'], true);
            $available_dates = is_array($decoded) ? $decoded : array_map('trim', explode(',', $row['available_dates']));
        } else {
            $available_dates = [];
        }

        if ($row['available_times']) {
            $decoded = json_decode($row['available_times'], true);
            $available_times = is_array($decoded) ? $decoded : array_map('trim', explode(',', $row['available_times']));
        } else {
            $available_times = [];
        }

        // If a specific date is provided, check if doctor is available on that day
        if ($appt_date) {
            $date_obj = new DateTime($appt_date);
            $day_name = $date_obj->format('l'); // e.g., "Monday", "Tuesday"

            // Check if doctor is available on this day
            if (!in_array($day_name, $available_dates)) {
                continue; // Skip this doctor if not available on this day
            }
        }

        $available_doctors[] = [
            'vet_id' => $row['vet_id'],
            'name' => 'Dr. ' . $row['name'],  // Add Dr. prefix
            'name_without_prefix' => $row['name'],  // Store original name for database queries
            'available_dates' => $available_dates,
            'available_times' => $available_times
        ];
    }

    echo json_encode(['success' => true, 'data' => $available_doctors]);
    $conn->close();
    exit();
}

// Get booked times for a specific date and doctor (public)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getBookedTimes') {
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    $doctor_name = isset($_GET['doctor']) ? trim($_GET['doctor']) : '';

    if (!$date) {
        echo json_encode(['success' => false, 'message' => 'Date is required']);
        $conn->close();
        exit();
    }

    // Remove 'Dr. ' prefix if present for database lookup
    $doctor_name = str_replace('Dr. ', '', $doctor_name);

    $query = "SELECT DISTINCT appt_time FROM tbl_appointments 
              WHERE appt_date = ? AND vet = ? AND status NOT IN ('cancelled', 'rejected')";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed']);
        $conn->close();
        exit();
    }

    $stmt->bind_param('ss', $date, $doctor_name);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_times = [];
    while ($row = $result->fetch_assoc()) {
        $booked_times[] = $row['appt_time'];
    }

    echo json_encode(['success' => true, 'data' => $booked_times]);
    $stmt->close();
    $conn->close();
    exit();
}

$conn->close();
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
