<?php
session_start();
require __DIR__ . '/../db.php';

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $alert = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $alert = "Email already registered.";
            } else {
                $stmt = $pdo->query("SELECT user_code FROM users ORDER BY id ASC");
                $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $num = 1;
                while (in_array(sprintf("USR-%04d", $num), $existing)) {
                    $num++;
                }
                $user_code = sprintf("USR-%04d", $num);

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users (user_code, name, email, password, role)
                    VALUES (?, ?, ?, ?, 'user')
                ");
                $stmt->execute([$user_code, $name, $email, $hashedPassword]);

                $alert = "success|Account created successfully! You can now log in.";
            }
        } catch (PDOException $e) {
            $alert = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up — Pet Food Place</title>
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
    <a href="login.php">Log In</a>
  </nav>
</header>

<!-- Main Content -->
<div class="container">
  <div class="content-wrapper narrow">
    <h2 style="text-align: center; margin-bottom: 30px; color: var(--text-dark); font-size: 2rem;">Create Account</h2>
    
    <?php if ($alert): ?>
      <?php if (strpos($alert, 'success|') === 0): ?>
        <div class="alert success"><?= htmlspecialchars(substr($alert, 8)) ?></div>
      <?php else: ?>
        <div class="alert error"><?= htmlspecialchars($alert) ?></div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="post">
      <div>
        <label>Full Name</label>
        <input type="text" name="name" required placeholder="Enter your full name">
      </div>
      
      <div>
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="Enter your email">
      </div>
      
      <div>
        <label>Password</label>
        <input type="password" name="password" required placeholder="Create a password">
      </div>
      
      <button type="submit" class="btn btn-brown w-full">Sign Up</button>
    </form>

    <div style="margin-top: 24px; text-align: center; color: var(--text-soft);">
      Already have an account? <a href="login.php" style="color: var(--brown); font-weight: 600; text-decoration: none;">Log In</a>
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