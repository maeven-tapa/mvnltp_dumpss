<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_contact = trim($_POST['customer_contact'] ?? '');
    $quantity = (int)$_POST['quantity'];

    if ($item_id && $customer_name && $customer_contact && $quantity > 0) {
        $order_code = generateOrderCode($pdo);

        $stmt = $pdo->prepare("INSERT INTO orders (order_code, item_id, customer_name, customer_contact, quantity)
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_code, $item_id, $customer_name, $customer_contact, $quantity]);

        echo "<p>Order placed successfully! Your Order ID is <strong>" . e($order_code) . "</strong>.</p>";
    } else {
        echo "<p style='color:red;'>Please fill in all fields.</p>";
    }
}
?>
