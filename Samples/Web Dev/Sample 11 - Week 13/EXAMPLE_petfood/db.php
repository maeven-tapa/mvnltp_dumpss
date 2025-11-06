<?php
// db.php — Connect to MySQL database
$DB_HOST = 'localhost:3307';
$DB_NAME = 'petshop';
$DB_USER = 'root';
$DB_PASS = 'amiel2004';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$DB_NAME`");

    // Create items table with unique custom ID
    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_code VARCHAR(20) UNIQUE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create orders table with custom order code
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_code VARCHAR(20) UNIQUE,
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

// Escaping function
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Generate next available PFPA-style item code.
 * It fills deleted gaps to keep codes sequential.
 */
function generateItemCode($pdo) {
    $rows = $pdo->query("SELECT item_code FROM items ORDER BY item_code ASC")->fetchAll(PDO::FETCH_COLUMN);
    $usedNumbers = [];

    foreach ($rows as $code) {
        if (preg_match('/PFPA-(\d{4})/', $code, $m)) {
            $usedNumbers[] = (int)$m[1];
        }
    }

    sort($usedNumbers);

    // Find the first missing gap or append new number
    $next = 1;
    foreach ($usedNumbers as $num) {
        if ($num != $next) break;
        $next++;
    }

    return sprintf("PFPA-%04d", $next);
}

/**
 * Generate next available ORD-style order code.
 * Same logic as generateItemCode().
 */
function generateOrderCode($pdo) {
    $rows = $pdo->query("SELECT order_code FROM orders ORDER BY order_code ASC")->fetchAll(PDO::FETCH_COLUMN);
    $usedNumbers = [];

    foreach ($rows as $code) {
        if (preg_match('/ORD-(\d{4})/', $code, $m)) {
            $usedNumbers[] = (int)$m[1];
        }
    }

    sort($usedNumbers);

    $next = 1;
    foreach ($usedNumbers as $num) {
        if ($num != $next) break;
        $next++;
    }

    return sprintf("ORD-%04d", $next);
}
?>