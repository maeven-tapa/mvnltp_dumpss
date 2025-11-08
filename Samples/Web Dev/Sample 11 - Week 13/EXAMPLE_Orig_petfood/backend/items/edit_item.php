<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Item not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $stmt = $pdo->prepare("UPDATE items SET name=?, description=?, price=?, stock=? WHERE id=?");
    $stmt->execute([$name, $desc, $price, $stock, $id]);

    header("Location: ../../pages/admin/home.php");
    exit;
}
?>

<!doctype html>
<html>
<head><meta charset="utf-8"><title>Edit Item</title><link rel="stylesheet" href="../../assets/css/styles1.css"></head>
<body>
<div class="container">
<h2>Edit Item</h2>
<form method="POST">
  <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
  <textarea name="description" required><?= htmlspecialchars($item['description']) ?></textarea>
  <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($item['price']) ?>" required>
  <input type="number" name="stock" value="<?= htmlspecialchars($item['stock']) ?>" required>
  <button type="submit" class="btn btn-brown">Update</button>
</form>
<p><a href="../../pages/admin/home.php" class="btn btn-light-brown">Back</a></p>
</div>
</body>
</html>
