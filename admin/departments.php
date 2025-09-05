<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle form submissions
$success = '';
$error = '';

// Handle department creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_department'])) {
    $name = trim($_POST['name']);
    $code = trim(strtoupper($_POST['code']));
    $description = trim($_POST['description']);
    $headId = !empty($_POST['head_id']) ? (int)$_POST['head_id'] : null;
    
    if (empty($name) || empty($code)) {
        $error = "Department name and code are required.";
    } else {
        // Check if code already exists
        $existing = fetchSingleRow($conn, "SELECT id FROM departments WHERE code = ?", "s", [$code]);
        
        if ($existing) {
            $error = "Department code already exists.";
        } else {
            $sql = "INSERT INTO departments (name, code, description, head_id) VALUES (?, ?, ?, ?)";
            $result = updateData($conn, $sql, "sssi", [$name, $code, $description, $headId]);
            
            if ($result > 0) {
                $success = "Department created successfully.";
            } else {
                $error = "Failed to create department.";
            }
        }
    }
}

// Handle department update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_department'])) {
    $id = (int)$_POST['department_id'];
    $name = trim($_POST['name']);
    $code = trim(strtoupper($_POST['code']));
    $description = trim($_POST['description']);
    $headId = !empty($_POST['head_id']) ? (int)$_POST['head_id'] : null;
    
    if (empty($name) || empty($code)) {
        $error = "Department name and code are required.";
    } else {
        // Check if code already exists for different department
        $existing = fetchSingleRow($conn, "SELECT id FROM departments WHERE code = ? AND id != ?", "si", [$code, $id]);
        
        if ($existing) {
            $error = "Department code already exists.";
        } else {
            $sql = "UPDATE departments SET name = ?, code = ?, description = ?, head_id = ? WHERE id = ?";
            $result = updateData($conn, $sql, "sssii", [$name, $code, $description, $headId, $id]);
            
            if ($result >= 0) {
                $success = "Department updated successfully.";
            } else {
                $error = "Failed to update department.";
            }
        }
    }
}

// Handle department status toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $id = (int)$_POST['department_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;
    
    $sql = "UPDATE departments SET is_active = ? WHERE id = ?";
    $result = updateData($conn, $sql, "ii", [$newStatus, $id]);
    
    if ($result >= 0) {
        $success = "Department status updated successfully.";
    } else {
        $error = "Failed to update department status.";
    }
}

// Get departments with statistics
$departmentSql = "SELECT d.id, d.name, d.code, d.description, d.head_id, d.created_at,
                         COALESCE(d.is_active, 1) as is_active,
                         u.full_name as head_name,
                         COUNT(DISTINCT c.id) as course_count,
                         COUNT(DISTINCT uf.id) as faculty_count,
                         COUNT(DISTINCT us.id) as student_count
                  FROM departments d
                  LEFT JOIN users u ON d.head_id = u.id
                  LEFT JOIN courses c ON d.name = c.department AND c.is_active = 1
                  LEFT JOIN users uf ON d.name = uf.department AND uf.role = 'faculty' AND uf.is_active = 1
                  LEFT JOIN users us ON d.name = us.department AND us.role = 'student' AND us.is_active = 1
                  GROUP BY d.id, d.name, d.code, d.description, d.head_id, d.created_at, d.is_active, u.full_name
                  ORDER BY d.name";
$departments = fetchMultipleRows($conn, $departmentSql);

// Get faculty members for head selection
$facultySql = "SELECT id, full_name, department FROM users WHERE role = 'faculty' AND is_active = 1 ORDER BY full_name";
$faculty = fetchMultipleRows($conn, $facultySql);

