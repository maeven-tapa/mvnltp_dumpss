<?php
session_start();
require 'db.php';
if (!isset($_SESSION['is_admin'])) { header('Location: home_admin.php'); exit; }


$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Missing ID');


$stmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
$stmt->execute([$id]);
header('Location: home_admin.php');
exit;