<?php
session_start();
require 'db.php';
if (!isset($_SESSION['is_admin'])) { header('Location: home_admin.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Missing ID');

$item = $pdo->prepare("SELECT * FROM items WHERE id=?");
$item->execute([$id]); $item = $item->fetch();
if (!$item) die('Item not found');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);

  $upd = $pdo->prepare("UPDATE items SET name=?, description=?, price=?, stock=? WHERE id=?");
  $upd->execute([$name, $desc, $price, $stock, $id]);
  header('Location: home_admin.php'); exit;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Edit Item</title><link rel="stylesheet" href="styles.css"></head>
<body>
<div class="container">
  <div class="card" style="max-width:720px;margin:auto">
    <h2>Edit Item — <?= e($item['item_code'] ?? '') ?></h2>
    <?php if($error) echo '<p style="color:#b33a2b">'.e($error).'</p>'; ?>
    <form method="post">
      <label class="meta">Name</label>
      <input type="text" name="name" value="<?= e($item['name']) ?>" required>
      <label class="meta">Description</label>
      <textarea name="description" rows="4"><?= e($item['description']) ?></textarea>
      <label class="meta">Price</label>
      <input type="number" name="price" step="0.01" value="<?= e($item['price']) ?>" required>
      <label class="meta">Stock</label>
      <input type="number" name="stock" value="<?= (int)$item['stock'] ?>" required>
      <div style="display:flex;gap:8px;margin-top:12px">
        <button class="btn" type="submit">Save Changes</button>
        <a class="secondary" href="home_admin.php">Cancel</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
