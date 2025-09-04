<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();

// Get all notices for students
$sql = "SELECT n.id, n.title, n.content, n.created_at, n.is_urgent, u.full_name as author
        FROM notices n
        JOIN users u ON n.author_id = u.id
        WHERE (n.target_audience = 'all' OR n.target_audience = 'students') 
        AND n.is_active = 1 
        AND (n.expires_at IS NULL OR n.expires_at > NOW())
        ORDER BY n.is_urgent DESC, n.created_at DESC";
$notices = fetchMultipleRows($conn, $sql);

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
                        <i class="bi bi-megaphone"></i> Notice Board
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Stay updated with latest announcements</small>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchNotices" placeholder="Search notices...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary" onclick="filterNotices('all')">All</button>
                            <button class="btn btn-outline-danger" onclick="filterNotices('urgent')">Urgent</button>
                            <button class="btn btn-outline-info" onclick="filterNotices('recent')">Recent</button>
                        </div>
                    </div>
                </div>

                <!-- Notices List -->
                <div class="row" id="noticesContainer">
                    <?php if (empty($notices)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle"></i>
                                No notices available at the moment.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notices as $notice): ?>
                            <div class="col-md-6 mb-3 notice-item" data-urgent="<?php echo $notice['is_urgent'] ? 'true' : 'false'; ?>" data-date="<?php echo $notice['created_at']; ?>">
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        setActiveNavItem('notices.php');

        // Search functionality
        document.getElementById('searchNotices').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const notices = document.querySelectorAll('.notice-item');
            
            notices.forEach(notice => {
                const title = notice.querySelector('.card-title').textContent.toLowerCase();
                const content = notice.querySelector('.card-text').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || content.includes(searchTerm)) {
                    notice.style.display = '';
                } else {
                    notice.style.display = 'none';
                }
            });
        });

        // Filter functionality
        function filterNotices(filter) {
            const notices = document.querySelectorAll('.notice-item');
            const now = new Date();
            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            
            notices.forEach(notice => {
                notice.style.display = '';
                
                switch(filter) {
                    case 'urgent':
                        if (notice.dataset.urgent !== 'true') {
                            notice.style.display = 'none';
                        }
                        break;
                    case 'recent':
                        const noticeDate = new Date(notice.dataset.date);
                        if (noticeDate < weekAgo) {
                            notice.style.display = 'none';
                        }
                        break;
                    case 'all':
                    default:
                        // Show all notices
                        break;
                }
            });
            
            // Update button states
            document.querySelectorAll('.btn-outline-secondary, .btn-outline-danger, .btn-outline-info').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>