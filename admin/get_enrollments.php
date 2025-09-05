<?php
require_once '../php/config.php';
requireRole('admin');

header('Content-Type: application/json');

$conn = getDBConnection();
$courseId = (int)($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid course ID']);
    exit;
}

try {
    $sql = "SELECT ce.id, u.full_name, u.username, u.email, ce.enrollment_date, ce.status,
                   c.course_code, c.course_name
            FROM course_enrollments ce
            JOIN users u ON ce.student_id = u.id
            JOIN courses c ON ce.course_id = c.id
            WHERE ce.course_id = ? AND ce.status = 'enrolled'
            ORDER BY ce.enrollment_date DESC, u.full_name";
    
    $enrollments = fetchMultipleRows($conn, $sql, "i", [$courseId]);
    
    // Get additional course statistics
    $statsSQL = "SELECT 
        (SELECT COUNT(*) FROM course_enrollments WHERE course_id = ? AND status = 'enrolled') as total_enrolled,
        (SELECT COUNT(*) FROM course_enrollments WHERE course_id = ? AND status = 'enrolled' AND enrollment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as recent_enrollments,
        (SELECT course_name FROM courses WHERE id = ?) as course_name";
    
    $stats = fetchSingleRow($conn, $statsSQL, "iii", [$courseId, $courseId, $courseId]);
    
    echo json_encode([
        'success' => true,
        'enrollments' => $enrollments,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>