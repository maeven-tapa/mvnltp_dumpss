<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $order_code = trim($_POST['order_code'] ?? '');
    $new_status = trim($_POST['status'] ?? '');

    // Allowed statuses
    $valid_status = ['reserved', 'completed', 'cancelled'];

    if ($order_code !== '' && in_array($new_status, $valid_status)) {

        // Fetch order with item_code and quantity
        $stmt = $pdo->prepare("
            SELECT item_code, quantity, status
            FROM orders
            WHERE order_code = ?
        ");
        $stmt->execute([$order_code]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {

            $old_status = $order['status'];
            $item_code  = $order['item_code'];
            $qty        = (int)$order['quantity'];

            /* -----------------------------------------------------------
                STOCK RESTORE LOGIC
                Only restore stock if:
                - New status is "cancelled"
                - Old status was NOT "cancelled" (prevents double-restore)
            ------------------------------------------------------------ */
            if ($new_status === 'cancelled' && $old_status !== 'cancelled') {

                $restore = $pdo->prepare("
                    UPDATE items 
                    SET stock = stock + ? 
                    WHERE item_code = ?
                ");
                $restore->execute([$qty, $item_code]);
            }

            /* -----------------------------------------------------------
                UPDATE ORDER STATUS
            ------------------------------------------------------------ */
            $upd = $pdo->prepare("
                UPDATE orders
                SET status = ?
                WHERE order_code = ?
            ");
            $upd->execute([$new_status, $order_code]);
        }
    }
}

header("Location: view_orders.php");
exit;
?>
