<?php
// Database connection configuration for University Companion WebApp
// Make sure XAMPP is running with MySQL service

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Default XAMPP MySQL password is empty
define('DB_NAME', 'university_companion');

// Create connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to utf8
        $conn->set_charset("utf8");
        
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Function to execute a prepared statement safely
function executeQuery($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        
        if ($result === false) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt;
    } catch (Exception $e) {
        throw new Exception("Query execution error: " . $e->getMessage());
    }
}

// Function to get a single row from database
function fetchSingleRow($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = executeQuery($conn, $sql, $types, $params);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    } catch (Exception $e) {
        throw new Exception("Fetch single row error: " . $e->getMessage());
    }
}

// Function to get multiple rows from database
function fetchMultipleRows($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = executeQuery($conn, $sql, $types, $params);
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    } catch (Exception $e) {
        throw new Exception("Fetch multiple rows error: " . $e->getMessage());
    }
}

// Function to insert data and return last insert ID
function insertData($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = executeQuery($conn, $sql, $types, $params);
        $insertId = $conn->insert_id;
        $stmt->close();
        return $insertId;
    } catch (Exception $e) {
        throw new Exception("Insert data error: " . $e->getMessage());
    }
}

// Function to update or delete data and return affected rows
function updateData($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = executeQuery($conn, $sql, $types, $params);
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    } catch (Exception $e) {
        throw new Exception("Update data error: " . $e->getMessage());
    }
}

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

// Function to verify CSRF token
function verifyCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to check if user is logged in
function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// Function to check user role
function hasRole($requiredRole) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    
    // Admin has access to everything
    if ($userRole === 'admin') {
        return true;
    }
    
    // Check specific role
    if (is_array($requiredRole)) {
        return in_array($userRole, $requiredRole);
    } else {
        return $userRole === $requiredRole;
    }
}

// Function to redirect if not authenticated
function requireAuth($redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit();
    }
}

// Function to redirect if doesn't have required role
function requireRole($requiredRole, $redirectTo = 'dashboard.php') {
    requireAuth();
    
    if (!hasRole($requiredRole)) {
        header("Location: $redirectTo");
        exit();
    }
}

// Function to log user activity (optional)
function logActivity($conn, $userId, $activity, $details = '') {
    try {
        $sql = "INSERT INTO user_activity_log (user_id, activity, details, timestamp) VALUES (?, ?, ?, NOW())";
        executeQuery($conn, $sql, "iss", [$userId, $activity, $details]);
    } catch (Exception $e) {
        // Log activity logging is optional, don't break the application
        error_log("Activity logging error: " . $e->getMessage());
    }
}

// Set default timezone
date_default_timezone_set('America/New_York'); // Change to your timezone

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>