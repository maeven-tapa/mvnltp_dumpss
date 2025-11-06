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

// Fetch all users
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getUsers') {
    $status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $query = "SELECT user_id, fname, lname, email, contact, status, created_at FROM tbl_users WHERE role = 'user'";

    if ($status_filter !== 'all') {
        $query .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
    }

    if ($search !== '') {
        $query .= " AND (CONCAT(fname, ' ', lname) LIKE '%" . $conn->real_escape_string($search) . "%' 
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

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $row['name'] = $row['fname'] . ' ' . $row['lname'];
        $users[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $users]);
    $conn->close();
    exit();
}

// Update user status
if ($method === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'updateUser') {
        $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';

        if ($user_id === '' || $status === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit();
        }

        // Validate status
        if (!in_array($status, ['active', 'inactive'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE tbl_users SET status = ? WHERE user_id = ? AND role = 'user'");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed']);
            exit();
        }

        $stmt->bind_param('ss', $status, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        $stmt->close();
    }

    $conn->close();
    exit();
}

$conn->close();
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
