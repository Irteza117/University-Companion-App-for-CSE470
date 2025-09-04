<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$error = '';
$success = '';

// Handle notice posting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_notice'])) {
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = sanitizeInput($_POST['content'] ?? '');
    $target_audience = sanitizeInput($_POST['target_audience'] ?? '');
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    
    if (empty($title) || empty($content) || empty($target_audience)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $sql = "INSERT INTO notices (title, content, author_id, target_audience, is_urgent, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $result = insertData($conn, $sql, "ssiis", [$title, $content, $_SESSION['user_id'], $target_audience, $is_urgent, $expires_at]);
            
            if ($result) {
                $success = 'Notice posted successfully!';
                // Clear form data
                $title = $content = $target_audience = $expires_at = '';
            } else {
                $error = 'Failed to post notice. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Error posting notice: ' . $e->getMessage();
        }
    }
}

// Get notices posted by this faculty
$sql = "SELECT n.id, n.title, n.content, n.created_at, n.is_urgent, n.target_audience, n.expires_at, n.is_active
        FROM notices n
        WHERE n.author_id = ?
        ORDER BY n.created_at DESC";
$myNotices = fetchMultipleRows($conn, $sql, "i", [$_SESSION['user_id']]);

// Get all notices visible to faculty
$sql = "SELECT n.id, n.title, n.content, n.created_at, n.is_urgent, u.full_name as author
        FROM notices n
        JOIN users u ON n.author_id = u.id
        WHERE (n.target_audience = 'all' OR n.target_audience = 'faculty') 
        AND n.is_active = 1 
        AND (n.expires_at IS NULL OR n.expires_at > NOW())
        ORDER BY n.is_urgent DESC, n.created_at DESC";
$allNotices = fetchMultipleRows($conn, $sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board - University Companion</title>
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
                            <a class="nav-link active" href="notices.php">
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
                        <i class="bi bi-megaphone"></i> Notice Board
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#postNoticeModal">
                            <i class="bi bi-plus"></i> Post Notice
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

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="noticeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-notices-tab" data-bs-toggle="tab" data-bs-target="#all-notices" type="button" role="tab">
                            <i class="bi bi-list-ul"></i> All Notices
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="my-notices-tab" data-bs-toggle="tab" data-bs-target="#my-notices" type="button" role="tab">
                            <i class="bi bi-person-check"></i> My Notices (<?php echo count($myNotices); ?>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="noticeTabContent">
                    <!-- All Notices Tab -->
                    <div class="tab-pane fade show active" id="all-notices" role="tabpanel">
                        <div class="row">
                            <?php if (empty($allNotices)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle"></i>
                                        No notices available.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($allNotices as $notice): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 <?php echo $notice['is_urgent'] ? 'border-danger' : ''; ?>">
                                            <?php if ($notice['is_urgent']): ?>
                                                <div class="card-header bg-danger text-white">
                                                    <i class="bi bi-exclamation-triangle"></i> URGENT
                                                </div>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <?php echo htmlspecialchars($notice['title']); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
                                                </p>
                                            </div>
                                            <div class="card-footer text-muted d-flex justify-content-between align-items-center">
                                                <small>
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($notice['author']); ?>
                                                </small>
                                                <small>
                                                    <i class="bi bi-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($notice['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- My Notices Tab -->
                    <div class="tab-pane fade" id="my-notices" role="tabpanel">
                        <div class="row">
                            <?php if (empty($myNotices)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle"></i>
                                        You haven't posted any notices yet.
                                        <br><br>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#postNoticeModal">
                                            <i class="bi bi-plus"></i> Post Your First Notice
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($myNotices as $notice): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 <?php echo $notice['is_urgent'] ? 'border-danger' : ''; ?>">
                                            <?php if ($notice['is_urgent']): ?>
                                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                                    <span><i class="bi bi-exclamation-triangle"></i> URGENT</span>
                                                    <span class="badge bg-light text-dark"><?php echo ucfirst($notice['target_audience']); ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <span>Notice</span>
                                                    <span class="badge bg-primary"><?php echo ucfirst($notice['target_audience']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <?php echo htmlspecialchars($notice['title']); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
                                                </p>
                                                <?php if ($notice['expires_at']): ?>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock"></i> Expires: <?php echo date('M j, Y g:i A', strtotime($notice['expires_at'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($notice['created_at'])); ?>
                                                </small>
                                                <div>
                                                    <span class="badge bg-<?php echo $notice['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $notice['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Post Notice Modal -->
    <div class="modal fade" id="postNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-megaphone"></i> Post New Notice
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Notice Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Notice Content *</label>
                            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="target_audience" class="form-label">Target Audience *</label>
                                <select class="form-control" id="target_audience" name="target_audience" required>
                                    <option value="">Select Audience</option>
                                    <option value="all">Everyone</option>
                                    <option value="students">Students Only</option>
                                    <option value="faculty">Faculty Only</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="expires_at" class="form-label">Expiry Date (Optional)</label>
                                <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_urgent" name="is_urgent">
                            <label class="form-check-label" for="is_urgent">
                                <i class="bi bi-exclamation-triangle text-danger"></i> Mark as Urgent
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="post_notice" class="btn btn-primary">
                            <i class="bi bi-send"></i> Post Notice
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
        setActiveNavItem('notices.php');
    </script>
</body>
</html>