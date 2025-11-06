<?php
session_start();
require 'db.php';

// Ensure only admin can use it
if (!isset($_SESSION['is_admin'])) {
    header('Location: home_admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    if ($order_id && in_array($new_status, ['reserved', 'completed', 'cancelled'])) {
        // Get order details
        $stmt = $pdo->prepare("SELECT item_id, quantity, status FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if ($order) {
            // If order was previously reserved or completed and now cancelled — return stock
            if ($new_status === 'cancelled' && $order['status'] !== 'cancelled') {
                $updStock = $pdo->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
                $updStock->execute([$order['quantity'], $order['item_id']]);
            }

            // Update order status
            $update = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $update->execute([$new_status, $order_id]);
        }
    }
}

// Redirect back to view_orders page
header('Location: view_orders.php');
exit;
