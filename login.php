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
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $conn = getDBConnection();
            
            // Get user from database
            $sql = "SELECT id, username, email, password, role, full_name, is_active FROM users WHERE (username = ? OR email = ?) AND is_active = 1";
            $user = fetchSingleRow($conn, $sql, "ss", [$username, $username]);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Set user role properly in session
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect based on role
                switch ($user['role']) {
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
                        header("Location: index.php");
                }
                exit();
            } else {
                $error = 'Invalid username/email or password.';
            }
            
            $conn->close();
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
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
    <title>Login - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="form-container">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-2">University Companion</h2>
                        <p class="text-muted">Login to your account</p>
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

                    <form method="POST" action="" id="loginForm" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person"></i> Username or Email
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Please provide a valid username or email.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock"></i> Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback">
                                Please provide your password.
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-2">Don't have an account?</p>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus"></i> Register Here
                        </a>
                    </div>

                    <div class="text-center mt-4">
                        <a href="index.html" class="text-muted">
                            <i class="bi bi-arrow-left"></i> Back to Home
                        </a>
                    </div>

                    <!-- Demo Credentials -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="text-muted">Demo Credentials:</h6>
                        <small class="d-block"><strong>Admin:</strong> admin / admin123</small>
                        <small class="d-block"><strong>Faculty:</strong> john.doe / faculty123</small>
                        <small class="d-block"><strong>Student:</strong> alice.brown / student123</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    </script>
</body>
</html>