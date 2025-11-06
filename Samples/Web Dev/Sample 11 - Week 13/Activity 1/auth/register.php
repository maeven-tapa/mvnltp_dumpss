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

// If the form was submitted, process registration
if ($_SERVER["REQUEST_METHOD"] === 'POST') {
        $fname = isset($_POST['fname']) ? trim($_POST['fname']) : '';
        $lname = isset($_POST['lname']) ? trim($_POST['lname']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        $pass = isset($_POST['password']) ? $_POST['password'] : '';
        // Default role and status for newly registered accounts
        $role = 'user';
        $status = 'active';

        // Basic validation
        if ($fname === '' || $lname === '' || $email === '' || $contact === '' || $pass === '') {
                echo "<script>alert('Please fill in all required fields.'); window.location.href='register.php';</script>";
                exit();
        }

        // Check if user already exists
        $checkQuery = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
        $checkQuery->bind_param("s", $email);
        $checkQuery->execute();
        $result = $checkQuery->get_result();

        if ($result && $result->num_rows > 0) {
                echo "<script>alert('Email already registered!'); window.location.href='register.php';</script>";
        } else {
                // Generate a user_id like USR0001
                $newId = 'USR0001';
                $res = $conn->query("SELECT user_id FROM tbl_users WHERE user_id LIKE 'USR%' ORDER BY user_id DESC LIMIT 1");
                if ($res && $res->num_rows > 0) {
                    $r = $res->fetch_assoc();
                    $last = $r['user_id'];
                    $num = intval(substr($last, 3));
                    $newId = sprintf('USR%04d', $num + 1);
                }

                // Insert new user using prepared statement (include contact, role and status)
                $insert = $conn->prepare("INSERT INTO tbl_users (user_id, fname, lname, email, contact, pass, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$insert) {
                    echo "<script>alert('Prepare failed: " . addslashes($conn->error) . "'); window.location.href='register.php';</script>";
                } else {
                    $insert->bind_param('ssssssss', $newId, $fname, $lname, $email, $contact, $pass, $role, $status);
                    if ($insert->execute()) {
                        echo "<script>alert('Successfully Registered!'); window.location.href='login.php';</script>";
                    } else {
                        echo "<script>alert('Registration error: " . addslashes($insert->error) . "'); window.location.href='register.php';</script>";
                    }
                    $insert->close();
                }
        }

        $checkQuery->close();
        $conn->close();
        exit();
}

// If not POST, fall through to show the registration form (HTML below)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Tails and Trails</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth/signup.css">
</head>
<body>
    <div class="signup-wrapper">
        <div class="signup-card">

            <div class="logo-box">
                <img src="../assets/images/logo.png" alt="Tails and Trails Logo">
            </div>

            <h1 class="signup-title">SIGN UP</h1>

            <form id="signupForm" action="register.php" method="POST">
                <div class="form-group">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" name="fname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" name="lname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact No.</label>
                    <input type="text" id="contact" name="contact" class="form-control" required placeholder="e.g. 09171234567">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-control" required>
                        <img src="../assets/images/eye-open.png" id="togglePassword" class="eye-icon" alt="Toggle password">
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirmPassword" class="form-control" required>
                        <img src="../assets/images/eye-open.png" id="toggleConfirm" class="eye-icon" alt="Toggle confirm password">
                    </div>
                </div>

                <button type="submit" class="btn-signup">Create Account</button>
                <p class="login-link">Already have an account? <a href="login.php">Log In</a></p>
            </form>
        </div>
    </div>
    <script src="../assets/js/auth/signup.js"></script>
</body>
</html>
