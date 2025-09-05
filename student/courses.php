<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get student's enrolled courses with detailed information
$sql = "SELECT c.id, c.course_code, c.course_name, c.description, c.credit_hours, c.academic_year,
               u.full_name as faculty_name, u.email as faculty_email,
               c.department as department_name,
               ce.enrollment_date as enrolled_at, ce.status,
               COUNT(DISTINCT cm.id) as material_count,
               COUNT(DISTINCT a.id) as assignment_count,
               COUNT(DISTINCT asub.id) as submitted_assignments,
               AVG(cf.rating) as avg_rating,
               COUNT(DISTINCT cf.id) as feedback_count
        FROM course_enrollments ce
        JOIN courses c ON ce.course_id = c.id
        LEFT JOIN course_assignments ca ON c.id = ca.course_id
        LEFT JOIN users u ON ca.faculty_id = u.id AND u.role = 'faculty'
        LEFT JOIN course_materials cm ON c.id = cm.course_id AND cm.is_active = 1
        LEFT JOIN assignments a ON c.id = a.course_id AND a.is_active = 1
        LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
        LEFT JOIN course_feedback cf ON c.id = cf.course_id
        WHERE ce.student_id = ?
        GROUP BY c.id, c.course_code, c.course_name, c.description, c.credit_hours, c.academic_year,
                 u.full_name, u.email, c.department, ce.enrollment_date, ce.status
        ORDER BY ce.enrollment_date DESC";
$enrolledCourses = fetchMultipleRows($conn, $sql, "ii", [$userId, $userId]);

