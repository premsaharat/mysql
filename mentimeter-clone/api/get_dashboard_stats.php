<?php
header('Content-Type: application/json');
require_once '../../includes/functions.php';

try {
    $userId = $_SESSION['user_id'] ?? 0;
    if (!$userId) {
        throw new Exception('User not logged in');
    }

    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tbsession WHERE created_by = ?");
    $stmt->execute([$userId]);
    $totalSessions = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT COUNT(r.responseid)
        FROM tbsession s
        LEFT JOIN tbslide sl ON s.sessionid = sl.sessionid
        LEFT JOIN tbresponse r ON sl.slideid = r.slideid
        WHERE s.created_by = ?
    ");
    $stmt->execute([$userId]);
    $totalResponses = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM tbsession WHERE created_by = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $activeSessions = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'total_sessions' => $totalSessions,
        'total_responses' => $totalResponses,
        'active_sessions' => $activeSessions
    ]);
} catch (Exception $e) {
    error_log("get_dashboard_stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>