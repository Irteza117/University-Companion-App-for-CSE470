<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $courseId = $_POST['course_id'];
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];
    
    try {
        // Check if feedback already exists for this course
        $checkSql = "SELECT id FROM course_feedback WHERE course_id = ? AND student_id = ?";
        $existing = fetchSingleRow($conn, $checkSql, "ii", [$courseId, $userId]);
        
        if ($existing) {
            // Update existing feedback
            $sql = "UPDATE course_feedback 
                    SET rating = ?, comments = ?, submitted_at = NOW() 
                    WHERE course_id = ? AND student_id = ?";
            executeQuery($conn, $sql, "isii", [$rating, $comments, $courseId, $userId]);
            $success = "Feedback updated successfully!";
        } else {
            // Insert new feedback
            $sql = "INSERT INTO course_feedback (course_id, student_id, rating, comments, submitted_at)
                    VALUES (?, ?, ?, ?, NOW())";
            executeQuery($conn, $sql, "iiss", [$courseId, $userId, $rating, $comments]);
            $success = "Feedback submitted successfully!";
        }
    } catch (Exception $e) {
        $error = "Failed to submit feedback: " . $e->getMessage();
    }
}

// Get student's enrolled courses with feedback status
$coursesSql = "SELECT c.id, c.course_code, c.course_name, c.description,
                      u.full_name as faculty_name,
                      cf.rating, cf.comments, cf.submitted_at,
                      ROUND(AVG(cf_all.rating), 1) as avg_rating,
                      COUNT(cf_all.id) as feedback_count
               FROM courses c
               JOIN course_enrollments ce ON c.id = ce.course_id
               JOIN users u ON c.faculty_id = u.id
               LEFT JOIN course_feedback cf ON c.id = cf.course_id AND cf.student_id = ?
               LEFT JOIN course_feedback cf_all ON c.id = cf_all.course_id
               WHERE ce.student_id = ? AND ce.status = 'enrolled'
               GROUP BY c.id, c.course_code, c.course_name, c.description, u.full_name, 
                        cf.rating, cf.comments, cf.submitted_at
               ORDER BY c.course_code";
