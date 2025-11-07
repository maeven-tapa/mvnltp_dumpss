<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../styles.css">
</head>
<body>
<div class="container">
  <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> (Admin)</h1>
  <p>This is the admin home page.</p>
  <a href="../auth/logout.php">Logout</a>
</div>
</body>
</html>
