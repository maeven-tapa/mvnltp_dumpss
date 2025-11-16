<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Function to generate the next available PF-XXXX code
function generateItemCode($pdo) {
    // Fetch all existing codes in ascending order
    $stmt = $pdo->query("SELECT item_code FROM items ORDER BY item_code ASC");
    $existingCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $num = 1;
    // Loop through all existing codes and find the first missing number
    foreach ($existingCodes as $code) {
        // Extract numeric part, e.g., from "PF-0005" → 5
        $currentNum = intval(substr($code, 3));
        if ($currentNum !== $num) {
            break; // Found a gap
        }
        $num++;
    }

    // Format new code, e.g., PF-0007
    return sprintf("PF-%04d", $num);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    // Auto-generate item_code
    $item_code = generateItemCode($pdo);

    $stmt = $pdo->prepare("INSERT INTO items (item_code, name, description, price, stock, created_at)
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$item_code, $name, $desc, $price, $stock]);

    header("Location: ../../pages/admin/home.php?added=1");
exit;
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Add Item</title>
<link rel="stylesheet" href="../../assets/css/styles1.css">
</head>
<body>
<div class="container">
  <h2>Add New Item</h2>

  <form method="POST">
    <input type="text" value="<?= htmlspecialchars(generateItemCode($pdo)) ?>" readonly>
    <input type="text" name="name" placeholder="Item Name" required>
    <textarea name="description" placeholder="Description" required></textarea>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <input type="number" name="stock" placeholder="Stock" required>
    <button type="submit" class="btn btn-brown">Save</button>
  </form>

  <p><a href="../../pages/admin/home.php" class="btn btn-light-brown">Back</a></p>
</div>
</body>
</html>
