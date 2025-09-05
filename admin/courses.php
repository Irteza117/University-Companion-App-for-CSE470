<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle form submissions
$success = '';
$error = '';

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_course'])) {
    $courseCode = sanitizeInput($_POST['course_code'] ?? '');
    $courseName = sanitizeInput($_POST['course_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $creditHours = (int)($_POST['credit_hours'] ?? 0);
    $maxStudents = !empty($_POST['max_students']) ? (int)$_POST['max_students'] : null;
    $academicYear = sanitizeInput($_POST['academic_year'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $facultyId = (int)($_POST['faculty_id'] ?? 0);
    
    if (empty($courseCode) || empty($courseName) || $creditHours <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if course code already exists
        $checkSql = "SELECT id FROM courses WHERE course_code = ?";
        $existing = fetchSingleRow($conn, $checkSql, "s", [$courseCode]);
        
        if ($existing) {
            $error = 'Course code already exists.';
        } else {
            $sql = "INSERT INTO courses (course_code, course_name, description, credit_hours, max_students, academic_year, department, semester, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Fall', 1)";
            $result = insertData($conn, $sql, "sssiiiss", [$courseCode, $courseName, $description, $creditHours, $maxStudents, $academicYear, $department]);
            
            if ($result > 0 && $facultyId > 0) {
                // Assign faculty to course via course_assignments table
                $assignSql = "INSERT INTO course_assignments (faculty_id, course_id, semester, academic_year) VALUES (?, ?, 'Fall', ?)";
                insertData($conn, $assignSql, "iis", [$facultyId, $result, $academicYear]);
            }
            
            if ($result > 0) {
                $success = "Course created successfully.";
            } else {
                $error = "Failed to create course.";
            }
        }
    }
}

// Handle course status toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_course_status'])) {
    $courseId = (int)$_POST['course_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;
    
    $sql = "UPDATE courses SET is_active = ? WHERE id = ?";
    $result = updateData($conn, $sql, "ii", [$newStatus, $courseId]);
    
    if ($result >= 0) {
        $success = "Course status updated successfully.";
    } else {
        $error = "Failed to update course status.";
    }
}

// Handle faculty assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_faculty'])) {
    $courseId = (int)$_POST['course_id'];
    $facultyId = (int)$_POST['faculty_id'];
    $academicYear = sanitizeInput($_POST['academic_year'] ?? date('Y'));
    
    // Remove existing assignment
    $deleteSql = "DELETE FROM course_assignments WHERE course_id = ?";
    executeQuery($conn, $deleteSql, "i", [$courseId]);
    
    // Add new assignment if faculty is selected
    if ($facultyId > 0) {
        $assignSql = "INSERT INTO course_assignments (faculty_id, course_id, semester, academic_year) VALUES (?, ?, 'Fall', ?)";
        $result = insertData($conn, $assignSql, "iis", [$facultyId, $courseId, $academicYear]);
        
        if ($result > 0) {
            $success = "Faculty assigned successfully.";
        } else {
            $error = "Failed to assign faculty.";
        }
    } else {
        $success = "Faculty unassigned successfully.";
    }
}

// Handle student enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll_student'])) {
    $courseId = (int)$_POST['course_id'];
    $studentId = (int)$_POST['student_id'];
    
    if ($studentId <= 0 || $courseId <= 0) {
        $error = "Please select a valid student and course.";
    } else {
        // Check if already enrolled
        $checkSql = "SELECT id FROM course_enrollments WHERE student_id = ? AND course_id = ?";
        $existing = fetchSingleRow($conn, $checkSql, "ii", [$studentId, $courseId]);
        
        if ($existing) {
            $error = "Student is already enrolled in this course.";
        } else {
            // Get student and course names for better feedback
            $studentInfo = fetchSingleRow($conn, "SELECT full_name FROM users WHERE id = ?", "i", [$studentId]);
            $courseInfo = fetchSingleRow($conn, "SELECT course_code, course_name FROM courses WHERE id = ?", "i", [$courseId]);
            
            $enrollSql = "INSERT INTO course_enrollments (student_id, course_id, status, enrollment_date) VALUES (?, ?, 'enrolled', NOW())";
            $result = insertData($conn, $enrollSql, "ii", [$studentId, $courseId]);
            
            if ($result > 0) {
                $success = "Successfully enrolled " . htmlspecialchars($studentInfo['full_name']) . " in " . htmlspecialchars($courseInfo['course_code']) . ".";
            } else {
                $error = "Failed to enroll student. Please try again.";
            }
        }
    }
}

// Handle student removal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_student'])) {
    $enrollmentId = (int)$_POST['enrollment_id'];
    
    // Get student and course info for better feedback
    $enrollmentInfo = fetchSingleRow($conn, 
        "SELECT u.full_name, c.course_code 
         FROM course_enrollments ce 
         JOIN users u ON ce.student_id = u.id 
         JOIN courses c ON ce.course_id = c.id 
         WHERE ce.id = ?", "i", [$enrollmentId]);
    
    $removeSql = "DELETE FROM course_enrollments WHERE id = ?";
    $result = executeQuery($conn, $removeSql, "i", [$enrollmentId]);
    
    if ($result) {
        if ($enrollmentInfo) {
            $success = "Successfully removed " . htmlspecialchars($enrollmentInfo['full_name']) . " from " . htmlspecialchars($enrollmentInfo['course_code']) . ".";
        } else {
            $success = "Student removed from course successfully.";
        }
    } else {
        $error = "Failed to remove student from course.";
    }
}

// Get filter parameters
$departmentFilter = $_GET['department'] ?? 'all';
$facultyFilter = $_GET['faculty'] ?? 'all';
$yearFilter = $_GET['year'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

// Build query with filters
$whereConditions = [];
$params = [];
$types = "";

if ($departmentFilter != 'all') {
    $whereConditions[] = "c.department = ?";
    $params[] = $departmentFilter;
    $types .= "s";
}

if ($facultyFilter != 'all') {
    $whereConditions[] = "ca.faculty_id = ?";
    $params[] = (int)$facultyFilter;
    $types .= "i";
}

if ($yearFilter != 'all') {
    $whereConditions[] = "c.academic_year = ?";
    $params[] = $yearFilter;
    $types .= "s";
}

if ($statusFilter != 'all') {
    $whereConditions[] = "c.is_active = ?";
    $params[] = ($statusFilter == 'active') ? 1 : 0;
    $types .= "i";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get courses with faculty information
$sql = "SELECT c.*, ca.faculty_id, u.full_name as faculty_name,
        COUNT(DISTINCT ce.student_id) as enrollment_count,
        COUNT(DISTINCT cm.id) as material_count
        FROM courses c
        LEFT JOIN course_assignments ca ON c.id = ca.course_id
        LEFT JOIN users u ON ca.faculty_id = u.id AND u.role = 'faculty'
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
        LEFT JOIN course_materials cm ON c.id = cm.course_id AND cm.is_active = 1
        $whereClause
        GROUP BY c.id, ca.faculty_id
        ORDER BY c.created_at DESC";

// If no courses, add default is_active condition
if (empty($whereConditions)) {
    $sql = "SELECT c.*, ca.faculty_id, u.full_name as faculty_name,
            COUNT(DISTINCT ce.student_id) as enrollment_count,
            COUNT(DISTINCT cm.id) as material_count
            FROM courses c
            LEFT JOIN course_assignments ca ON c.id = ca.course_id
            LEFT JOIN users u ON ca.faculty_id = u.id AND u.role = 'faculty'
            LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
            LEFT JOIN course_materials cm ON c.id = cm.course_id AND cm.is_active = 1
            GROUP BY c.id, ca.faculty_id
            ORDER BY c.created_at DESC";
}

$courses = fetchMultipleRows($conn, $sql, $types, $params);

// Get faculty list for dropdown
$facultyList = fetchMultipleRows($conn, "SELECT id, full_name FROM users WHERE role = 'faculty' AND is_active = 1 ORDER BY full_name");

// Get student list for enrollment
$studentList = fetchMultipleRows($conn, "SELECT id, full_name, username FROM users WHERE role = 'student' AND is_active = 1 ORDER BY full_name");

// Get departments for filter
$departments = fetchMultipleRows($conn, "SELECT DISTINCT department FROM courses WHERE department IS NOT NULL AND department != '' ORDER BY department");

// Get academic years
$academicYears = fetchMultipleRows($conn, "SELECT DISTINCT academic_year FROM courses WHERE academic_year IS NOT NULL ORDER BY academic_year DESC");

// Get statistics
$stats = fetchSingleRow($conn, "SELECT 
    COUNT(DISTINCT c.id) as total_courses,
    COUNT(DISTINCT CASE WHEN c.id IS NOT NULL THEN c.id END) as active_courses,
    COUNT(DISTINCT c.department) as total_departments,
    COUNT(DISTINCT ca.faculty_id) as faculty_assigned
    FROM courses c
    LEFT JOIN course_assignments ca ON c.id = ca.course_id");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4 primary-text fs-4 fw-bold text-uppercase border-bottom">
                <i class="bi bi-mortarboard-fill me-2"></i>Admin Panel
            </div>
            <div class="list-group list-group-flush my-3">
                <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="users.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-people me-2"></i>User Management
                </a>
                <a href="courses.php" class="list-group-item list-group-item-action bg-transparent text-white active">
                    <i class="bi bi-book me-2"></i>Courses
                </a>
                <a href="notices.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-megaphone me-2"></i>Notices
                </a>
                <a href="events.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-calendar-event me-2"></i>Events
                </a>

            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../php/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-book"></i> Course Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="student_assignments.php" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus"></i> Bulk Assign Students
                            </a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                <i class="bi bi-plus"></i> Add Course
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total_courses']; ?></h4>
                                <p class="mb-0">Total Courses</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['active_courses']; ?></h4>
                                <p class="mb-0">Active Courses</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total_departments']; ?></h4>
                                <p class="mb-0">Departments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['faculty_assigned']; ?></h4>
                                <p class="mb-0">Faculty Assigned</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" name="department">
                                    <option value="all">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                                <?php echo $departmentFilter == $dept['department'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="faculty" class="form-label">Faculty</label>
                                <select class="form-select" name="faculty">
                                    <option value="all">All Faculty</option>
                                    <?php foreach ($facultyList as $faculty): ?>
                                        <option value="<?php echo $faculty['id']; ?>" 
                                                <?php echo $facultyFilter == $faculty['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($faculty['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="year" class="form-label">Academic Year</label>
                                <select class="form-select" name="year">
                                    <option value="all">All Years</option>
                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year['academic_year']); ?>" 
                                                <?php echo $yearFilter == $year['academic_year'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year['academic_year']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Courses Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Courses</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($courses)): ?>
                            <div class="text-center p-4">
                                <i class="bi bi-book display-1 text-muted"></i>
                                <p class="text-muted mt-3">No courses found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Course</th>
                                            <th>Faculty</th>
                                            <th>Department</th>
                                            <th>Credits</th>
                                            <th>Enrollments</th>
                                            <th>Materials</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courses as $course): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($course['course_code']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($course['course_name']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($course['faculty_name'] ?? 'Unassigned'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($course['department'] ?? 'N/A'); ?></td>
                                                <td><?php echo $course['credit_hours']; ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $course['enrollment_count']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $course['material_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($course['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="viewCourse(<?php echo $course['id']; ?>)">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="manageFaculty(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_code']); ?>', <?php echo $course['faculty_id'] ?? 0; ?>)">
                                                            <i class="bi bi-person-gear"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="manageEnrollments(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_code']); ?>')">
                                                            <i class="bi bi-people-fill"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure?')">
                                                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                            <input type="hidden" name="current_status" value="<?php echo $course['is_active']; ?>">
                                                            <button type="submit" name="toggle_course_status" 
                                                                    class="btn btn-outline-<?php echo $course['is_active'] ? 'warning' : 'success'; ?>">
                                                                <i class="bi bi-<?php echo $course['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Course Modal -->
    <div class="modal fade" id="createCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="course_code" class="form-label">Course Code *</label>
                                <input type="text" class="form-control" name="course_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="credit_hours" class="form-label">Credit Hours *</label>
                                <input type="number" class="form-control" name="credit_hours" min="1" max="6" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="course_name" class="form-label">Course Name *</label>
                            <input type="text" class="form-control" name="course_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" name="department">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="academic_year" class="form-label">Academic Year</label>
                                <select class="form-select" name="academic_year">
                                    <option value="2024">2024</option>
                                    <option value="2025">2025</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="max_students" class="form-label">Max Students</label>
                                <input type="number" class="form-control" name="max_students" min="1" placeholder="Leave empty for unlimited">
                                <small class="form-text text-muted">Leave empty for unlimited enrollment</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="faculty_id" class="form-label">Assign Faculty</label>
                            <select class="form-select" name="faculty_id">
                                <option value="">Select Faculty (Optional)</option>
                                <?php foreach ($facultyList as $faculty): ?>
                                    <option value="<?php echo $faculty['id']; ?>">
                                        <?php echo htmlspecialchars($faculty['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_course" class="btn btn-primary">Create Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Faculty Assignment Modal -->
    <div class="modal fade" id="facultyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Faculty</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="faculty-course-id" name="course_id">
                        <div class="mb-3">
                            <label class="form-label">Course: <span id="faculty-course-code" class="fw-bold"></span></label>
                        </div>
                        <div class="mb-3">
                            <label for="faculty_assignment" class="form-label">Select Faculty</label>
                            <select class="form-select" id="faculty-select" name="faculty_id">
                                <option value="0">Unassign Faculty</option>
                                <?php foreach ($facultyList as $faculty): ?>
                                    <option value="<?php echo $faculty['id']; ?>">
                                        <?php echo htmlspecialchars($faculty['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="academic_year_assign" class="form-label">Academic Year</label>
                            <select class="form-select" name="academic_year">
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_faculty" class="btn btn-primary">Update Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enrollment Management Modal -->
    <div class="modal fade" id="enrollmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Enrollments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-person-plus"></i> Enroll New Student</h6>
                            <form method="POST" class="mb-4">
                                <input type="hidden" id="enroll-course-id" name="course_id">
                                <div class="mb-3">
                                    <label class="form-label">Course: <span id="enroll-course-code" class="fw-bold text-primary"></span></label>
                                </div>
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Select Student</label>
                                    <select class="form-select" name="student_id" id="student-select" required>
                                        <option value="">Choose Student...</option>
                                        <?php foreach ($studentList as $student): ?>
                                            <option value="<?php echo $student['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($student['full_name']); ?>" 
                                                    data-username="<?php echo htmlspecialchars($student['username']); ?>">
                                                <?php echo htmlspecialchars($student['full_name']); ?> (<?php echo htmlspecialchars($student['username']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="bi bi-info-circle"></i> Select a student to enroll in this course
                                    </small>
                                </div>
                                <button type="submit" name="enroll_student" class="btn btn-success">
                                    <i class="bi bi-plus-circle"></i> Enroll Student
                                </button>
                            </form>
                            
                            <!-- Quick Stats -->
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-2"><i class="bi bi-graph-up"></i> Enrollment Stats</h6>
                                    <div id="enrollment-stats">
                                        <small class="text-muted">Loading stats...</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-people"></i> Current Enrollments</h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Students enrolled in this course</small>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshEnrollments()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                            <div id="current-enrollments" class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-2 mb-0">Loading enrollments...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('wrapper').classList.toggle('toggled');
        });

        function viewCourse(courseId) {
            alert('View course details functionality to be implemented');
        }

        function manageFaculty(courseId, courseCode, currentFacultyId) {
            document.getElementById('faculty-course-id').value = courseId;
            document.getElementById('faculty-course-code').textContent = courseCode;
            document.getElementById('faculty-select').value = currentFacultyId || 0;
            
            const facultyModal = new bootstrap.Modal(document.getElementById('facultyModal'));
            facultyModal.show();
        }

        function manageEnrollments(courseId, courseCode) {
            document.getElementById('enroll-course-id').value = courseId;
            document.getElementById('enroll-course-code').textContent = courseCode;
            
            // Reset student selection
            document.getElementById('student-select').value = '';
            
            // Load current enrollments and stats
            loadEnrollments(courseId);
            loadEnrollmentStats(courseId);
            
            const enrollmentModal = new bootstrap.Modal(document.getElementById('enrollmentModal'));
            enrollmentModal.show();
        }

        function loadEnrollments(courseId) {
            const container = document.getElementById('current-enrollments');
            container.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 mb-0">Loading enrollments...</p>
                </div>
            `;
            
            fetch('get_enrollments.php?course_id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        if (data.enrollments.length === 0) {
                            html = `
                                <div class="text-center py-4">
                                    <i class="bi bi-people text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2 mb-0">No students enrolled yet.</p>
                                    <small class="text-muted">Use the form on the left to enroll students.</small>
                                </div>
                            `;
                        } else {
                            data.enrollments.forEach((enrollment, index) => {
                                const enrollmentDate = new Date(enrollment.enrollment_date).toLocaleDateString();
                                html += `
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-3 border rounded bg-light">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: bold;">
                                                    ${enrollment.full_name.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <strong class="d-block">${enrollment.full_name}</strong>
                                                    <small class="text-muted">@${enrollment.username} • Enrolled: ${enrollmentDate}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove ${enrollment.full_name} from this course?')">
                                            <input type="hidden" name="enrollment_id" value="${enrollment.id}">
                                            <button type="submit" name="remove_student" class="btn btn-outline-danger btn-sm" title="Remove student">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                `;
                            });
                        }
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="text-center py-4">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                                <p class="text-danger mt-2 mb-0">Error loading enrollments.</p>
                                <small class="text-muted">${data.error || 'Please try again.'}</small>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i class="bi bi-wifi-off text-muted" style="font-size: 2rem;"></i>
                            <p class="text-danger mt-2 mb-0">Network error occurred.</p>
                            <small class="text-muted">Please check your connection and try again.</small>
                        </div>
                    `;
                });
        }

        function loadEnrollmentStats(courseId) {
            const container = document.getElementById('enrollment-stats');
            
            fetch('get_enrollments.php?course_id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const totalStudents = data.enrollments.length;
                        const recentEnrollments = data.enrollments.filter(e => {
                            const enrollDate = new Date(e.enrollment_date);
                            const weekAgo = new Date();
                            weekAgo.setDate(weekAgo.getDate() - 7);
                            return enrollDate >= weekAgo;
                        }).length;
                        
                        container.innerHTML = `
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h5 text-primary mb-0">${totalStudents}</div>
                                    <small class="text-muted">Total Enrolled</small>
                                </div>
                                <div class="col-6">
                                    <div class="h5 text-success mb-0">${recentEnrollments}</div>
                                    <small class="text-muted">This Week</small>
                                </div>
                            </div>
                        `;
                    } else {
                        container.innerHTML = '<small class="text-muted">Stats unavailable</small>';
                    }
                })
                .catch(error => {
                    container.innerHTML = '<small class="text-muted">Stats unavailable</small>';
                });
        }

        function refreshEnrollments() {
            const courseId = document.getElementById('enroll-course-id').value;
            if (courseId) {
                loadEnrollments(courseId);
                loadEnrollmentStats(courseId);
            }
        }
    </script>
</body>
</html>