$courses = fetchMultipleRows($conn, $coursesSql, "ii", [$userId, $userId]);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Feedback - University Companion</title>
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
                            <a class="nav-link active" href="feedback.php">
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
                        <i class="bi bi-chat-square-text"></i> Course Feedback
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Share your experience and help improve courses</small>
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

                <!-- Feedback Statistics -->
                <div class="row mb-4">
                    <?php
                    $totalCourses = count($courses);
                    $submittedFeedback = count(array_filter($courses, function($c) { return $c['rating'] !== null; }));
                    $pendingFeedback = $totalCourses - $submittedFeedback;
                    $avgRatingGiven = 0;
                    if ($submittedFeedback > 0) {
                        $totalRating = array_sum(array_map(function($c) { return $c['rating'] ?: 0; }, $courses));
                        $avgRatingGiven = round($totalRating / $submittedFeedback, 1);
                    }
                    ?>
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
                                <h4><?php echo $submittedFeedback; ?></h4>
                                <p class="mb-0">Feedback Submitted</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $pendingFeedback; ?></h4>
                                <p class="mb-0">Pending Feedback</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $avgRatingGiven; ?></h4>
                                <p class="mb-0">Avg Rating Given</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses List -->
                <?php if (empty($courses)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-journal-x display-1 text-muted"></i>
                        <h4 class="mt-3">No enrolled courses</h4>
                        <p class="text-muted">You need to be enrolled in courses to provide feedback</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 course-card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($course['faculty_name']); ?></small>
                                            </div>
                                            <?php if ($course['rating']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Submitted
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-clock"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                                        <p class="card-text small"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                        
                                        <!-- Course Rating Summary -->
                                        <?php if ($course['avg_rating']): ?>
                                            <div class="rating-summary mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="stars me-2">
                                                        <?php
                                                        $avgRating = $course['avg_rating'];
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $avgRating) {
                                                                echo '<i class="bi bi-star-fill text-warning"></i>';
                                                            } elseif ($i - 0.5 <= $avgRating) {
                                                                echo '<i class="bi bi-star-half text-warning"></i>';
                                                            } else {
                                                                echo '<i class="bi bi-star text-muted"></i>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                    <span class="small text-muted">
                                                        <?php echo $avgRating; ?>/5 (<?php echo $course['feedback_count']; ?> reviews)
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- User's Feedback -->
                                        <?php if ($course['rating']): ?>
                                            <div class="user-feedback mb-3">
                                                <div class="bg-light p-3 rounded">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <strong class="me-2">Your Rating:</strong>
                                                        <div class="stars">
                                                            <?php
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                echo $i <= $course['rating'] ? 
                                                                    '<i class="bi bi-star-fill text-warning"></i>' : 
                                                                    '<i class="bi bi-star text-muted"></i>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <?php if ($course['comments']): ?>
                                                        <small><?php echo nl2br(htmlspecialchars($course['comments'])); ?></small>
                                                    <?php endif; ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            Submitted: <?php echo date('M j, Y', strtotime($course['submitted_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <button type="button" 
                                                class="btn btn-primary btn-sm w-100" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#feedbackModal<?php echo $course['id']; ?>">
                                            <i class="bi bi-chat-square-text"></i>
                                            <?php echo $course['rating'] ? 'Update Feedback' : 'Give Feedback'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Feedback Modal -->
                            <div class="modal fade" id="feedbackModal<?php echo $course['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Course Feedback - <?php echo htmlspecialchars($course['course_code']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <h6><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                                    <p class="text-muted mb-0">Faculty: <?php echo htmlspecialchars($course['faculty_name']); ?></p>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Rating *</label>
                                                    <div class="rating-input">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" 
                                                                       type="radio" 
                                                                       name="rating" 
                                                                       id="rating<?php echo $course['id']; ?>_<?php echo $i; ?>"
                                                                       value="<?php echo $i; ?>"
                                                                       <?php echo ($course['rating'] == $i) ? 'checked' : ''; ?>
                                                                       required>
                                                                <label class="form-check-label" 
                                                                       for="rating<?php echo $course['id']; ?>_<?php echo $i; ?>">
                                                                    <?php echo $i; ?> 
                                                                    <?php
                                                                    for ($j = 1; $j <= $i; $j++) {
                                                                        echo '<i class="bi bi-star-fill text-warning"></i>';
                                                                    }
                                                                    ?>
                                                                </label>
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <div class="form-text">
                                                        1 = Poor, 2 = Fair, 3 = Good, 4 = Very Good, 5 = Excellent
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="comments<?php echo $course['id']; ?>" class="form-label">
                                                        Comments (Optional)
                                                    </label>
                                                    <textarea class="form-control" 
                                                              id="comments<?php echo $course['id']; ?>"
                                                              name="comments" 
                                                              rows="4"
                                                              placeholder="Share your experience with this course..."><?php echo htmlspecialchars($course['comments'] ?: ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancel
                                                </button>
                                                <button type="submit" name="submit_feedback" class="btn btn-primary">
                                                    <i class="bi bi-send"></i> Submit Feedback
                                                </button>
                                            </div>
                                        </form>
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
        setActiveNavItem('feedback.php');
    </script>

    <style>
        .course-card {
            transition: transform 0.2s;
        }
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stars i {
            font-size: 0.9rem;
        }
        .rating-input .form-check {
            margin-bottom: 10px;
        }
        .rating-input .form-check-label {
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .rating-input .form-check-input:checked + .form-check-label {
            background-color: #fff3cd;
        }
        .user-feedback {
            font-size: 0.9rem;
        }
    </style>
</body>
</html>