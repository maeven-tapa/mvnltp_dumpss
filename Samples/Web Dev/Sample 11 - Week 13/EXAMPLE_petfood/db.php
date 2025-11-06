<?php
// db.php — Connect to MySQL database
$DB_HOST = '127.0.0.1';
$DB_NAME = 'petshop';
$DB_USER = 'root';
$DB_PASS = '';


try {
$pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);


$pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$DB_NAME`");


// Create tables
$pdo->exec("CREATE TABLE IF NOT EXISTS items (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255) NOT NULL,
description TEXT,
price DECIMAL(10,2) NOT NULL DEFAULT 0,
stock INT NOT NULL DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");


$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
id INT AUTO_INCREMENT PRIMARY KEY,
item_id INT NOT NULL,
customer_name VARCHAR(255) NOT NULL,
customer_contact VARCHAR(255) NOT NULL,
quantity INT NOT NULL DEFAULT 1,
status VARCHAR(20) NOT NULL DEFAULT 'reserved',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");


} catch (PDOException $e) {
die('Database error: ' . $e->getMessage());
}


function e($str) {
return htmlspecialchars($str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}