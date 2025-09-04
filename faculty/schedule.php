<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get faculty's class schedule
$scheduleSql = "SELECT s.id, s.day_of_week, s.start_time, s.end_time, s.room_number,
                       c.course_code, c.course_name, c.id as course_id,
                       COUNT(ce.id) as enrolled_students
                FROM class_schedule s
                JOIN courses c ON s.course_id = c.id
                LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
                WHERE c.faculty_id = ? AND c.is_active = 1
                GROUP BY s.id, s.day_of_week, s.start_time, s.end_time, s.room_number,
                         c.course_code, c.course_name, c.id
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
                    s.start_time";
$schedule = fetchMultipleRows($conn, $scheduleSql, "i", [$userId]);

// Organize schedule by day
$weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$scheduleByDay = [];
foreach ($weekDays as $day) {
    $scheduleByDay[$day] = [];
}

foreach ($schedule as $class) {
    $scheduleByDay[$class['day_of_week']][] = $class;
}

// Get today's classes
$today = date('l'); // Full day name (e.g., Monday)
$todayClasses = $scheduleByDay[$today] ?? [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule - University Companion</title>
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
                            <a class="nav-link active" href="schedule.php">
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
                    <h1 class="h2">
                        <i class="bi bi-calendar-week"></i> Class Schedule
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Weekly Teaching Schedule</small>
                    </div>
                </div>

                <!-- Today's Schedule Card -->
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
                                <h5 class="mt-3">No classes today</h5>
                                <p class="text-muted">Enjoy your day off!</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($todayClasses as $class): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($class['course_code']); ?></h6>
                                                <p class="card-text mb-2"><?php echo htmlspecialchars($class['course_name']); ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock"></i> 
                                                            <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                                            <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="bi bi-geo-alt"></i> 
                                                            Room <?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="bi bi-people"></i> 
                                                            <?php echo $class['enrolled_students']; ?> students
                                                        </small>
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

                <!-- Weekly Schedule -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-week"></i> Weekly Schedule
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedule)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <h4 class="mt-3">No schedule available</h4>
                                <p class="text-muted">Contact administration to set up your class schedule</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered schedule-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 14%;">Day</th>
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
                                                        <div class="text-muted text-center py-3">
                                                            <i class="bi bi-calendar-x"></i> No classes
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="row">
                                                            <?php foreach ($scheduleByDay[$day] as $class): ?>
                                                                <div class="col-md-6 col-lg-4 mb-2">
                                                                    <div class="schedule-item border rounded p-2 bg-light">
                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                            <div>
                                                                                <strong class="text-primary"><?php echo htmlspecialchars($class['course_code']); ?></strong>
                                                                                <br>
                                                                                <small><?php echo htmlspecialchars($class['course_name']); ?></small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mt-2">
                                                                            <small class="text-muted">
                                                                                <i class="bi bi-clock"></i> 
                                                                                <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                                                                <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                                                            </small>
                                                                            <br>
                                                                            <small class="text-muted">
                                                                                <i class="bi bi-geo-alt"></i> 
                                                                                Room <?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?>
                                                                            </small>
                                                                            <br>
                                                                            <small class="text-muted">
                                                                                <i class="bi bi-people"></i> 
                                                                                <?php echo $class['enrolled_students']; ?> students
                                                                            </small>
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
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Schedule Statistics -->
                <?php if (!empty($schedule)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up"></i> Schedule Statistics
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="statistic-item">
                                        <h3 class="text-primary"><?php echo count($schedule); ?></h3>
                                        <p class="text-muted mb-0">Total Classes/Week</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="statistic-item">
                                        <h3 class="text-success"><?php echo count($todayClasses); ?></h3>
                                        <p class="text-muted mb-0">Classes Today</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="statistic-item">
                                        <h3 class="text-info">
                                            <?php 
                                            $uniqueDays = array_unique(array_column($schedule, 'day_of_week'));
                                            echo count($uniqueDays);
                                            ?>
                                        </h3>
                                        <p class="text-muted mb-0">Teaching Days</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="statistic-item">
                                        <h3 class="text-warning">
                                            <?php 
                                            $totalStudents = array_sum(array_column($schedule, 'enrolled_students'));
                                            echo $totalStudents;
                                            ?>
                                        </h3>
                                        <p class="text-muted mb-0">Weekly Students</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        setActiveNavItem('schedule.php');
    </script>

    <style>
        .schedule-item {
            transition: all 0.2s;
        }
        .schedule-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .schedule-table th {
            background-color: #f8f9fa;
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