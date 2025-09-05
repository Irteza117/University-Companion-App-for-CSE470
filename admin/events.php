<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle form submissions
$success = '';
$error = '';

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_event'])) {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $location = sanitizeInput($_POST['location'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? 'general');
    $maxRegistrations = (int)($_POST['max_registrations'] ?? 0);
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    if (empty($title) || empty($eventDate)) {
        $error = 'Please fill in all required fields.';
    } else {
        $eventDateTime = $eventDate . ' ' . ($eventTime ?: '00:00:00');
        
        $sql = "INSERT INTO events (title, description, event_date, location, organizer_id, category, max_participants, registration_required, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $result = insertData($conn, $sql, "sssisiii", [$title, $description, $eventDateTime, $location, $_SESSION['user_id'], $category, $maxRegistrations, $isPublic]);
        
        if ($result > 0) {
            $success = "Event created successfully.";
        } else {
            $error = "Failed to create event.";
        }
    }
}

// Handle event status toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_event_status'])) {
    $eventId = (int)$_POST['event_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;
    
    $sql = "UPDATE events SET is_active = ? WHERE id = ?";
    $result = updateData($conn, $sql, "ii", [$newStatus, $eventId]);
    
    if ($result >= 0) {
        $success = "Event status updated successfully.";
    } else {
        $error = "Failed to update event status.";
    }
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$timeFilter = $_GET['time'] ?? 'all';

// Build query with filters
$whereConditions = [];
$params = [];
$types = "";

if ($categoryFilter != 'all') {
    $whereConditions[] = "e.category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

if ($timeFilter == 'upcoming') {
    $whereConditions[] = "e.event_date >= NOW()";
} elseif ($timeFilter == 'past') {
    $whereConditions[] = "e.event_date < NOW()";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get events with organizer information and registration counts
$sql = "SELECT e.*, u.full_name as organizer_name,
        COUNT(er.id) as registration_count
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        LEFT JOIN event_registrations er ON e.id = er.event_id AND er.status = 'registered'
        $whereClause
        GROUP BY e.id
        ORDER BY e.event_date DESC";

$events = fetchMultipleRows($conn, $sql, $types, $params);

// Get statistics
$stats = fetchSingleRow($conn, "SELECT 
    COUNT(*) as total_events,
    SUM(CASE WHEN event_date >= NOW() THEN 1 ELSE 0 END) as upcoming_events,
    SUM(CASE WHEN event_date < NOW() THEN 1 ELSE 0 END) as past_events,
    SUM(CASE WHEN category = 'academic' THEN 1 ELSE 0 END) as academic_events,
    SUM(CASE WHEN category = 'cultural' THEN 1 ELSE 0 END) as cultural_events
    FROM events");

// Get registration statistics
$regStats = fetchSingleRow($conn, "SELECT COUNT(*) as total_registrations
    FROM event_registrations er
    JOIN events e ON er.event_id = e.id
    WHERE er.status = 'registered'");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - University Companion</title>
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
                <a href="notices.php" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="bi bi-megaphone me-2"></i>Notices
                </a>
                <a href="events.php" class="list-group-item list-group-item-action bg-transparent text-white active">
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
                        <i class="bi bi-calendar-event"></i> Event Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                            <i class="bi bi-plus"></i> Create Event
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
                                <h4><?php echo $stats['total_events']; ?></h4>
                                <p class="mb-0">Total Events</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['upcoming_events']; ?></h4>
                                <p class="mb-0">Upcoming</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['past_events']; ?></h4>
                                <p class="mb-0">Past Events</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['academic_events']; ?></h4>
                                <p class="mb-0">Academic</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $stats['cultural_events']; ?></h4>
                                <p class="mb-0">Cultural</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body text-center">
                                <h4><?php echo $regStats['total_registrations']; ?></h4>
                                <p class="mb-0">Registrations</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" name="category">
                                    <option value="all" <?php echo $categoryFilter == 'all' ? 'selected' : ''; ?>>All Categories</option>
                                    <option value="academic" <?php echo $categoryFilter == 'academic' ? 'selected' : ''; ?>>Academic</option>
                                    <option value="cultural" <?php echo $categoryFilter == 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                                    <option value="sports" <?php echo $categoryFilter == 'sports' ? 'selected' : ''; ?>>Sports</option>
                                    <option value="social" <?php echo $categoryFilter == 'social' ? 'selected' : ''; ?>>Social</option>
                                    <option value="general" <?php echo $categoryFilter == 'general' ? 'selected' : ''; ?>>General</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="time" class="form-label">Time Period</label>
                                <select class="form-select" name="time">
                                    <option value="all" <?php echo $timeFilter == 'all' ? 'selected' : ''; ?>>All Events</option>
                                    <option value="upcoming" <?php echo $timeFilter == 'upcoming' ? 'selected' : ''; ?>>Upcoming Only</option>
                                    <option value="past" <?php echo $timeFilter == 'past' ? 'selected' : ''; ?>>Past Events</option>
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

                <!-- Events List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Events</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($events)): ?>
                            <div class="text-center p-4">
                                <i class="bi bi-calendar-event display-1 text-muted"></i>
                                <p class="text-muted mt-3">No events found.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($events as $event): ?>
                                    <?php
                                    $eventDate = new DateTime($event['event_date']);
                                    $now = new DateTime();
                                    $isPast = $eventDate < $now;
                                    $daysUntil = $eventDate->diff($now)->days;
                                    ?>
                                    <div class="list-group-item <?php echo $isPast ? 'bg-light' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h5 class="mb-0 me-2"><?php echo htmlspecialchars($event['title']); ?></h5>
                                                    <span class="badge bg-<?php 
                                                        echo $event['category'] == 'academic' ? 'primary' : 
                                                            ($event['category'] == 'cultural' ? 'success' :
                                                            ($event['category'] == 'sports' ? 'warning' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst($event['category']); ?>
                                                    </span>
                                                    <?php if ($isPast): ?>
                                                        <span class="badge bg-secondary ms-2">PAST</span>
                                                    <?php elseif ($daysUntil <= 7): ?>
                                                        <span class="badge bg-danger ms-2">SOON</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($event['description']): ?>
                                                    <p class="mb-2"><?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 150))); ?><?php echo strlen($event['description']) > 150 ? '...' : ''; ?></p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> <?php echo $eventDate->format('M j, Y g:i A'); ?> •
                                                    <?php if ($event['location']): ?>
                                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?> •
                                                    <?php endif; ?>
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($event['organizer_name']); ?> •
                                                    <i class="bi bi-people"></i> <?php echo $event['registration_count']; ?> registered
                                                    <?php if ($event['max_participants'] > 0): ?>
                                                        / <?php echo $event['max_participants']; ?> max
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="btn-group btn-group-sm ms-3">
                                                <button type="button" class="btn btn-outline-primary" onclick="viewEvent(<?php echo $event['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="editEvent(<?php echo $event['id']; ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info" onclick="viewRegistrations(<?php echo $event['id']; ?>)">
                                                    <i class="bi bi-people"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $event['is_active']; ?>">
                                                    <button type="submit" name="toggle_event_status" class="btn btn-outline-<?php echo $event['is_active'] ? 'warning' : 'success'; ?>">
                                                        <i class="bi bi-<?php echo $event['is_active'] ? 'pause' : 'play'; ?>"></i>
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

    <!-- Create Event Modal -->
    <div class="modal fade" id="createEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_date" class="form-label">Event Date *</label>
                                <input type="date" class="form-control" name="event_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="event_time" class="form-label">Event Time</label>
                                <input type="time" class="form-control" name="event_time">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" name="location">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" name="category">
                                    <option value="general">General</option>
                                    <option value="academic">Academic</option>
                                    <option value="cultural">Cultural</option>
                                    <option value="sports">Sports</option>
                                    <option value="social">Social</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_registrations" class="form-label">Max Registrations</label>
                                <input type="number" class="form-control" name="max_registrations" min="0" placeholder="0 = Unlimited">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_public" id="is_public" checked>
                                <label class="form-check-label" for="is_public">
                                    Make this event public (visible to all users)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_event" class="btn btn-primary">Create Event</button>
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

        function viewEvent(eventId) {
            alert('View event details functionality to be implemented');
        }

        function editEvent(eventId) {
            alert('Edit event functionality to be implemented');
        }

        function viewRegistrations(eventId) {
            alert('View registrations functionality to be implemented');
        }
    </script>
</body>
</html>