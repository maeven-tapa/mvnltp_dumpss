<?php
session_start();
require 'db.php';

$alert = '';
$item = null;
$id = (int)($_GET['id'] ?? 0);
if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
  $stmt->execute([$id]);
  $item = $stmt->fetch();
}

if (!$item) {
  header('Location: home_user.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['customer_name'] ?? '');
  $contact = trim($_POST['customer_contact'] ?? '');
  $qty = max(1, (int)($_POST['quantity'] ?? 1));

  // check stock
  $stmt = $pdo->prepare("SELECT stock, name FROM items WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();

  if (!$row) { $alert = "Item not found."; }
  elseif ($row['stock'] < $qty) { $alert = "Not enough stock."; }
  elseif ($name === '' || $contact === '') { $alert = "Please fill required fields."; }
  else {
    // create order with order_code and decrease stock (transaction)
    try {
      $pdo->beginTransaction();

      $order_code = generateOrderCode($pdo);
      $ins = $pdo->prepare("INSERT INTO orders (order_code, item_id, customer_name, customer_contact, quantity) VALUES (?, ?, ?, ?, ?)");
      $ins->execute([$order_code, $id, $name, $contact, $qty]);

      $upd = $pdo->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
      $upd->execute([$qty, $id]);

      $pdo->commit();
      $alert = "Order placed! Your Order ID: $order_code";
    } catch (Exception $e) {
      $pdo->rollBack();
      $alert = "Could not place order: " . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Order — <?= e($item['name']) ?></title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">PF</div>
      <h1>Order</h1>
    </div>
    <div class="links">
      <a href="home_user.php">Back to shop</a>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:18px;">
    <div class="card">
      <div class="thumb"><?= e($item['item_code'] ?? '') ?></div>
      <h2><?= e($item['name']) ?></h2>
      <div class="meta"><?= nl2br(e($item['description'])) ?></div>
      <p class="price">₱<?= number_format($item['price'],2) ?></p>
      <p class="meta">Available: <?= (int)$item['stock'] ?></p>
    </div>

    <div class="card">
      <h3>Reserve this item</h3>
      <form method="post">
        <label class="meta">Your name</label>
        <input type="text" name="customer_name" required>

        <label class="meta">Contact (phone or email)</label>
        <input type="text" name="customer_contact" required>

        <label class="meta">Quantity</label>
        <input type="number" name="quantity" min="1" max="<?= (int)$item['stock'] ?>" value="1" required>

        <button class="btn" type="submit">Confirm reservation</button>
      </form>
    </div>
  </div>
</div>

<?php if($alert): ?>
<script>
alert(<?= json_encode($alert) ?>);
window.location.href = "home_user.php";
</script>
<?php endif; ?>
</body>
</html>
