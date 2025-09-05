<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle form submissions
$success = '';
$error = '';

// Handle notice creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_notice'])) {
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = sanitizeInput($_POST['content'] ?? '');
    $targetAudience = sanitizeInput($_POST['target_audience'] ?? 'all');
    $isUrgent = isset($_POST['is_urgent']) ? 1 : 0;
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    
    if (empty($title) || empty($content)) {
        $error = 'Please fill in all required fields.';
    } else {
        $sql = "INSERT INTO notices (title, content, author_id, target_audience, is_urgent, expires_at, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        $result = insertData($conn, $sql, "ssiiss", [$title, $content, $_SESSION['user_id'], $targetAudience, $isUrgent, $expiresAt]);
        
        if ($result > 0) {
            $success = "Notice posted successfully.";
        } else {
            $error = "Failed to post notice.";
        }
    }
}

// Handle notice status toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_notice_status'])) {
    $noticeId = (int)$_POST['notice_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;
    
    $sql = "UPDATE notices SET is_active = ? WHERE id = ?";
    $result = updateData($conn, $sql, "ii", [$newStatus, $noticeId]);
    
    if ($result >= 0) {
        $success = "Notice status updated successfully.";
    } else {
        $error = "Failed to update notice status.";
    }
}

// Handle notice deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_notice'])) {
    $noticeId = (int)$_POST['notice_id'];
    
    $sql = "UPDATE notices SET is_active = 0 WHERE id = ?";
    $result = updateData($conn, $sql, "i", [$noticeId]);
    
    if ($result >= 0) {
        $success = "Notice deleted successfully.";
    } else {
        $error = "Failed to delete notice.";
    }
}

