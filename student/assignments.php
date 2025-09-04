<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $assignmentId = $_POST['assignment_id'];
    $submissionText = $_POST['submission_text'] ?? '';
    
    try {
        // Handle file upload
        $fileName = null;
        $filePath = null;
        
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['submission_file']['name']);
            $filePath = $uploadDir . $fileName;
            
            // Validate file type and size
            $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'zip'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $maxSize = 10 * 1024 * 1024; // 10MB
            
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception('Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP');
            }
            
            if ($_FILES['submission_file']['size'] > $maxSize) {
                throw new Exception('File size exceeds 10MB limit');
            }
            
            if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $filePath)) {
                throw new Exception('Failed to upload file');
            }
        }
        
        // Check if submission already exists
        $checkSql = "SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?";
        $existing = fetchSingleRow($conn, $checkSql, "ii", [$assignmentId, $userId]);
        
        if ($existing) {
            // Update existing submission
            $sql = "UPDATE assignment_submissions 
                    SET submission_text = ?, file_name = ?, file_path = ?, submitted_at = NOW()
                    WHERE assignment_id = ? AND student_id = ?";
            executeQuery($conn, $sql, "sssii", [$submissionText, $fileName, $filePath, $assignmentId, $userId]);
        } else {
            // Create new submission
            $sql = "INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_name, file_path, submitted_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";
            executeQuery($conn, $sql, "iisss", [$assignmentId, $userId, $submissionText, $fileName, $filePath]);
        }
        
        $success = "Assignment submitted successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get student's assignments
$sql = "SELECT a.id, a.title, a.description, a.due_date, a.max_points,
               c.course_code, c.course_name,
               s.id as submission_id, s.submission_text, s.file_name, s.submitted_at, s.grade, s.feedback,
               CASE 
                   WHEN a.due_date < NOW() THEN 'overdue'
                   WHEN a.due_date < DATE_ADD(NOW(), INTERVAL 24 HOUR) THEN 'due_soon'
                   ELSE 'upcoming'
               END as status
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN course_enrollments ce ON c.id = ce.course_id
        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
        WHERE ce.student_id = ? AND ce.status = 'enrolled' AND a.is_active = 1
        ORDER BY a.due_date ASC";
