<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../pages/admin/home.php?error=invalid_id");
    exit;
}

$id = (int)$_GET['id'];

try {
    // Begin transaction for safety
    $pdo->beginTransaction();

    // Check if item exists before deletion
    $check = $pdo->prepare("SELECT item_code FROM items WHERE id = ?");
    $check->execute([$id]);
    $item = $check->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        header("Location: ../../pages/admin/home.php?error=notfound");
        exit;
    }

    // Delete item safely
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$id]);

    // Commit the transaction
    $pdo->commit();

    // Redirect with success flag
    header("Location: ../../pages/admin/home.php?error=db&message=" . urlencode($e->getMessage()));
exit;

} catch (PDOException $e) {
    // Roll back if anything goes wrong
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../../pages/admin/home.php?error=db&message=" . urlencode($e->getMessage()));
    exit;
}
?>
