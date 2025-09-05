<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Get all class schedules with comprehensive information
$scheduleSql = "SELECT s.id, s.day_of_week, s.start_time, s.end_time, s.room_number,
                       c.course_code, c.course_name, c.id as course_id, c.department,
                       u.full_name as faculty_name, u.id as faculty_id,
                       COUNT(ce.id) as enrolled_students,
                       s.semester, s.academic_year
                FROM class_schedule s
                JOIN courses c ON s.course_id = c.id
                JOIN users u ON s.faculty_id = u.id
                LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
                WHERE s.is_active = 1 AND c.is_active = 1
                GROUP BY s.id, s.day_of_week, s.start_time, s.end_time, s.room_number,
                         c.course_code, c.course_name, c.id, c.department,
                         u.full_name, u.id, s.semester, s.academic_year
                ORDER BY 
                    CASE s.day_of_week 
                        WHEN 'Monday' THEN 1 
                        WHEN 'Tuesday' THEN 2 
                        WHEN 'Wednesday' THEN 3 
                        WHEN 'Thursday' THEN 4 
                        WHEN 'Friday' THEN 5 
                        WHEN 'Saturday' THEN 6 
                        WHEN 'Sunday' THEN 7 
                    END,
                    s.start_time, c.course_code";
$allSchedules = fetchMultipleRows($conn, $scheduleSql);

// Organize schedule by day for grid view
$weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$scheduleByDay = [];
foreach ($weekDays as $day) {
    $scheduleByDay[$day] = [];
}

foreach ($allSchedules as $class) {
    $scheduleByDay[$class['day_of_week']][] = $class;
}

// Get today's classes
$today = date('l'); // Full day name (e.g., Monday)
$todayClasses = $scheduleByDay[$today] ?? [];

