<?php
require_once 'php/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Please provide a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['student', 'faculty'])) {
        $error = 'Please select a valid role.';
    } else {
        try {
            $conn = getDBConnection();
            
            // Check if username or email already exists
            $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $existing = fetchSingleRow($conn, $sql, "ss", [$username, $email]);
            
            if ($existing) {
                $error = 'Username or email already exists.';
            } else {
                // Hash password and insert user
                $hashedPassword = hashPassword($password);
                
                $sql = "INSERT INTO users (username, email, password, role, full_name, department, phone) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $userId = insertData($conn, $sql, "sssssss", [$username, $email, $hashedPassword, $role, $full_name, $department, $phone]);
                
                if ($userId) {
                    $success = 'Registration successful! You can now login with your credentials.';
                    // Clear form data
                    $username = $email = $full_name = $role = $department = $phone = '';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
            
            $conn->close();
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
            error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8 col-lg-6">
                <div class="form-container">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-2">University Companion</h2>
                        <p class="text-muted">Create your account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="registerForm" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person"></i> Username *
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a username.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope"></i> Email *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">
                                <i class="bi bi-person-badge"></i> Full Name *
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Please provide your full name.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">
                                    <i class="bi bi-people"></i> Role *
                                </label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="student" <?php echo ($role ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="faculty" <?php echo ($role ?? '') === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select your role.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">
                                    <i class="bi bi-building"></i> Department
                                </label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo htmlspecialchars($department ?? ''); ?>" 
                                       placeholder="e.g., Computer Science">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <i class="bi bi-telephone"></i> Phone Number
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                                   placeholder="e.g., +1234567890">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Password *
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="6" required>
                                <div class="invalid-feedback">
                                    Password must be at least 6 characters long.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="bi bi-lock-fill"></i> Confirm Password *
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                                <div class="invalid-feedback">
                                    Please confirm your password.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-primary">Terms and Conditions</a> *
                            </label>
                            <div class="invalid-feedback">
                                You must agree to the terms and conditions.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus"></i> Register
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-2">Already have an account?</p>
                        <a href="login.php" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login Here
                        </a>
                    </div>

                    <div class="text-center mt-4">
                        <a href="index.html" class="text-muted">
                            <i class="bi bi-arrow-left"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                event.preventDefault();
                event.stopPropagation();
                document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
            } else {
                document.getElementById('confirm_password').setCustomValidity('');
            }
            
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.classList.add('was-validated');
        });

        // Real-time password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function() {
            var password = document.getElementById('password').value;
            var confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>