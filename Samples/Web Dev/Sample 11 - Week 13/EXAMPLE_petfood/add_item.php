<?php
session_start();
require 'db.php';

// Restrict access to admin only
if (!isset($_SESSION['is_admin'])) {
    header('Location: home_admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate inputs
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if ($name === '') {
        $error = 'Name is required.';
    } else {
        // Generate unique code
        $item_code = generateItemCode($pdo);

        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO items (item_code, name, description, price, stock)
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$item_code, $name, $desc, $price, $stock]);

        // Redirect back to admin home after successful insert
        header('Location: home_admin.php');
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Add Item</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 500px; margin: auto; }
        .form-field { margin-bottom: 10px; }
        input, textarea, button { width: 100%; padding: 8px; }
        button { background: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h2>Add Pet Food Item</h2>
    <?php if (!empty($error)) echo '<p style="color:red">'.e($error).'</p>'; ?>
    <form method="post">
        <div class="form-field"><input name="name" placeholder="Item name" required></div>
        <div class="form-field"><textarea name="description" placeholder="Description"></textarea></div>
        <div class="form-field"><input name="price" type="number" step="0.01" placeholder="Price" required></div>
        <div class="form-field"><input name="stock" type="number" value="0" placeholder="Stock" required></div>
        <div class="form-field"><button type="submit">Add Item</button></div>
    </form>
    <p><a href="home_admin.php">Back</a></p>
</div>
</body>
</html>
