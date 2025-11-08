<?php
session_start();

// Allow only admins to access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /EXAMPLE_Orig_petfood/backend/auth/login.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="/EXAMPLE_Orig_petfood/css/styles.css">
</head>
<body>
<div class="container">
  <h1>Welcome, <?= htmlspecialchars($_SESSION['email']) ?>!</h1>
  <p>This is the <strong>Admin</strong> home page.</p>
  <a href="/EXAMPLE_Orig_petfood/backend/auth/logout.php">Logout</a>
</div>
</body>
</html>
