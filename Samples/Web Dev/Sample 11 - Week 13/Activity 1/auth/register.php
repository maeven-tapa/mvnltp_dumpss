<?php
session_start();

// If user is already logged in, redirect them to their appropriate dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'user';
    if ($role === 'admin') {
        header("Location: ../pages/admin/dashboard.php");
    } else {
        header("Location: ../pages/user/dashboard.php");
    }
    exit();
}

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
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth/signup.css">
</head>
<body>
    <div class="signup-wrapper container py-5">
        <div class="signup-card mx-auto">

            <div class="logo-box">
                <img src="../assets/images/logo.png" alt="Tails and Trails Logo">
            </div>

            <h1 class="signup-title">Create an account</h1>

            <form id="signupForm" action="register.php" method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="fname" class="form-label">First Name</label>
                        <input type="text" id="fname" name="fname" class="form-control" required placeholder="John" autocomplete="given-name">
                        <div class="invalid-feedback">First name is required.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="lname" class="form-label">Last Name</label>
                        <input type="text" id="lname" name="lname" class="form-control" required placeholder="Doe" autocomplete="family-name">
                        <div class="invalid-feedback">Last name is required.</div>
                    </div>

                    <div class="col-12">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="name@example.com" autocomplete="email">
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>

                    <div class="col-12">
                        <label for="contact" class="form-label">Contact No.</label>
                        <input type="tel" id="contact" name="contact" class="form-control" required placeholder="e.g. 09171234567" autocomplete="tel">
                        <div class="invalid-feedback">Please enter a valid contact number (7-15 digits).</div>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control" required placeholder="Create a strong password" autocomplete="new-password">
                            <img src="../assets/images/eye-open.png" id="togglePassword" class="eye-icon" alt="Toggle password">
                            <div class="invalid-feedback">Password is required.</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirmPassword" class="form-control" required placeholder="Re-type your password" autocomplete="new-password">
                            <img src="../assets/images/eye-open.png" id="toggleConfirm" class="eye-icon" alt="Toggle confirm password">
                            <div class="invalid-feedback">Please confirm your password.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Create Account</button>
                </div>

                <p class="login-link mt-3">Already have an account? <a href="login.php">Log In</a></p>
            </form>
        </div>
    </div>
        <!-- Bootstrap JS (Bundle includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Script loading order (IMPORTANT: app.js MUST be first) -->
        <script src="../assets/js/app.js"></script>
        <script src="../assets/js/auth/signup.js"></script>
        <script>
        // Bootstrap form validation integration
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')

            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
        </script>
</body>
</html>
