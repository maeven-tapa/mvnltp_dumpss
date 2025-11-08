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

$error_message = '';

// Handle POST (login attempt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $pass = isset($_POST['pass']) ? $_POST['pass'] : '';

    if ($email === '' || $pass === '') {
        $error_message = 'Please enter both email and password.';
    } else {
        $query = $conn->prepare("SELECT * FROM tbl_users WHERE email = ? AND pass = ?");
        $query->bind_param("ss", $email, $pass);
        $query->execute();
        $result = $query->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $fullname = $user['fname'] . ' ' . $user['lname'];

            // Check account status first
            if (isset($user['status']) && strtolower($user['status']) !== 'active') {
                $error_message = 'Your account is currently inactive. Please contact the administrator for reactivation.';
            } else {
                // Set session values (store first and last name separately and keep fullname for compatibility)
                $_SESSION['fname'] = isset($user['fname']) ? $user['fname'] : '';
                $_SESSION['lname'] = isset($user['lname']) ? $user['lname'] : '';
                $_SESSION['name'] = $fullname;
                // Support both new `user_id` string column or legacy `id` numeric column
                $_SESSION['user_id'] = isset($user['user_id']) ? $user['user_id'] : (isset($user['id']) ? $user['id'] : null);
                $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';

                // Redirect based on role. For now, admin will be redirected to the same dashboard as a placeholder.
                $role = strtolower($_SESSION['role']);
                if ($role === 'user') {
                    // redirect to the user dashboard (pages/user/dashboard.php)
                    header("Location: ../pages/user/dashboard.php");
                    exit();
                } elseif ($role === 'admin') {
                    // Redirect to the admin dashboard (pages/admin/dashboard.php)
                    header("Location: ../pages/admin/dashboard.php");
                    exit();
                } else {
                    // Unknown role -> default to user dashboard
                    header("Location: ../pages/user/dashboard.php");
                    exit();
                }
            }
        } else {
            $error_message = 'Invalid email or password. Please try again.';
        }
    }
}

// If not POST, show login form
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Tails and Trails</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/auth/login.css">
</head>
<body>
  <div class="login-container">
    <div class="welcome-section">
      <div class="logo-section">
  <img src="../assets/images/logo.png" alt="Logo" style="width: 20%; height: 20%; object-fit: contain;">
      </div>

      <div class="slideshow">
        <div class="slide active">
          <div class="welcome-content">
            <h1>YOUR PET'S SECOND HOME</h1>
            <p>Log in and let's keep your furry friend happy and healthy!</p>
          </div>
        </div>

        <div class="slide">
          <div class="welcome-content">
            <h1>Grooming. Caring. Loving.</h1>
            <p>It all happens here — expert care from vets & groomers who love animals.</p>
          </div>
        </div>

        <div class="slide image-full"></div>
      </div>

      <div class="slideshow-indicators">
        <div class="indicator active" data-target="0"></div>
        <div class="indicator" data-target="1"></div>
        <div class="indicator" data-target="2"></div>
      </div>
    </div>

    <div class="login-section">
      <div class="login-form-container">
        <h1 class="login-title">LOG IN</h1>
        
        <?php if (!empty($error_message)): ?>
          <div class="error-box" id="errorBox">
            <div class="error-icon">⚠</div>
            <div class="error-content">
              <div class="error-title">Login Failed</div>
              <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            </div>
            <button class="error-close" onclick="closeErrorBox()">×</button>
          </div>
        <?php endif; ?>

        <form id="loginForm" action="login.php" method="POST">
          <div class="form-group">
            <label for="username">Email</label>
            <input id="username" name="email" type="email" class="form-control" required />
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper" style="position: relative;">
              <input id="password" name="pass" type="password" class="form-control" required />
              <img
                id="togglePassword"
                src="../assets/images/eye-open.png"
                alt="Show Password"
                class="password-toggle"
                style="width: 20px; height: 20px; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"
              />
            </div>
          </div>


          <button class="btn-login" name="submit" type="submit">Log In</button>
        </form>

        <div class="register-link">
          Don't have an account? <a href="register.php">Register Now</a>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/auth/login.js"></script>
</body>
</html>
