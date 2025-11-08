<?php
/**
 * Session Validation and Security Check
 * Include this file at the beginning of all protected pages
 * Usage: require_once __DIR__ . '/../includes/session-check.php';
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to validate session and redirect if invalid
function validateSession($required_role = null) {
    // Check if user_id exists in session
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Session expired or not logged in - redirect to login
        header('Location: ' . dirname(dirname(__FILE__)) . '/auth/login.php');
        exit();
    }
    
    // If specific role is required, check it
    if ($required_role !== null) {
        $user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'user';
        $required_role = strtolower($required_role);
        
        if ($user_role !== $required_role) {
            // User doesn't have required role - redirect to appropriate dashboard
            if ($user_role === 'admin') {
                header('Location: ' . dirname(dirname(__FILE__)) . '/pages/admin/dashboard.php');
            } else {
                header('Location: ' . dirname(dirname(__FILE__)) . '/pages/user/dashboard.php');
            }
            exit();
        }
    }
    
    // Session is valid
    return true;
}

// Validate the session for this request
validateSession();

// Optional: Set response headers to prevent caching of authenticated pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
