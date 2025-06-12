<?php
header('Content-Type: application/json');
require_once '../../includes/functions.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = filter_var($input['sessionId'] ?? 0, FILTER_VALIDATE_INT);
    $status = $input['status'] ?? '';

    if (!$sessionId || !in_array($status, ['active', 'inactive'])) {
        throw new Exception('Invalid input');
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE tbsession SET status = ? WHERE sessionid = ?");
    $stmt->execute([$status, $sessionId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("update_session_status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>