<?php
session_start();
require_once '../db.php';

function e($s){ return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function generateOrderCode($pdo) {
    $stmt = $pdo->query("SELECT order_code FROM orders ORDER BY id ASC");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $num = 1;
    while (in_array(sprintf("ORD-%04d", $num), $existing)) $num++;
    return sprintf("ORD-%04d", $num);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Expect item_code (string) instead of numeric id
    $item_code = trim($_POST['item_code'] ?? '');
    $name = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['customer_contact'] ?? '');
    $qty = max(1, (int)($_POST['quantity'] ?? 0));

    if ($item_code !== '' && $name !== '' && $contact !== '' && $qty > 0) {
        // Verify stock before committing
        $stmt = $pdo->prepare("SELECT stock FROM items WHERE item_code = ? FOR UPDATE");
        try {
            $pdo->beginTransaction();
            $stmt->execute([$item_code]);
            $stock = (int)$stmt->fetchColumn();

            if ($stock < $qty) {
                $pdo->rollBack();
                echo "<p style='color:red;'>Not enough stock available.</p>";
                exit;
            }

            $order_code = generateOrderCode($pdo);

            $pdo->prepare("INSERT INTO orders (order_code, item_code, customer_name, customer_contact, quantity, status, created_at)
                           VALUES (?, ?, ?, ?, ?, 'reserved', NOW())")->execute([$order_code, $item_code, $name, $contact, $qty]);
            $pdo->prepare("UPDATE items SET stock = stock - ? WHERE item_code = ?")->execute([$qty, $item_code]);
            $pdo->commit();

            echo "<p>Order placed! Your Order ID is <strong>" . e($order_code) . "</strong>.</p>";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo "<p style='color:red;'>Failed to place order: " . e($e->getMessage()) . "</p>";
        }

    } else {
        echo "<p style='color:red;'>Please fill all fields.</p>";
    }
}
?>
