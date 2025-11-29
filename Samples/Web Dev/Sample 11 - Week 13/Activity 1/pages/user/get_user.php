<?php




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

    $parts = preg_split('/\s+/', trim($_SESSION['name']), 2);
    $userFirstName = isset($parts[0]) ? $parts[0] : '';
    $userLastName = isset($parts[1]) ? $parts[1] : '';
    $userFullName = trim($userFirstName . ' ' . $userLastName);
}


if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'fname' => $userFirstName,
        'lname' => $userLastName,
        'fullname' => $userFullName
    ]);
    exit();
}


?>
