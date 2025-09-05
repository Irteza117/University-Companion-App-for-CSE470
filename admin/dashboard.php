<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Get system statistics
$sql = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1) as total_students,
    (SELECT COUNT(*) FROM users WHERE role = 'faculty' AND is_active = 1) as total_faculty,
    (SELECT COUNT(*) FROM courses WHERE academic_year = '2024') as total_courses,
    (SELECT COUNT(*) FROM notices WHERE is_active = 1) as total_notices";
$stats = fetchSingleRow($conn, $sql);

// Get recent registrations
$sql = "SELECT full_name, role, created_at FROM users 
        WHERE is_active = 1 
        ORDER BY created_at DESC LIMIT 5";
$recentUsers = fetchMultipleRows($conn, $sql);

// Get recent notices
$sql = "SELECT n.title, n.created_at, u.full_name as author
        FROM notices n
        JOIN users u ON n.author_id = u.id
        WHERE n.is_active = 1
        ORDER BY n.created_at DESC LIMIT 5";
$recentNotices = fetchMultipleRows($conn, $sql);

// Get upcoming events
$sql = "SELECT e.title, e.event_date, e.location, u.full_name as organizer
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        WHERE e.is_active = 1 AND e.event_date >= CURDATE()
        ORDER BY e.event_date ASC LIMIT 5";
$upcomingEvents = fetchMultipleRows($conn, $sql);

// Get course enrollment stats
$sql = "SELECT c.course_code, c.course_name, COUNT(ce.student_id) as enrollment_count
        FROM courses c
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
        GROUP BY c.id, c.course_code, c.course_name
        ORDER BY enrollment_count DESC LIMIT 5";
$popularCourses = fetchMultipleRows($conn, $sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Companion</title>
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
                            <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
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
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="student_assignments.php">
                                <i class="bi bi-person-plus"></i> Student Assignments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="departments.php">
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
                    <h1 class="h2">Admin Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</small>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Students</h5>
                                        <h2><?php echo $stats['total_students'] ?? 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-people display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Faculty</h5>
                                        <h2><?php echo $stats['total_faculty'] ?? 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-person-badge display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Courses</h5>
                                        <h2><?php echo $stats['total_courses'] ?? 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-book display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Active Notices</h5>
                                        <h2><?php echo $stats['total_notices'] ?? 0; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-megaphone display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Registrations -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="bi bi-person-plus"></i> Recent Registrations</h5>
                                <a href="users.php" class="btn btn-outline-primary btn-sm">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentUsers)): ?>
                                    <p class="text-muted">No recent registrations.</p>
                                <?php else: ?>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo ucfirst($user['role']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Notices -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="bi bi-megaphone"></i> Recent Notices</h5>
                                <a href="notices.php" class="btn btn-outline-primary btn-sm">Manage</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentNotices)): ?>
                                    <p class="text-muted">No recent notices.</p>
                                <?php else: ?>
                                    <?php foreach ($recentNotices as $notice): ?>
                                        <div class="notice-card card mb-2">
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($notice['title']); ?></h6>
                                                <small class="text-muted">
                                                    By <?php echo htmlspecialchars($notice['author']); ?> • 
                                                    <?php echo date('M j, Y', strtotime($notice['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Upcoming Events -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="bi bi-calendar-event"></i> Upcoming Events</h5>
                                <a href="events.php" class="btn btn-outline-primary btn-sm">Manage</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($upcomingEvents)): ?>
                                    <p class="text-muted">No upcoming events.</p>
                                <?php else: ?>
                                    <?php foreach ($upcomingEvents as $event): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($event['title']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($event['location'] ?? 'TBA'); ?> • 
                                                    Organized by <?php echo htmlspecialchars($event['organizer']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Popular Courses -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-graph-up"></i> Course Enrollments</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($popularCourses)): ?>
                                    <p class="text-muted">No course data available.</p>
                                <?php else: ?>
                                    <?php foreach ($popularCourses as $course): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($course['course_code']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($course['course_name']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary"><?php echo $course['enrollment_count']; ?> students</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <a href="users.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-person-plus"></i> Add User
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="courses.php" class="btn btn-outline-success w-100">
                                            <i class="bi bi-book"></i> Add Course
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="student_assignments.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-person-plus"></i> Assign Students
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="notices.php" class="btn btn-outline-info w-100">
                                            <i class="bi bi-megaphone"></i> Post Notice
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="events.php" class="btn btn-outline-warning w-100">
                                            <i class="bi bi-calendar-plus"></i> Create Event
                                        </a>
                                    </div>
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
        setActiveNavItem('dashboard.php');
    </script>
</body>
</html>