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

// Fetch all doctors
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getDoctors') {
    $status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $query = "SELECT vet_id, name, email, contact, status, available_dates, available_times, created_at FROM tbl_vets";

    if ($status_filter !== 'all') {
        $query .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
    }

    if ($search !== '') {
        $query .= " AND (name LIKE '%" . $conn->real_escape_string($search) . "%' 
                       OR email LIKE '%" . $conn->real_escape_string($search) . "%' 
                       OR contact LIKE '%" . $conn->real_escape_string($search) . "%')";
    }

    $query .= " ORDER BY created_at DESC";

    $result = $conn->query($query);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Query failed']);
        $conn->close();
        exit();
    }

    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON fields
        $row['available_dates'] = $row['available_dates'] ? json_decode($row['available_dates'], true) : [];
        $row['available_times'] = $row['available_times'] ? json_decode($row['available_times'], true) : [];
        $doctors[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $doctors]);
    $conn->close();
    exit();
}

// Get available doctors (for appointment dropdown - filters by status and availability)
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
        $available_dates = $row['available_dates'] ? json_decode($row['available_dates'], true) : [];
        $available_times = $row['available_times'] ? json_decode($row['available_times'], true) : [];

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
            'name' => $row['name'],
            'available_times' => $available_times
        ];
    }

    echo json_encode(['success' => true, 'data' => $available_doctors]);
    $conn->close();
    exit();
}

// Add new doctor
if ($method === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'addDoctor') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'On Duty';
        $available_dates = isset($_POST['available_dates']) ? trim($_POST['available_dates']) : '';
        $available_times = isset($_POST['available_times']) ? trim($_POST['available_times']) : '';

        // Validate required fields
        if ($name === '' || $email === '' || $contact === '') {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }

        // Generate vet_id
        $vet_id = generateVetId($conn);

        // Convert comma-separated values to JSON arrays
        $dates_array = $available_dates ? array_map('trim', explode(',', $available_dates)) : [];
        $times_array = $available_times ? array_map('trim', explode(',', $available_times)) : [];

        $dates_json = json_encode($dates_array);
        $times_json = json_encode($times_array);

        $stmt = $conn->prepare("INSERT INTO tbl_vets (vet_id, name, email, contact, status, available_dates, available_times) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param('sssssss', $vet_id, $name, $email, $contact, $status, $dates_json, $times_json);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Doctor added successfully', 'vet_id' => $vet_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'updateDoctor') {
        $vet_id = isset($_POST['vet_id']) ? trim($_POST['vet_id']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $available_dates = isset($_POST['available_dates']) ? trim($_POST['available_dates']) : '';
        $available_times = isset($_POST['available_times']) ? trim($_POST['available_times']) : '';

        if ($vet_id === '' || $name === '' || $status === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit();
        }

        // Validate status
        $valid_statuses = ['On Duty', 'Off Duty', 'On Leave'];
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }

        // Convert comma-separated values to JSON arrays
        $dates_array = $available_dates ? array_map('trim', explode(',', $available_dates)) : [];
        $times_array = $available_times ? array_map('trim', explode(',', $available_times)) : [];

        $dates_json = json_encode($dates_array);
        $times_json = json_encode($times_array);

        $stmt = $conn->prepare("UPDATE tbl_vets SET name = ?, email = ?, contact = ?, status = ?, available_dates = ?, available_times = ? WHERE vet_id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed']);
            exit();
        }

        $stmt->bind_param('sssssss', $name, $email, $contact, $status, $dates_json, $times_json, $vet_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Doctor updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        $stmt->close();
    }
    elseif ($action === 'deleteDoctor') {
        $vet_id = isset($_POST['vet_id']) ? trim($_POST['vet_id']) : '';

        if ($vet_id === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM tbl_vets WHERE vet_id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed']);
            exit();
        }

        $stmt->bind_param('s', $vet_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Doctor deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        $stmt->close();
    }

    $conn->close();
    exit();
}

// Helper function to generate unique vet_id
function generateVetId($conn) {
    $query = "SELECT MAX(CAST(SUBSTRING(vet_id, 4) AS UNSIGNED)) as max_id FROM tbl_vets WHERE vet_id LIKE 'VET%'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return 'VET' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

$conn->close();
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
