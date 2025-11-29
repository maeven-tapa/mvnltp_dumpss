<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$_SESSION = array();


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


header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');


header("Location: ../index.php");
exit();
?>
