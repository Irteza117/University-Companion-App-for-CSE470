<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Simple query that should work with basic schema
$sql = "SELECT a.id, a.title, a.description, a.due_date, 
               COALESCE(a.max_points, a.max_marks, 100) as max_points,
               c.course_code, c.course_name,
               CASE 
                   WHEN a.due_date < NOW() THEN 'overdue'
                   WHEN a.due_date < DATE_ADD(NOW(), INTERVAL 24 HOUR) THEN 'due_soon'
                   ELSE 'upcoming'
               END as status
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN course_enrollments ce ON c.id = ce.course_id
        WHERE ce.student_id = ? AND ce.status = 'enrolled' AND a.is_active = 1
        ORDER BY a.due_date ASC";

$assignments = fetchMultipleRows($conn, $sql, "i", [$userId]);
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
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../profile.php"><i class="bi bi-person"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../php/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
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
                </div>

                <!-- Assignment Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo count($assignments); ?></h4>
                                <p class="mb-0">Total Assignments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($assignments, function($a) { return $a['status'] === 'due_soon'; })); ?></h4>
                                <p class="mb-0">Due Soon</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($assignments, function($a) { return $a['status'] === 'overdue'; })); ?></h4>
                                <p class="mb-0">Overdue</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo count(array_filter($assignments, function($a) { return $a['status'] === 'upcoming'; })); ?></h4>
                                <p class="mb-0">Upcoming</p>
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
                                    </div>
                                    <div class="card-footer">
                                        <button type="button" 
                                                class="btn btn-outline-secondary btn-sm w-100" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewModal<?php echo $assignment['id']; ?>">
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
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
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i>
                                                <strong>Note:</strong> Assignment submission system is currently being updated. Please contact your instructor for submission instructions.
                                            </div>
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
    </style>
</body>
</html>