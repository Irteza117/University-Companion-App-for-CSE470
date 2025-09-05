<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get student's class schedule
$sql = "SELECT cs.day_of_week, cs.start_time, cs.end_time, cs.room_number,
               c.course_code, c.course_name, u.full_name as faculty_name
        FROM class_schedule cs
        JOIN courses c ON cs.course_id = c.id
        JOIN course_enrollments ce ON c.id = ce.course_id
        JOIN users u ON cs.faculty_id = u.id
        WHERE ce.student_id = ? AND ce.status = 'enrolled' AND cs.is_active = 1
        ORDER BY 
            CASE cs.day_of_week 
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
            END,
            cs.start_time";
$schedule = fetchMultipleRows($conn, $sql, "i", [$userId]);

// Get today's schedule
$today = date('l');
$todaySchedule = array_filter($schedule, function($class) use ($today) {
    return $class['day_of_week'] === $today;
});

// Get next class
$now = date('H:i:s');
$nextClass = null;
foreach ($todaySchedule as $class) {
    if ($class['start_time'] > $now) {
        $nextClass = $class;
        break;
    }
}

// If no class today, find next class this week
if (!$nextClass) {
    $currentDayNum = date('N'); // 1 = Monday, 7 = Sunday
    for ($i = $currentDayNum + 1; $i <= 7; $i++) {
        $dayName = date('l', strtotime('Monday this week +' . ($i - 1) . ' days'));
        $daySchedule = array_filter($schedule, function($class) use ($dayName) {
            return $class['day_of_week'] === $dayName;
        });
        if (!empty($daySchedule)) {
            $nextClass = reset($daySchedule);
            break;
        }
    }
}

// Organize schedule by day
$weekSchedule = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days as $day) {
    $weekSchedule[$day] = array_filter($schedule, function($class) use ($day) {
        return $class['day_of_week'] === $day;
    });
}

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
    <style>
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        .day-column {
            min-height: 400px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
        }
        .day-header {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .day-header.today {
            background-color: #007bff;
            color: white;
        }
        .class-block {
            background-color: #e7f3ff;
            border: 1px solid #007bff;
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }
        .class-block.current {
            background-color: #d4edda;
            border-color: #28a745;
        }
        .class-block.next {
            background-color: #fff3cd;
            border-color: #ffc107;
        }
        .time-slot {
            font-weight: bold;
            color: #007bff;
        }
        @media (max-width: 768px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }
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
                    <h1 class="h2">
                        <i class="bi bi-calendar-week"></i> My Class Schedule
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printSchedule()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Current Status -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-clock"></i> Current Status
                                </h5>
                                <p class="mb-1"><strong>Today:</strong> <?php echo date('l, F j, Y'); ?></p>
                                <p class="mb-1"><strong>Time:</strong> <span id="currentTime"><?php echo date('g:i A'); ?></span></p>
                                <p class="mb-0">
                                    <strong>Classes Today:</strong> 
                                    <span class="badge bg-primary"><?php echo count($todaySchedule); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-calendar-check"></i> Next Class
                                </h5>
                                <?php if ($nextClass): ?>
                                    <p class="mb-1">
                                        <strong><?php echo htmlspecialchars($nextClass['course_code']); ?></strong>
                                    </p>
                                    <p class="mb-1">
                                        <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($nextClass['start_time'])); ?> - <?php echo date('g:i A', strtotime($nextClass['end_time'])); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($nextClass['room_number'] ?? 'TBA'); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted mb-0">No upcoming classes today</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Schedule Grid -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-table"></i> Weekly Schedule
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedule)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <h4 class="mt-3">No classes scheduled</h4>
                                <p class="text-muted">Your class schedule will appear here once courses are assigned</p>
                            </div>
                        <?php else: ?>
                            <div class="schedule-grid">
                                <?php foreach ($days as $day): ?>
                                    <div class="day-column">
                                        <div class="day-header <?php echo ($day === $today) ? 'today' : ''; ?>">
                                            <?php echo $day; ?>
                                            <?php if ($day === $today): ?>
                                                <br><small>Today</small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (empty($weekSchedule[$day])): ?>
                                            <div class="text-center text-muted mt-3">
                                                <i class="bi bi-calendar-x"></i><br>
                                                <small>No classes</small>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($weekSchedule[$day] as $class): ?>
                                                <?php
                                                $isCurrentClass = false;
                                                $isNextClass = false;
                                                
                                                if ($day === $today) {
                                                    $currentTime = date('H:i:s');
                                                    if ($currentTime >= $class['start_time'] && $currentTime <= $class['end_time']) {
                                                        $isCurrentClass = true;
                                                    } elseif ($nextClass && $class === $nextClass) {
                                                        $isNextClass = true;
                                                    }
                                                }
                                                
                                                $blockClass = 'class-block';
                                                if ($isCurrentClass) $blockClass .= ' current';
                                                elseif ($isNextClass) $blockClass .= ' next';
                                                ?>
                                                <div class="<?php echo $blockClass; ?>">
                                                    <div class="time-slot">
                                                        <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                                        <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                                    </div>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($class['course_code']); ?>
                                                    </div>
                                                    <div class="small">
                                                        <?php echo htmlspecialchars($class['course_name']); ?>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($class['faculty_name']); ?>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?>
                                                    </div>
                                                    <?php if ($isCurrentClass): ?>
                                                        <div class="badge bg-success mt-1">
                                                            <i class="bi bi-play-circle"></i> Current
                                                        </div>
                                                    <?php elseif ($isNextClass): ?>
                                                        <div class="badge bg-warning mt-1">
                                                            <i class="bi bi-clock"></i> Next
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Today's Detailed Schedule -->
                <?php if (!empty($todaySchedule)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-day"></i> Today's Schedule (<?php echo $today; ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php foreach ($todaySchedule as $class): ?>
                                    <?php
                                    $currentTime = date('H:i:s');
                                    $isCurrentClass = $currentTime >= $class['start_time'] && $currentTime <= $class['end_time'];
                                    $isPastClass = $currentTime > $class['end_time'];
                                    $isFutureClass = $currentTime < $class['start_time'];
                                    ?>
                                    <div class="list-group-item <?php echo $isCurrentClass ? 'list-group-item-success' : ($isPastClass ? 'list-group-item-light' : ''); ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($class['course_code'] . ' - ' . $class['course_name']); ?>
                                                    <?php if ($isCurrentClass): ?>
                                                        <span class="badge bg-success ms-2">
                                                            <i class="bi bi-play-circle"></i> In Progress
                                                        </span>
                                                    <?php elseif ($isPastClass): ?>
                                                        <span class="badge bg-secondary ms-2">
                                                            <i class="bi bi-check-circle"></i> Completed
                                                        </span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="mb-1">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($class['faculty_name']); ?> •
                                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?>
                                                </p>
                                            </div>
                                            <div class="text-end">
                                                <strong>
                                                    <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                                    <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php
                                                    $duration = (strtotime($class['end_time']) - strtotime($class['start_time'])) / 60;
                                                    echo $duration . ' minutes';
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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

        // Update current time every minute
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('currentTime').textContent = timeString;
        }

        // Update time immediately and then every minute
        updateTime();
        setInterval(updateTime, 60000);

        // Print schedule function
        function printSchedule() {
            window.print();
        }

        // Auto-refresh page every 5 minutes to update current class status
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>