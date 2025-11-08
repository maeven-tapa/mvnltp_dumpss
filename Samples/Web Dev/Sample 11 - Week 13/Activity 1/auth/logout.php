<?php
// Start session to access session data before destroying
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
if (session_id() !== '') {
    setcookie(session_name(), '', array(
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ));
}

session_destroy();

// Set cache control headers to prevent caching of this page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');

// Redirect to home page
header("Location: ../index.php");
exit();
?>