// Get statistics
$stats = fetchSingleRow($conn, "SELECT 
    COUNT(DISTINCT s.id) as total_classes,
    COUNT(DISTINCT s.course_id) as courses_with_schedule,
    COUNT(DISTINCT s.faculty_id) as faculty_teaching,
    COUNT(DISTINCT s.room_number) as rooms_used,
    COUNT(DISTINCT CONCAT(s.day_of_week, s.start_time)) as unique_time_slots
    FROM class_schedule s 
    WHERE s.is_active = 1");

// Get room utilization
$roomUtilization = fetchMultipleRows($conn, "SELECT 
    room_number,
    COUNT(*) as classes_count,
    GROUP_CONCAT(DISTINCT day_of_week ORDER BY 
        CASE day_of_week 
            WHEN 'Monday' THEN 1 
            WHEN 'Tuesday' THEN 2 
            WHEN 'Wednesday' THEN 3 
            WHEN 'Thursday' THEN 4 
            WHEN 'Friday' THEN 5 
            WHEN 'Saturday' THEN 6 
            WHEN 'Sunday' THEN 7 
        END SEPARATOR ', ') as days_used
    FROM class_schedule 
    WHERE is_active = 1 
    GROUP BY room_number 
    ORDER BY classes_count DESC");

// Get department schedule summary
$departmentSummary = fetchMultipleRows($conn, "SELECT 
    c.department,
    COUNT(DISTINCT s.id) as total_classes,
    COUNT(DISTINCT c.id) as courses_count,
    COUNT(DISTINCT s.faculty_id) as faculty_count,
    SUM(CASE WHEN ce.status = 'enrolled' THEN 1 ELSE 0 END) as total_students
    FROM class_schedule s
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN course_enrollments ce ON c.id = ce.course_id
    WHERE s.is_active = 1 AND c.is_active = 1
    GROUP BY c.department
    ORDER BY total_classes DESC");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule Management - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .schedule-card { 
            transition: transform 0.2s; 
            cursor: pointer; 
        }
        .schedule-card:hover { 
            transform: translateY(-2px); 
        }
        .time-slot { 
            font-weight: bold; 
            color: #0d6efd; 
        }
        .room-badge {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        .department-badge {
            font-size: 0.75em;
        }
    </style>
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
                            <a class="nav-link" href="departments.php">
                                <i class="bi bi-building"></i> Departments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="schedule.php">
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
                        <i class="bi bi-calendar-week"></i> Class Schedule Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print Schedule
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                <i class="bi bi-plus"></i> Add Class
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5 class="mb-0"><?php echo $stats['total_classes']; ?></h5>
                                <small>Total Classes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5 class="mb-0"><?php echo $stats['courses_with_schedule']; ?></h5>
                                <small>Courses</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h5 class="mb-0"><?php echo $stats['faculty_teaching']; ?></h5>
                                <small>Faculty</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h5 class="mb-0"><?php echo $stats['rooms_used']; ?></h5>
                                <small>Rooms Used</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body text-center">
                                <h5 class="mb-0"><?php echo $stats['unique_time_slots']; ?></h5>
                                <small>Time Slots</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Schedule Overview -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-day"></i> Today's Classes - <?php echo $today . ', ' . date('F j, Y'); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todayClasses)): ?>
                            <div class="text-center py-3">
                                <i class="bi bi-calendar-x display-4 text-muted"></i>
                                <h5 class="mt-3">No classes scheduled for today</h5>
                                <p class="text-muted">All faculty and students have a break today!</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($todayClasses as $class): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card schedule-card border-start border-4 border-primary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($class['course_code']); ?></h6>
                                                        <p class="card-text small mb-1"><?php echo htmlspecialchars($class['course_name']); ?></p>
                                                        <span class="badge bg-light text-dark department-badge"><?php echo htmlspecialchars($class['department']); ?></span>
                                                    </div>
                                                </div>
                                                <hr class="my-2">
                                                <div class="small">
                                                    <div class="time-slot">
                                                        <i class="bi bi-clock"></i> 
                                                        <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                                        <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                                    </div>
                                                    <div class="text-muted mt-1">
                                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($class['faculty_name']); ?>
                                                    </div>
                                                    <div class="text-muted mt-1">
                                                        <i class="bi bi-geo-alt"></i> 
                                                        <span class="room-badge"><?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?></span>
                                                    </div>
                                                    <div class="text-muted mt-1">
                                                        <i class="bi bi-people"></i> <?php echo $class['enrolled_students']; ?> students
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Weekly Schedule Grid -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-calendar-week"></i> Weekly Schedule Overview
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 12%;">Day</th>
                                                <th>Classes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($weekDays as $day): ?>
                                                <tr class="<?php echo ($day === $today) ? 'table-primary' : ''; ?>">
                                                    <td class="align-middle">
                                                        <strong><?php echo $day; ?></strong>
                                                        <?php if ($day === $today): ?>
                                                            <br><small class="badge bg-primary">Today</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (empty($scheduleByDay[$day])): ?>
                                                            <div class="text-muted text-center py-2">
                                                                <i class="bi bi-calendar-x"></i> No classes scheduled
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="row g-2">
                                                                <?php foreach ($scheduleByDay[$day] as $class): ?>
                                                                    <div class="col-md-6 col-lg-4">
                                                                        <div class="card card-body p-2 small">
                                                                            <div class="d-flex justify-content-between align-items-start">
                                                                                <div>
                                                                                    <strong><?php echo htmlspecialchars($class['course_code']); ?></strong>
                                                                                    <div class="text-muted">
                                                                                        <?php echo date('g:i A', strtotime($class['start_time'])); ?>-<?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                                                                    </div>
                                                                                </div>
                                                                                <span class="room-badge"><?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Department Summary -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-building"></i> Department Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($departmentSummary as $dept): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                        <div>
                                            <strong><?php echo htmlspecialchars($dept['department']); ?></strong>
                                            <div class="small text-muted">
                                                <?php echo $dept['courses_count']; ?> courses, <?php echo $dept['faculty_count']; ?> faculty
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary"><?php echo $dept['total_classes']; ?> classes</span>
                                            <div class="small text-muted"><?php echo $dept['total_students']; ?> students</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Room Utilization -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-door-open"></i> Room Utilization
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($roomUtilization)): ?>
                                    <p class="text-muted">No rooms currently scheduled.</p>
                                <?php else: ?>
                                    <?php foreach (array_slice($roomUtilization, 0, 8) as $room): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong>Room <?php echo htmlspecialchars($room['room_number']); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars($room['days_used']); ?></div>
                                            </div>
                                            <span class="badge bg-success"><?php echo $room['classes_count']; ?> classes</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Schedule Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-table"></i> Complete Schedule Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Course</th>
                                        <th>Faculty</th>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Room</th>
                                        <th>Students</th>
                                        <th>Department</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allSchedules as $class): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($class['course_code']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($class['course_name']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($class['faculty_name']); ?></td>
                                            <td>
                                                <span class="<?php echo ($class['day_of_week'] === $today) ? 'badge bg-primary' : ''; ?>">
                                                    <?php echo $class['day_of_week']; ?>
                                                </span>
                                            </td>
                                            <td class="time-slot">
                                                <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                            </td>
                                            <td>
                                                <span class="room-badge"><?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?php echo $class['enrolled_students']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark department-badge">
                                                    <?php echo htmlspecialchars($class['department']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Schedule Modal (placeholder for future functionality) -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Class Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Schedule management functionality will be implemented here.</p>
                    <p>For now, please manage class schedules through the database or contact system administrator.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        if (typeof setActiveNavItem === 'function') {
            setActiveNavItem('schedule.php');
        }
    </script>
</body>
</html>