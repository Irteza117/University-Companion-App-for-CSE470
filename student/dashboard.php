<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get student's enrolled courses
$sql = "SELECT c.course_code, c.course_name, c.credit_hours, u.full_name as faculty_name
        FROM course_enrollments ce
        JOIN courses c ON ce.course_id = c.id
        LEFT JOIN course_assignments ca ON c.id = ca.course_id
        LEFT JOIN users u ON ca.faculty_id = u.id AND u.role = 'faculty'
        WHERE ce.student_id = ? AND ce.status = 'enrolled'";
$enrolledCourses = fetchMultipleRows($conn, $sql, "i", [$userId]);

// Get recent notices
$sql = "SELECT title, content, created_at FROM notices 
        WHERE (target_audience = 'all' OR target_audience = 'students') 
        AND is_active = 1 
        ORDER BY created_at DESC LIMIT 5";
$recentNotices = fetchMultipleRows($conn, $sql);

// Get upcoming assignments
$sql = "SELECT a.title, a.due_date, c.course_name, c.course_code
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN course_enrollments ce ON c.id = ce.course_id
        WHERE ce.student_id = ? AND a.is_active = 1 AND a.due_date > NOW()
        ORDER BY a.due_date ASC LIMIT 5";
$upcomingAssignments = fetchMultipleRows($conn, $sql, "i", [$userId]);

// Get today's schedule
$today = date('l'); // Gets day name (Monday, Tuesday, etc.)
$sql = "SELECT cs.start_time, cs.end_time, cs.room_number, c.course_name, c.course_code
        FROM class_schedule cs
        JOIN courses c ON cs.course_id = c.id
        JOIN course_enrollments ce ON c.id = ce.course_id
        WHERE ce.student_id = ? AND cs.day_of_week = ? AND cs.is_active = 1
        ORDER BY cs.start_time";
$todaySchedule = fetchMultipleRows($conn, $sql, "is", [$userId, $today]);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - University Companion</title>
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
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="teachers.php">
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
                    <h1 class="h2">Student Dashboard</h1>
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
                                        <h5 class="card-title">Enrolled Courses</h5>
                                        <h2><?php echo count($enrolledCourses); ?></h2>
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
                                        <h5 class="card-title">Pending Assignments</h5>
                                        <h2><?php echo count($upcomingAssignments); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-clipboard-check display-4"></i>
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
                                        <h5 class="card-title">Today's Classes</h5>
                                        <h2><?php echo count($todaySchedule); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-calendar-week display-4"></i>
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
                                        <h5 class="card-title">New Notices</h5>
                                        <h2><?php echo count($recentNotices); ?></h2>
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
                    <!-- Today's Schedule -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-calendar-week"></i> Today's Schedule (<?php echo $today; ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($todaySchedule)): ?>
                                    <p class="text-muted">No classes scheduled for today.</p>
                                <?php else: ?>
                                    <?php foreach ($todaySchedule as $class): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($class['course_code']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($class['course_name']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                                    <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                                </small><br>
                                                <small class="text-muted">Room: <?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <a href="schedule.php" class="btn btn-outline-primary btn-sm">View Full Schedule</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Assignments -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-clipboard-check"></i> Upcoming Assignments</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($upcomingAssignments)): ?>
                                    <p class="text-muted">No upcoming assignments.</p>
                                <?php else: ?>
                                    <?php foreach ($upcomingAssignments as $assignment): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($assignment['title']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($assignment['course_code']); ?> - <?php echo htmlspecialchars($assignment['course_name']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    Due: <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <a href="assignments.php" class="btn btn-outline-primary btn-sm">View All Assignments</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Notices -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-megaphone"></i> Recent Notices</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentNotices)): ?>
                                    <p class="text-muted">No recent notices.</p>
                                <?php else: ?>
                                    <?php foreach ($recentNotices as $notice): ?>
                                        <div class="notice-card card mb-2">
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($notice['title']); ?></h6>
                                                <p class="card-text mb-1 small">
                                                    <?php echo htmlspecialchars(substr($notice['content'], 0, 100)) . (strlen($notice['content']) > 100 ? '...' : ''); ?>
                                                </p>
                                                <small class="notice-date"><?php echo date('M j, Y', strtotime($notice['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <a href="notices.php" class="btn btn-outline-primary btn-sm">View All Notices</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrolled Courses -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-book"></i> Enrolled Courses</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($enrolledCourses)): ?>
                                    <p class="text-muted">No enrolled courses.</p>
                                <?php else: ?>
                                    <?php foreach ($enrolledCourses as $course): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($course['course_code']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($course['course_name']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    Credits: <?php echo $course['credit_hours']; ?><br>
                                                    Faculty: <?php echo htmlspecialchars($course['faculty_name'] ?? 'TBA'); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <a href="courses.php" class="btn btn-outline-primary btn-sm">View All Courses</a>
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