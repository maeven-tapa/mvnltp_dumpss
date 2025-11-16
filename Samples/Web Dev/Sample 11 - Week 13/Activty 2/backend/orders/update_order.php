<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Use order_code (string) as the identifier
    $order_code = trim($_POST['order_code'] ?? '');
    $new_status = trim($_POST['status'] ?? '');

    if ($order_code !== '' && in_array($new_status, ['reserved', 'completed', 'cancelled'])) {

        // Get current order details by order_code
        $stmt = $pdo->prepare("SELECT item_code, quantity, status FROM orders WHERE order_code = ?");
        $stmt->execute([$order_code]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {

            // If order is changed to cancelled → restock items only ONCE
            if ($new_status === 'cancelled' && $order['status'] !== 'cancelled') {
                $restore = $pdo->prepare("UPDATE items SET stock = stock + ? WHERE item_code = ?");
                $restore->execute([$order['quantity'], $order['item_code']]);
            }

            // Update order status
            $upd = $pdo->prepare("UPDATE orders SET status = ? WHERE order_code = ?");
            $upd->execute([$new_status, $order_code]);
        }
    }
}

header("Location: view_orders.php");
exit;
?>