// Get course statistics
$totalCourses = count($enrolledCourses);
$totalCredits = array_sum(array_column($enrolledCourses, 'credit_hours'));
$totalMaterials = array_sum(array_column($enrolledCourses, 'material_count'));
$totalAssignments = array_sum(array_column($enrolledCourses, 'assignment_count'));
$totalSubmitted = array_sum(array_column($enrolledCourses, 'submitted_assignments'));

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
                        <i class="bi bi-book"></i> My Courses
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Academic Year: 2024</small>
                    </div>
                </div>

                <!-- Course Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $totalCourses; ?></h4>
                                <p class="mb-0">Enrolled Courses</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $totalCredits; ?></h4>
                                <p class="mb-0">Total Credits</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $totalMaterials; ?></h4>
                                <p class="mb-0">Course Materials</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $totalSubmitted; ?>/<?php echo $totalAssignments; ?></h4>
                                <p class="mb-0">Assignments Done</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Courses -->
                <?php if (empty($enrolledCourses)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-journal-x display-1 text-muted"></i>
                        <h4 class="mt-3">No enrolled courses</h4>
                        <p class="text-muted">You haven't enrolled in any courses yet. Contact your academic advisor for course registration.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($enrolledCourses as $course): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 course-card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($course['department_name'] ?? 'No Department'); ?></small>
                                            </div>
                                            <span class="badge bg-primary"><?php echo $course['credit_hours']; ?> Credits</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                                        <p class="card-text small"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                        
                                        <!-- Faculty Information -->
                                        <div class="faculty-info mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle text-primary me-2"></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($course['faculty_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($course['faculty_email']); ?></small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Course Statistics -->
                                        <div class="course-stats mb-3">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <strong><?php echo $course['material_count']; ?></strong>
                                                    <br><small class="text-muted">Materials</small>
                                                </div>
                                                <div class="col-4">
                                                    <strong><?php echo $course['submitted_assignments']; ?>/<?php echo $course['assignment_count']; ?></strong>
                                                    <br><small class="text-muted">Assignments</small>
                                                </div>
                                                <div class="col-4">
                                                    <strong><?php echo $course['avg_rating'] ? round($course['avg_rating'], 1) : 'N/A'; ?></strong>
                                                    <br><small class="text-muted">Rating</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Progress Bar -->
                                        <?php 
                                        $progress = $course['assignment_count'] > 0 ? 
                                            round(($course['submitted_assignments'] / $course['assignment_count']) * 100) : 0;
                                        ?>
                                        <div class="progress mb-3" style="height: 20px;">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $progress; ?>%"
                                                 aria-valuenow="<?php echo $progress; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo $progress; ?>% Complete
                                            </div>
                                        </div>

                                        <!-- Enrollment Info -->
                                        <div class="enrollment-info">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-check"></i>
                                                Enrolled: <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-grid gap-2">
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#courseModal<?php echo $course['id']; ?>">
                                                <i class="bi bi-eye"></i> View Details
                                            </button>
                                            <div class="btn-group" role="group">
                                                <a href="materials.php?course=<?php echo $course['id']; ?>" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-file-earmark-text"></i> Materials
                                                </a>
                                                <a href="assignments.php?course=<?php echo $course['id']; ?>" 
                                                   class="btn btn-outline-warning btn-sm">
                                                    <i class="bi bi-clipboard-check"></i> Assignments
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Course Details Modal -->
                            <div class="modal fade" id="courseModal<?php echo $course['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <h6>Course Information</h6>
                                                    <p><strong>Department:</strong> <?php echo htmlspecialchars($course['department_name']); ?></p>
                                                    <p><strong>Credit Hours:</strong> <?php echo $course['credit_hours']; ?></p>
                                                    <p><strong>Academic Year:</strong> <?php echo $course['academic_year']; ?></p>
                                                    <p><strong>Status:</strong> 
                                                        <span class="badge bg-success"><?php echo ucfirst($course['status']); ?></span>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Faculty Information</h6>
                                                    <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['faculty_name']); ?></p>
                                                    <p><strong>Email:</strong> 
                                                        <a href="mailto:<?php echo htmlspecialchars($course['faculty_email']); ?>">
                                                            <?php echo htmlspecialchars($course['faculty_email']); ?>
                                                        </a>
                                                    </p>
                                                    <p><strong>Course Rating:</strong>
                                                        <?php if ($course['avg_rating']): ?>
                                                            <span class="badge bg-warning">
                                                                <?php echo round($course['avg_rating'], 1); ?>/5
                                                            </span>
                                                            <small class="text-muted">(<?php echo $course['feedback_count']; ?> reviews)</small>
                                                        <?php else: ?>
                                                            <span class="text-muted">No ratings yet</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <h6>Course Description</h6>
                                                <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <div class="card text-center">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo $course['material_count']; ?></h5>
                                                            <p class="card-text">Course Materials</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card text-center">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo $course['assignment_count']; ?></h5>
                                                            <p class="card-text">Total Assignments</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card text-center">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo $course['submitted_assignments']; ?></h5>
                                                            <p class="card-text">Submitted</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <h6>Assignment Progress</h6>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-success" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $progress; ?>%">
                                                        <?php echo $progress; ?>% Complete
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Close
                                            </button>
                                            <a href="materials.php?course=<?php echo $course['id']; ?>" 
                                               class="btn btn-info">
                                                <i class="bi bi-file-earmark-text"></i> View Materials
                                            </a>
                                            <a href="assignments.php?course=<?php echo $course['id']; ?>" 
                                               class="btn btn-warning">
                                                <i class="bi bi-clipboard-check"></i> View Assignments
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Academic Progress Summary -->
                <?php if (!empty($enrolledCourses)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up"></i> Academic Progress Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Credit Distribution</h6>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th>Credits</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($enrolledCourses as $course): ?>
                                                <?php
                                                $progress = $course['assignment_count'] > 0 ? 
                                                    round(($course['submitted_assignments'] / $course['assignment_count']) * 100) : 0;
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                                    <td><?php echo $course['credit_hours']; ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 15px;">
                                                            <div class="progress-bar" 
                                                                 style="width: <?php echo $progress; ?>%"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Overall Statistics</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="stat-item text-center p-2">
                                                <h4 class="text-primary"><?php echo $totalCourses; ?></h4>
                                                <small>Total Courses</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-item text-center p-2">
                                                <h4 class="text-success"><?php echo $totalCredits; ?></h4>
                                                <small>Total Credits</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-item text-center p-2">
                                                <h4 class="text-info"><?php echo $totalMaterials; ?></h4>
                                                <small>Materials Available</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-item text-center p-2">
                                                <h4 class="text-warning">
                                                    <?php echo $totalAssignments > 0 ? round(($totalSubmitted / $totalAssignments) * 100) : 0; ?>%
                                                </h4>
                                                <small>Assignment Progress</small>
                                            </div>
                                        </div>
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
        .faculty-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
        }
        .course-stats {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
        }
        .stat-item {
            border-radius: 5px;
            background-color: #f8f9fa;
            margin-bottom: 10px;
        }
        .progress {
            height: 20px;
        }
    </style>
</body>
</html>