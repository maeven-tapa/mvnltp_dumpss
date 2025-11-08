<!-- ADD ITEM PAGE (backend/items/add_item.php) -->
<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $item_code = $_POST['item_code'];

    $stmt = $pdo->prepare("INSERT INTO items (item_code, name, description, price, stock, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$item_code, $name, $desc, $price, $stock]);

    $_SESSION['message'] = "Item added successfully!";
    header("Location: ../../pages/admin/home.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Item — Pet Food Place</title>
<link rel="stylesheet" href="../../assets/css/shared.css">
</head>
<body>

<div id="paw-container"></div>

<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h1>Add New Item</h1>
  </div>
  <nav class="links">
    <a href="../../pages/admin/home.php" class="btn btn-light-brown">← Back to Dashboard</a>
  </nav>
</header>

<div class="container">
  <div class="content-wrapper">
    <h2 style="margin-bottom: 30px; color: var(--text-dark); font-size: 2rem;">Add New Product</h2>
    
    <form method="POST">
      <div>
        <label>Item Code</label>
        <input type="text" name="item_code" placeholder="e.g., DOG-001" required>
      </div>
      
      <div>
        <label>Item Name</label>
        <input type="text" name="name" placeholder="Enter product name" required>
      </div>
      
      <div>
        <label>Description</label>
        <textarea name="description" placeholder="Describe the product..." required></textarea>
      </div>
      
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
        <div>
          <label>Price (₱)</label>
          <input type="number" step="0.01" name="price" placeholder="0.00" required>
        </div>
        
        <div>
          <label>Stock Quantity</label>
          <input type="number" name="stock" placeholder="0" required>
        </div>
      </div>
      
      <button type="submit" class="btn btn-brown w-full">Add Item</button>
    </form>
  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
</body>
</html>

<!-- ============================================== -->
<!-- EDIT ITEM PAGE (backend/items/edit_item.php) -->
<!-- ============================================== -->
<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Item not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $stmt = $pdo->prepare("UPDATE items SET name=?, description=?, price=?, stock=? WHERE id=?");
    $stmt->execute([$name, $desc, $price, $stock, $id]);

    $_SESSION['message'] = "Item updated successfully!";
    header("Location: ../../pages/admin/home.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <h2 style="margin-bottom: 30px; color: var(--text-dark); font-size: 2rem;">Edit Product Details</h2>
    
    <form method="POST">
      <div>
        <label>Item Code</label>
        <input type="text" value="<?= htmlspecialchars($item['item_code']) ?>" disabled style="background: #f5f5f5;">
        <small style="color: var(--text-soft); display: block; margin-top: 4px;">Item code cannot be changed</small>
      </div>
      
      <div>
        <label>Item Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
      </div>
      
      <div>
        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($item['description']) ?></textarea>
      </div>
      
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
        <div>
          <label>Price (₱)</label>
          <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($item['price']) ?>" required>
        </div>
        
        <div>
          <label>Stock Quantity</label>
          <input type="number" name="stock" value="<?= htmlspecialchars($item['stock']) ?>" required>
        </div>
      </div>
      
      <button type="submit" class="btn btn-brown w-full">Update Item</button>
    </form>
  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
</body>
</html>