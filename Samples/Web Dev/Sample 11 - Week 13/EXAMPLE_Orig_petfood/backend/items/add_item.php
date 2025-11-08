<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $item_code = $_POST['item_code'];

    $stmt = $pdo->prepare("INSERT INTO items (item_code, name, description, price, stock, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$item_code, $name, $desc, $price, $stock]);

    header("Location: ../../pages/admin/home.php");
    exit;
}
?>

<!doctype html>
<html>
<head><meta charset="utf-8"><title>Add Item</title><link rel="stylesheet" href="../../assets/css/styles1.css"></head>
<body>
<div class="container">
<h2>Add New Item</h2>
<form method="POST">
  <input type="text" name="item_code" placeholder="Item Code" required>
  <input type="text" name="name" placeholder="Item Name" required>
  <textarea name="description" placeholder="Description" required></textarea>
  <input type="number" step="0.01" name="price" placeholder="Price" required>
  <input type="number" name="stock" placeholder="Stock" required>
  <button type="submit" class="btn btn-brown">Save</button>
</form>
<p><a href="../../pages/admin/home.php" class="btn btn-light-brown">Back</a></p>
</div>
</body>
</html>
