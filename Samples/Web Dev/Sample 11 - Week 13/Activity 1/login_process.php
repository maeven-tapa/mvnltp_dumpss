<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'db_trailsandtails_midterm';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $pass = $_POST['pass'];

    $query = $conn->prepare("SELECT * FROM tbl_users WHERE email = ? AND pass = ?");
    $query->bind_param("ss", $email, $pass);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $fullname = $user['fname'] . ' ' . $user['lname'];
        $_SESSION['name'] = $fullname;
        echo "Successfully logged in!";
        echo "<script>window.location.href='dashboard.html?name=" . urlencode($fullname) . "';</script>";
    } else {
        echo "Wrong Email or Password";
    }

    $query->close();
}

$conn->close();
?>
