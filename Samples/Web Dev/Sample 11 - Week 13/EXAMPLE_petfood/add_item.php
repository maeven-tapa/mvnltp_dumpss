<?php
session_start();
require 'db.php';
if (!isset($_SESSION['is_admin'])) { header('Location: home_admin.php'); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$stock = (int)($_POST['stock'] ?? 0);
if ($name === '') $error = 'Name required';
if (empty($error)) {
$ins = $pdo->prepare('INSERT INTO items (name, description, price, stock) VALUES (?,?,?,?)');
$ins->execute([$name, $desc, $price, $stock]);
header('Location: home_admin.php'); exit;
}
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Add Item</title></head><body>
<div class="container">
<h2>Add Item</h2>
<?php if (!empty($error)) echo '<p style="color:red">'.e($error).'</p>'; ?>
<form method="post">
<div class="form-field"><input name="name" placeholder="Item name" required></div>
<div class="form-field"><textarea name="description" placeholder="Description"></textarea></div>
<div class="form-field"><input name="price" type="number" step="0.01" placeholder="Price"></div>
<div class="form-field"><input name="stock" type="number" value="0" placeholder="Stock"></div>
<div class="form-field"><button type="submit">Add</button></div>
</form>
<p><a href="home_admin.php">Back</a></p>
</div>
</body></html>