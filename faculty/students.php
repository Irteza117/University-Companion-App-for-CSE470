<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get course filter from query parameter
$selectedCourseId = $_GET['course'] ?? '';

// Get faculty's courses for filter dropdown
$coursesSql = "SELECT id, course_code, course_name FROM courses WHERE faculty_id = ? AND is_active = 1 ORDER BY course_code";
$courses = fetchMultipleRows($conn, $coursesSql, "i", [$userId]);

// Build students query
$studentsSql = "SELECT DISTINCT u.id, u.full_name, u.email, u.phone, u.id as student_id, u.created_at as academic_year,
                       u.department as department_name,
                       GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') as enrolled_courses,
                       COUNT(DISTINCT ce.course_id) as course_count,
                       AVG(CASE WHEN asub.grade IS NOT NULL THEN asub.grade ELSE NULL END) as avg_grade,
                       COUNT(DISTINCT asub.id) as submitted_assignments,
                       COUNT(DISTINCT a.id) as total_assignments
                FROM course_enrollments ce
                JOIN users u ON ce.student_id = u.id
                JOIN courses c ON ce.course_id = c.id
                LEFT JOIN assignments a ON c.id = a.course_id AND a.is_active = 1
                LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = u.id
                WHERE c.faculty_id = ? AND ce.status = 'enrolled' AND u.is_active = 1";

$params = [$userId];
$types = "i";

if (!empty($selectedCourseId)) {
    $studentsSql .= " AND c.id = ?";
    $params[] = $selectedCourseId;
    $types .= "i";
}

$studentsSql .= " GROUP BY u.id, u.full_name, u.email, u.phone, u.created_at, u.department
                  ORDER BY u.full_name";

$students = fetchMultipleRows($conn, $studentsSql, $types, $params);

// Get selected course details if filter is applied
$selectedCourse = null;
if ($selectedCourseId) {
    $courseSql = "SELECT course_code, course_name FROM courses WHERE id = ? AND faculty_id = ?";
    $selectedCourse = fetchSingleRow($conn, $courseSql, "ii", [$selectedCourseId, $userId]);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - University Companion</title>
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
                                <i class="bi bi-calendar-event"></i> Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="students.php">
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
                        <i class="bi bi-people"></i> Students
                        <?php if ($selectedCourse): ?>
                            <small class="text-muted">- <?php echo htmlspecialchars($selectedCourse['course_code']); ?></small>
                        <?php endif; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Manage your students</small>
                    </div>
                </div>

                <!-- Course Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <select class="form-select" name="course" onchange="this.form.submit()">
                                    <option value="">All Students from My Courses</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                                <?php echo ($selectedCourseId == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Student Statistics -->
                <div class="row mb-4">
                    <?php
                    $totalStudents = count($students);
                    $avgGrades = array_filter(array_column($students, 'avg_grade'));
                    $overallAvgGrade = !empty($avgGrades) ? round(array_sum($avgGrades) / count($avgGrades), 1) : 0;
                    $totalAssignments = array_sum(array_column($students, 'submitted_assignments'));
                    $totalEnrollments = array_sum(array_column($students, 'course_count'));
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $totalStudents; ?></h4>
                                <p class="mb-0">Total Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $totalEnrollments; ?></h4>
                                <p class="mb-0">Course Enrollments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $totalAssignments; ?></h4>
                                <p class="mb-0">Assignments Submitted</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $overallAvgGrade ?: 'N/A'; ?></h4>
                                <p class="mb-0">Average Grade</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students List -->
                <?php if (empty($students)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people display-1 text-muted"></i>
                        <h4 class="mt-3">No students found</h4>
                        <p class="text-muted">
                            <?php if ($selectedCourse): ?>
                                No students are enrolled in this course yet
                            <?php else: ?>
                                No students are enrolled in your courses yet
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Student List</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Contact</th>
                                            <th>Academic Info</th>
                                            <th>Enrolled Courses</th>
                                            <th>Performance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person-circle display-6 text-primary me-3"></i>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">ID: <?php echo htmlspecialchars($student['id']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="bi bi-envelope"></i> 
                                                        <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" 
                                                           class="text-decoration-none">
                                                            <?php echo htmlspecialchars($student['email']); ?>
                                                        </a>
                                                        <br>
                                                        <?php if ($student['phone']): ?>
                                                            <i class="bi bi-phone"></i> 
                                                            <a href="tel:<?php echo htmlspecialchars($student['phone']); ?>" 
                                                               class="text-decoration-none">
                                                                <?php echo htmlspecialchars($student['phone']); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <strong>Year:</strong> <?php echo htmlspecialchars(date('Y', strtotime($student['academic_year'])) ?? 'N/A'); ?>
                                                        <br>
                                                        <strong>Department:</strong> <?php echo htmlspecialchars($student['department_name'] ?? 'N/A'); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <strong><?php echo $student['course_count']; ?></strong> course(s)
                                                        <br>
                                                        <?php echo htmlspecialchars($student['enrolled_courses']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php if ($student['avg_grade'] !== null): ?>
                                                            <strong>Avg Grade:</strong> 
                                                            <span class="badge <?php echo $student['avg_grade'] >= 80 ? 'bg-success' : ($student['avg_grade'] >= 70 ? 'bg-warning' : 'bg-danger'); ?>">
                                                                <?php echo round($student['avg_grade'], 1); ?>
                                                            </span>
                                                            <br>
                                                        <?php endif; ?>
                                                        <strong>Assignments:</strong> <?php echo $student['submitted_assignments']; ?>/<?php echo $student['total_assignments']; ?>
                                                        <?php if ($student['total_assignments'] > 0): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                (<?php echo round(($student['submitted_assignments'] / $student['total_assignments']) * 100); ?>% completion)
                                                            </small>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" 
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-envelope"></i> Email
                                                        </a>
                                                        <?php if (!$selectedCourse): ?>
                                                            <button type="button" 
                                                                    class="btn btn-outline-info btn-sm"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#studentModal<?php echo $student['id']; ?>">
                                                                <i class="bi bi-eye"></i> View Details
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
        setActiveNavItem('students.php');
    </script>
</body>
</html>