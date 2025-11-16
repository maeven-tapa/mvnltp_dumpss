<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['email'])) {
    session_destroy();
    header("Location: login.php");
exit;
}

$email = $_SESSION['email'];
$isForced = isset($_SESSION['force_change_password']) && $_SESSION['force_change_password'] === true;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = trim($_POST['new_password']);
    $conf = trim($_POST['confirm_password']);

    if (empty($newpass) || empty($conf)) {
        $message = "Please fill in all fields.";
    } elseif ($newpass !== $conf) {
        $message = "Passwords do not match. Please try again.";
    } elseif (strlen($newpass) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :hash WHERE email = :email");
        $stmt->execute([':hash' => $hash, ':email' => $email]);

        unset($_SESSION['force_change_password']);
        $_SESSION['message'] = "Password changed successfully!";
        header("Location: ../../pages/admin/home.php");
      exit;
    }
}

if ($isForced) {
    if (!isset($_SESSION['last_activity'])) $_SESSION['last_activity'] = time();
    if (time() - $_SESSION['last_activity'] > 180) {
        session_destroy();
        header("Location: login.php");
      exit;
    }
    $_SESSION['last_activity'] = time();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password — Pet Food Place</title>
<link rel="stylesheet" href="../../assets/css/shared.css">
</head>
<body>

<div id="paw-container"></div>

<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h2>Change Password</h2>
  </div>
  <?php if (!$isForced): ?>
  <nav class="links">
    <a href="/EXAMPLE_Orig_petfood/pages/admin/home.php" class="btn btn-light-brown">← Back to Dashboard</a>
  </nav>
  <?php endif; ?>
</header>

<div class="container">
  <div class="content-wrapper narrow">
    <h2 style="text-align: center; margin-bottom: 30px; color: var(--text-dark); font-size: 2rem;">Update Password</h2>

    <?php if ($isForced): ?>
    <div class="alert" style="background: linear-gradient(135deg, #fff4e6, #ffe9a6); border-color: #f0d98a;">
      <strong>⚠️ Security Notice:</strong> You must change your password before accessing the Admin Dashboard.
    </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
    <div class="alert error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div>
        <label>New Password</label>
        <input type="password" name="new_password" required minlength="6" placeholder="Enter new password">
      </div>
      
      <div>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter new password">
      </div>
      
      <button type="submit" class="btn btn-brown w-full">Update Password</button>
    </form>
  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
<script>
<?php if ($isForced): ?>
// Warn user if they try to close/refresh without finishing
window.onbeforeunload = function() {
  return "Are you sure you want to leave? Unsaved changes will be lost and your session may expire.";
};

// Remove warning on form submission
const form = document.querySelector("form");
form.addEventListener("submit", function() {
    window.onbeforeunload = null;
});
<?php endif; ?>
</script>

</body>
</html>