<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($fullName) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists
        $checkSql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $existing = fetchSingleRow($conn, $checkSql, "ss", [$username, $email]);
        
        if ($existing) {
            $error = 'Username or email already exists.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert new user
            $sql = "INSERT INTO users (username, email, password, full_name, role, department, phone, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            $result = insertData($conn, $sql, "sssssss", [$username, $email, $hashedPassword, $fullName, $role, $department, $phone]);
            
            if ($result > 0) {
                $success = "User '{$fullName}' created successfully.";
                // Clear form data
                $username = $email = $fullName = $role = $department = $phone = '';
            } else {
                $error = "Failed to create user. Please try again.";
            }
        }
    }
}

// Get departments for dropdown
$departments = fetchMultipleRows($conn, "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4 primary-text fs-4 fw-bold text-uppercase border-bottom">
                <i class="bi bi-mortarboard-fill me-2"></i>Admin Panel
            </div>
            <div class="list-group list-group-flush my-3">
                <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="users.php" class="list-group-item list-group-item-action bg-transparent text-white active">
                    <i class="bi bi-people me-2"></i>User Management
                </a>
                <a href="courses.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-book me-2"></i>Courses
                </a>
                <a href="notices.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-megaphone me-2"></i>Notices
                </a>
                <a href="events.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-calendar-event me-2"></i>Events
                </a>

            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../php/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-person-plus"></i> Add New User
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="users.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Users
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Add User Form -->
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">User Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <!-- Username -->
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">Username *</label>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                            <div class="form-text">Username must be unique and contain only letters, numbers, and underscores.</div>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Password -->
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">Password *</label>
                                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                            <div class="form-text">Password must be at least 6 characters long.</div>
                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                        </div>
                                    </div>

                                    <!-- Full Name -->
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($fullName ?? ''); ?>" required>
                                    </div>

                                    <div class="row">
                                        <!-- Role -->
                                        <div class="col-md-6 mb-3">
                                            <label for="role" class="form-label">Role *</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="">Select Role</option>
                                                <option value="student" <?php echo (($role ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                                                <option value="faculty" <?php echo (($role ?? '') === 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                                                <option value="admin" <?php echo (($role ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </div>

                                        <!-- Department -->
                                        <div class="col-md-6 mb-3">
                                            <label for="department" class="form-label">Department</label>
                                            <input type="text" class="form-control" id="department" name="department" 
                                                   value="<?php echo htmlspecialchars($department ?? ''); ?>" 
                                                   list="department-list" placeholder="e.g., Computer Science">
                                            <datalist id="department-list">
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                                               placeholder="e.g., +1234567890">
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex justify-content-between">
                                        <a href="users.php" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </a>
                                        <button type="submit" name="create_user" class="btn btn-primary">
                                            <i class="bi bi-person-plus"></i> Create User
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('wrapper').classList.toggle('toggled');
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>