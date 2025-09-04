<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get faculty's courses with enrollment details
$coursesSql = "SELECT c.id, c.course_code, c.course_name, c.description, c.credit_hours, 
                      c.academic_year, c.semester, c.max_students,
                      d.name as department_name,
                      COUNT(ce.id) as enrolled_students,
                      GROUP_CONCAT(DISTINCT s.day_of_week ORDER BY 
                          CASE s.day_of_week 
                              WHEN 'Monday' THEN 1 
                              WHEN 'Tuesday' THEN 2 
                              WHEN 'Wednesday' THEN 3 
                              WHEN 'Thursday' THEN 4 
                              WHEN 'Friday' THEN 5 
                              WHEN 'Saturday' THEN 6 
                              WHEN 'Sunday' THEN 7 
                          END SEPARATOR ', ') as schedule_days,
                      MIN(s.start_time) as earliest_class,
                      MAX(s.end_time) as latest_class
               FROM courses c
               LEFT JOIN departments d ON c.department_id = d.id
               LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
               LEFT JOIN class_schedule s ON c.id = s.course_id
               WHERE c.faculty_id = ? AND c.is_active = 1
               GROUP BY c.id, c.course_code, c.course_name, c.description, c.credit_hours, 
                        c.academic_year, c.semester, c.max_students, d.name
               ORDER BY c.course_code";
$courses = fetchMultipleRows($conn, $coursesSql, "i", [$userId]);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - University Companion</title>
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
                            <a class="nav-link active" href="courses.php">
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
                    <h1 class="h2">
                        <i class="bi bi-book"></i> My Courses
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Academic Year 2024 Courses</small>
                    </div>
                </div>

                <!-- Course Statistics -->
                <div class="row mb-4">
                    <?php
                    $totalCourses = count($courses);
                    $totalStudents = array_sum(array_column($courses, 'enrolled_students'));
                    $avgStudentsPerCourse = $totalCourses > 0 ? round($totalStudents / $totalCourses, 1) : 0;
                    $totalCredits = array_sum(array_column($courses, 'credit_hours'));
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $totalCourses; ?></h4>
                                <p class="mb-0">Total Courses</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $totalStudents; ?></h4>
                                <p class="mb-0">Total Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $avgStudentsPerCourse; ?></h4>
                                <p class="mb-0">Avg Students/Course</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $totalCredits; ?></h4>
                                <p class="mb-0">Total Credit Hours</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses List -->
                <?php if (empty($courses)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-journal-x display-1 text-muted"></i>
                        <h4 class="mt-3">No courses assigned</h4>
                        <p class="text-muted">Contact administration to get course assignments</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 course-card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($course['department_name']); ?></small>
                                            </div>
                                            <span class="badge bg-primary"><?php echo $course['credit_hours']; ?> credits</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                        <p class="card-text text-muted small">
                                            <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . (strlen($course['description']) > 100 ? '...' : ''); ?>
                                        </p>
                                        
                                        <div class="course-details mb-3">
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <strong><?php echo $course['enrolled_students']; ?></strong>
                                                    <br><small class="text-muted">Students</small>
                                                </div>
                                                <div class="col-6">
                                                    <strong><?php echo $course['max_students'] ?: 'Unlimited'; ?></strong>
                                                    <br><small class="text-muted">Max Capacity</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($course['schedule_days']): ?>
                                            <div class="schedule-info bg-light p-2 rounded mb-3">
                                                <small>
                                                    <strong>Schedule:</strong> <?php echo htmlspecialchars($course['schedule_days']); ?>
                                                    <?php if ($course['earliest_class']): ?>
                                                        <br>
                                                        <strong>Time:</strong> 
                                                        <?php echo date('g:i A', strtotime($course['earliest_class'])); ?> - 
                                                        <?php echo date('g:i A', strtotime($course['latest_class'])); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-grid gap-2">
                                            <div class="btn-group" role="group">
                                                <a href="students.php?course=<?php echo $course['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-people"></i> Students
                                                </a>
                                                <a href="materials.php?course=<?php echo $course['id']; ?>" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-file-earmark"></i> Materials
                                                </a>
                                            </div>
                                            <div class="btn-group" role="group">
                                                <a href="assignments.php?course=<?php echo $course['id']; ?>" 
                                                   class="btn btn-outline-warning btn-sm">
                                                    <i class="bi bi-clipboard-check"></i> Assignments
                                                </a>
                                                <a href="feedback.php?course=<?php echo $course['id']; ?>" 
                                                   class="btn btn-outline-success btn-sm">
                                                    <i class="bi bi-chat-square-text"></i> Feedback
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        setActiveNavItem('courses.php');
    </script>

    <style>
        .course-card {
            transition: transform 0.2s;
        }
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .schedule-info {
            font-size: 0.85rem;
        }
    </style>
</body>
</html>