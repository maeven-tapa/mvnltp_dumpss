<?php
session_start();
require 'db.php';

$ADMIN_PASSWORD = 'admin123'; // change in production
$error = '';

if (isset($_GET['logout'])) {
  unset($_SESSION['is_admin']);
  header('Location: home_admin.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pass'])) {
  if ($_POST['admin_pass'] === $ADMIN_PASSWORD) {
    $_SESSION['is_admin'] = true;
    header('Location: home_admin.php'); exit;
  } else {
    $error = 'Invalid password';
  }
}

if (empty($_SESSION['is_admin'])) {
  // Login form
  ?>
  <!doctype html>
  <html>
  <head><meta charset="utf-8"><title>Admin Login</title><link rel="stylesheet" href="styles.css"></head>
  <body>
  <div class="container">
    <div class="card" style="max-width:420px;margin:auto">
      <h2>Admin Login</h2>
      <?php if($error) echo '<p style="color:#b33a2b">'.e($error).'</p>'; ?>
      <form method="post">
        <input type="password" name="admin_pass" placeholder="Password" required>
        <button class="btn" type="submit">Log in</button>
      </form>
      <p style="margin-top:12px"><a href="home_user.php" class="btn btn-light-brown">Back to shop</a></p>
    </div>
  </div>
  </body>
  </html>
  <?php
  exit;
}

// admin content
$items = $pdo->query("SELECT * FROM items ORDER BY created_at DESC")->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><title>Admin — Items</title><link rel="stylesheet" href="styles.css"></head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">PF</div>
      <h1>Admin Panel</h1>
    </div>
	<div class="admin-bar">
		<a href="add_item.php" class="btn btn-light-brown">Add Item</a>
		<a href="view_orders.php" class="btn btn-brown">View Orders</a>
		<a href="home_admin.php?logout=1" class="btn btn-danger">Logout</a>
	</div>
  </div>

  <h2>Inventory</h2>
  <div class="grid">
    <?php foreach($items as $it): ?>
      <div class="card">
        <div class="thumb"><?= e($it['item_code'] ?? '') ?></div>
        <h3><?= e($it['name']) ?></h3>
        <div class="meta"><?= nl2br(e($it['description'])) ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
          <div>
            <div class="price">₱<?= number_format($it['price'],2) ?></div>
            <div class="meta">Stock: <?= (int)$it['stock'] ?></div>
          </div>
          <div style="display:flex;gap:8px">
				<a href="edit_item.php?id=<?= (int)$it['id'] ?>" class="btn btn-light-brown">Edit</a>
				<a href="delete_item.php?id=<?= (int)$it['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