// Get overall statistics
$stats = fetchSingleRow($conn, "SELECT 
    COUNT(*) as total_departments,
    SUM(CASE WHEN COALESCE(is_active, 1) = 1 THEN 1 ELSE 0 END) as active_departments,
    COUNT(DISTINCT head_id) as departments_with_heads
    FROM departments");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
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
                            <li><a class="dropdown-item" href="../profile.php"><i class="bi bi-person"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../php/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i> User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="courses.php">
                                <i class="bi bi-book"></i> Course Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="departments.php">
                                <i class="bi bi-building"></i> Departments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="schedule.php">
                                <i class="bi bi-calendar-week"></i> Class Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notices.php">
                                <i class="bi bi-megaphone"></i> Notice Board
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="events.php">
                                <i class="bi bi-calendar-event"></i> Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-building"></i> Department Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                                <i class="bi bi-plus"></i> Add Department
                            </button>
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
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4 class="mb-0"><?php echo $stats['total_departments']; ?></h4>
                                <small>Total Departments</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4 class="mb-0"><?php echo $stats['active_departments']; ?></h4>
                                <small>Active Departments</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4 class="mb-0"><?php echo $stats['departments_with_heads']; ?></h4>
                                <small>With Department Heads</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Departments Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-table"></i> Departments Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Department</th>
                                        <th>Code</th>
                                        <th>Department Head</th>
                                        <th>Faculty</th>
                                        <th>Students</th>
                                        <th>Courses</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($departments)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bi bi-building display-4"></i>
                                                <p class="mt-2">No departments found. Create your first department to get started.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($departments as $dept): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($dept['name']); ?></strong>
                                                    <?php if (!empty($dept['description'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($dept['description'], 0, 60)); ?><?php echo strlen($dept['description']) > 60 ? '...' : ''; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($dept['code']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($dept['head_name']): ?>
                                                        <i class="bi bi-person-check text-success"></i>
                                                        <?php echo htmlspecialchars($dept['head_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="bi bi-person-dash"></i> No head assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?php echo $dept['faculty_count']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?php echo $dept['student_count']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?php echo $dept['course_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($dept['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary" onclick="editDepartment(<?php echo $dept['id']; ?>)" data-bs-toggle="modal" data-bs-target="#editDepartmentModal">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                                                            <input type="hidden" name="current_status" value="<?php echo $dept['is_active']; ?>">
                                                            <button type="submit" name="toggle_status" class="btn btn-outline-<?php echo $dept['is_active'] ? 'warning' : 'success'; ?>" 
                                                                    onclick="return confirm('Are you sure you want to <?php echo $dept['is_active'] ? 'deactivate' : 'activate'; ?> this department?')">
                                                                <i class="bi bi-<?php echo $dept['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Department Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Department Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="code" class="form-label">Department Code *</label>
                            <input type="text" class="form-control" id="code" name="code" maxlength="10" required style="text-transform: uppercase;">
                            <small class="form-text text-muted">Short code for the department (e.g., CS, EE, MATH)</small>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="head_id" class="form-label">Department Head</label>
                            <select class="form-select" id="head_id" name="head_id">
                                <option value="">Select Department Head (Optional)</option>
                                <?php foreach ($faculty as $f): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['full_name']); ?> 
                                        <?php if ($f['department']): ?>(<?php echo htmlspecialchars($f['department']); ?>)<?php endif; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_department" class="btn btn-primary">Create Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_department_id" name="department_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Department Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_code" class="form-label">Department Code *</label>
                            <input type="text" class="form-control" id="edit_code" name="code" maxlength="10" required style="text-transform: uppercase;">
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_head_id" class="form-label">Department Head</label>
                            <select class="form-select" id="edit_head_id" name="head_id">
                                <option value="">Select Department Head (Optional)</option>
                                <?php foreach ($faculty as $f): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['full_name']); ?> 
                                        <?php if ($f['department']): ?>(<?php echo htmlspecialchars($f['department']); ?>)<?php endif; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_department" class="btn btn-primary">Update Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        if (typeof setActiveNavItem === 'function') {
            setActiveNavItem('departments.php');
        }

        // Department data for editing
        const departments = <?php echo json_encode($departments); ?>;

        function editDepartment(id) {
            const dept = departments.find(d => d.id == id);
            if (dept) {
                document.getElementById('edit_department_id').value = dept.id;
                document.getElementById('edit_name').value = dept.name;
                document.getElementById('edit_code').value = dept.code;
                document.getElementById('edit_description').value = dept.description || '';
                document.getElementById('edit_head_id').value = dept.head_id || '';
            }
        }

        // Auto uppercase department codes
        document.getElementById('code').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        document.getElementById('edit_code').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>