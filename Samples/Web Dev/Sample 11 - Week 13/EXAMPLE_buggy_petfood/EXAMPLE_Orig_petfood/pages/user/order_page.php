<?php
session_start();
require_once '../../backend/db.php';

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* -----------------------------------------------------------
   Generate next available ORD-XXXX code with gap detection
------------------------------------------------------------ */
function generateOrderCode($pdo) {
    $stmt = $pdo->query("SELECT order_code FROM orders ORDER BY order_code ASC");
    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $num = 1;
    foreach ($codes as $c) {
        $n = intval(substr($c, 4));
        if ($n !== $num) break;
        $num++;
    }
    return sprintf("ORD-%04d", $num);
}


/* -----------------------------------------------------------
   GET ITEM BY item_code (string like PF-0001)
------------------------------------------------------------ */
if (!isset($_GET['item_code']) || strlen(trim($_GET['item_code'])) === 0) {
  header("Location: home.php");
  exit;
}

$item_code = trim($_GET['item_code']);

$stmt = $pdo->prepare("SELECT * FROM items WHERE item_code = ?");
$stmt->execute([$item_code]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: home.php");
    exit;
}

$alert = "";

/* -----------------------------------------------------------
   PROCESS ORDER SUBMISSION
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['customer_contact'] ?? '');
    $qty = max(1, intval($_POST['quantity'] ?? 1));

    // Fetch latest stock and reserve row for update to avoid race
    $stmt = $pdo->prepare("SELECT stock FROM items WHERE item_code = ? FOR UPDATE");

    try {
      $pdo->beginTransaction();
      $stmt->execute([$item_code]);
      $stock = intval($stmt->fetchColumn());

      if ($stock < $qty) {
        $pdo->rollBack();
        $alert = "Not enough stock available.";
      } elseif ($name === '' || $contact === '') {
        $pdo->rollBack();
        $alert = "Please fill in all required fields.";
      } else {

        $order_code = generateOrderCode($pdo);

        // Insert order using item_code (no numeric ids)
        $ins = $pdo->prepare(
          "INSERT INTO orders (order_code, user_id, item_code, quantity, total, created_at)
          VALUES (?, ?, ?, ?, ?, NOW())"
        );

        $total = $item['price'] * $qty;
        $ins->execute([
          $order_code,
          $_SESSION['user_id'], // user ID session value
          $item_code,
          $qty,
          $total
        ]);

        // Update stock
        $upd = $pdo->prepare("UPDATE items SET stock = stock - ? WHERE item_code = ?");
        $upd->execute([$qty, $item_code]);

            $pdo->commit();

            // Redirect back to user dashboard with a success flag and order code
            header("Location: home.php?ordered=" . urlencode($order_code));
            exit;
      }

    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $alert = "Failed to place order: " . $e->getMessage();
    }
    // end POST handling

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

    <!-- Product Display -->
    <div class="card">
      <div class="thumb" style="height: 250px; font-size: 24px;"><?= e($item['item_code']) ?></div>
      <h2 style="margin: 20px 0 12px 0; font-size: 2rem;"><?= e($item['name']) ?></h2>
      <div class="meta"><?= nl2br(e($item['description'])) ?></div>

      <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0ebe6;">
        <div class="price" style="font-size: 2rem;">₱<?= number_format($item['price'],2) ?></div>
        <div class="meta" style="margin-top: 8px;">
          Available Stock:
          <strong><?= (int)$item['stock'] ?> units</strong>
        </div>
      </div>
    </div>

    <!-- Order Form -->
    <div class="card" style="position: sticky; top: 100px;">
      <h3 style="margin-bottom: 20px;">Complete Your Order</h3>

      <form method="POST">

        <input type="hidden" name="item_code" value="<?= htmlspecialchars($item_code) ?>">

        <div>
          <label>Your Full Name</label>
          <input type="text" name="customer_name" required>
        </div>

        <div>
          <label>Contact Information</label>
          <input type="text" name="customer_contact" required>
        </div>

        <div>
          <label>Quantity</label>
          <input type="number"
                 name="quantity"
                 min="1"
                 max="<?= (int)$item['stock'] ?>"
                 value="1"
                 required>
        </div>

        <div style="background: #faf4ef; padding: 16px; border-radius: 12px; margin: 20px 0;">
          <div style="display: flex; justify-content: space-between;">
            <span>Price per unit:</span>
            <span>₱<?= number_format($item['price'],2) ?></span>
          </div>

          <div style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: bold;">
            <span>Total:</span>
            <span>₱<span id="total"><?= number_format($item['price'],2) ?></span></span>
          </div>
        </div>

        <button class="btn btn-brown w-full" style="font-size: 1.1rem;">Confirm Order</button>
      </form>
    </div>

  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> Pet Food Place.
</footer>

<script src="../../assets/js/paw-animation.js"></script>

<script>
// Live total price calculator + clamp quantity to available stock
const price = Number(<?= json_encode($item['price']) ?>);
const qtyInput = document.querySelector('input[name="quantity"]');
const totalOutput = document.getElementById('total');
const maxStock = Number(<?= (int)$item['stock'] ?>);

function clampQty() {
  let v = qtyInput.value.replace(/,/g, '').trim();
  // remove non-digits
  v = v.replace(/[^0-9]/g, '');
  let n = parseInt(v, 10);
  if (isNaN(n) || n < 1) n = 1;
  if (n > maxStock) n = maxStock;
  qtyInput.value = n;
  return n;
}

function updateTotal() {
  const qty = clampQty();
  const total = price * qty;
  totalOutput.textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

qtyInput.addEventListener('input', updateTotal);
qtyInput.addEventListener('change', updateTotal);
// initialize
updateTotal();
</script>

</body>
</html>
