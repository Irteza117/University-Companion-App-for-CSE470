<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();

// Get search parameters
$searchTerm = $_GET['search'] ?? '';
$departmentFilter = $_GET['department'] ?? '';

// Build search query
$sql = "SELECT u.id, u.full_name, u.email, u.phone, 
               u.department as department_name,
               GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code) as courses
        FROM users u
        LEFT JOIN course_assignments ca ON u.id = ca.faculty_id
        LEFT JOIN courses c ON ca.course_id = c.id AND c.academic_year = '2024'
        WHERE u.role = 'faculty' AND u.is_active = 1";

$params = [];
$types = "";

if (!empty($searchTerm)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.department LIKE ?)";
    $searchParam = '%' . $searchTerm . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

if (!empty($departmentFilter)) {
    $sql .= " AND u.department = ?";
    $params[] = $departmentFilter;
    $types .= "s";
}

$sql .= " GROUP BY u.id, u.full_name, u.email, u.phone, u.department
          ORDER BY u.full_name";

$faculty = fetchMultipleRows($conn, $sql, $types, $params);

// Get all departments for filter dropdown
$departmentSql = "SELECT DISTINCT department as name FROM users WHERE role = 'faculty' AND department IS NOT NULL AND department != '' ORDER BY department";
$departments = fetchMultipleRows($conn, $departmentSql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Directory - University Companion</title>
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
                            <a class="nav-link" href="courses.php">
                                <i class="bi bi-book"></i> My Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="schedule.php">
                                <i class="bi bi-calendar-week"></i> Class Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="assignments.php">
                                <i class="bi bi-clipboard-check"></i> Assignments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="materials.php">
                                <i class="bi bi-file-earmark-text"></i> Course Materials
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notices.php">
                                <i class="bi bi-megaphone"></i> Notice Board
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="events.php">
                                <i class="bi bi-calendar-event"></i> Upcoming Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="teachers.php">
                                <i class="bi bi-people"></i> Teacher Directory
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="feedback.php">
                                <i class="bi bi-chat-square-text"></i> Course Feedback
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-people"></i> Teacher Directory
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Find faculty contact information</small>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           name="search" 
                                           placeholder="Search by name, email, or department..." 
                                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="department">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['name']); ?>" 
                                                <?php echo ($departmentFilter == $dept['name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Faculty Cards -->
                <div class="row">
                    <?php if (empty($faculty)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="bi bi-person-x display-1 text-muted"></i>
                                <h4 class="mt-3">No faculty found</h4>
                                <p class="text-muted">Try adjusting your search criteria</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($faculty as $teacher): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 faculty-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="faculty-avatar me-3">
                                                <i class="bi bi-person-circle display-4 text-primary"></i>
                                            </div>
                                            <div>
                                                <h5 class="card-title mb-1">
                                                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    <small><?php echo htmlspecialchars($teacher['department_name']); ?></small>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="contact-info">
                                            <div class="mb-2">
                                                <i class="bi bi-envelope text-primary"></i>
                                                <small class="ms-2">
                                                    <a href="mailto:<?php echo htmlspecialchars($teacher['email']); ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($teacher['email']); ?>
                                                    </a>
                                                </small>
                                            </div>
                                            
                                            <?php if (!empty($teacher['phone'])): ?>
                                                <div class="mb-2">
                                                    <i class="bi bi-phone text-primary"></i>
                                                    <small class="ms-2">
                                                        <a href="tel:<?php echo htmlspecialchars($teacher['phone']); ?>" 
                                                           class="text-decoration-none">
                                                            <?php echo htmlspecialchars($teacher['phone']); ?>
                                                        </a>
                                                    </small>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($teacher['courses'])): ?>
                                                <div class="mb-2">
                                                    <i class="bi bi-book text-primary"></i>
                                                    <small class="ms-2">
                                                        <strong>Courses:</strong>
                                                        <?php echo htmlspecialchars($teacher['courses']); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0">
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <a href="mailto:<?php echo htmlspecialchars($teacher['email']); ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-envelope"></i> Email
                                            </a>
                                            <?php if (!empty($teacher['phone'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($teacher['phone']); ?>" 
                                                   class="btn btn-outline-success btn-sm">
                                                    <i class="bi bi-phone"></i> Call
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Faculty Statistics -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up"></i> Faculty Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="statistic-item">
                                    <h3 class="text-primary"><?php echo count($faculty); ?></h3>
                                    <p class="text-muted mb-0">Faculty Members</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="statistic-item">
                                    <h3 class="text-success"><?php echo count($departments); ?></h3>
                                    <p class="text-muted mb-0">Departments</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="statistic-item">
                                    <h3 class="text-info">
                                        <?php 
                                        $totalCourses = 0;
                                        foreach ($faculty as $teacher) {
                                            if (!empty($teacher['courses'])) {
                                                $totalCourses += count(explode(',', $teacher['courses']));
                                            }
                                        }
                                        echo $totalCourses;
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Courses Offered</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="statistic-item">
                                    <h3 class="text-warning">
                                        <?php 
                                        $avgCourses = $totalCourses > 0 ? round($totalCourses / count($faculty), 1) : 0;
                                        echo $avgCourses;
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Avg Courses/Faculty</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        setActiveNavItem('teachers.php');
    </script>

    <style>
        .faculty-card {
            transition: transform 0.2s;
        }
        .faculty-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .faculty-avatar {
            flex-shrink: 0;
        }
        .contact-info {
            font-size: 0.9rem;
        }
        .statistic-item {
            padding: 1rem;
        }
        .statistic-item h3 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
    </style>
</body>
</html>