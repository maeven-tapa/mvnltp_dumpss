<?php
session_start();
require_once __DIR__ . '/../backend/db.php';

function e($str) { return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function generateOrderCode($pdo) {
    $stmt = $pdo->query("SELECT order_code FROM orders ORDER BY id ASC");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $num = 1;
    while (in_array(sprintf("ORD-%04d", $num), $existing)) {
        $num++;
    }
    return sprintf("ORD-%04d", $num);
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) { header('Location: ../pages/user/home.php'); exit; }

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['customer_contact'] ?? '');
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    if ($name && $contact && $qty > 0 && $item['stock'] >= $qty) {
        try {
            $pdo->beginTransaction();

            $order_code = generateOrderCode($pdo);
            $ins = $pdo->prepare("INSERT INTO orders (order_code, item_id, customer_name, customer_contact, quantity) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$order_code, $id, $name, $contact, $qty]);

            $pdo->prepare("UPDATE items SET stock = stock - ? WHERE id = ?")->execute([$qty, $id]);

            $pdo->commit();
            $alert = "Order placed successfully! Your Order ID: $order_code";
        } catch (Exception $e) {
            $pdo->rollBack();
            $alert = "Order failed: " . $e->getMessage();
        }
    } else {
        $alert = "Invalid details or not enough stock.";
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Order — <?= e($item['name']) ?></title>
<link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">PF</div>
      <h1>Order</h1>
    </div>
    <div class="links"><a href="../pages/user/home.php">Back to shop</a></div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:18px;">
    <div class="card">
      <h2><?= e($item['name']) ?></h2>
      <p><?= nl2br(e($item['description'])) ?></p>
      <p>₱<?= number_format($item['price'],2) ?></p>
      <p>Stock: <?= (int)$item['stock'] ?></p>
    </div>
    <div class="card">
      <h3>Reserve this item</h3>
      <form method="post">
        <label>Your name</label>
        <input type="text" name="customer_name" required>
        <label>Contact</label>
        <input type="text" name="customer_contact" required>
        <label>Quantity</label>
        <input type="number" name="quantity" min="1" max="<?= (int)$item['stock'] ?>" required>
        <button class="btn btn-brown" type="submit">Confirm Order</button>
      </form>
    </div>
  </div>
</div>

<?php if($alert): ?>
<script>alert(<?= json_encode($alert) ?>);window.location.href="../pages/user/home.php";</script>
<?php endif; ?>
</body>
</html>
