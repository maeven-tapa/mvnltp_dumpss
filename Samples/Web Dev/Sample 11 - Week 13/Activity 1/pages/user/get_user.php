<?php
// Helper to expose user first/last/full name.
// Can be included by other PHP pages or accessed directly (returns JSON).

// Ensure session is started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userFirstName = '';
$userLastName = '';
$userFullName = '';

if (isset($_SESSION['fname']) || isset($_SESSION['lname'])) {
    $userFirstName = isset($_SESSION['fname']) ? $_SESSION['fname'] : '';
    $userLastName = isset($_SESSION['lname']) ? $_SESSION['lname'] : '';
    $userFullName = trim($userFirstName . ' ' . $userLastName);
} elseif (isset($_SESSION['name'])) {
    // Fallback: try to split the stored fullname into first and last
    $parts = preg_split('/\s+/', trim($_SESSION['name']), 2);
    $userFirstName = isset($parts[0]) ? $parts[0] : '';
    $userLastName = isset($parts[1]) ? $parts[1] : '';
    $userFullName = trim($userFirstName . ' ' . $userLastName);
}

// If accessed directly, return JSON
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'fname' => $userFirstName,
        'lname' => $userLastName,
        'fullname' => $userFullName
    ]);
    exit();
}

// When included, variables available: $userFirstName, $userLastName, $userFullName
?>
