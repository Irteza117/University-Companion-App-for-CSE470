<?php
require_once 'php/config.php';

// If user is not logged in, redirect to login
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Redirect based on user role
$userRole = $_SESSION['user_role'];

switch ($userRole) {
    case 'admin':
        header("Location: admin/dashboard.php");
        break;
    case 'faculty':
        header("Location: faculty/dashboard.php");
        break;
    case 'student':
        header("Location: student/dashboard.php");
        break;
    default:
        // If unknown role, logout and redirect to login
        header("Location: logout.php");
        break;
}
exit();
?>