// Get filter parameters
$audienceFilter = $_GET['audience'] ?? 'all';
$urgentFilter = $_GET['urgent'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'active';

// Build query with filters
$whereConditions = ['n.is_active = 1'];
$params = [];
$types = "";

if ($audienceFilter != 'all') {
    $whereConditions[] = "n.target_audience = ?";
    $params[] = $audienceFilter;
    $types .= "s";
}

if ($urgentFilter == 'urgent') {
    $whereConditions[] = "n.is_urgent = 1";
} elseif ($urgentFilter == 'normal') {
    $whereConditions[] = "n.is_urgent = 0";
}

if ($statusFilter == 'active') {
    $whereConditions[] = "(n.expires_at IS NULL OR n.expires_at > NOW())";
} elseif ($statusFilter == 'expired') {
    $whereConditions[] = "n.expires_at <= NOW()";
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Get notices with author information
$sql = "SELECT n.*, u.full_name as author_name
        FROM notices n
        JOIN users u ON n.author_id = u.id
        $whereClause
        ORDER BY n.is_urgent DESC, n.created_at DESC";

$notices = fetchMultipleRows($conn, $sql, $types, $params);

// Get statistics
$stats = fetchSingleRow($conn, "SELECT 
    COUNT(*) as total_notices,
    SUM(CASE WHEN is_urgent = 1 THEN 1 ELSE 0 END) as urgent_notices,
    SUM(CASE WHEN target_audience = 'students' THEN 1 ELSE 0 END) as student_notices,
    SUM(CASE WHEN target_audience = 'faculty' THEN 1 ELSE 0 END) as faculty_notices,
    SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_notices
    FROM notices WHERE is_active = 1");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Management - University Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4 primary-text fs-4 fw-bold text-uppercase border-bottom">
                <i class="bi bi-mortarboard-fill me-2"></i>Admin Panel
            </div>
            <div class="list-group list-group-flush my-3">
                <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="users.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-people me-2"></i>User Management
                </a>
                <a href="courses.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-book me-2"></i>Courses
                </a>
                <a href="notices.php" class="list-group-item list-group-item-action bg-transparent text-white active">
                    <i class="bi bi-megaphone me-2"></i>Notices
                </a>
                <a href="events.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-calendar-event me-2"></i>Events
                </a>

            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../php/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-megaphone"></i> Notice Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
                            <i class="bi bi-plus"></i> Post Notice
                        </button>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['total_notices']; ?></h4>
                                <p class="mb-0">Total Notices</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['urgent_notices']; ?></h4>
                                <p class="mb-0">Urgent</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['student_notices']; ?></h4>
                                <p class="mb-0">For Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['faculty_notices']; ?></h4>
                                <p class="mb-0">For Faculty</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['expired_notices']; ?></h4>
                                <p class="mb-0">Expired</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="audience" class="form-label">Target Audience</label>
                                <select class="form-select" name="audience">
                                    <option value="all" <?php echo $audienceFilter == 'all' ? 'selected' : ''; ?>>All Audiences</option>
                                    <option value="students" <?php echo $audienceFilter == 'students' ? 'selected' : ''; ?>>Students</option>
                                    <option value="faculty" <?php echo $audienceFilter == 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                    <option value="all" <?php echo $audienceFilter == 'all' ? 'selected' : ''; ?>>Everyone</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="urgent" class="form-label">Priority</label>
                                <select class="form-select" name="urgent">
                                    <option value="all" <?php echo $urgentFilter == 'all' ? 'selected' : ''; ?>>All Priorities</option>
                                    <option value="urgent" <?php echo $urgentFilter == 'urgent' ? 'selected' : ''; ?>>Urgent Only</option>
                                    <option value="normal" <?php echo $urgentFilter == 'normal' ? 'selected' : ''; ?>>Normal Only</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Notices List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Notices</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($notices)): ?>
                            <div class="text-center p-4">
                                <i class="bi bi-megaphone display-1 text-muted"></i>
                                <p class="text-muted mt-3">No notices found.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($notices as $notice): ?>
                                    <?php
                                    $isExpired = $notice['expires_at'] && strtotime($notice['expires_at']) < time();
                                    $isUrgent = $notice['is_urgent'];
                                    ?>
                                    <div class="list-group-item <?php echo $isUrgent ? 'border-start border-danger border-3' : ''; ?> <?php echo $isExpired ? 'bg-light text-muted' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h5 class="mb-0 me-2"><?php echo htmlspecialchars($notice['title']); ?></h5>
                                                    <?php if ($isUrgent): ?>
                                                        <span class="badge bg-danger">URGENT</span>
                                                    <?php endif; ?>
                                                    <?php if ($isExpired): ?>
                                                        <span class="badge bg-secondary ms-2">EXPIRED</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars(substr($notice['content'], 0, 200))); ?><?php echo strlen($notice['content']) > 200 ? '...' : ''; ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($notice['author_name']); ?> •
                                                    <i class="bi bi-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($notice['created_at'])); ?> •
                                                    <i class="bi bi-people"></i> <?php echo ucfirst($notice['target_audience']); ?>
                                                    <?php if ($notice['expires_at']): ?>
                                                        • <i class="bi bi-clock"></i> Expires: <?php echo date('M j, Y', strtotime($notice['expires_at'])); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="btn-group btn-group-sm ms-3">
                                                <button type="button" class="btn btn-outline-primary" onclick="viewNotice(<?php echo $notice['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="editNotice(<?php echo $notice['id']; ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                                    <input type="hidden" name="notice_id" value="<?php echo $notice['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $notice['is_active']; ?>">
                                                    <button type="submit" name="toggle_notice_status" class="btn btn-outline-warning">
                                                        <i class="bi bi-pause"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notice?')">
                                                    <input type="hidden" name="notice_id" value="<?php echo $notice['id']; ?>">
                                                    <button type="submit" name="delete_notice" class="btn btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Notice Modal -->
    <div class="modal fade" id="createNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Notice</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Notice Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Notice Content *</label>
                            <textarea class="form-control" name="content" rows="6" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="target_audience" class="form-label">Target Audience</label>
                                <select class="form-select" name="target_audience">
                                    <option value="all">Everyone</option>
                                    <option value="students">Students Only</option>
                                    <option value="faculty">Faculty Only</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expires_at" class="form-label">Expiry Date (Optional)</label>
                                <input type="datetime-local" class="form-control" name="expires_at">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_urgent" id="is_urgent">
                                <label class="form-check-label" for="is_urgent">
                                    <strong>Mark as Urgent</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_notice" class="btn btn-primary">Post Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('wrapper').classList.toggle('toggled');
        });

        function viewNotice(noticeId) {
            alert('View notice details functionality to be implemented');
        }

        function editNotice(noticeId) {
            alert('Edit notice functionality to be implemented');
        }
    </script>
</body>
</html>