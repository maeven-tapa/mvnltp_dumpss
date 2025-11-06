<?php
session_start();
require 'db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header('Location: home_user.php'); exit;
}


$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$name = trim($_POST['customer_name'] ?? '');
$contact = trim($_POST['customer_contact'] ?? '');
$qty = max(1, (int)($_POST['quantity'] ?? 1));


if (!$item_id || !$name || !$contact) {
die('Missing required fields. <a href="home_user.php">Back</a>');
}


// Check stock
$stmt = $pdo->prepare('SELECT stock, name FROM items WHERE id = ?');
$stmt->execute([$item_id]);
$item = $stmt->fetch();
if (!$item) die('Item not found.');
if ($item['stock'] < $qty) die('Not enough stock.');


$pdo->beginTransaction();
try {
$ins = $pdo->prepare('INSERT INTO orders (item_id, customer_name, customer_contact, quantity) VALUES (?,?,?,?)');
$ins->execute([$item_id, $name, $contact, $qty]);


// decrement stock
$upd = $pdo->prepare('UPDATE items SET stock = stock - ? WHERE id = ?');
$upd->execute([$qty, $item_id]);


$pdo->commit();
echo "Reservation successful for " . e($item['name']) . ". <a href=\"home_user.php\">Back</a>";
} catch (Exception $e) {
$pdo->rollBack();
die('Could not complete reservation: ' . $e->getMessage());
}