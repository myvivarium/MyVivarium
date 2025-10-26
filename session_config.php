<?php

/**
 * Session Security Configuration
 *
 * This file configures secure session settings to protect against session hijacking,
 * session fixation, and other session-based attacks. It should be included at the
 * beginning of any file that uses sessions.
 *
 */

// Configure session cookie parameters for security
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access to session cookie (XSS protection)
ini_set('session.cookie_secure', 1);    // Only send cookie over HTTPS connections
ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF attacks
ini_set('session.use_only_cookies', 1); // Only use cookies for session ID
ini_set('session.use_strict_mode', 1);  // Reject uninitialized session IDs

// Set session timeout to 30 minutes of inactivity
ini_set('session.gc_maxlifetime', 1800); // 30 minutes in seconds
ini_set('session.cookie_lifetime', 1800); // Cookie expires after 30 minutes

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    // Last request was more than 30 minutes ago
    session_unset();     // Unset $_SESSION variable
    session_destroy();   // Destroy session data in storage
    session_start();     // Start a new session
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity timestamp

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    // Session started more than 30 minutes ago
    session_regenerate_id(true);    // Change session ID and delete old session
    $_SESSION['CREATED'] = time();  // Update creation time
}

?>
