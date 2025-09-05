<?php
require_once '../php/config.php';
requireRole('student');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle event registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    $eventId = $_POST['event_id'];
    
    try {
        // Check if already registered
        $checkSql = "SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?";
        $existing = fetchSingleRow($conn, $checkSql, "ii", [$eventId, $userId]);
        
        if ($existing) {
            $error = "You are already registered for this event.";
        } else {
            $sql = "INSERT INTO event_registrations (event_id, user_id, registered_at) VALUES (?, ?, NOW())";
            executeQuery($conn, $sql, "ii", [$eventId, $userId]);
            $success = "Successfully registered for the event!";
        }
    } catch (Exception $e) {
        $error = "Failed to register for event: " . $e->getMessage();
    }
}

// Handle event unregistration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unregister_event'])) {
    $eventId = $_POST['event_id'];
    
    try {
        $sql = "DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?";
        executeQuery($conn, $sql, "ii", [$eventId, $userId]);
        $success = "Successfully unregistered from the event.";
    } catch (Exception $e) {
        $error = "Failed to unregister from event: " . $e->getMessage();
    }
}

// Get search parameters
$searchTerm = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$dateFilter = $_GET['date_filter'] ?? 'upcoming';

// Build events query
$sql = "SELECT e.id, e.title, e.description, e.event_date, e.location, e.category, e.max_participants,
               u.full_name as organizer_name,
               COUNT(er.id) as registered_count,
               MAX(CASE WHEN er.user_id = ? THEN 1 ELSE 0 END) as is_registered
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        LEFT JOIN event_registrations er ON e.id = er.event_id
        WHERE e.is_active = 1";

$params = [$userId];
$types = "i";

// Date filter
if ($dateFilter === 'upcoming') {
    $sql .= " AND e.event_date >= NOW()";
} elseif ($dateFilter === 'this_week') {
    $sql .= " AND e.event_date >= NOW() AND e.event_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)";
} elseif ($dateFilter === 'this_month') {
    $sql .= " AND e.event_date >= NOW() AND e.event_date <= DATE_ADD(NOW(), INTERVAL 1 MONTH)";
}

