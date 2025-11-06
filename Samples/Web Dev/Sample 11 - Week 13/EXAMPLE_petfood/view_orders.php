<?php
session_start();
require 'db.php';
if (!isset($_SESSION['is_admin'])) { header('Location: home_admin.php'); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
$stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->execute([$_POST['status'], (int)$_POST['order_id']]);
}


$orders = $pdo->query('SELECT o.*, i.name AS item_name FROM orders o JOIN items i ON i.id = o.item_id ORDER BY o.created_at DESC')->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Orders</title>
<link rel="stylesheet" href="styles.css"></head><body>
<div class="container">
<h2>Orders</h2>
<p><a href="home_admin.php">Back</a></p>
<?php foreach($orders as $o): ?>
<div class="card">
<p><strong><?= e($o['item_name']) ?></strong> — <?= (int)$o['quantity'] ?> pcs</p>
<p>Customer: <?= e($o['customer_name']) ?> (<?= e($o['customer_contact']) ?>)</p>
<p>Status: <?= e($o['status']) ?> | <small><?= e($o['created_at']) ?></small></p>
<form method="post">
<input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
<select name="status">
<option value="reserved" <?= $o['status']==='reserved'?'selected':'' ?>>Reserved</option>
<option value="fulfilled" <?= $o['status']==='fulfilled'?'selected':'' ?>>Fulfilled</option>
<option value="cancelled" <?= $o['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
</select>
<button type="submit">Update</button>
</form>
</div>
<?php endforeach; ?>
</div>
</body></html>