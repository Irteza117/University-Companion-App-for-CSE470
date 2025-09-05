<?php
require_once 'config.php';

// Make sure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log the logout activity if user was logged in
if (isLoggedIn()) {
    try {
        $conn = getDBConnection();
        logActivity($conn, $_SESSION['user_id'], 'logout', 'User logged out');
        $conn->close();
    } catch (Exception $e) {
        // Continue with logout even if logging fails
        error_log("Logout activity logging failed: " . $e->getMessage());
    }
}

// Destroy all session data
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

// Redirect to login page with success message
header("Location: ../login.php?message=" . urlencode("You have been successfully logged out."));
exit();
?>