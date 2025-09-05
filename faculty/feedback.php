<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get faculty's courses with feedback statistics
$coursesSql = "SELECT c.id, c.course_code, c.course_name, c.description,
                      COUNT(cf.id) as feedback_count,
                      ROUND(AVG(cf.rating), 1) as avg_rating,
                      COUNT(DISTINCT ce.student_id) as enrolled_students
               FROM courses c
               LEFT JOIN course_feedback cf ON c.id = cf.course_id
               LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
               WHERE c.faculty_id = ?
               GROUP BY c.id, c.course_code, c.course_name, c.description
               ORDER BY c.course_code";
$courses = fetchMultipleRows($conn, $coursesSql, "i", [$userId]);

// Get selected course feedback details
$selectedCourseId = $_GET['course'] ?? '';
$feedback = [];
if ($selectedCourseId) {
    $feedbackSql = "SELECT cf.rating, cf.comments, cf.submitted_at,
                           u.full_name, u.student_id
                    FROM course_feedback cf
                    JOIN users u ON cf.student_id = u.id
                    JOIN courses c ON cf.course_id = c.id
                    WHERE cf.course_id = ? AND c.faculty_id = ?
                    ORDER BY cf.submitted_at DESC";
    $feedback = fetchMultipleRows($conn, $feedbackSql, "ii", [$selectedCourseId, $userId]);
    
    // Get selected course details
    $selectedCourseSql = "SELECT course_code, course_name FROM courses WHERE id = ? AND faculty_id = ?";
    $selectedCourse = fetchSingleRow($conn, $selectedCourseSql, "ii", [$selectedCourseId, $userId]);
}

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
                        <small class="text-muted">View student feedback for your courses</small>
                    </div>
                </div>

                <!-- Feedback Statistics -->
                <div class="row mb-4">
                    <?php
                    $totalCourses = count($courses);
                    $totalFeedback = array_sum(array_column($courses, 'feedback_count'));
                    $totalStudents = array_sum(array_column($courses, 'enrolled_students'));
                    $avgOverallRating = 0;
                    if ($totalFeedback > 0) {
                        $ratedCourses = array_filter($courses, function($c) { return $c['avg_rating'] !== null; });
                        if (!empty($ratedCourses)) {
                            $avgOverallRating = round(array_sum(array_column($ratedCourses, 'avg_rating')) / count($ratedCourses), 1);
                        }
                    }
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $totalCourses; ?></h4>
                                <p class="mb-0">Your Courses</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $totalFeedback; ?></h4>
                                <p class="mb-0">Total Feedback</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $totalStudents; ?></h4>
                                <p class="mb-0">Total Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $avgOverallRating; ?></h4>
                                <p class="mb-0">Avg Rating</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Select Course to View Feedback</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <select class="form-select" name="course" onchange="this.form.submit()">
                                    <option value="">Select a course...</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                                <?php echo ($selectedCourseId == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                            (<?php echo $course['feedback_count']; ?> feedback)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> View
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Courses Overview -->
                <?php if (empty($selectedCourseId)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Courses Overview</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($courses)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-journal-x display-1 text-muted"></i>
                                    <h4 class="mt-3">No courses found</h4>
                                    <p class="text-muted">You haven't been assigned to any courses yet</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($courses as $course): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card course-summary">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                                    <p class="card-text"><?php echo htmlspecialchars($course['course_name']); ?></p>
                                                    
                                                    <div class="stats mb-3">
                                                        <div class="row text-center">
                                                            <div class="col-4">
                                                                <strong><?php echo $course['enrolled_students']; ?></strong>
                                                                <br><small class="text-muted">Students</small>
                                                            </div>
                                                            <div class="col-4">
                                                                <strong><?php echo $course['feedback_count']; ?></strong>
                                                                <br><small class="text-muted">Feedback</small>
                                                            </div>
                                                            <div class="col-4">
                                                                <strong><?php echo $course['avg_rating'] ?: 'N/A'; ?></strong>
                                                                <br><small class="text-muted">Avg Rating</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($course['avg_rating']): ?>
                                                        <div class="rating-display text-center mb-3">
                                                            <?php
                                                            $rating = $course['avg_rating'];
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                if ($i <= $rating) {
                                                                    echo '<i class="bi bi-star-fill text-warning"></i>';
                                                                } elseif ($i - 0.5 <= $rating) {
                                                                    echo '<i class="bi bi-star-half text-warning"></i>';
                                                                } else {
                                                                    echo '<i class="bi bi-star text-muted"></i>';
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="d-grid">
                                                        <a href="?course=<?php echo $course['id']; ?>" 
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-eye"></i> View Feedback
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Individual Course Feedback -->
                <?php if ($selectedCourseId && isset($selectedCourse)): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Feedback for <?php echo htmlspecialchars($selectedCourse['course_code'] . ' - ' . $selectedCourse['course_name']); ?>
                            </h5>
                            <a href="feedback.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left"></i> Back to Overview
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($feedback)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-chat-square display-1 text-muted"></i>
                                    <h4 class="mt-3">No feedback yet</h4>
                                    <p class="text-muted">Students haven't provided feedback for this course</p>
                                </div>
                            <?php else: ?>
                                <!-- Feedback Summary -->
                                <div class="feedback-summary mb-4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="rating-breakdown">
                                                <h6>Rating Breakdown</h6>
                                                <?php
                                                $ratingCounts = array_count_values(array_column($feedback, 'rating'));
                                                for ($i = 5; $i >= 1; $i--) {
                                                    $count = $ratingCounts[$i] ?? 0;
                                                    $percentage = count($feedback) > 0 ? round(($count / count($feedback)) * 100) : 0;
                                                ?>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="me-2"><?php echo $i; ?> star</span>
                                                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                            <div class="progress-bar bg-warning" 
                                                                 style="width: <?php echo $percentage; ?>%"></div>
                                                        </div>
                                                        <span class="text-muted"><?php echo $count; ?></span>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="overall-rating text-center">
                                                <h6>Overall Rating</h6>
                                                <?php
                                                $avgRating = array_sum(array_column($feedback, 'rating')) / count($feedback);
                                                ?>
                                                <div class="display-4 text-warning mb-2"><?php echo round($avgRating, 1); ?></div>
                                                <div class="stars mb-2">
                                                    <?php
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
                                                <p class="text-muted"><?php echo count($feedback); ?> reviews</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Individual Feedback -->
                                <h6>Student Comments</h6>
                                <div class="feedback-list">
                                    <?php foreach ($feedback as $fb): ?>
                                        <div class="feedback-item border rounded p-3 mb-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($fb['full_name']); ?></strong>
                                                    <span class="text-muted">(ID: <?php echo htmlspecialchars($fb['student_id']); ?>)</span>
                                                </div>
                                                <div class="text-end">
                                                    <div class="stars mb-1">
                                                        <?php
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            echo $i <= $fb['rating'] ? 
                                                                '<i class="bi bi-star-fill text-warning"></i>' : 
                                                                '<i class="bi bi-star text-muted"></i>';
                                                        }
                                                        ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($fb['submitted_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <?php if ($fb['comments']): ?>
                                                <div class="comments">
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($fb['comments'])); ?></p>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted">
                                                    <em>No additional comments provided</em>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
        setActiveNavItem('feedback.php');
    </script>

    <style>
        .course-summary {
            transition: transform 0.2s;
        }
        .course-summary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .rating-display i {
            font-size: 1.2rem;
        }
        .stars i {
            font-size: 1rem;
        }
        .feedback-item {
            background-color: #f8f9fa;
        }
        .progress {
            height: 20px;
        }
    </style>
</body>
</html>