<?php
session_start();
require_once '../../backend/db.php';

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

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
$alert = '';

$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: home.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['customer_contact'] ?? '');
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    $stmt = $pdo->prepare("SELECT stock FROM items WHERE id = ?");
    $stmt->execute([$id]);
    $stock = $stmt->fetchColumn();

    if ($stock < $qty) {
        $alert = "Not enough stock.";
    } elseif ($name === '' || $contact === '') {
        $alert = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();
            $order_code = generateOrderCode($pdo);
            $ins = $pdo->prepare("INSERT INTO orders (order_code, item_id, customer_name, customer_contact, quantity, status, created_at)
                                  VALUES (?, ?, ?, ?, ?, 'reserved', NOW())");
            $ins->execute([$order_code, $id, $name, $contact, $qty]);

            $upd = $pdo->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
            $upd->execute([$qty, $id]);

            $pdo->commit();
            $alert = "Order placed successfully! Your Order ID: $order_code";
        } catch (Exception $e) {
            $pdo->rollBack();
            $alert = "Failed to place order: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Order — <?= e($item['name']) ?></title>
<link rel="stylesheet" href="../../assets/css/styles1.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">PF</div>
      <h1>Order Page</h1>
    </div>
    <div class="links">
      <a href="home.php" class="btn btn-light-brown">Back</a>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:18px;">
    <div class="card">
      <div class="thumb"><?= e($item['item_code']) ?></div>
      <h2><?= e($item['name']) ?></h2>
      <div class="meta"><?= nl2br(e($item['description'])) ?></div>
      <p class="price">₱<?= number_format($item['price'],2) ?></p>
      <p class="meta">Available: <?= (int)$item['stock'] ?></p>
    </div>

    <div class="card">
      <h3>Reserve this item</h3>
      <form method="POST">
        <label>Your name</label>
        <input type="text" name="customer_name" required>
        <label>Contact (email or phone)</label>
        <input type="text" name="customer_contact" required>
        <label>Quantity</label>
        <input type="number" name="quantity" min="1" max="<?= (int)$item['stock'] ?>" value="1" required>
        <button class="btn btn-brown" type="submit">Confirm</button>
      </form>
    </div>
  </div>
</div>

<?php if($alert): ?>
<script>
alert(<?= json_encode($alert) ?>);
window.location.href = "home.php";
</script>
<?php endif; ?>
</body>
</html>
