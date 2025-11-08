<?php
session_start();
include_once '../db.php'; // connect to DB

// If already logged in, send to correct home page
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: /EXAMPLE_Orig_petfood/pages/admin/home.php");
        exit;
    } else {
        header("Location: /EXAMPLE_Orig_petfood/pages/user/home.php");
        exit;
    }
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Find user in DB
    $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // Login success
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
            $error = "Incorrect password!";
        }
    } else {
        $error = "User not found!";
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

<form method="POST" action="">
    <h2>Login</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Log In</button>
</form>

</body>
</html>
