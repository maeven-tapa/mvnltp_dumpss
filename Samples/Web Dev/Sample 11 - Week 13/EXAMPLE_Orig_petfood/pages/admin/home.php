<?php
session_start();
require_once '../../backend/db.php';

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/auth/login.php");
    exit;
}

// 2️Enforce password change if required
if (!empty($_SESSION['password_change_required'])) {
    header("Location: ../../backend/auth/change_password.php?force=1");
    exit;
}

// Fetch all items
$stmt = $pdo->query("SELECT * FROM items ORDER BY created_at DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin — Inventory</title>
<link rel="stylesheet" href="../../assets/css/styles1.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">PF</div>
      <h1>Admin Dashboard</h1>
    </div>
    <div>
      <a href="../../backend/orders/view_orders.php" class="btn btn-light-brown">View Orders</a>
      <a href="../../backend/auth/logout.php" class="btn btn-danger">Logout</a>
    </div>
  </div>

  <h2>Inventory</h2>
  <a href="../../backend/items/add_item.php" class="btn btn-brown" style="margin-bottom:15px;display:inline-block;">+ Add Item</a>

  <div class="grid">
    <?php foreach ($items as $it): ?>
    <div class="card">
      <div class="thumb"><?= e($it['item_code'] ?? '') ?></div>
      <h3><?= e($it['name']) ?></h3>
      <div class="meta"><?= nl2br(e($it['description'])) ?></div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
        <div>
          <div class="price">₱<?= number_format($it['price'], 2) ?></div>
          <div class="meta">Stock: <?= (int)$it['stock'] ?></div>
        </div>
        <div style="display:flex;gap:8px;">
          <a href="../../backend/items/edit_item.php?id=<?= (int)$it['id'] ?>" class="btn btn-light-brown">Edit</a>
          <a href="../../backend/items/delete_item.php?id=<?= (int)$it['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
