<?php
session_start();
require_once '../../backend/db.php';

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/auth/login.php");
    exit;
}

if (!empty($_SESSION['password_change_required'])) {
    header("Location: ../../backend/auth/change_password.php?force=1");
    exit;
}

$stmt = $pdo->query("SELECT * FROM items ORDER BY created_at DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Pet Food Place</title>
<link rel="stylesheet" href="../../assets/css/shared.css">
</head>
<body>

<!-- Floating Paw Prints -->
<div id="paw-container"></div>

<!-- Header -->
<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h1>Admin Dashboard</h1>
  </div>
  <nav class="links">
    <a href="../../backend/orders/view_orders.php" class="btn btn-light-brown">View Orders</a>
    <a href="../../backend/auth/change_password.php" class="btn btn-outline">Change Password</a>
    <a href="../../backend/auth/logout.php" class="btn btn-danger">Logout</a>
  </nav>
</header>

<!-- Main Content -->
<div class="container">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="margin: 0; color: var(--text-dark); font-size: 2rem;">Inventory Management</h2>
    <a href="../../backend/items/add_item.php" class="btn btn-brown">+ Add New Item</a>
  </div>

  <?php if (isset($_SESSION['message'])): ?>
    <div class="alert success"><?= htmlspecialchars($_SESSION['message']) ?></div>
    <?php unset($_SESSION['message']); ?>
  <?php endif; ?>

  <div class="grid">
    <?php foreach ($items as $it): ?>
    <div class="card">
      <div class="thumb"><?= e($it['item_code'] ?? 'N/A') ?></div>
      <h3><?= e($it['name']) ?></h3>
      <div class="meta"><?= nl2br(e($it['description'])) ?></div>
      
      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0ebe6;">
        <div>
          <div class="price">₱<?= number_format($it['price'], 2) ?></div>
          <div class="meta" style="margin-top: 4px;">
            Stock: <strong><?= (int)$it['stock'] ?></strong>
          </div>
        </div>
        <div style="display: flex; gap: 8px; flex-direction: column;">
          <a href="../../backend/items/edit_item.php?id=<?= (int)$it['id'] ?>" class="btn btn-light-brown" style="padding: 8px 16px; font-size: 0.9rem;">Edit</a>
          <a href="../../backend/items/delete_item.php?id=<?= (int)$it['id'] ?>" class="btn btn-danger" style="padding: 8px 16px; font-size: 0.9rem;" onclick="return confirm('Delete this item?')">Delete</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Footer -->
<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
</body>
</html>