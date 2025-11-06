<?php
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin login</title></head><body>
<div class="container">
<h2>Admin Login</h2>
<?php if (!empty($error)) echo '<p style="color:red">'.e($error).'</p>'; ?>
<form method="post">
<input type="password" name="admin_pass" placeholder="Admin password">
<button type="submit">Log in</button>
</form>
<p><a href="home_user.php">Back to user</a></p>
</div>
</body></html>
<?php
exit;
}


// Admin view
$items = $pdo->query('SELECT * FROM items ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin — Manage Items</title>
<link rel="stylesheet" href="styles.css"></head>
<body>
<div class="container">
<div class="admin-bar">
<a href="add_item.php">Add Item</a> |
<a href="view_orders.php">View Orders</a> |
<a href="home_admin.php?logout=1">Logout</a>
</div>


<h1>Items</h1>
<?php foreach($items as $it): ?>
<div class="card">
<div class="item-row">
<div>
<h3><?= e($it['name']) ?> <small class="meta">₱<?= number_format($it['price'],2) ?></small></h3>
<p><?= nl2br(e($it['description'])) ?></p>
<p><small>Stock: <?= (int)$it['stock'] ?></small></p>
</div>
<div style="min-width:160px; text-align:right">
<a href="edit_item.php?id=<?= (int)$it['id'] ?>">Edit</a> |
<a href="delete_item.php?id=<?= (int)$it['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
</div>
</div>
</div>
<?php endforeach; ?>


</div>
</body>
</html>