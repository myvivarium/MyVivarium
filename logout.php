<?php

/**
 * Logout Script
 * 
 * This script logs out the user by destroying the session and redirecting to the index.php page.
 * 
 */

// Check if a session is already started, and start a new session if not
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to the index.php page
header("Location: index.php");
exit;  // Ensure no further code is executed
