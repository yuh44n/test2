<?php
// Ensure proper session configuration
ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.name', 'HOTELSESSID'); // Custom session name

// Start or resume session
session_start();

// Set character encoding
ini_set('default_charset', 'UTF-8');

// Database connection
require_once 'connect.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['ID_CLIENT']) && !empty($_SESSION['ID_CLIENT']);
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.html");
        exit();
    }
}

// Function to get current user ID
function getCurrentUserId() {
    return isset($_SESSION['ID_CLIENT']) ? $_SESSION['ID_CLIENT'] : null;
}

// Function to get current user name
function getCurrentUserName() {
    if (isset($_SESSION['PRENOM']) && isset($_SESSION['NOM'])) {
        return $_SESSION['PRENOM'] . ' ' . $_SESSION['NOM'];
    }
    return null;
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['ROLE']) && $_SESSION['ROLE'] == 0;
}

// Function to regenerate session ID (helps prevent session fixation)
function regenerateSessionId() {
    session_regenerate_id(true);
}

// Function to log user in and set session variables
function loginUser($userId, $nom, $prenom, $role) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['ID_CLIENT'] = $userId;
    $_SESSION['NOM'] = $nom;
    $_SESSION['PRENOM'] = $prenom;
    $_SESSION['ROLE'] = $role;
    $_SESSION['LOGIN_TIME'] = time();
    
    // Return success
    return true;
}

// Function to log user out
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}
?>
