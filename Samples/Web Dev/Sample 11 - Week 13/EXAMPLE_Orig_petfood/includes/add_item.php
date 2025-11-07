<?php
session_start();
require 'db.php';
if (!isset($_SESSION['is_admin'])) { header('Location: home_admin.php'); exit; }

function generateItemCode($pdo) {
    // Get all existing item codes
    $stmt = $pdo->query("SELECT item_code FROM items ORDER BY id ASC");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Find first available number slot
    $num = 1;
    while (in_array(sprintf("PFPA-%04d", $num), $existing)) {
        $num++;
    }
    
    return sprintf("PFPA-%04d", $num);
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);

  if ($name === '') $error = 'Name required';
  if (!$error) {
    $item_code = generateItemCode($pdo);
    $ins = $pdo->prepare("INSERT INTO items (item_code, name, description, price, stock) VALUES (?,?,?,?,?)");
    $ins->execute([$item_code, $name, $desc, $price, $stock]);
    header('Location: home_admin.php'); exit;
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Add Item</title><link rel="stylesheet" href="styles.css"></head>
<body>
<div class="container">
  <div class="card" style="max-width:720px;margin:auto">
    <h2>Add New Item</h2>
    <?php if($error) echo '<p style="color:#b33a2b">'.e($error).'</p>'; ?>
    <form method="post">
      <label class="meta">Name</label>
      <input type="text" name="name" required>
      <label class="meta">Description</label>
      <textarea name="description" rows="4"></textarea>
      <label class="meta">Price</label>
      <input type="number" name="price" step="0.01" required>
      <label class="meta">Stock</label>
      <input type="number" name="stock" value="0" required>
      <div style="display:flex;gap:8px;margin-top:12px">
        <button class="btn" type="submit">Add Item</button>
        <a class="btn btn-light-brown" href="home_admin.php">Cancel</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
