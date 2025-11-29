<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function validateSession($required_role = null) {

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {

        header('Location: ' . dirname(dirname(__FILE__)) . '/auth/login.php');
        exit();
    }


    if ($required_role !== null) {
        $user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'user';
        $required_role = strtolower($required_role);

        if ($user_role !== $required_role) {

            if ($user_role === 'admin') {
                header('Location: ' . dirname(dirname(__FILE__)) . '/pages/admin/dashboard.php');
            } else {
                header('Location: ' . dirname(dirname(__FILE__)) . '/pages/user/dashboard.php');
            }
            exit();
        }
    }


    return true;
}


validateSession();


header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
