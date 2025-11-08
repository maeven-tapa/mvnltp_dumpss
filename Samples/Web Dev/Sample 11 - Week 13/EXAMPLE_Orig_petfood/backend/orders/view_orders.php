<?php
session_start();
require_once '../db.php';

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$orders = $pdo->query("
    SELECT o.id, o.order_code, o.customer_name, o.customer_contact, o.status,
           o.quantity, o.created_at, i.name AS item_name, i.price
    FROM orders o
    JOIN items i ON o.item_id = i.id
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders Management — Pet Food Place</title>
<link rel="stylesheet" href="../../assets/css/shared.css">
</head>
<body>

<!-- Floating Paw Prints -->
<div id="paw-container"></div>

<!-- Header -->
<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h1>Orders Management</h1>
  </div>
  <nav class="links">
    <a href="../../pages/admin/home.php" class="btn btn-light-brown">← Back to Dashboard</a>
    <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
  </nav>
</header>

<!-- Main Content -->
<div class="container">
  <h2 style="margin-bottom: 30px; color: var(--text-dark); font-size: 2rem;">All Orders</h2>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Order Code</th>
          <th>Customer Name</th>
          <th>Contact</th>
          <th>Item</th>
          <th>Qty</th>
          <th>Total</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong><?= e($o['order_code']) ?></strong></td>
          <td><?= e($o['customer_name']) ?></td>
          <td><?= e($o['customer_contact']) ?></td>
          <td><?= e($o['item_name']) ?></td>
          <td><?= (int)$o['quantity'] ?></td>
          <td><strong>₱<?= number_format($o['price'] * $o['quantity'], 2) ?></strong></td>
          <td>
            <span class="status <?= strtolower($o['status']) ?>">
              <?= e(ucfirst($o['status'])) ?>
            </span>
          </td>
          <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
          <td>
            <form method="POST" action="update_order.php" style="display: flex; gap: 8px; align-items: center;">
              <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
              <select name="status" required style="padding: 8px 12px; border-radius: 8px; font-size: 0.9rem;">
                <option <?= $o['status']=='reserved'?'selected':'' ?> value="reserved">Reserved</option>
                <option <?= $o['status']=='completed'?'selected':'' ?> value="completed">Completed</option>
                <option <?= $o['status']=='cancelled'?'selected':'' ?> value="cancelled">Cancelled</option>
              </select>
              <button type="submit" class="btn btn-brown" style="padding: 8px 16px; font-size: 0.9rem;">Update</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Footer -->
<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
</body>
</html>