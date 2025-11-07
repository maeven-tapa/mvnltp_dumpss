<?php
$host = 'localhost:3307';
$db   = 'petshop'; // change to your database name
$user = 'root';
$pass = 'amiel2004'; // leave blank for XAMPP default

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
