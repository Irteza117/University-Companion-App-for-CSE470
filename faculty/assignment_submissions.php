<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];
$assignmentId = $_GET['id'] ?? 0;

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grade'])) {
    $submissionId = $_POST['submission_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];
    
    try {
        $sql = "UPDATE assignment_submissions 
                SET grade = ?, feedback = ?, graded_at = NOW() 
                WHERE id = ?";
        executeQuery($conn, $sql, "isi", [$grade, $feedback, $submissionId]);
        $success = "Grade submitted successfully!";
    } catch (Exception $e) {
        $error = "Failed to submit grade: " . $e->getMessage();
    }
}

// Get assignment details
$assignmentSql = "SELECT a.id, a.title, a.description, a.due_date, a.max_points,
                         c.course_code, c.course_name
                  FROM assignments a
                  JOIN courses c ON a.course_id = c.id
                  WHERE a.id = ? AND c.faculty_id = ?";
$assignment = fetchSingleRow($conn, $assignmentSql, "ii", [$assignmentId, $userId]);

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Get all submissions for this assignment
$submissionsSql = "SELECT s.id, s.submission_text, s.file_name, s.file_path, s.submitted_at, 
                          s.grade, s.feedback, s.graded_at,
                          u.full_name, u.email, u.id as user_id
                   FROM assignment_submissions s
                   JOIN users u ON s.student_id = u.id
                   WHERE s.assignment_id = ?
                   ORDER BY s.submitted_at DESC";
$submissions = fetchMultipleRows($conn, $submissionsSql, "i", [$assignmentId]);

// Get enrolled students who haven't submitted
$noSubmissionSql = "SELECT u.full_name, u.email, u.id as user_id
                    FROM course_enrollments ce
                    JOIN users u ON ce.student_id = u.id
                    WHERE ce.course_id = (SELECT course_id FROM assignments WHERE id = ?) 
                    AND ce.status = 'enrolled'
                    AND u.id NOT IN (
                        SELECT student_id FROM assignment_submissions WHERE assignment_id = ?
                    )
                    ORDER BY u.full_name";
$noSubmissions = fetchMultipleRows($conn, $noSubmissionSql, "ii", [$assignmentId, $assignmentId]);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Submissions - University Companion</title>
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
                                <i class="bi bi-calendar-event"></i> Events
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
                        <i class="bi bi-clipboard-check"></i> Assignment Submissions
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="assignments.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Assignments
                        </a>
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

                <!-- Assignment Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Assignment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                <p class="text-muted mb-2">
                                    <strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?>
                                </p>
                                <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light p-3 rounded">
                                    <p class="mb-2">
                                        <strong>Due Date:</strong><br>
                                        <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Maximum Points:</strong><br>
                                        <?php echo $assignment['max_points']; ?> points
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submission Statistics -->
                <div class="row mb-4">
                    <?php
                    $totalSubmissions = count($submissions);
                    $gradedCount = count(array_filter($submissions, function($s) { return $s['grade'] !== null; }));
                    $pendingCount = $totalSubmissions - $gradedCount;
                    $noSubmissionCount = count($noSubmissions);
                    $totalStudents = $totalSubmissions + $noSubmissionCount;
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
                                <h4><?php echo $totalSubmissions; ?></h4>
                                <p class="mb-0">Submissions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $gradedCount; ?></h4>
                                <p class="mb-0">Graded</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $pendingCount; ?></h4>
                                <p class="mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submissions -->
                <?php if (empty($submissions)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="mt-3">No submissions yet</h4>
                        <p class="text-muted">Student submissions will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Student Submissions</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($submissions as $submission): ?>
                                <div class="submission-item border rounded p-3 mb-3 <?php echo $submission['grade'] !== null ? 'bg-light' : ''; ?>">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($submission['full_name']); ?></h6>
                                                    <small class="text-muted">
                                                        ID: <?php echo htmlspecialchars($submission['user_id']); ?> • 
                                                        <?php echo htmlspecialchars($submission['email']); ?>
                                                    </small>
                                                </div>
                                                <small class="text-muted">
                                                    Submitted: <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                                                </small>
                                            </div>
                                            
                                            <?php if ($submission['submission_text']): ?>
                                                <div class="mb-2">
                                                    <strong>Submission Text:</strong>
                                                    <div class="bg-white p-2 border rounded mt-1">
                                                        <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($submission['file_name']): ?>
                                                <div class="mb-2">
                                                    <strong>Attached File:</strong>
                                                    <div class="mt-1">
                                                        <i class="bi bi-paperclip"></i>
                                                        <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" 
                                                           target="_blank" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($submission['file_name']); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <?php if ($submission['grade'] !== null): ?>
                                                <!-- Already Graded -->
                                                <div class="bg-success text-white p-3 rounded">
                                                    <h5 class="mb-1">Grade: <?php echo $submission['grade']; ?>/<?php echo $assignment['max_points']; ?></h5>
                                                    <small>Graded: <?php echo date('M j, Y g:i A', strtotime($submission['graded_at'])); ?></small>
                                                    
                                                    <?php if ($submission['feedback']): ?>
                                                        <div class="mt-2">
                                                            <strong>Feedback:</strong>
                                                            <div class="mt-1">
                                                                <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-outline-light btn-sm mt-2 w-100"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#gradeModal<?php echo $submission['id']; ?>">
                                                        <i class="bi bi-pencil"></i> Edit Grade
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <!-- Not Graded -->
                                                <div class="text-center">
                                                    <button type="button" 
                                                            class="btn btn-primary w-100"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#gradeModal<?php echo $submission['id']; ?>">
                                                        <i class="bi bi-star"></i> Grade Submission
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grade Modal -->
                                <div class="modal fade" id="gradeModal<?php echo $submission['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Grade Submission - <?php echo htmlspecialchars($submission['full_name']); ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="grade<?php echo $submission['id']; ?>" class="form-label">
                                                            Grade (out of <?php echo $assignment['max_points']; ?> points) *
                                                        </label>
                                                        <input type="number" 
                                                               class="form-control" 
                                                               id="grade<?php echo $submission['id']; ?>"
                                                               name="grade" 
                                                               min="0" 
                                                               max="<?php echo $assignment['max_points']; ?>"
                                                               step="0.1"
                                                               value="<?php echo $submission['grade']; ?>"
                                                               required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="feedback<?php echo $submission['id']; ?>" class="form-label">
                                                            Feedback (Optional)
                                                        </label>
                                                        <textarea class="form-control" 
                                                                  id="feedback<?php echo $submission['id']; ?>"
                                                                  name="feedback" 
                                                                  rows="4"
                                                                  placeholder="Provide feedback to the student..."><?php echo htmlspecialchars($submission['feedback']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        Cancel
                                                    </button>
                                                    <button type="submit" name="submit_grade" class="btn btn-primary">
                                                        <i class="bi bi-star"></i> Submit Grade
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Students who haven't submitted -->
                <?php if (!empty($noSubmissions)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0 text-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                Students Who Haven't Submitted (<?php echo count($noSubmissions); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($noSubmissions as $student): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-x text-warning me-2"></i>
                                            <div>
                                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    ID: <?php echo htmlspecialchars($student['user_id']); ?>
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
        setActiveNavItem('assignments.php');
    </script>

    <style>
        .submission-item {
            transition: all 0.2s;
        }
        .submission-item:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html>