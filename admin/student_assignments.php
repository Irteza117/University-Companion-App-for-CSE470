<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle form submissions
$success = '';
$error = '';

// Handle bulk enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_enroll'])) {
    $courseId = (int)$_POST['course_id'];
    $studentIds = $_POST['student_ids'] ?? [];
    
    if ($courseId <= 0 || empty($studentIds)) {
        $error = "Please select a course and at least one student.";
    } else {
        $successCount = 0;
        $errorCount = 0;
        $duplicateCount = 0;
        $errors = [];
        
        foreach ($studentIds as $studentId) {
            $studentId = (int)$studentId;
            if ($studentId <= 0) continue;
            
            // Check if already enrolled
            $checkSql = "SELECT id FROM course_enrollments WHERE student_id = ? AND course_id = ?";
            $existing = fetchSingleRow($conn, $checkSql, "ii", [$studentId, $courseId]);
            
            if ($existing) {
                $duplicateCount++;
            } else {
                $enrollSql = "INSERT INTO course_enrollments (student_id, course_id, status, enrollment_date) VALUES (?, ?, 'enrolled', NOW())";
                $result = insertData($conn, $enrollSql, "ii", [$studentId, $courseId]);
                
                if ($result > 0) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
        }
        
        $message = "Bulk enrollment completed: ";
        $message .= "$successCount students enrolled successfully";
        if ($duplicateCount > 0) $message .= ", $duplicateCount already enrolled";
        if ($errorCount > 0) $message .= ", $errorCount failed";
        $message .= ".";
        
        if ($errorCount == 0) {
            $success = $message;
        } else {
            $error = $message;
        }
    }
}

// Handle bulk unenrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_unenroll'])) {
    $courseId = (int)$_POST['course_id'];
    $enrollmentIds = $_POST['enrollment_ids'] ?? [];
    
    if ($courseId <= 0 || empty($enrollmentIds)) {
        $error = "Please select at least one enrollment to remove.";
    } else {
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($enrollmentIds as $enrollmentId) {
            $enrollmentId = (int)$enrollmentId;
            if ($enrollmentId <= 0) continue;
            
            $removeSql = "DELETE FROM course_enrollments WHERE id = ? AND course_id = ?";
            $result = executeQuery($conn, $removeSql, "ii", [$enrollmentId, $courseId]);
            
            if ($result) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        $message = "Bulk removal completed: $successCount students removed";
        if ($errorCount > 0) $message .= ", $errorCount failed";
        $message .= ".";
        
        if ($errorCount == 0) {
            $success = $message;
        } else {
            $error = $message;
        }
    }
}

