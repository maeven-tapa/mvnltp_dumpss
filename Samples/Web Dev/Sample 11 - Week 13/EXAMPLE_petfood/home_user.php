<?php
session_start();
require 'db.php';

// Fetch available items
$items = $pdo->query("SELECT * FROM items ORDER BY created_at DESC")->fetchAll();

$alertMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_contact = trim($_POST['customer_contact'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($item_id && $customer_name && $customer_contact && $quantity > 0) {
        // Get current stock
        $stmt = $pdo->prepare("SELECT stock FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if ($item && $item['stock'] >= $quantity) {
            // Generate unique order code
            $order_code = generateOrderCode($pdo);

            // Insert order
            $ins = $pdo->prepare("INSERT INTO orders (order_code, item_id, customer_name, customer_contact, quantity)
                                  VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$order_code, $item_id, $customer_name, $customer_contact, $quantity]);

            // Update stock
            $upd = $pdo->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
            $upd->execute([$quantity, $item_id]);

            $alertMessage = "Order successful! Your Order ID is $order_code.";
        } else {
            $alertMessage = "Sorry, not enough stock available.";
        }
    } else {
        $alertMessage = "Please fill in all required fields.";
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Pet Food Reservation</title>
<link rel="stylesheet" href="styles.css">
<style>
body { font-family: Arial, sans-serif; background: #f9fafb; margin: 0; }
.container { max-width: 900px; margin: 40px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; }
h1 { text-align: center; }
input, textarea, select, button { padding: 8px; width: 100%; margin-bottom: 8px; }
button { background: #007bff; color: white; border: none; cursor: pointer; }
button:hover { background: #0056b3; }
</style>
</head>
<body>
<div class="container">
    <h1>Reserve Your Pet Food</h1>

    <?php foreach ($items as $it): ?>
        <div class="card">
            <h3><?= e($it['name']) ?> <small>₱<?= number_format($it['price'], 2) ?></small></h3>
            <p><?= nl2br(e($it['description'])) ?></p>
            <p><strong>Available Stock:</strong> <?= (int)$it['stock'] ?></p>

            <?php if ($it['stock'] > 0): ?>
                <form method="post">
                    <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                    <input type="text" name="customer_name" placeholder="Your name" required>
                    <input type="text" name="customer_contact" placeholder="Contact number" required>
                    <input type="number" name="quantity" min="1" max="<?= (int)$it['stock'] ?>" value="1" required>
                    <button type="submit">Reserve</button>
                </form>
            <?php else: ?>
                <p style="color:red;">Out of stock</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($alertMessage): ?>
<script>
alert("<?= e($alertMessage) ?>");
window.location.href = "home_user.php"; // refresh after alert
</script>
<?php endif; ?>
</body>
</html>
