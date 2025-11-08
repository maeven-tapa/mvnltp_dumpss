<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Get item ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../pages/admin/home.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch existing item details
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: ../../pages/admin/home.php?error=notfound");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    // Update only allowed fields (not item_code)
    $stmt = $pdo->prepare("UPDATE items 
                           SET name = ?, description = ?, price = ?, stock = ?, updated_at = NOW() 
                           WHERE id = ?");
    $stmt->execute([$name, $desc, $price, $stock, $id]);

    header("Location: ../../pages/admin/home.php?updated=1");
    exit;
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Item</title>
<link rel="stylesheet" href="../../assets/css/styles1.css">
</head>
<body>
<div class="container">
  <h2>Edit Item</h2>

  <form method="POST">
    <!-- Show item_code as read-only so admin cannot modify it -->
    <label>Item Code</label>
    <input type="text" value="<?= htmlspecialchars($item['item_code']) ?>" readonly>

    <label>Item Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>

    <label>Description</label>
    <textarea name="description" required><?= htmlspecialchars($item['description']) ?></textarea>

    <label>Price</label>
    <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($item['price']) ?>" required>

    <label>Stock</label>
    <input type="number" name="stock" value="<?= htmlspecialchars($item['stock']) ?>" required>

    <button type="submit" class="btn btn-brown">Update</button>
  </form>

  <p><a href="../../pages/admin/home.php" class="btn btn-light-brown">Back</a></p>
</div>
</body>
</html>
