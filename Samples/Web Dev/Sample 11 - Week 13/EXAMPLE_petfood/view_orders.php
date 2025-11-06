<?php
session_start();
require 'db.php';
if (!isset($_SESSION['is_admin'])) { header('Location: home_admin.php'); exit; }

$orders = $pdo->query("
  SELECT o.*, i.name AS item_name, i.price AS item_price
  FROM orders o LEFT JOIN items i ON o.item_id = i.id
  ORDER BY o.created_at DESC
")->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Orders</title><link rel="stylesheet" href="styles.css"></head>
<body>
<div class="container">
  <div class="header">
    <div class="brand"><div class="logo">PF</div><h1>Orders</h1></div>
    <div class="links">
      <a href="home_admin.php" class="btn btn-light-brown">Back</a>
    </div>
  </div>

  <div class="card">
    <?php if(empty($orders)): ?>
      <p>No orders yet.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr><th>Order</th><th>Item</th><th>Customer</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach($orders as $o): ?>
          <tr>
            <td><?= e($o['order_code']) ?></td>
            <td><?= e($o['item_name'] ?? '[deleted]') ?></td>
            <td><?= e($o['customer_name']) ?><br><small><?= e($o['customer_contact']) ?></small></td>
            <td><?= (int)$o['quantity'] ?></td>
            <td>₱<?= number_format(($o['item_price'] ?? 0) * $o['quantity'],2) ?></td>
            <td><span class="status <?= e(strtolower($o['status'])) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
            <td><?= e($o['created_at']) ?></td>
            <td>
              <form method="post" action="update_order.php" style="display:flex;gap:6px">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <select name="status">
                  <option value="reserved" <?= $o['status']=='reserved'?'selected':'' ?>>Reserved</option>
                  <option value="completed" <?= $o['status']=='completed'?'selected':'' ?>>Completed</option>
                  <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
                <button class="btn" type="submit">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