// Search filter
if (!empty($searchTerm)) {
    $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $searchParam = '%' . $searchTerm . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

// Category filter
if (!empty($categoryFilter)) {
    $sql .= " AND e.category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

$sql .= " GROUP BY e.id, e.title, e.description, e.event_date, e.location, e.category, e.max_participants, u.full_name
          ORDER BY e.event_date ASC";

$events = fetchMultipleRows($conn, $sql, $types, $params);

// Get event categories for filter
$categorySql = "SELECT DISTINCT category FROM events WHERE is_active = 1 AND category IS NOT NULL ORDER BY category";
$categories = fetchMultipleRows($conn, $categorySql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Events - University Companion</title>
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
                            <a class="nav-link active" href="events.php">
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
                        <i class="bi bi-calendar-event"></i> Upcoming Events
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <small class="text-muted">Discover and register for university events</small>
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

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           name="search" 
                                           placeholder="Search events..." 
                                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                                <?php echo ($categoryFilter === $cat['category']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="date_filter">
                                    <option value="upcoming" <?php echo ($dateFilter === 'upcoming') ? 'selected' : ''; ?>>All Upcoming</option>
                                    <option value="this_week" <?php echo ($dateFilter === 'this_week') ? 'selected' : ''; ?>>This Week</option>
                                    <option value="this_month" <?php echo ($dateFilter === 'this_month') ? 'selected' : ''; ?>>This Month</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Events List -->
                <?php if (empty($events)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                        <h4 class="mt-3">No events found</h4>
                        <p class="text-muted">Check back later for upcoming university events</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($events as $event): ?>
                            <?php
                            $eventDate = new DateTime($event['event_date']);
                            $now = new DateTime();
                            $isPast = $eventDate < $now;
                            $isToday = $eventDate->format('Y-m-d') === $now->format('Y-m-d');
                            $isFull = $event['max_participants'] && $event['registered_count'] >= $event['max_participants'];
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 event-card <?php echo $isPast ? 'past-event' : ($isToday ? 'today-event' : ''); ?>">
                                    <div class="card-header d-flex justify-content-between align-items-start">
                                        <div>
                                            <?php if ($event['category']): ?>
                                                <span class="badge bg-secondary mb-1">
                                                    <?php echo htmlspecialchars($event['category']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($event['title']); ?></h6>
                                        </div>
                                        <?php if ($event['is_registered']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Registered
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                                        
                                        <div class="event-details mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-calendar-event text-primary me-2"></i>
                                                <small>
                                                    <strong><?php echo $eventDate->format('M j, Y'); ?></strong>
                                                    at <?php echo $eventDate->format('g:i A'); ?>
                                                    <?php if ($isToday): ?>
                                                        <span class="badge bg-warning ms-1">Today</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <?php if ($event['location']): ?>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-geo-alt text-primary me-2"></i>
                                                    <small><?php echo htmlspecialchars($event['location']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-person text-primary me-2"></i>
                                                <small>Organized by <?php echo htmlspecialchars($event['organizer_name']); ?></small>
                                            </div>
                                            
                                            <?php if ($event['max_participants']): ?>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-people text-primary me-2"></i>
                                                    <small>
                                                        <?php echo $event['registered_count']; ?>/<?php echo $event['max_participants']; ?> participants
                                                        <?php if ($isFull): ?>
                                                            <span class="badge bg-danger ms-1">Full</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-people text-primary me-2"></i>
                                                    <small><?php echo $event['registered_count']; ?> participants</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <?php if ($isPast): ?>
                                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                                <i class="bi bi-clock-history"></i> Event Ended
                                            </button>
                                        <?php elseif ($event['is_registered']): ?>
                                            <div class="d-grid gap-2">
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#eventModal<?php echo $event['id']; ?>">
                                                    <i class="bi bi-eye"></i> View Details
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" 
                                                            name="unregister_event" 
                                                            class="btn btn-outline-danger btn-sm w-100"
                                                            onclick="return confirm('Are you sure you want to unregister from this event?')">
                                                        <i class="bi bi-x-circle"></i> Unregister
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif ($isFull): ?>
                                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                                <i class="bi bi-people-fill"></i> Event Full
                                            </button>
                                        <?php else: ?>
                                            <div class="d-grid gap-2">
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#eventModal<?php echo $event['id']; ?>">
                                                    <i class="bi bi-eye"></i> View Details
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" 
                                                            name="register_event" 
                                                            class="btn btn-primary btn-sm w-100">
                                                        <i class="bi bi-plus-circle"></i> Register
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Event Details Modal -->
                            <div class="modal fade" id="eventModal<?php echo $event['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if ($event['category']): ?>
                                                <div class="mb-3">
                                                    <span class="badge bg-primary fs-6">
                                                        <?php echo htmlspecialchars($event['category']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-3">
                                                <h6>Description</h6>
                                                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <h6><i class="bi bi-calendar-event"></i> Date & Time</h6>
                                                    <p>
                                                        <?php echo $eventDate->format('l, F j, Y'); ?><br>
                                                        <?php echo $eventDate->format('g:i A'); ?>
                                                    </p>
                                                </div>
                                                <?php if ($event['location']): ?>
                                                    <div class="col-md-6">
                                                        <h6><i class="bi bi-geo-alt"></i> Location</h6>
                                                        <p><?php echo htmlspecialchars($event['location']); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <h6><i class="bi bi-person"></i> Organizer</h6>
                                                    <p><?php echo htmlspecialchars($event['organizer_name']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6><i class="bi bi-people"></i> Participants</h6>
                                                    <p>
                                                        <?php echo $event['registered_count']; ?> registered
                                                        <?php if ($event['max_participants']): ?>
                                                            (Max: <?php echo $event['max_participants']; ?>)
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <?php if ($event['is_registered']): ?>
                                                <div class="alert alert-success">
                                                    <i class="bi bi-check-circle"></i> You are registered for this event!
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Close
                                            </button>
                                            <?php if (!$isPast && !$event['is_registered'] && !$isFull): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" 
                                                            name="register_event" 
                                                            class="btn btn-primary">
                                                        <i class="bi bi-plus-circle"></i> Register for Event
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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
        setActiveNavItem('events.php');
    </script>

    <style>
        .event-card {
            transition: transform 0.2s;
        }
        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .past-event {
            opacity: 0.7;
        }
        .today-event {
            border-left: 4px solid #ffc107;
        }
        .event-details {
            font-size: 0.9rem;
        }
    </style>
</body>
</html>