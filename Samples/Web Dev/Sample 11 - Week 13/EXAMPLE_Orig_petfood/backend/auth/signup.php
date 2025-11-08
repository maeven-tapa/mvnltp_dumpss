<?php
session_start();
require __DIR__ . '/../db.php'; 

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = md5(trim($_POST['password'] ?? ''));

    if ($name === '' || $email === '' || $password === '') {
        $alert = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $alert = "Email already registered.";
        } else {
            // Create unique user code
            $stmt = $pdo->query("SELECT user_code FROM users ORDER BY id ASC");
            $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $num = 1;
            while (in_array(sprintf("USR-%04d", $num), $existing)) {
                $num++;
            }
            $user_code = sprintf("USR-%04d", $num);

            // Default role = user
            $stmt = $pdo->prepare("INSERT INTO users (user_code, name, email, password, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->execute([$user_code, $name, $email, $password]);

            $alert = "Account created successfully! You can now log in.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Sign Up — Pet Food Place</title>
<link rel="stylesheet" href="../css/styles.css"> <!-- FIXED PATH -->
</head>
<body>
<div class="container">
  <h2>Create Account</h2>
  <form method="post" action="">
    <input type="text" name="name" placeholder="Full name" required>
    <input type="email" name="email" placeholder="Email address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Sign Up</button>
  </form>
  <p>Already have an account? <a href="login.php">Log in</a></p>
</div>

<?php if ($alert): ?>
<script>alert(<?= json_encode($alert) ?>);</script>
<?php endif; ?>
</body>
</html>
