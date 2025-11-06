<?php
session_start();
require 'db.php';

// Only admins can view
if (!isset($_SESSION['is_admin'])) {
    header('Location: home_admin.php');
    exit;
}

$stmt = $pdo->query("
    SELECT 
        o.id, o.order_code, o.customer_name, o.customer_contact,
        o.quantity, o.status, o.created_at,
        i.name AS item_name, i.price AS item_price
    FROM orders o
    LEFT JOIN items i ON o.item_id = i.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>View Orders</title>
<link rel="stylesheet" href="styles.css">
<style>
body { font-family: Arial, sans-serif; background: #f9fafb; margin: 0; }
.container { max-width: 1000px; margin: 40px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
h1 { text-align: center; margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #007bff; color: white; }
tr:nth-child(even) { background: #f2f2f2; }
.status.reserved { color: orange; font-weight: bold; }
.status.completed { color: green; font-weight: bold; }
.status.cancelled { color: red; font-weight: bold; }
select { padding: 5px; }
button { padding: 6px 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #0056b3; }
</style>
</head>
<body>
<div class="container">
    <a href="home_admin.php" class="back">← Back to Admin Panel</a>
    <h1>Customer Orders</h1>

    <?php if (empty($orders)): ?>
        <p>No orders found.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Order Code</th>
                <th>Pet Food</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= e($o['order_code']) ?></td>
                <td><?= e($o['item_name'] ?? '[Deleted Item]') ?></td>
                <td><?= e($o['customer_name']) ?></td>
                <td><?= e($o['customer_contact']) ?></td>
                <td><?= (int)$o['quantity'] ?></td>
                <td>₱<?= number_format(($o['item_price'] ?? 0) * $o['quantity'], 2) ?></td>
                <td class="status <?= e(strtolower($o['status'])) ?>"><?= e(ucfirst($o['status'])) ?></td>
                <td><?= e($o['created_at']) ?></td>
                <td>
                    <form method="post" action="update_order.php" style="display:flex; gap:4px;">
                        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                        <select name="status">
                            <option value="reserved" <?= $o['status']=='reserved'?'selected':'' ?>>Reserved</option>
                            <option value="completed" <?= $o['status']=='completed'?'selected':'' ?>>Completed</option>
                            <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
