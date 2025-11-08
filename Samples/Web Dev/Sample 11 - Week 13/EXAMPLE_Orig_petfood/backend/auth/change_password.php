<?php
session_start();
require_once '../db.php';

// ✅ SECURITY: Only logged-in admins can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['email'])) {
    session_destroy();
    header("Location: /EXAMPLE_Orig_petfood/backend/auth/login.php");
    exit;
}

$email = $_SESSION['email'];
$isForced = isset($_SESSION['force_change_password']) && $_SESSION['force_change_password'] === true;

$message = "";

// ✅ Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = trim($_POST['new_password']);
    $conf = trim($_POST['confirm_password']);

    // 1️⃣ Validation checks
    if (empty($newpass) || empty($conf)) {
        $message = "⚠️ Please fill in all fields.";
    } elseif ($newpass !== $conf) {
        $message = "❌ Passwords do not match. Please try again.";
    } elseif (strlen($newpass) < 6) {
        $message = "⚠️ Password must be at least 6 characters long.";
    } else {
        // 2️⃣ Hash and update password
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :hash WHERE email = :email");
        $stmt->execute([':hash' => $hash, ':email' => $email]);

        // 3️⃣ Remove force flag and redirect to admin dashboard
        unset($_SESSION['force_change_password']);
        $_SESSION['message'] = "✅ Password changed successfully!";
        header("Location: /EXAMPLE_Orig_petfood/pages/admin/home.php");
        exit;
    }
}

// ✅ Security catch: If user leaves this page without submitting, session expires in 3 minutes
// Force logout if session expires (3 mins)
if ($isForced) {
    if (!isset($_SESSION['last_activity'])) $_SESSION['last_activity'] = time();
    if (time() - $_SESSION['last_activity'] > 180) {
        session_destroy();
        header("Location: /EXAMPLE_Orig_petfood/backend/auth/login.php");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Change Password — Admin</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #f9f4e6, #f1d1a2);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.container {
    background: white;
    border-radius: 10px;
    padding: 30px;
    width: 380px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.alert {
    background: #fff5d4;
    border: 1px solid #f0d98a;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 10px;
}
.btn {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn-brown {
    background: #8B4513;
    color: white;
    font-weight: bold;
}
.btn-brown:hover {
    background: #6e3510;
}
h2 {
    text-align: center;
}
a.back {
    display: block;
    margin-top: 15px;
    text-align: center;
    color: #6e3510;
    text-decoration: none;
}
</style>
</head>
<body>
<div class="container">
  <h2>Change Password</h2>

  <?php if ($isForced): ?>
  <div class="alert">
    <strong>Security Notice:</strong> You must change your password before accessing the Admin Dashboard.
  </div>
  <?php endif; ?>

  <?php if (!empty($message)): ?>
  <div class="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <label>New Password</label><br>
    <input type="password" name="new_password" required minlength="6" style="width:100%;padding:8px;margin:5px 0;"><br>
    <label>Confirm Password</label><br>
    <input type="password" name="confirm_password" required minlength="6" style="width:100%;padding:8px;margin:5px 0;"><br>
    <button type="submit" class="btn btn-brown">Update Password</button>
  </form>

  <?php if (!$isForced): ?>
  <a href="/EXAMPLE_Orig_petfood/pages/admin/home.php" class="back">← Back to Dashboard</a>
  <?php endif; ?>
</div>

<script>
// 🚨 Warn user if they try to close/refresh without finishing
window.onbeforeunload = function() {
  return "Are you sure you want to leave? Unsaved changes will be lost and your session may expire.";
};

// ✅ Remove warning on form submission
const form = document.querySelector("form");
form.addEventListener("submit", function() {
    window.onbeforeunload = null;
});
</script>

</body>
</html>
