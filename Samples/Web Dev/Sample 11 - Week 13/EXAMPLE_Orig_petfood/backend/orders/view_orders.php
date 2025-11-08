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
           o.quantity, o.created_at, i.name AS item_name
    FROM orders o
    JOIN items i ON o.item_id = i.id
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin — View Orders</title>
<link rel="stylesheet" href="../../assets/css/styles1.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">PF</div>
      <h1>Orders Management</h1>
    </div>
    <div class="admin-bar">
      <a href="../../pages/admin/home.php" class="btn btn-light-brown">Back</a>
      <a href="../auth/login.php?logout=1" class="btn btn-danger">Logout</a>
    </div>
  </div>

  <h2>All Orders</h2>
  <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse;">
    <tr>
      <th>Order Code</th>
      <th>Customer</th>
      <th>Contact</th>
      <th>Item</th>
      <th>Qty</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
    <?php foreach ($orders as $o): ?>
    <tr>
      <td><?= e($o['order_code']) ?></td>
      <td><?= e($o['customer_name']) ?></td>
      <td><?= e($o['customer_contact']) ?></td>
      <td><?= e($o['item_name']) ?></td>
      <td><?= (int)$o['quantity'] ?></td>
      <td><?= e(ucfirst($o['status'])) ?></td>
      <td>
        <form method="POST" action="update_order.php" style="display:inline;">
          <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
          <select name="status" required>
            <option <?= $o['status']=='reserved'?'selected':'' ?> value="reserved">Reserved</option>
            <option <?= $o['status']=='completed'?'selected':'' ?> value="completed">Completed</option>
            <option <?= $o['status']=='cancelled'?'selected':'' ?> value="cancelled">Cancelled</option>
          </select>
          <button type="submit" class="btn btn-brown">Update</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
