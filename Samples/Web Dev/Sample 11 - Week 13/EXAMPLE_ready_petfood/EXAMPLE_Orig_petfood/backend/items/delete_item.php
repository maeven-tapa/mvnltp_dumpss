<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Expect an item_code (string like PF-0001) instead of a numeric id
if (!isset($_GET['item_code']) || strlen(trim($_GET['item_code'])) === 0) {
    header("Location: ../../pages/admin/home.php?error=invalid_id");
    exit;
}

$item_code = trim($_GET['item_code']);

try {
    // Begin transaction for safety
    $pdo->beginTransaction();

    // Check if the item exists (by item_code)
    $check = $pdo->prepare("SELECT item_code FROM items WHERE item_code = ?");
    $check->execute([$item_code]);
    $item = $check->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        header("Location: ../../pages/admin/home.php?error=notfound");
        exit;
    }

    // Delete the item by item_code (dependent orders will be removed if FK uses ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM items WHERE item_code = ?");
    $stmt->execute([$item_code]);

    // Commit the transaction
    $pdo->commit();

    // SUCCESS REDIRECT
    header("Location: ../../pages/admin/home.php?deleted=1");
    exit;

} catch (PDOException $e) {

    // Rollback if needed
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Redirect with actual error message
    header("Location: ../../pages/admin/home.php?error=db&message=" . urlencode($e->getMessage()));
    exit;
}
?>
