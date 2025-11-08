<?php
session_start();
include_once '../db.php';

$error = '';

// Redirect already logged-in users
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: /EXAMPLE_Orig_petfood/pages/admin/home.php");
        exit;
    } else {
        header("Location: /EXAMPLE_Orig_petfood/pages/user/home.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $stored = $user['password'];

            if (password_verify($password, $stored)) {
                if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE users SET password = :newHash WHERE id = :id");
                    $update->execute([':newHash' => $newHash, ':id' => $user['id']]);
                }

                if ($user['role'] === 'admin' && password_verify('admin123', $stored)) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['force_change_password'] = true;
                    header("Location: /EXAMPLE_Orig_petfood/backend/auth/change_password.php?force=1");
                    exit;
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: /EXAMPLE_Orig_petfood/pages/admin/home.php");
                } else {
                    header("Location: /EXAMPLE_Orig_petfood/pages/user/home.php");
                }
                exit;

            } elseif (md5($password) === $stored) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = :newHash WHERE id = :id");
                $update->execute([':newHash' => $newHash, ':id' => $user['id']]);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin' && $password === 'admin123') {
                    $_SESSION['force_change_password'] = true;
                    header("Location: /EXAMPLE_Orig_petfood/backend/auth/change_password.php?force=1");
                    exit;
                }

                if ($user['role'] === 'admin') {
                    header("Location: /EXAMPLE_Orig_petfood/pages/admin/home.php");
                } else {
                    header("Location: /EXAMPLE_Orig_petfood/pages/user/home.php");
                }
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Pet Food Place</title>
<link rel="stylesheet" href="../../assets/css/shared.css">
</head>
<body>

<!-- Floating Paw Prints -->
<div id="paw-container"></div>

<!-- Header -->
<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h2>Pet Food Place</h2>
  </div>
  <nav class="links">
    <a href="../../index.php">Home</a>
    <a href="signup.php">Sign Up</a>
  </nav>
</header>

<!-- Main Content -->
<div class="container">
  <div class="content-wrapper narrow">
    <h2 style="text-align: center; margin-bottom: 30px; color: var(--text-dark); font-size: 2rem;">Welcome Back</h2>
    
    <?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div>
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="Enter your email">
      </div>
      
      <div>
        <label>Password</label>
        <input type="password" name="password" required placeholder="Enter your password">
      </div>
      
      <button type="submit" class="btn btn-brown w-full">Login</button>
    </form>

    <div style="margin-top: 24px; text-align: center; color: var(--text-soft);">
      Don't have an account? <a href="signup.php" style="color: var(--brown); font-weight: 600; text-decoration: none;">Sign Up</a>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
</body>
</html>