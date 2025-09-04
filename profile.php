<?php
require_once 'php/config.php';
requireAuth();

$conn = getDBConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    try {
        // Get current user data
        $userSql = "SELECT * FROM users WHERE id = ?";
        $user = fetchSingleRow($conn, $userSql, "i", [$userId]);
        
        // Verify current password if trying to change password
        if (!empty($newPassword)) {
            if (empty($currentPassword) || !verifyPassword($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect.");
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match.");
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception("New password must be at least 6 characters long.");
            }
        }
        
        // Validate email
        if (!validateEmail($email)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Check if email is taken by another user
        $emailCheckSql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $emailExists = fetchSingleRow($conn, $emailCheckSql, "si", [$email, $userId]);
        if ($emailExists) {
            throw new Exception("Email address is already taken by another user.");
        }
        
        // Update profile
        if (!empty($newPassword)) {
            $hashedPassword = hashPassword($newPassword);
            $updateSql = "UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, updated_at = NOW() WHERE id = ?";
            executeQuery($conn, $updateSql, "ssssi", [$fullName, $email, $phone, $hashedPassword, $userId]);
        } else {
            $updateSql = "UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?";
            executeQuery($conn, $updateSql, "sssi", [$fullName, $email, $phone, $userId]);
        }
        
        // Update session data
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        
        // Log activity
        logActivity($conn, $userId, 'profile_update', 'User updated profile information');
        
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $user = fetchSingleRow($conn, $userSql, "i", [$userId]);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    // Get current user data
    $userSql = "SELECT * FROM users WHERE id = ?";
    $user = fetchSingleRow($conn, $userSql, "i", [$userId]);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $userRole; ?>/dashboard.php">
                <i class="bi bi-mortarboard-fill"></i>
                University Companion
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2">
                        <i class="bi bi-person-circle"></i> My Profile
                    </h1>
                    <a href="<?php echo $userRole; ?>/dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Information Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-person-badge"></i> Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="full_name" 
                                               name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" 
                                               class="form-control" 
                                               id="phone" 
                                               name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" 
                                               readonly>
                                    </div>
                                </div>
                            </div>

                            <?php if ($userRole === 'student'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Student ID</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?>" 
                                                   readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Academic Year</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['academic_year'] ?? 'N/A'); ?>" 
                                                   readonly>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <hr>

                            <h6 class="mb-3">
                                <i class="bi bi-key"></i> Change Password (Optional)
                            </h6>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="current_password" 
                                               name="current_password"
                                               placeholder="Enter current password">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="new_password" 
                                               name="new_password"
                                               placeholder="Enter new password">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password"
                                               placeholder="Confirm new password">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Information Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle"></i> Account Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Account Created:</strong> <?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> 
                                    <?php 
                                    echo $user['updated_at'] ? 
                                        date('M j, Y g:i A', strtotime($user['updated_at'])) : 
                                        'Never'; 
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Account Status:</strong> 
                                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </p>
                                <p><strong>Email Verified:</strong> 
                                    <span class="badge <?php echo $user['email_verified'] ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo $user['email_verified'] ? 'Verified' : 'Pending'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        // Password validation
        document.getElementById('new_password').addEventListener('input', function() {
            const currentPassword = document.getElementById('current_password');
            const newPassword = this.value;
            
            if (newPassword.length > 0) {
                currentPassword.required = true;
            } else {
                currentPassword.required = false;
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>