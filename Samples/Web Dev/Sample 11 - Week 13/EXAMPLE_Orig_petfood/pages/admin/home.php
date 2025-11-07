<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>User Dashboard</title>
<link rel="stylesheet" href="../styles.css">
</head>
<body>
<div class="container">
  <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
  <p>This is the user home page.</p>
  <a href="../auth/logout.php">Logout</a>
</div>
</body>
</html>
