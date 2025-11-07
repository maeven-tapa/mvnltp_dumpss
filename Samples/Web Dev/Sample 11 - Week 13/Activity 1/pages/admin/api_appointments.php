<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

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

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Fetch all appointments (both registered users and guests)
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getAppointments') {
    $status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'date';
    $sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';

    $query = "SELECT tbl_appointments.id, tbl_appointments.booking_type, tbl_appointments.user_id, 
              CONCAT(tbl_users.fname, ' ', tbl_users.lname) as user_name,
              tbl_appointments.guest_name, tbl_appointments.guest_email, tbl_appointments.guest_contact, 
              tbl_appointments.pet_name, tbl_appointments.service, tbl_appointments.vet, 
              tbl_appointments.appt_date, tbl_appointments.appt_time, tbl_appointments.status 
              FROM tbl_appointments 
              LEFT JOIN tbl_users ON tbl_appointments.user_id = tbl_users.user_id 
              WHERE tbl_appointments.status != 'Archived'";

    if ($status_filter !== 'all') {
        $query .= " AND tbl_appointments.status = '" . $conn->real_escape_string($status_filter) . "'";
    }

    if ($search !== '') {
        $query .= " AND (tbl_appointments.pet_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                       OR tbl_appointments.service LIKE '%" . $conn->real_escape_string($search) . "%' 
                       OR tbl_appointments.vet LIKE '%" . $conn->real_escape_string($search) . "%'
                       OR tbl_appointments.guest_name LIKE '%" . $conn->real_escape_string($search) . "%'
                       OR CONCAT(tbl_users.fname, ' ', tbl_users.lname) LIKE '%" . $conn->real_escape_string($search) . "%')";
    }

    $sort_by_map = ['date' => 'appt_date', 'time' => 'appt_time', 'status' => 'status'];
    $sort_column = isset($sort_by_map[$sort_by]) ? $sort_by_map[$sort_by] : 'appt_date';
    $sort_order = in_array(strtoupper($sort_order), ['ASC', 'DESC']) ? strtoupper($sort_order) : 'DESC';
    $query .= " ORDER BY tbl_appointments.$sort_column $sort_order";

    $result = $conn->query($query);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
        $conn->close();
        exit();
    }

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $appointments]);
    $conn->close();
    exit();
}

// Get available doctors (filtered by status and availability for a specific date)
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getAvailableDoctors') {
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

// Get booked times for a specific date and doctor
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getBookedTimes') {
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

// Add or update appointment
if ($method === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'addAppointment') {
        $booking_type = isset($_POST['booking_type']) ? trim($_POST['booking_type']) : 'guest';
        $pet_name = isset($_POST['pet_name']) ? trim($_POST['pet_name']) : '';
        $service = isset($_POST['service']) ? trim($_POST['service']) : '';
        $vet = isset($_POST['vet']) ? trim($_POST['vet']) : '';
        $appt_date = isset($_POST['date']) ? $_POST['date'] : '';
        $appt_time = isset($_POST['time']) ? $_POST['time'] : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'Pending';
        
        // Variables for both types
        $user_id = null;
        $guest_name = null;
        $guest_email = null;
        $guest_contact = null;

        if ($booking_type === 'registered') {
            $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
            if ($user_id === '') {
                echo json_encode(['success' => false, 'message' => 'User ID required for registered booking']);
                exit();
            }
        } else {
            $guest_name = isset($_POST['guest_name']) ? trim($_POST['guest_name']) : '';
            $guest_email = isset($_POST['guest_email']) ? trim($_POST['guest_email']) : '';
            $guest_contact = isset($_POST['guest_contact']) ? trim($_POST['guest_contact']) : '';
            
            if ($guest_name === '' || $guest_email === '' || $guest_contact === '') {
                echo json_encode(['success' => false, 'message' => 'Guest name, email, and contact required']);
                exit();
            }
        }

        if ($pet_name === '' || $service === '' || $appt_date === '' || $appt_time === '') {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO tbl_appointments (booking_type, user_id, guest_name, guest_email, guest_contact, pet_name, service, vet, appt_date, appt_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param('sssssssssss', $booking_type, $user_id, $guest_name, $guest_email, $guest_contact, $pet_name, $service, $vet, $appt_date, $appt_time, $status);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment added successfully', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'updateAppointment') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';

        if ($id === 0 || $status === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE tbl_appointments SET status = ? WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed']);
            exit();
        }

        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        $stmt->close();
    }
    elseif ($action === 'deleteAppointment') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE tbl_appointments SET status = 'Archived' WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed']);
            exit();
        }

        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment archived successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Archive failed']);
        }
        $stmt->close();
    }

    $conn->close();
    exit();
}

$conn->close();
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