$assignments = fetchMultipleRows($conn, $sql, "ii", [$userId, $userId]);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - University Companion</title>
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
                            <a class="nav-link active" href="assignments.php">
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
                        <i class="bi bi-clipboard-check"></i> My Assignments
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Submit and track your assignments</small>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Assignment Statistics -->
                <div class="row mb-4">
                    <?php
                    $totalAssignments = count($assignments);
                    $submittedCount = 0;
                    $overdueCount = 0;
                    $gradedCount = 0;
                    
                    foreach ($assignments as $assignment) {
                        if ($assignment['submission_id']) $submittedCount++;
                        if ($assignment['status'] === 'overdue' && !$assignment['submission_id']) $overdueCount++;
                        if ($assignment['grade'] !== null) $gradedCount++;
                    }
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $totalAssignments; ?></h4>
                                <p class="mb-0">Total Assignments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $submittedCount; ?></h4>
                                <p class="mb-0">Submitted</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body text-center">
                                <h4><?php echo $overdueCount; ?></h4>
                                <p class="mb-0">Overdue</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $gradedCount; ?></h4>
                                <p class="mb-0">Graded</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignments List -->
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard-x display-1 text-muted"></i>
                        <h4 class="mt-3">No assignments found</h4>
                        <p class="text-muted">Assignments will appear here when faculty creates them</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 assignment-card <?php echo $assignment['status']; ?>">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="mb-0">
                                                <?php echo htmlspecialchars($assignment['course_code']); ?>
                                            </h6>
                                            <span class="badge bg-<?php 
                                                echo $assignment['status'] === 'overdue' ? 'danger' :
                                                     ($assignment['status'] === 'due_soon' ? 'warning' : 'secondary');
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                        <p class="card-text small">
                                            <?php echo htmlspecialchars(substr($assignment['description'], 0, 100)) . '...'; ?>
                                        </p>
                                        
                                        <div class="assignment-details mb-3">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i>
                                                Due: <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="bi bi-star"></i>
                                                Points: <?php echo $assignment['max_points']; ?>
                                            </small>
                                        </div>

                                        <!-- Submission Status -->
                                        <?php if ($assignment['submission_id']): ?>
                                            <div class="submission-status mb-3">
                                                <div class="alert alert-success py-2">
                                                    <small>
                                                        <i class="bi bi-check-circle"></i>
                                                        Submitted: <?php echo date('M j, Y g:i A', strtotime($assignment['submitted_at'])); ?>
                                                    </small>
                                                    <?php if ($assignment['file_name']): ?>
                                                        <br><small>
                                                            <i class="bi bi-paperclip"></i>
                                                            <?php echo htmlspecialchars($assignment['file_name']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if ($assignment['grade'] !== null): ?>
                                                    <div class="grade-display">
                                                        <strong>Grade: <?php echo $assignment['grade']; ?>/<?php echo $assignment['max_points']; ?></strong>
                                                        <?php if ($assignment['feedback']): ?>
                                                            <div class="feedback mt-2">
                                                                <small class="text-muted">
                                                                    <strong>Feedback:</strong><br>
                                                                    <?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?>
                                                                </small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <?php if (!$assignment['submission_id'] || ($assignment['status'] !== 'overdue' && $assignment['grade'] === null)): ?>
                                            <button type="button" 
                                                    class="btn btn-primary btn-sm w-100" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#submitModal<?php echo $assignment['id']; ?>">
                                                <i class="bi bi-upload"></i>
                                                <?php echo $assignment['submission_id'] ? 'Resubmit' : 'Submit'; ?> Assignment
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary btn-sm w-100" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?php echo $assignment['id']; ?>">
                                                <i class="bi bi-eye"></i> View Details
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Assignment Modal -->
                            <div class="modal fade" id="submitModal<?php echo $assignment['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Submit Assignment: <?php echo htmlspecialchars($assignment['title']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="modal-body">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Assignment Description</label>
                                                    <div class="bg-light p-3 rounded">
                                                        <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="submission_text<?php echo $assignment['id']; ?>" class="form-label">
                                                        Submission Text
                                                    </label>
                                                    <textarea class="form-control" 
                                                              id="submission_text<?php echo $assignment['id']; ?>"
                                                              name="submission_text" 
                                                              rows="5" 
                                                              placeholder="Enter your submission text here..."><?php 
                                                        echo $assignment['submission_id'] ? htmlspecialchars($assignment['submission_text']) : '';
                                                    ?></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="submission_file<?php echo $assignment['id']; ?>" class="form-label">
                                                        Upload File (Optional)
                                                    </label>
                                                    <input type="file" 
                                                           class="form-control" 
                                                           id="submission_file<?php echo $assignment['id']; ?>"
                                                           name="submission_file"
                                                           accept=".pdf,.doc,.docx,.txt,.zip">
                                                    <div class="form-text">
                                                        Allowed formats: PDF, DOC, DOCX, TXT, ZIP (Max 10MB)
                                                    </div>
                                                    <?php if ($assignment['file_name']): ?>
                                                        <small class="text-muted">
                                                            Current file: <?php echo htmlspecialchars($assignment['file_name']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancel
                                                </button>
                                                <button type="submit" name="submit_assignment" class="btn btn-primary">
                                                    <i class="bi bi-upload"></i> Submit Assignment
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- View Assignment Modal -->
                            <div class="modal fade" id="viewModal<?php echo $assignment['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Assignment Details: <?php echo htmlspecialchars($assignment['title']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Description:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Due Date:</strong> <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Max Points:</strong> <?php echo $assignment['max_points']; ?>
                                            </div>
                                            
                                            <?php if ($assignment['submission_id']): ?>
                                                <hr>
                                                <h6>Your Submission</h6>
                                                <div class="mb-3">
                                                    <strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($assignment['submitted_at'])); ?>
                                                </div>
                                                <?php if ($assignment['submission_text']): ?>
                                                    <div class="mb-3">
                                                        <strong>Text:</strong><br>
                                                        <div class="bg-light p-3 rounded">
                                                            <?php echo nl2br(htmlspecialchars($assignment['submission_text'])); ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($assignment['file_name']): ?>
                                                    <div class="mb-3">
                                                        <strong>File:</strong> <?php echo htmlspecialchars($assignment['file_name']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($assignment['grade'] !== null): ?>
                                                    <div class="mb-3">
                                                        <strong>Grade:</strong> 
                                                        <span class="badge bg-primary fs-6">
                                                            <?php echo $assignment['grade']; ?>/<?php echo $assignment['max_points']; ?>
                                                        </span>
                                                    </div>
                                                    <?php if ($assignment['feedback']): ?>
                                                        <div class="mb-3">
                                                            <strong>Feedback:</strong><br>
                                                            <div class="bg-light p-3 rounded">
                                                                <?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Close
                                            </button>
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
        setActiveNavItem('assignments.php');
    </script>

    <style>
        .assignment-card {
            transition: transform 0.2s;
        }
        .assignment-card:hover {
            transform: translateY(-2px);
        }
        .assignment-card.overdue {
            border-left: 4px solid #dc3545;
        }
        .assignment-card.due_soon {
            border-left: 4px solid #ffc107;
        }
        .assignment-card.upcoming {
            border-left: 4px solid #28a745;
        }
        .submission-status .alert {
            margin-bottom: 0.5rem;
        }
        .grade-display {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .feedback {
            max-height: 100px;
            overflow-y: auto;
        }
    </style>
</body>
</html>