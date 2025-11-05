<?php
$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'db_trailsandtails_midterm';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Check if email already exists
    $checkQuery = $conn->prepare("SELECT * FROM tbl_newsletter WHERE email = ?");
    $checkQuery->bind_param("s", $email);
    $checkQuery->execute();
    $result = $checkQuery->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed to our newsletter!']);
    } else {
        // Insert new subscription
        $insertQuery = $conn->prepare("INSERT INTO tbl_newsletter (email) VALUES (?)");
        $insertQuery->bind_param("s", $email);
        
        if ($insertQuery->execute()) {
            echo json_encode(['success' => true, 'message' => 'Successfully subscribed to our newsletter!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error subscribing. Please try again.']);
        }
        $insertQuery->close();
    }
    
    $checkQuery->close();
}

$conn->close();
?>
