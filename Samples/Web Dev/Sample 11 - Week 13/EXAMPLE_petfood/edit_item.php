<?php
session_start();
require 'db.php';
if (!isset($_SESSION['is_admin'])) { header('Location: home_admin.php'); exit; }


$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Missing ID');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$stock = (int)($_POST['stock'] ?? 0);
$upd = $pdo->prepare('UPDATE items SET name=?, description=?, price=?, stock=? WHERE id=?');
$upd->execute([$name, $desc, $price, $stock, $id]);
header('Location: home_admin.php'); exit;
}


$item = $pdo->prepare('SELECT * FROM items WHERE id=?');
$item->execute([$id]);
$item = $item->fetch();
if (!$item) die('Item not found');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Edit Item</title>
<link rel="stylesheet" href="styles.css"></head><body>
<div class="container">
<h2>Edit Item</h2>
<form method="post">
<div class="form-field"><input name="name" value="<?= e($item['name']) ?>" required></div>
<div class="form-field"><textarea name="description"><?= e($item['description']) ?></textarea></div>
<div class="form-field"><input name="price" type="number" step="0.01" value="<?= e($item['price']) ?>"></div>
<div class="form-field"><input name="stock" type="number" value="<?= (int)$item['stock'] ?>"></div>
<div class="form-field"><button type="submit">Save</button></div>
</form>
<p><a href="home_admin.php">Back</a></p>
</div>
</body></html>