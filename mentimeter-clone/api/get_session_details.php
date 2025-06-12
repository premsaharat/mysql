<?php
header('Content-Type: application/json');
require_once '../../includes/functions.php';

try {
    $sessionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$sessionId) {
        throw new Exception('Invalid session ID');
    }

    $conn = getDBConnection();
    
    // Get session details
    $stmt = $conn->prepare("
        SELECT 
            s.session_name,
            COUNT(DISTINCT v.view_id) AS views,
            COUNT(DISTINCT sl.slideid) AS question_count
        FROM tbsession s
        LEFT JOIN tbslide sl ON s.sessionid = sl.sessionid
        LEFT JOIN tbviews v ON s.sessionid = v.sessionid
        WHERE s.sessionid = ?
    ");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        throw new Exception('Session not found');
    }

    echo json_encode([
        'success' => true,
        'views' => $session['views'] ?? 0,
        'question_count' => $session['question_count'] ?? 0,
        'duration' => 'N/A' // Calculate if needed
    ]);
} catch (Exception $e) {
    error_log("get_session_details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>