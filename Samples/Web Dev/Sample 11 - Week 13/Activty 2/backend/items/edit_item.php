<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}


// Validate item_code (expecting string like PF-0001)
if (!isset($_GET['item_code']) || strlen(trim($_GET['item_code'])) === 0) {
  header("Location: ../../pages/admin/home.php?error=invalid_id");
  exit;
}

$item_code = trim($_GET['item_code']);

// Fetch existing item by item_code
$stmt = $pdo->prepare("SELECT * FROM items WHERE item_code = ?");
$stmt->execute([$item_code]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: ../../pages/admin/home.php?error=notfound");
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['name']);
    $desc  = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];

    $update = $pdo->prepare("UPDATE items
      SET name = ?, description = ?, price = ?, stock = ?
      WHERE item_code = ?");

    $update->execute([$name, $desc, $price, $stock, $item_code]);

    $_SESSION['message'] = "Item updated successfully!";
    header("Location: ../../pages/admin/home.php");
    exit;
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Item — Pet Food Place</title>
<link rel="stylesheet" href="../../assets/css/shared.css">
</head>
<body>

<div id="paw-container"></div>

<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h1>Edit Item</h1>
  </div>
  <nav class="links">
    <a href="../../pages/admin/home.php" class="btn btn-light-brown">← Back to Dashboard</a>
  </nav>
</header>

<div class="container">
  <div class="content-wrapper">
    <h2>Edit Product Details</h2>

    <form method="POST">

      <div>
        <label>Item Code</label>
        <input type="text" value="<?= htmlspecialchars($item['item_code']) ?>" disabled style="background: #eee;">
        <small style="color:#777;">Item code cannot be changed</small>
      </div>

      <div>
        <label>Item Name</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($item['name']) ?>">
      </div>

      <div>
        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($item['description']) ?></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
        <div>
          <label>Price (₱)</label>
          <input type="number" step="0.01" name="price" required value="<?= htmlspecialchars($item['price']) ?>">
        </div>

        <div>
          <label>Stock</label>
          <input type="number" name="stock" required value="<?= htmlspecialchars($item['stock']) ?>">
        </div>
      </div>

      <button class="btn btn-brown w-full" type="submit">Update Item</button>
    </form>
  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> Pet Food Place.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
</body>
</html>
