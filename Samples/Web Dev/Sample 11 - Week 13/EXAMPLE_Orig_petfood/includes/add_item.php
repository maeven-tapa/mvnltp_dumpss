<?php
session_start();
require_once __DIR__ . '/../backend/db.php';

if (!isset($_SESSION['is_admin'])) {
    header('Location: ../pages/admin/home.php');
    exit;
}

function e($str) { return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function generateItemCode($pdo) {
    $stmt = $pdo->query("SELECT item_code FROM items ORDER BY id ASC");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $num = 1;
    while (in_array(sprintf("PFPA-%04d", $num), $existing)) {
        $num++;
    }
    return sprintf("PFPA-%04d", $num);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if ($name === '') {
        $error = 'Name required';
    } else {
        $item_code = generateItemCode($pdo);
        $stmt = $pdo->prepare("INSERT INTO items (item_code, name, description, price, stock) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$item_code, $name, $desc, $price, $stock]);
        header('Location: ../pages/admin/home.php');
        exit;
    }
}
?>
