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
        $alert = "Not enough stock available.";
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
            $alert = "success|Order placed successfully! Your Order ID: $order_code";
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order — <?= e($item['name']) ?></title>
<link rel="stylesheet" href="../../assets/css/shared.css">
<style>
.order-layout {
  display: grid;
  grid-template-columns: 1fr 450px;
  gap: 30px;
  margin-top: 30px;
}

@media (max-width: 992px) {
  .order-layout {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>

<!-- Floating Paw Prints -->
<div id="paw-container"></div>

<!-- Header -->
<header class="header">
  <div class="brand">
    <div class="logo">PF</div>
    <h1>Place Order</h1>
  </div>
  <nav class="links">
    <a href="home.php" class="btn btn-light-brown">← Back to Products</a>
  </nav>
</header>

<!-- Main Content -->
<div class="container">
  <?php if ($alert): ?>
    <?php if (strpos($alert, 'success|') === 0): ?>
      <div class="alert success"><?= htmlspecialchars(substr($alert, 8)) ?></div>
    <?php else: ?>
      <div class="alert error"><?= htmlspecialchars($alert) ?></div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="order-layout">
    <!-- Product Details -->
    <div class="card">
      <div class="thumb" style="height: 250px; font-size: 24px;"><?= e($item['item_code']) ?></div>
      <h2 style="margin: 20px 0 12px 0; font-size: 2rem; color: var(--text-dark);"><?= e($item['name']) ?></h2>
      <div class="meta" style="font-size: 1.05rem; line-height: 1.7; margin-bottom: 20px;">
        <?= nl2br(e($item['description'])) ?>
      </div>
      
      <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 2px solid #f0ebe6;">
        <div>
          <div class="price" style="font-size: 2rem;">₱<?= number_format($item['price'],2) ?></div>
          <div class="meta" style="margin-top: 8px; font-size: 1rem;">
            Available Stock: <strong style="color: <?= $item['stock'] > 10 ? '#137d3b' : '#b45a00' ?>">
              <?= (int)$item['stock'] ?> units
            </strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Form -->
    <div class="card" style="position: sticky; top: 100px;">
      <h3 style="margin-bottom: 20px; font-size: 1.5rem; color: var(--text-dark);">Complete Your Order</h3>
      <form method="POST">
        <div>
          <label>Your Full Name</label>
          <input type="text" name="customer_name" required placeholder="Enter your name">
        </div>
        
        <div>
          <label>Contact Information</label>
          <input type="text" name="customer_contact" required placeholder="Email or phone number">
        </div>
        
        <div>
          <label>Quantity</label>
          <input type="number" name="quantity" min="1" max="<?= (int)$item['stock'] ?>" value="1" required>
        </div>
        
        <div style="background: linear-gradient(135deg, #f9f3ed, #ffffff); padding: 16px; border-radius: 12px; margin: 20px 0;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span style="color: var(--text-soft);">Price per unit:</span>
            <span style="font-weight: 600;">₱<?= number_format($item['price'],2) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid #e2d8d1;">
            <span style="font-weight: 700; color: var(--text-dark);">Total:</span>
            <span style="font-weight: 700; color: var(--brown); font-size: 1.2rem;">₱<span id="total"><?= number_format($item['price'],2) ?></span></span>
          </div>
        </div>
        
        <button type="submit" class="btn btn-brown w-full" style="font-size: 1.1rem; padding: 14px;">
          Confirm Order
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  &copy; <?= date('Y') ?> Pet Food Place. All rights reserved.
</footer>

<script src="../../assets/js/paw-animation.js"></script>
<script>
// Calculate total price dynamically
const pricePerUnit = <?= $item['price'] ?>;
const quantityInput = document.querySelector('input[name="quantity"]');
const totalSpan = document.getElementById('total');

quantityInput.addEventListener('input', function() {
  const qty = parseInt(this.value) || 1;
  const total = pricePerUnit * qty;
  totalSpan.textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
});
</script>
</body>
</html>