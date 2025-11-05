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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    
    // Check if user already exists
    $checkQuery = $conn->prepare("SELECT * FROM tbl_users WHERE email = ?");
    $checkQuery->bind_param("s", $email);
    $checkQuery->execute();
    $result = $checkQuery->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>
                alert('Email already registered!');
                window.location.href='signup.html';
              </script>";
    } else {
        // Insert new user
        $sql = "INSERT INTO tbl_users (fname, lname, email, pass) VALUES ('$fname', '$lname', '$email', '$pass')";
        
        if ($conn->query($sql) === TRUE) {
            echo "<script>
                    alert('Successfully Registered!');
                    window.location.href='login.html';
                  </script>";
        } else {
            echo "<script>
                    alert('Error: " . $sql . "<br>" . $conn->error . "');
                    window.location.href='signup.html';
                  </script>";
        }
    }
    
    $checkQuery->close();
}

$conn->close();
?>
