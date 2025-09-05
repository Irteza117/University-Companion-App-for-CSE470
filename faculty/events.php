<?php
require_once '../php/config.php';
requireRole('faculty');

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle new event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $eventDate = $_POST['event_date'];
    $location = $_POST['location'];
    $category = $_POST['category'];
    $maxParticipants = $_POST['max_participants'] ?: null;
    
    try {
        $sql = "INSERT INTO events (title, description, event_date, location, category, max_participants, organizer_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        executeQuery($conn, $sql, "sssssii", [$title, $description, $eventDate, $location, $category, $maxParticipants, $userId]);
        $success = "Event created successfully!";
    } catch (Exception $e) {
        $error = "Failed to create event: " . $e->getMessage();
    }
}

// Handle event status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_event'])) {
    $eventId = $_POST['event_id'];
    $newStatus = $_POST['new_status'];
    
    try {
        $sql = "UPDATE events SET is_active = ? WHERE id = ? AND organizer_id = ?";
        executeQuery($conn, $sql, "iii", [$newStatus, $eventId, $userId]);
        $success = $newStatus ? "Event activated successfully!" : "Event deactivated successfully!";
    } catch (Exception $e) {
        $error = "Failed to update event: " . $e->getMessage();
    }
}

// Get faculty's events
$eventsSql = "SELECT e.id, e.title, e.description, e.event_date, e.location, e.category, 
                     e.max_participants, e.is_active, e.created_at,
                     COUNT(er.id) as registered_count
              FROM events e
              LEFT JOIN event_registrations er ON e.id = er.event_id
              WHERE e.organizer_id = ?
              GROUP BY e.id, e.title, e.description, e.event_date, e.location, e.category, 
                       e.max_participants, e.is_active, e.created_at
              ORDER BY e.event_date DESC";
$events = fetchMultipleRows($conn, $eventsSql, "i", [$userId]);

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
                            <a class="nav-link active" href="events.php">
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
                        <i class="bi bi-calendar-event"></i> Event Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                            <i class="bi bi-plus-circle"></i> Create Event
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

                <!-- Event Statistics -->
                <div class="row mb-4">
                    <?php
                    $totalEvents = count($events);
                    $activeEvents = count(array_filter($events, function($e) { return $e['is_active']; }));
                    $upcomingEvents = count(array_filter($events, function($e) { 
                        return $e['is_active'] && strtotime($e['event_date']) > time(); 
                    }));
                    $totalRegistrations = array_sum(array_column($events, 'registered_count'));
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $totalEvents; ?></h4>
                                <p class="mb-0">Total Events</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $activeEvents; ?></h4>
                                <p class="mb-0">Active Events</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $upcomingEvents; ?></h4>
                                <p class="mb-0">Upcoming</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $totalRegistrations; ?></h4>
                                <p class="mb-0">Total Registrations</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Events List -->
                <?php if (empty($events)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                        <h4 class="mt-3">No events created yet</h4>
                        <p class="text-muted">Click "Create Event" to organize your first event</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Your Events</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Event</th>
                                            <th>Date & Time</th>
                                            <th>Location</th>
                                            <th>Category</th>
                                            <th>Registrations</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events as $event): ?>
                                            <?php
                                            $eventDate = new DateTime($event['event_date']);
                                            $now = new DateTime();
                                            $isPast = $eventDate < $now;
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Created: <?php echo date('M j, Y', strtotime($event['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo $eventDate->format('M j, Y'); ?><br>
                                                    <small><?php echo $eventDate->format('g:i A'); ?></small>
                                                    <?php if ($isPast): ?>
                                                        <br><span class="badge bg-secondary">Past</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($event['location'] ?: 'TBA'); ?>
                                                </td>
                                                <td>
                                                    <?php if ($event['category']): ?>
                                                        <span class="badge bg-primary">
                                                            <?php echo htmlspecialchars($event['category']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo $event['registered_count']; ?></strong>
                                                    <?php if ($event['max_participants']): ?>
                                                        / <?php echo $event['max_participants']; ?>
                                                        <?php if ($event['registered_count'] >= $event['max_participants']): ?>
                                                            <br><span class="badge bg-danger">Full</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($event['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <button type="button" 
                                                                class="btn btn-outline-primary btn-sm"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#viewModal<?php echo $event['id']; ?>">
                                                            <i class="bi bi-eye"></i> View
                                                        </button>
                                                        
                                                        <?php if (!$isPast): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                                <input type="hidden" name="new_status" value="<?php echo $event['is_active'] ? 0 : 1; ?>">
                                                                <button type="submit" 
                                                                        name="toggle_event" 
                                                                        class="btn btn-outline-<?php echo $event['is_active'] ? 'warning' : 'success'; ?> btn-sm">
                                                                    <i class="bi bi-<?php echo $event['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                                    <?php echo $event['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
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

    <!-- Create Event Modal -->
    <div class="modal fade" id="createEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required
                                      placeholder="Provide details about the event..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_date" class="form-label">Event Date & Time *</label>
                                    <input type="datetime-local" class="form-control" id="event_date" name="event_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="Event venue or location">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">Select Category</option>
                                        <option value="Academic">Academic</option>
                                        <option value="Cultural">Cultural</option>
                                        <option value="Sports">Sports</option>
                                        <option value="Workshop">Workshop</option>
                                        <option value="Seminar">Seminar</option>
                                        <option value="Conference">Conference</option>
                                        <option value="Social">Social</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_participants" class="form-label">Maximum Participants</label>
                                    <input type="number" class="form-control" id="max_participants" name="max_participants" 
                                           min="1" placeholder="Leave empty for unlimited">
                                    <div class="form-text">Leave empty for unlimited registrations</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_event" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Event Modals -->
    <?php foreach ($events as $event): ?>
        <div class="modal fade" id="viewModal<?php echo $event['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Date & Time:</strong><br>
                                <?php echo date('l, F j, Y g:i A', strtotime($event['event_date'])); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Location:</strong><br>
                                <?php echo htmlspecialchars($event['location'] ?: 'TBA'); ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Category:</strong><br>
                                <?php echo htmlspecialchars($event['category'] ?: 'Not specified'); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Registrations:</strong><br>
                                <?php echo $event['registered_count']; ?>
                                <?php if ($event['max_participants']): ?>
                                    / <?php echo $event['max_participants']; ?> (Max)
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Status:</strong><br>
                            <?php if ($event['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                            
                            <?php if (strtotime($event['event_date']) < time()): ?>
                                <span class="badge bg-secondary">Past Event</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Set active navigation item
        setActiveNavItem('events.php');

        // Set minimum event date to current date/time
        document.addEventListener('DOMContentLoaded', function() {
            const eventDateInput = document.getElementById('event_date');
            const now = new Date();
            const offsetMs = now.getTimezoneOffset() * 60 * 1000;
            const localISOTime = (new Date(now.getTime() - offsetMs)).toISOString().slice(0, 16);
            eventDateInput.min = localISOTime;
        });
    </script>
</body>
</html>