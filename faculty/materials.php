<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$error = '';
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_material'])) {
    $course_id = sanitizeInput($_POST['course_id'] ?? '');
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($course_id) || empty($title) || !isset($_FILES['material_file'])) {
        $error = 'Please fill in all required fields and select a file.';
    } else {
        $file = $_FILES['material_file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload failed.';
        } else {
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                           'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                           'application/zip', 'text/plain'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, ZIP, TXT';
            } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
                $error = 'File size must be less than 10MB.';
            } else {
                // Create upload directory if it doesn't exist
                $uploadDir = '../uploads/materials/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Save to database
                    $sql = "INSERT INTO course_materials (course_id, title, description, file_name, file_path, file_size, file_type, uploaded_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $result = insertData($conn, $sql, "isssiisi", [
                        $course_id, $title, $description, $fileName, 
                        '/uploads/materials/' . $fileName, $file['size'], $file['type'], $_SESSION['user_id']
                    ]);
                    
                    if ($result) {
                        $success = 'Material uploaded successfully!';
                    } else {
                        $error = 'Failed to save material information.';
                        unlink($filePath); // Remove uploaded file
                    }
                } else {
                    $error = 'Failed to upload file.';
                }
            }
        }
    }
}

// Handle material deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_material'])) {
    $material_id = intval($_POST['material_id']);
    
    // Get material info first
    $sql = "SELECT file_path FROM course_materials WHERE id = ? AND uploaded_by = ?";
    $material = fetchSingleRow($conn, $sql, "ii", [$material_id, $_SESSION['user_id']]);
    
    if ($material) {
        // Delete from database
        $sql = "UPDATE course_materials SET is_active = 0 WHERE id = ? AND uploaded_by = ?";
        $result = updateData($conn, $sql, "ii", [$material_id, $_SESSION['user_id']]);
        
        if ($result) {
            $success = 'Material deleted successfully!';
        } else {
            $error = 'Failed to delete material.';
        }
    } else {
        $error = 'Material not found or you do not have permission to delete it.';
    }
}

// Get faculty's assigned courses
$sql = "SELECT c.id, c.course_code, c.course_name 
        FROM course_assignments ca
        JOIN courses c ON ca.course_id = c.id
        WHERE ca.faculty_id = ?
        ORDER BY c.course_code";
$courses = fetchMultipleRows($conn, $sql, "i", [$_SESSION['user_id']]);

// Get materials uploaded by this faculty
$sql = "SELECT cm.id, cm.title, cm.description, cm.file_name, cm.file_size, cm.upload_date, 
               c.course_code, c.course_name, cm.file_type
        FROM course_materials cm
        JOIN courses c ON cm.course_id = c.id
        WHERE cm.uploaded_by = ? AND cm.is_active = 1
        ORDER BY cm.upload_date DESC";
$materials = fetchMultipleRows($conn, $sql, "i", [$_SESSION['user_id']]);

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
                        <i class="bi bi-file-earmark-text"></i> Course Materials
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal">
                            <i class="bi bi-cloud-upload"></i> Upload Material
                        </button>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
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
                                        <h5 class="card-title">Courses</h5>
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
                    <div class="col-md-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">This Month</h5>
                                        <h2><?php 
                                            $thisMonth = array_filter($materials, function($m) {
                                                return date('Y-m', strtotime($m['upload_date'])) === date('Y-m');
                                            });
                                            echo count($thisMonth);
                                        ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-calendar-month display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Materials List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul"></i> My Uploaded Materials
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($materials)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                                <h4 class="mt-3">No materials uploaded yet</h4>
                                <p class="text-muted">Start by uploading your first course material</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal">
                                    <i class="bi bi-cloud-upload"></i> Upload Material
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th>Course</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Upload Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materials as $material): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($material['title']); ?></strong>
                                                        <?php if ($material['description']): ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($material['description']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($material['course_code']); ?></span>
                                                    <br>
                                                    <small><?php echo htmlspecialchars($material['course_name']); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $iconClass = 'bi-file-earmark';
                                                    if (str_contains($material['file_type'], 'pdf')) $iconClass = 'bi-file-earmark-pdf';
                                                    elseif (str_contains($material['file_type'], 'word')) $iconClass = 'bi-file-earmark-word';
                                                    elseif (str_contains($material['file_type'], 'presentation')) $iconClass = 'bi-file-earmark-ppt';
                                                    elseif (str_contains($material['file_type'], 'zip')) $iconClass = 'bi-file-earmark-zip';
                                                    ?>
                                                    <i class="bi <?php echo $iconClass; ?> text-primary"></i>
                                                    <?php echo strtoupper(pathinfo($material['file_name'], PATHINFO_EXTENSION)); ?>
                                                </td>
                                                <td><?php echo round($material['file_size'] / 1024, 1); ?> KB</td>
                                                <td><?php echo date('M j, Y', strtotime($material['upload_date'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../uploads/materials/<?php echo htmlspecialchars($material['file_name']); ?>" 
                                                           class="btn btn-outline-primary" target="_blank" title="Download">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteMaterial(<?php echo $material['id']; ?>, '<?php echo htmlspecialchars($material['title']); ?>')"
                                                                title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
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
            </main>
        </div>
    </div>

    <!-- Upload Material Modal -->
    <div class="modal fade" id="uploadMaterialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cloud-upload"></i> Upload Course Material
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course *</label>
                            <select class="form-control" id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Material Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="material_file" class="form-label">File *</label>
                            <input type="file" class="form-control" id="material_file" name="material_file" required
                                   accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.txt">
                            <div class="form-text">
                                Allowed types: PDF, DOC, DOCX, PPT, PPTX, ZIP, TXT. Maximum size: 10MB
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload_material" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Upload Material
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Material Form (Hidden) -->
    <form method="POST" action="" id="deleteMaterialForm" style="display: none;">
        <input type="hidden" id="delete_material_id" name="material_id">
        <input type="hidden" name="delete_material" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        setActiveNavItem('materials.php');

        // File validation
        document.getElementById('material_file').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    this.value = '';
                }
            }
        });

        // Delete material function
        function deleteMaterial(materialId, title) {
            if (confirm('Are you sure you want to delete "' + title + '"?')) {
                document.getElementById('delete_material_id').value = materialId;
                document.getElementById('deleteMaterialForm').submit();
            }
        }
    </script>
</body>
</html>