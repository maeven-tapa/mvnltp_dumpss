<?php
session_start();
include_once '../db.php'; // defines $pdo

$error = ''; // Initialize error message

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

            // ✅ Step 1: Try modern password_verify()
            if (password_verify($password, $stored)) {

                // Optional: rehash if algorithm changed
                if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE users SET password = :newHash WHERE id = :id");
                    $update->execute([':newHash' => $newHash, ':id' => $user['id']]);
                }

                // Success: set session and redirect
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: /EXAMPLE_Orig_petfood/pages/admin/home.php");
                } else {
                    header("Location: /EXAMPLE_Orig_petfood/pages/user/home.php");
                }
                exit;

            // ✅ Step 2: Check if password matches old MD5 hash
            } elseif (md5($password) === $stored) {
                // Automatically upgrade old MD5 hash to new bcrypt
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = :newHash WHERE id = :id");
                $update->execute([':newHash' => $newHash, ':id' => $user['id']]);

                // Set session variables and redirect
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

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
<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #f9f4e6, #f1d1a2);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
form {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    width: 320px;
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
.error {
    color: red;
    font-size: 0.9em;
    margin-bottom: 10px;
}
</style>
</head>
<body>

<!-- ✅ Absolute path ensures correct action -->
<form method="POST" action="/EXAMPLE_Orig_petfood/backend/auth/login.php">
    <h2>Login</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Log In</button>
</form>

</body>
</html>
