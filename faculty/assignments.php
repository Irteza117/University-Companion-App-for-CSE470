<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle new assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $courseId = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $dueDate = $_POST['due_date'];
    $maxPoints = $_POST['max_points'];
    
    try {
        $sql = "INSERT INTO assignments (course_id, title, description, due_date, max_points, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        executeQuery($conn, $sql, "isssii", [$courseId, $title, $description, $dueDate, $maxPoints, $userId]);
        $success = "Assignment created successfully!";
    } catch (Exception $e) {
        $error = "Failed to create assignment: " . $e->getMessage();
    }
}

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

// Get faculty's courses
$coursesSql = "SELECT id, course_code, course_name FROM courses WHERE faculty_id = ? ORDER BY course_code";
$courses = fetchMultipleRows($conn, $coursesSql, "i", [$userId]);

// Get faculty's assignments with submission counts
$assignmentsSql = "SELECT a.id, a.title, a.description, a.due_date, a.max_points, a.created_at,
                          c.course_code, c.course_name,
                          COUNT(s.id) as total_submissions,
                          COUNT(CASE WHEN s.grade IS NOT NULL THEN 1 END) as graded_submissions
                   FROM assignments a
                   JOIN courses c ON a.course_id = c.id
                   LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
                   WHERE c.faculty_id = ? AND a.is_active = 1
                   GROUP BY a.id, a.title, a.description, a.due_date, a.max_points, a.created_at, c.course_code, c.course_name
                   ORDER BY a.due_date DESC";
$assignments = fetchMultipleRows($conn, $assignmentsSql, "i", [$userId]);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Management - University Companion</title>
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
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-clipboard-check"></i> Assignment Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                            <i class="bi bi-plus-circle"></i> Create Assignment
                        </button>
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
                    $totalSubmissions = array_sum(array_column($assignments, 'total_submissions'));
                    $totalGraded = array_sum(array_column($assignments, 'graded_submissions'));
                    $pendingGrading = $totalSubmissions - $totalGraded;
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
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $totalSubmissions; ?></h4>
                                <p class="mb-0">Total Submissions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $totalGraded; ?></h4>
                                <p class="mb-0">Graded</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $pendingGrading; ?></h4>
                                <p class="mb-0">Pending Grading</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignments List -->
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard-x display-1 text-muted"></i>
                        <h4 class="mt-3">No assignments created yet</h4>
                        <p class="text-muted">Click "Create Assignment" to get started</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Your Assignments</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Assignment</th>
                                            <th>Course</th>
                                            <th>Due Date</th>
                                            <th>Points</th>
                                            <th>Submissions</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <?php
                                            $isOverdue = strtotime($assignment['due_date']) < time();
                                            $submissionRate = $assignment['total_submissions'] > 0 ? 
                                                round(($assignment['graded_submissions'] / $assignment['total_submissions']) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Created: <?php echo date('M j, Y', strtotime($assignment['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo htmlspecialchars($assignment['course_code']); ?>
                                                    </span>
                                                    <br>
                                                    <small><?php echo htmlspecialchars($assignment['course_name']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
                                                    <?php if ($isOverdue): ?>
                                                        <br><span class="badge bg-danger">Overdue</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo $assignment['max_points']; ?></strong> pts
                                                </td>
                                                <td>
                                                    <strong><?php echo $assignment['total_submissions']; ?></strong> submitted
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo $assignment['graded_submissions']; ?> graded (<?php echo $submissionRate; ?>%)
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($assignment['total_submissions'] == 0): ?>
                                                        <span class="badge bg-secondary">No Submissions</span>
                                                    <?php elseif ($assignment['graded_submissions'] == $assignment['total_submissions']): ?>
                                                        <span class="badge bg-success">All Graded</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending Review</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <a href="assignment_submissions.php?id=<?php echo $assignment['id']; ?>" 
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-eye"></i> View Submissions
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-outline-secondary btn-sm"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal<?php echo $assignment['id']; ?>">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
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

    <!-- Create Assignment Modal -->
    <div class="modal fade" id="createAssignmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course *</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Assignment Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required
                                      placeholder="Provide detailed instructions for the assignment..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date *</label>
                                    <input type="datetime-local" class="form-control" id="due_date" name="due_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_points" class="form-label">Maximum Points *</label>
                                    <input type="number" class="form-control" id="max_points" name="max_points" min="1" max="1000" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_assignment" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        setActiveNavItem('assignments.php');

        // Set minimum due date to current date/time
        document.addEventListener('DOMContentLoaded', function() {
            const dueDateInput = document.getElementById('due_date');
            const now = new Date();
            const offsetMs = now.getTimezoneOffset() * 60 * 1000;
            const localISOTime = (new Date(now.getTime() - offsetMs)).toISOString().slice(0, 16);
            dueDateInput.min = localISOTime;
        });
    </script>
</body>
</html>