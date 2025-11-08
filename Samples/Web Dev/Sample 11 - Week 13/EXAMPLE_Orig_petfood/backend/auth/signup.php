<?php
session_start();
require __DIR__ . '/../db.php'; // includes $pdo

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $alert = "Please fill in all fields.";
    } else {
        try {
            // Check if email already exists
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

                // ✅ Securely hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Default role = user
                $stmt = $pdo->prepare("
                    INSERT INTO users (user_code, name, email, password, role)
                    VALUES (?, ?, ?, ?, 'user')
                ");
                $stmt->execute([$user_code, $name, $email, $hashedPassword]);

                $alert = "Account created successfully! You can now log in.";
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
<link rel="stylesheet" href="../css/styles.css"> <!-- ensure this path is correct -->
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
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    width: 350px;
    text-align: center;
}
input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
}
button {
    width: 100%;
    background: #8B4513;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
}
button:hover {
    background: #6e3510;
}
a {
    color: #8B4513;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
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