// Get courses for selection
$courses = fetchMultipleRows($conn, 
    "SELECT c.id, c.course_code, c.course_name, c.department,
            COUNT(ce.id) as enrollment_count
     FROM courses c
     LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
     WHERE c.is_active = 1
     GROUP BY c.id
     ORDER BY c.department, c.course_code");

// Get students for selection
$students = fetchMultipleRows($conn, 
    "SELECT id, full_name, username, email, department 
     FROM users 
     WHERE role = 'student' AND is_active = 1 
     ORDER BY department, full_name");

// Get departments for filtering
$departments = fetchMultipleRows($conn, 
    "SELECT DISTINCT department FROM users WHERE role = 'student' AND department IS NOT NULL ORDER BY department");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Course Assignments - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .student-card {
            transition: all 0.2s;
            cursor: pointer;
        }
        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .student-card.selected {
            border-color: #0d6efd;
            background-color: #e3f2fd;
        }
        .course-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
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
                            <a class="nav-link active" href="student_assignments.php">
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
                    <h1 class="h2">
                        <i class="bi bi-person-plus"></i> Student Course Assignments
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="courses.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Courses
                            </a>
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

                <!-- Course Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-book"></i> Step 1: Select Course</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($courses as $course): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card course-card h-100" data-course-id="<?php echo $course['id']; ?>" onclick="selectCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_code']); ?>', '<?php echo htmlspecialchars($course['course_name']); ?>')">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                            <p class="card-text small"><?php echo htmlspecialchars($course['course_name']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($course['department']); ?></span>
                                                <small class="text-muted"><?php echo $course['enrollment_count']; ?> enrolled</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Selected Course Info -->
                <div id="course-info" class="card mb-4" style="display: none;">
                    <div class="card-body course-info-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Selected Course</h5>
                                <h4 id="selected-course-name" class="mb-0"></h4>
                                <p id="selected-course-code" class="mb-0 opacity-75"></p>
                            </div>
                            <button type="button" class="btn btn-light btn-sm" onclick="clearCourseSelection()">
                                <i class="bi bi-x"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bulk Enrollment Form -->
                <div id="enrollment-form" class="card mb-4" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-people-fill"></i> Step 2: Select Students to Enroll</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllStudents()">
                                <i class="bi bi-check-all"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllStudents()">
                                <i class="bi bi-x-square"></i> Clear All
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bulk-enroll-form">
                            <input type="hidden" id="bulk-course-id" name="course_id">
                            
                            <!-- Department Filter -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <select class="form-select" id="department-filter" onchange="filterStudents()">
                                        <option value="">All Departments</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                                <?php echo htmlspecialchars($dept['department']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="search-students" placeholder="Search students..." onkeyup="filterStudents()">
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary me-2" id="selected-count">0 selected</span>
                                        <button type="submit" name="bulk_enroll" class="btn btn-success" disabled id="enroll-btn">
                                            <i class="bi bi-plus-circle"></i> Enroll Selected
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Students Grid -->
                            <div class="row" id="students-grid">
                                <?php foreach ($students as $student): ?>
                                    <div class="col-md-4 mb-3 student-item" data-department="<?php echo htmlspecialchars($student['department'] ?? ''); ?>" data-name="<?php echo htmlspecialchars(strtolower($student['full_name'])); ?>" data-username="<?php echo htmlspecialchars(strtolower($student['username'])); ?>">
                                        <div class="card student-card" onclick="toggleStudent(<?php echo $student['id']; ?>)">
                                            <div class="card-body p-3">
                                                <div class="form-check">
                                                    <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" id="student-<?php echo $student['id']; ?>">
                                                    <label class="form-check-label w-100" for="student-<?php echo $student['id']; ?>">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: bold;">
                                                                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                                                <small class="text-muted">@<?php echo htmlspecialchars($student['username']); ?></small>
                                                                <?php if ($student['department']): ?>
                                                                    <div><span class="badge bg-light text-dark"><?php echo htmlspecialchars($student['department']); ?></span></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Current Enrollments Management -->
                <div id="current-enrollments-section" class="card" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Current Enrollments</h5>
                    </div>
                    <div class="card-body">
                        <div id="current-enrollments-content">
                            <!-- Content will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        let selectedCourseId = null;

        function selectCourse(courseId, courseCode, courseName) {
            selectedCourseId = courseId;
            
            // Update UI
            document.getElementById('selected-course-name').textContent = courseName;
            document.getElementById('selected-course-code').textContent = courseCode;
            document.getElementById('bulk-course-id').value = courseId;
            
            // Show course info and enrollment form
            document.getElementById('course-info').style.display = 'block';
            document.getElementById('enrollment-form').style.display = 'block';
            document.getElementById('current-enrollments-section').style.display = 'block';
            
            // Load current enrollments
            loadCurrentEnrollments(courseId);
            
            // Scroll to the form
            document.getElementById('course-info').scrollIntoView({behavior: 'smooth'});
        }

        function clearCourseSelection() {
            selectedCourseId = null;
            document.getElementById('course-info').style.display = 'none';
            document.getElementById('enrollment-form').style.display = 'none';
            document.getElementById('current-enrollments-section').style.display = 'none';
            clearAllStudents();
        }

        function toggleStudent(studentId) {
            const checkbox = document.getElementById('student-' + studentId);
            const card = checkbox.closest('.student-card');
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            
            updateSelectedCount();
        }

        function selectAllStudents() {
            const visibleCheckboxes = document.querySelectorAll('.student-item:not([style*="display: none"]) .student-checkbox');
            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
                checkbox.closest('.student-card').classList.add('selected');
            });
            updateSelectedCount();
        }

        function clearAllStudents() {
            document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.student-card').classList.remove('selected');
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.student-checkbox:checked').length;
            document.getElementById('selected-count').textContent = selectedCount + ' selected';
            document.getElementById('enroll-btn').disabled = selectedCount === 0;
        }

        function filterStudents() {
            const departmentFilter = document.getElementById('department-filter').value.toLowerCase();
            const searchTerm = document.getElementById('search-students').value.toLowerCase();
            
            document.querySelectorAll('.student-item').forEach(item => {
                const department = item.dataset.department.toLowerCase();
                const name = item.dataset.name;
                const username = item.dataset.username;
                
                const matchesDepartment = !departmentFilter || department.includes(departmentFilter);
                const matchesSearch = !searchTerm || name.includes(searchTerm) || username.includes(searchTerm);
                
                if (matchesDepartment && matchesSearch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                    // Unselect hidden students
                    const checkbox = item.querySelector('.student-checkbox');
                    if (checkbox.checked) {
                        checkbox.checked = false;
                        checkbox.closest('.student-card').classList.remove('selected');
                    }
                }
            });
            
            updateSelectedCount();
        }

        function loadCurrentEnrollments(courseId) {
            const container = document.getElementById('current-enrollments-content');
            container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            fetch('get_enrollments.php?course_id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        if (data.enrollments.length === 0) {
                            html = '<p class="text-muted text-center">No students currently enrolled.</p>';
                        } else {
                            html = '<form method="POST" id="bulk-unenroll-form">';
                            html += '<input type="hidden" name="course_id" value="' + courseId + '">';
                            html += '<div class="d-flex justify-content-between align-items-center mb-3">';
                            html += '<h6 class="mb-0">' + data.enrollments.length + ' students enrolled</h6>';
                            html += '<button type="submit" name="bulk_unenroll" class="btn btn-danger btn-sm" onclick="return confirm(\'Remove selected students from this course?\')"><i class="bi bi-trash"></i> Remove Selected</button>';
                            html += '</div>';
                            html += '<div class="row">';
                            
                            data.enrollments.forEach(enrollment => {
                                const enrollmentDate = new Date(enrollment.enrollment_date).toLocaleDateString();
                                html += `
                                    <div class="col-md-6 mb-2">
                                        <div class="card">
                                            <div class="card-body p-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="enrollment_ids[]" value="${enrollment.id}" id="enrollment-${enrollment.id}">
                                                    <label class="form-check-label w-100" for="enrollment-${enrollment.id}">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                                                ${enrollment.full_name.charAt(0).toUpperCase()}
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold">${enrollment.full_name}</div>
                                                                <small class="text-muted">Enrolled: ${enrollmentDate}</small>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            html += '</div></form>';
                        }
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<p class="text-danger text-center">Error loading enrollments.</p>';
                    }
                })
                .catch(error => {
                    container.innerHTML = '<p class="text-danger text-center">Network error occurred.</p>';
                });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCount();
        });
    </script>
</body>
</html>