<?php
// Admin Account Creator Script
// Save as: create_admin.php

require_once 'php/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Account Creator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #007bff; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .credentials { background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px; margin: 15px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 Admin Account Creator</h1>";

try {
    $conn = getDBConnection();
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Check if admin user already exists
    $checkSql = "SELECT id, username, email, full_name, is_active FROM users WHERE role = 'admin' OR username = 'admin'";
    $existingAdmins = fetchMultipleRows($conn, $checkSql);
    
    if (!empty($existingAdmins)) {
        echo "<div class='info'>📋 Existing Admin Accounts Found:</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Active</th></tr>";
        foreach ($existingAdmins as $admin) {
            $activeStatus = $admin['is_active'] ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>" . $admin['id'] . "</td>";
            echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
            echo "<td>" . $activeStatus . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Admin credentials
    $adminUsername = 'admin';
    $adminEmail = 'admin@university.edu';
    $adminPassword = 'admin123';
    $adminFullName = 'System Administrator';
    $hashedPassword = hashPassword($adminPassword);
    
    // Check if specific admin exists
    $adminCheckSql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $existingAdmin = fetchSingleRow($conn, $adminCheckSql, "ss", [$adminUsername, $adminEmail]);
    
    if ($existingAdmin) {
        // Update existing admin
        echo "<div class='info'>📝 Updating existing admin account...</div>";
        
        $updateSql = "UPDATE users SET 
                      username = ?, 
                      email = ?, 
                      password = ?, 
                      role = 'admin', 
                      full_name = ?, 
                      is_active = 1, 
                      updated_at = NOW() 
                      WHERE id = ?";
        
        $result = updateData($conn, $updateSql, "ssssi", [
            $adminUsername,
            $adminEmail, 
            $hashedPassword,
            $adminFullName,
            $existingAdmin['id']
        ]);
        
        if ($result >= 0) {
            echo "<div class='success'>✅ Admin account updated successfully!</div>";
        } else {
            echo "<div class='error'>❌ Failed to update admin account</div>";
        }
    } else {
        // Create new admin
        echo "<div class='info'>📝 Creating new admin account...</div>";
        
        $insertSql = "INSERT INTO users (username, email, password, role, full_name, department, is_active, created_at) 
                      VALUES (?, ?, ?, 'admin', ?, 'Administration', 1, NOW())";
        
        $result = insertData($conn, $insertSql, "ssss", [
            $adminUsername,
            $adminEmail,
            $hashedPassword,
            $adminFullName
        ]);
        
        if ($result > 0) {
            echo "<div class='success'>✅ Admin account created successfully! (ID: $result)</div>";
        } else {
            echo "<div class='error'>❌ Failed to create admin account</div>";
        }
    }
    
    // Verify the admin account
    echo "<div class='info'>🔍 Verifying admin account...</div>";
    $verifySql = "SELECT id, username, email, role, full_name, is_active FROM users WHERE username = ?";
    $verifiedAdmin = fetchSingleRow($conn, $verifySql, "s", [$adminUsername]);
    
    if ($verifiedAdmin) {
        echo "<div class='success'>✅ Admin account verified in database</div>";
        
        // Test password verification
        $testSql = "SELECT password FROM users WHERE username = ?";
        $passwordTest = fetchSingleRow($conn, $testSql, "s", [$adminUsername]);
        
        if ($passwordTest && verifyPassword($adminPassword, $passwordTest['password'])) {
            echo "<div class='success'>✅ Password verification test passed</div>";
        } else {
            echo "<div class='error'>❌ Password verification test failed</div>";
        }
        
        // Display credentials
        echo "<div class='credentials'>";
        echo "<h3>🔑 Admin Login Credentials</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td><strong>Username</strong></td><td>" . htmlspecialchars($adminUsername) . "</td></tr>";
        echo "<tr><td><strong>Email</strong></td><td>" . htmlspecialchars($adminEmail) . "</td></tr>";
        echo "<tr><td><strong>Password</strong></td><td>" . htmlspecialchars($adminPassword) . "</td></tr>";
        echo "<tr><td><strong>Role</strong></td><td>Administrator</td></tr>";
        echo "<tr><td><strong>Full Name</strong></td><td>" . htmlspecialchars($adminFullName) . "</td></tr>";
        echo "</table>";
        echo "</div>";
        
        echo "<div class='success'>";
        echo "<h3>✅ Ready to Login!</h3>";
        echo "<p>You can now log in using either:</p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> " . htmlspecialchars($adminUsername) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($adminEmail) . "</li>";
        echo "</ul>";
        echo "<p><strong>Password:</strong> " . htmlspecialchars($adminPassword) . "</p>";
        echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Login Page</a></p>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>❌ Could not verify admin account creation</div>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>📋 Make sure:</div>";
    echo "<ul>";
    echo "<li>XAMPP MySQL service is running</li>";
    echo "<li>Database 'university_companion' exists</li>";
    echo "<li>Database tables have been imported</li>";
    echo "</ul>";
}

echo "</div></body></html>";
?>