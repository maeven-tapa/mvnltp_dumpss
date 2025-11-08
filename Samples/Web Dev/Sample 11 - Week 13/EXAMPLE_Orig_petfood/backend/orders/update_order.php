<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    if ($order_id && in_array($new_status, ['reserved', 'completed', 'cancelled'])) {
        $stmt = $pdo->prepare("SELECT item_id, quantity, status FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            if ($new_status === 'cancelled' && $order['status'] !== 'cancelled') {
                $restore = $pdo->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
                $restore->execute([$order['quantity'], $order['item_id']]);
            }

            $update = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $update->execute([$new_status, $order_id]);
        }
    }
}

header("Location: view_orders.php");
exit;
?>
