<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle form submissions for user management
$success = '';
$error = '';

// Handle user status toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;
    
    $sql = "UPDATE users SET is_active = ? WHERE id = ?";
    $result = updateData($conn, $sql, "ii", [$newStatus, $userId]);
    
    if ($result >= 0) {
        $success = "User status updated successfully.";
    } else {
        $error = "Failed to update user status.";
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $userId = (int)$_POST['user_id'];
    
    // Don't allow deletion of admin users or current user
    if ($userId == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $userCheck = fetchSingleRow($conn, "SELECT role FROM users WHERE id = ?", "i", [$userId]);
        if ($userCheck && $userCheck['role'] == 'admin') {
            $error = "Admin users cannot be deleted.";
        } else {
            $sql = "UPDATE users SET is_active = 0 WHERE id = ?";
            $result = updateData($conn, $sql, "i", [$userId]);
            
            if ($result >= 0) {
                $success = "User has been deactivated successfully.";
            } else {
                $error = "Failed to deactivate user.";
            }
        }
    }
}

// Get filter parameters
$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];
$types = "";

if ($roleFilter != 'all') {
    $whereConditions[] = "u.role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

if ($statusFilter != 'all') {
    $whereConditions[] = "u.is_active = ?";
    $params[] = ($statusFilter == 'active') ? 1 : 0;
    $types .= "i";
}

if (!empty($searchTerm)) {
    $whereConditions[] = "(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get users with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$sql = "SELECT u.id, u.username, u.email, u.full_name, u.role, u.department, u.is_active, u.created_at
        FROM users u
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT $limit OFFSET $offset";

$users = fetchMultipleRows($conn, $sql, $types, $params);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM users u $whereClause";
$totalCount = fetchSingleRow($conn, $countSql, $types, $params)['total'];
$totalPages = ceil($totalCount / $limit);

// Get statistics
$stats = fetchSingleRow($conn, "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as total_students,
    SUM(CASE WHEN role = 'faculty' THEN 1 ELSE 0 END) as total_faculty,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_registrations
    FROM users");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - University Companion</title>
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
                        <i class="bi bi-people"></i> User Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="add_user.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-plus"></i> Add User
                            </a>
                        </div>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total_users']; ?></h4>
                                <p class="mb-0">Total Users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total_students']; ?></h4>
                                <p class="mb-0">Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total_faculty']; ?></h4>
                                <p class="mb-0">Faculty</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total_admins']; ?></h4>
                                <p class="mb-0">Admins</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['active_users']; ?></h4>
                                <p class="mb-0">Active</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['recent_registrations']; ?></h4>
                                <p class="mb-0">Recent (30d)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                       placeholder="Name, username, or email">
                            </div>
                            <div class="col-md-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="all" <?php echo $roleFilter == 'all' ? 'selected' : ''; ?>>All Roles</option>
                                    <option value="student" <?php echo $roleFilter == 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="faculty" <?php echo $roleFilter == 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                    <option value="admin" <?php echo $roleFilter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $statusFilter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Users (<?php echo $totalCount; ?> total)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($users)): ?>
                            <div class="text-center p-4">
                                <i class="bi bi-people display-1 text-muted"></i>
                                <p class="text-muted mt-3">No users found matching your criteria.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <i class="bi bi-person-circle display-6 text-<?php 
                                                                echo $user['role'] == 'admin' ? 'danger' : 
                                                                    ($user['role'] == 'faculty' ? 'success' : 'primary'); 
                                                            ?>"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($user['username']); ?> | 
                                                                <?php echo htmlspecialchars($user['email']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $user['role'] == 'admin' ? 'danger' : 
                                                            ($user['role'] == 'faculty' ? 'success' : 'primary'); 
                                                    ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="viewUser(<?php echo $user['id']; ?>)">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" 
                                                                onclick="editUser(<?php echo $user['id']; ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Are you sure you want to change this user\'s status?')">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                                                                <button type="submit" name="toggle_status" 
                                                                        class="btn btn-outline-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>">
                                                                    <i class="bi bi-<?php echo $user['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                                </button>
                                                            </form>
                                                            <?php if ($user['role'] != 'admin'): ?>
                                                                <form method="POST" style="display: inline;" 
                                                                      onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <button type="submit" name="delete_user" class="btn btn-outline-danger">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Users pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchTerm); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('wrapper').classList.toggle('toggled');
        });

        // User management functions
        function viewUser(userId) {
            // TODO: Implement user view modal or redirect to user details page
            alert('View user functionality to be implemented');
        }

        function editUser(userId) {
            // TODO: Implement user edit modal or redirect to user edit page
            alert('Edit user functionality to be implemented');
        }
    </script>
</body>
</html>