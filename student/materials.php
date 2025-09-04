<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get materials for courses the student is enrolled in
$sql = "SELECT cm.id, cm.title, cm.description, cm.file_name, cm.file_size, cm.upload_date, cm.file_type,
               c.course_code, c.course_name, u.full_name as uploaded_by
        FROM course_materials cm
        JOIN courses c ON cm.course_id = c.id
        JOIN course_enrollments ce ON c.id = ce.course_id
        JOIN users u ON cm.uploaded_by = u.id
        WHERE ce.student_id = ? AND ce.status = 'enrolled' AND cm.is_active = 1
        ORDER BY cm.upload_date DESC";
$materials = fetchMultipleRows($conn, $sql, "i", [$userId]);

// Get enrolled courses for filtering
$sql = "SELECT DISTINCT c.id, c.course_code, c.course_name
        FROM courses c
        JOIN course_enrollments ce ON c.id = ce.course_id
        WHERE ce.student_id = ? AND ce.status = 'enrolled'
        ORDER BY c.course_code";
$courses = fetchMultipleRows($conn, $sql, "i", [$userId]);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - University Companion</title>
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
                            <a class="nav-link active" href="materials.php">
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
                        <i class="bi bi-file-earmark-text"></i> Course Materials
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Download study materials for your enrolled courses</small>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchMaterials" placeholder="Search materials...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-control" id="courseFilter">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Materials</h5>
                                        <h2><?php echo count($materials); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-file-earmark-text display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Enrolled Courses</h5>
                                        <h2><?php echo count($courses); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-book display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">This Week</h5>
                                        <h2><?php 
                                            $thisWeek = array_filter($materials, function($m) {
                                                return date('Y-W', strtotime($m['upload_date'])) === date('Y-W');
                                            });
                                            echo count($thisWeek);
                                        ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-calendar-week display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Size</h5>
                                        <h2><?php 
                                            $totalSize = array_sum(array_column($materials, 'file_size'));
                                            echo round($totalSize / (1024 * 1024), 1) . 'MB';
                                        ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-hdd display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Materials Grid -->
                <div class="row" id="materialsContainer">
                    <?php if (empty($materials)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle display-1"></i>
                                <h4 class="mt-3">No materials available</h4>
                                <p class="text-muted">Your instructors haven't uploaded any materials yet, or you're not enrolled in any courses.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($materials as $material): ?>
                            <div class="col-lg-4 col-md-6 mb-4 material-item" data-course="<?php echo $material['course_code']; ?>" data-course-id="<?php echo $material['id']; ?>">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($material['course_code']); ?></span>
                                        <span class="text-muted">
                                            <?php
                                            $iconClass = 'bi-file-earmark';
                                            if (str_contains($material['file_type'], 'pdf')) $iconClass = 'bi-file-earmark-pdf';
                                            elseif (str_contains($material['file_type'], 'word')) $iconClass = 'bi-file-earmark-word';
                                            elseif (str_contains($material['file_type'], 'presentation')) $iconClass = 'bi-file-earmark-ppt';
                                            elseif (str_contains($material['file_type'], 'zip')) $iconClass = 'bi-file-earmark-zip';
                                            ?>
                                            <i class="bi <?php echo $iconClass; ?>"></i>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted"><?php echo htmlspecialchars($material['course_name']); ?></small>
                                        </p>
                                        <?php if ($material['description']): ?>
                                            <p class="card-text"><?php echo htmlspecialchars($material['description']); ?></p>
                                        <?php endif; ?>
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <small class="text-muted">Size</small><br>
                                                <strong><?php echo round($material['file_size'] / 1024, 1); ?> KB</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Type</small><br>
                                                <strong><?php echo strtoupper(pathinfo($material['file_name'], PATHINFO_EXTENSION)); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($material['uploaded_by']); ?><br>
                                                <i class="bi bi-calendar"></i> <?php echo date('M j, Y', strtotime($material['upload_date'])); ?>
                                            </small>
                                            <a href="../uploads/materials/<?php echo htmlspecialchars($material['file_name']); ?>" 
                                               class="btn btn-primary btn-sm" target="_blank">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        setActiveNavItem('materials.php');

        // Search functionality
        document.getElementById('searchMaterials').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterMaterials();
        });

        // Course filter
        document.getElementById('courseFilter').addEventListener('change', function() {
            filterMaterials();
        });

        function filterMaterials() {
            const searchTerm = document.getElementById('searchMaterials').value.toLowerCase();
            const courseFilter = document.getElementById('courseFilter').value;
            const materials = document.querySelectorAll('.material-item');
            
            materials.forEach(material => {
                const title = material.querySelector('.card-title').textContent.toLowerCase();
                const description = material.querySelector('.card-text').textContent.toLowerCase();
                const course = material.dataset.course;
                const courseId = material.dataset.courseId;
                
                let showMaterial = true;
                
                // Search filter
                if (searchTerm && !title.includes(searchTerm) && !description.includes(searchTerm) && !course.toLowerCase().includes(searchTerm)) {
                    showMaterial = false;
                }
                
                // Course filter
                if (courseFilter && !course.includes(courseFilter)) {
                    showMaterial = false;
                }
                
                material.style.display = showMaterial ? '' : 'none';
            });
        }
    </script>
</body>
</html>