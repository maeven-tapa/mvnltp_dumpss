<?php
session_start();
require __DIR__ . '/../db.php';

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $hashedPassword = md5($password);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $hashedPassword]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // ✅ Automatically detect your base project folder correctly
        $projectFolder = 'EXAMPLE_Orig_petfood'; // <-- adjust only this if your folder name changes

        if ($user['role'] === 'admin') {
            header("Location: /$projectFolder/pages/admin/home.php");
        } else {
            header("Location: /$projectFolder/pages/user/home.php");
        }
        exit;
    } else {
        $alert = "Invalid email or password.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Login — Pet Food Place</title>
<link rel="stylesheet" href="../styles.css">
</head>
<body>
<div class="container">
  <h2>Welcome Back</h2>
  <form method="post">
    <input type="email" name="email" placeholder="Email address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Log In</button>
  </form>
  <p>Don’t have an account? <a href="signup.php">Sign up</a></p>
</div>

<?php if ($alert): ?>
<script>alert(<?= json_encode($alert) ?>);</script>
<?php endif; ?>
</body>
</html>
