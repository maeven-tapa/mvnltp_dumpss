<?php
// test_db.php — quick connection test for db.php
// Run: php test_db.php

require __DIR__ . '/db.php';

try {
    // Simple test query
    $stmt = $pdo->query('SELECT 1');
    $val = $stmt->fetchColumn();
    if ($val !== false) {
        echo "Connection OK — SELECT 1 returned: " . $val . PHP_EOL;
    } else {
        echo "Connection made but SELECT returned no value." . PHP_EOL;
    }
} catch (PDOException $e) {
    echo "Connection test failed: " . $e->getMessage() . PHP_EOL;
}
