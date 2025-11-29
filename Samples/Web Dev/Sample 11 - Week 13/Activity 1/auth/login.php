<?php
session_start();


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


            if (isset($user['status']) && strtolower($user['status']) !== 'active') {
                $error_message = 'Your account is currently inactive. Please contact the administrator for reactivation.';
            } else {

                $_SESSION['fname'] = isset($user['fname']) ? $user['fname'] : '';
                $_SESSION['lname'] = isset($user['lname']) ? $user['lname'] : '';
                $_SESSION['name'] = $fullname;

                $_SESSION['user_id'] = isset($user['user_id']) ? $user['user_id'] : (isset($user['id']) ? $user['id'] : null);
                $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';


                $role = strtolower($_SESSION['role']);
                if ($role === 'user') {

                    header("Location: ../pages/user/dashboard.php");
                    exit();
                } elseif ($role === 'admin') {

                    header("Location: ../pages/admin/dashboard.php");
                    exit();
                } else {

                    header("Location: ../pages/user/dashboard.php");
                    exit();
                }
            }
        } else {
            $error_message = 'Invalid email or password. Please try again.';
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Tails and Trails</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/auth/login.css">
</head>
<body class="login-page">
  <div class="login-container">

    <div class="welcome-section">

      <div class="logo-section">
        <img src="../assets/images/logo.png" alt="Tails and Trails Logo" class="logo-img">
      </div>


      <div id="loginCarousel" class="carousel slide carousel-fade slideshow-image-container" data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-inner slideshow">

          <div class="carousel-item slide-image active">
            <img src="../assets/images/slide.jpg" alt="Tails and Trails Service" class="slide-image-bg">
            <div class="slide-gradient-overlay"></div>
            <div class="welcome-content">
              <div class="content-icon mb-4">
                <i class="bi bi-heart-fill"></i>
              </div>
              <h1 class="welcome-title">YOUR PET'S SECOND HOME</h1>
              <p class="welcome-subtitle">Log in and let's keep your furry friend happy and healthy!</p>
            </div>
          </div>


          <div class="carousel-item slide-image">
            <img src="../assets/images/slide.jpg" alt="Tails and Trails Service" class="slide-image-bg">
            <div class="slide-gradient-overlay"></div>
            <div class="welcome-content">
              <div class="content-icon mb-4">
                <i class="bi bi-star-fill"></i>
              </div>
              <h1 class="welcome-title">Grooming. Caring. Loving.</h1>
              <p class="welcome-subtitle">It all happens here — expert care from vets & groomers who love animals.</p>
            </div>
          </div>


          <div class="carousel-item slide-image">
            <img src="../assets/images/slide.jpg" alt="Tails and Trails Service" class="slide-image-bg">
            <div class="slide-gradient-overlay"></div>
            <div class="welcome-content">
              <div class="content-icon mb-4">
                <i class="bi bi-flower1"></i>
              </div>
              <h1 class="welcome-title">Trust & Excellence</h1>
              <p class="welcome-subtitle">Professional service with a personal touch for every pet in our care.</p>
            </div>
          </div>
        </div>


        <div class="slideshow-indicators">
          <button class="indicator active" data-bs-target="#loginCarousel" data-bs-slide-to="0" aria-label="Slide 1"></button>
          <button class="indicator" data-bs-target="#loginCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
          <button class="indicator" data-bs-target="#loginCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
      </div>
    </div>


    <div class="login-section">
      <div class="login-form-wrapper">
        <div class="login-form-container">
          <div class="text-center mb-4">
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to your account</p>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" id="errorBox" role="alert">
              <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-circle-fill me-3 mt-1"></i>
                <div>
                  <strong>Login Failed</strong>
                  <p class="mb-0"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
              </div>
              <button type="button" class="btn-close" onclick="closeErrorBox()"></button>
            </div>
          <?php endif; ?>

          <form id="loginForm" action="login.php" method="POST" class="needs-validation" novalidate>

            <div class="form-group mb-3">
              <label for="username" class="form-label fw-600">Email Address</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                  <i class="bi bi-envelope-fill" style="color: var(--primary); font-size: 1.1rem;"></i>
                </span>
                <input
                  id="username"
                  name="email"
                  type="email"
                  class="form-control form-control-lg bg-light border-start-0"
                  placeholder="your@email.com"
                  required
                />
                <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
            </div>


            <div class="form-group mb-3">
              <label for="password" class="form-label fw-600">Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                  <i class="bi bi-lock-fill" style="color: var(--primary); font-size: 1.1rem;"></i>
                </span>
                <input
                  id="password"
                  name="pass"
                  type="password"
                  class="form-control form-control-lg bg-light border-start-0 border-end-0"
                  placeholder="Enter your password"
                  required
                />
                <button
                  type="button"
                  id="togglePassword"
                  class="btn btn-light border-start-0"
                  aria-label="Toggle password visibility"
                >
                  <i class="bi bi-eye-fill" style="color: var(--primary); font-size: 1.1rem;"></i>
                </button>
                <div class="invalid-feedback">Password is required.</div>
                </div>
            </div>


            <button type="submit" name="submit" class="btn btn-primary btn-lg w-100 fw-600 mb-3">
              <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
          </form>


          <div class="text-center mb-3">
            <small class="text-muted">Don't have an account?</small>
          </div>


          <a href="register.php" class="btn btn-outline-primary btn-lg w-100 fw-600">
            <i class="bi bi-person-plus me-2"></i>Create Account
          </a>
        </div>


        <div class="text-center mt-4 pt-3 border-top">
          <small class="text-muted">© 2025 Tails and Trails. All rights reserved.</small>
        </div>
      </div>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app.js"></script>
  <script src="../assets/js/auth/login.js"></script>
</body>
</html>
