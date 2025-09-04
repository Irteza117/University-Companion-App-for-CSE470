<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get faculty's assigned courses
$sql = "SELECT c.course_code, c.course_name, c.credit_hours, COUNT(ce.student_id) as student_count
        FROM course_assignments ca
        JOIN courses c ON ca.course_id = c.id
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
        WHERE ca.faculty_id = ?
        GROUP BY c.id, c.course_code, c.course_name, c.credit_hours";
$assignedCourses = fetchMultipleRows($conn, $sql, "i", [$userId]);

// Get comprehensive material statistics
$sql = "SELECT 
        COUNT(*) as total_materials,
        SUM(CASE WHEN cm.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_materials,
        SUM(cm.download_count) as total_downloads,
        AVG(cm.download_count) as avg_downloads
        FROM course_materials cm
        JOIN courses c ON cm.course_id = c.id
        WHERE c.faculty_id = ? AND cm.is_active = 1";
$materialStats = fetchSingleRow($conn, $sql, "i", [$userId]);
$totalMaterials = $materialStats['total_materials'] ?? 0;
$recentMaterials = $materialStats['recent_materials'] ?? 0;
$totalDownloads = $materialStats['total_downloads'] ?? 0;
$avgDownloads = round($materialStats['avg_downloads'] ?? 0, 1);

// Get material breakdown by course
$sql = "SELECT c.course_code, c.course_name, 
        COUNT(cm.id) as material_count,
        SUM(cm.download_count) as course_downloads
        FROM courses c
        LEFT JOIN course_materials cm ON c.id = cm.course_id AND cm.is_active = 1
        WHERE c.faculty_id = ?
        GROUP BY c.id, c.course_code, c.course_name
        ORDER BY material_count DESC";
$materialsByCourse = fetchMultipleRows($conn, $sql, "i", [$userId]);

// Get recent material activities
$sql = "SELECT cm.title, cm.created_at, cm.download_count, c.course_code
        FROM course_materials cm
        JOIN courses c ON cm.course_id = c.id
        WHERE c.faculty_id = ? AND cm.is_active = 1
        ORDER BY cm.created_at DESC LIMIT 5";
$recentMaterialActivity = fetchMultipleRows($conn, $sql, "i", [$userId]);

// Get assignment statistics
$sql = "SELECT 
        COUNT(*) as total_assignments,
        COUNT(CASE WHEN a.due_date >= NOW() THEN 1 END) as upcoming_assignments,
        COUNT(CASE WHEN a.due_date < NOW() THEN 1 END) as past_assignments
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE c.faculty_id = ? AND a.is_active = 1";
$assignmentStats = fetchSingleRow($conn, $sql, "i", [$userId]);
$totalAssignments = $assignmentStats['total_assignments'] ?? 0;
$upcomingAssignments = $assignmentStats['upcoming_assignments'] ?? 0;
$pastAssignments = $assignmentStats['past_assignments'] ?? 0;

// Get feedback statistics
$sql = "SELECT 
        COUNT(*) as total_feedback,
        AVG(cf.rating) as avg_rating
        FROM course_feedback cf
        JOIN courses c ON cf.course_id = c.id
        WHERE c.faculty_id = ?";
$feedbackStats = fetchSingleRow($conn, $sql, "i", [$userId]);
$totalFeedback = $feedbackStats['total_feedback'] ?? 0;
$avgRating = round($feedbackStats['avg_rating'] ?? 0, 1);

// Get recent assignments
$sql = "SELECT a.title, a.due_date, c.course_name, c.course_code
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN course_assignments ca ON c.id = ca.course_id
        WHERE ca.faculty_id = ? AND a.is_active = 1
        ORDER BY a.created_at DESC LIMIT 5";
$recentAssignments = fetchMultipleRows($conn, $sql, "i", [$userId]);

// Get today's schedule
$today = date('l');
$sql = "SELECT cs.start_time, cs.end_time, cs.room_number, c.course_name, c.course_code
        FROM class_schedule cs
        JOIN courses c ON cs.course_id = c.id
        WHERE cs.faculty_id = ? AND cs.day_of_week = ? AND cs.is_active = 1
        ORDER BY cs.start_time";
$todaySchedule = fetchMultipleRows($conn, $sql, "is", [$userId, $today]);

// Get pending submissions count
$sql = "SELECT COUNT(*) as pending_count FROM assignment_submissions asub
        JOIN assignments a ON asub.assignment_id = a.id
        JOIN course_assignments ca ON a.course_id = ca.course_id
        WHERE ca.faculty_id = ? AND asub.status = 'submitted'";
$pendingStats = fetchSingleRow($conn, $sql, "i", [$userId]);
$pendingSubmissions = $pendingStats['pending_count'] ?? 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - University Companion</title>
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
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
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
                                <i class="bi bi-calendar-event"></i> Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php">
                                <i class="bi bi-people"></i> Students
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
                    <h1 class="h2">Faculty Dashboard</h1>
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
                                        <h5 class="card-title">Assigned Courses</h5>
                                        <h2><?php echo count($assignedCourses); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-book display-4"></i>
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
                                        <h5 class="card-title">Course Materials</h5>
                                        <h2><?php echo $totalMaterials; ?></h2>
                                        <small>+<?php echo $recentMaterials; ?> this month</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-file-earmark-text display-4"></i>
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
                                        <h5 class="card-title">Total Downloads</h5>
                                        <h2><?php echo $totalDownloads; ?></h2>
                                        <small>Avg: <?php echo $avgDownloads; ?> per material</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-download display-4"></i>
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
                                        <h5 class="card-title">Avg Rating</h5>
                                        <h2><?php echo $avgRating ?: 'N/A'; ?></h2>
                                        <small><?php echo $totalFeedback; ?> reviews</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-star display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Students</h5>
                                        <h2><?php echo array_sum(array_column($assignedCourses, 'student_count')); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-people display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Assignments</h5>
                                        <h2><?php echo $totalAssignments; ?></h2>
                                        <small><?php echo $upcomingAssignments; ?> upcoming</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-clipboard-check display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-dark">
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
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Pending Reviews</h5>
                                        <h2><?php echo $pendingSubmissions; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-hourglass-split display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Material Statistics Section -->
                <div class="row mb-4">
                    <!-- Materials by Course -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="bi bi-bar-chart"></i> Materials by Course</h5>
                                <a href="materials.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus"></i> Upload
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($materialsByCourse)): ?>
                                    <p class="text-muted">No materials uploaded yet.</p>
                                <?php else: ?>
                                    <?php foreach ($materialsByCourse as $course): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($course['course_code']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($course['course_name']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary"><?php echo $course['material_count']; ?> materials</span><br>
                                                <small class="text-muted"><?php echo $course['course_downloads']; ?> downloads</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Material Activity -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-activity"></i> Recent Material Activity</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentMaterialActivity)): ?>
                                    <p class="text-muted">No recent material activity.</p>
                                <?php else: ?>
                                    <?php foreach ($recentMaterialActivity as $material): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                            <div>
                                                <strong><?php echo htmlspecialchars($material['title']); ?></strong><br>
                                                <small class="text-muted">
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($material['course_code']); ?></span>
                                                    <?php echo date('M j, Y', strtotime($material['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-success">
                                                    <i class="bi bi-download"></i> <?php echo $material['download_count']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Today's Schedule -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="bi bi-calendar-week"></i> Today's Schedule (<?php echo $today; ?>)</h5>
                                <a href="schedule.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus"></i> Manage
                                </a>
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
                            </div>
                        </div>
                    </div>

                    <!-- Recent Assignments -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="bi bi-clipboard-check"></i> Recent Assignments</h5>
                                <a href="assignments.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus"></i> Create New
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentAssignments)): ?>
                                    <p class="text-muted">No assignments created yet.</p>
                                <?php else: ?>
                                    <?php foreach ($recentAssignments as $assignment): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($assignment['title']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($assignment['course_code']); ?> - <?php echo htmlspecialchars($assignment['course_name']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    Due: <?php echo date('M j, Y', strtotime($assignment['due_date'])); ?>
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
                    <!-- Assigned Courses -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-book"></i> Assigned Courses</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($assignedCourses)): ?>
                                    <p class="text-muted">No courses assigned yet.</p>
                                <?php else: ?>
                                    <?php foreach ($assignedCourses as $course): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong><?php echo htmlspecialchars($course['course_code']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($course['course_name']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    Credits: <?php echo $course['credit_hours']; ?><br>
                                                    Students: <?php echo $course['student_count']; ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <a href="courses.php" class="btn btn-outline-primary btn-sm">Manage Courses</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="materials.php" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-plus"></i> Upload Course Material
                                    </a>
                                    <a href="assignments.php" class="btn btn-outline-success">
                                        <i class="bi bi-clipboard-plus"></i> Create Assignment
                                    </a>
                                    <a href="notices.php" class="btn btn-outline-info">
                                        <i class="bi bi-megaphone"></i> Post Notice
                                    </a>
                                    <a href="events.php" class="btn btn-outline-warning">
                                        <i class="bi bi-calendar-plus"></i> Create Event
                                    </a>
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