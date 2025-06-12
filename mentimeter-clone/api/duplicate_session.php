<?php
header('Content-Type: application/json');
require_once '../../includes/functions.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = filter_var($input['sessionId'] ?? 0, FILTER_VALIDATE_INT);

    if (!$sessionId) {
        throw new Exception('Invalid session ID');
    }

    $conn = getDBConnection();
    $conn->beginTransaction();

    // Copy session
    $stmt = $conn->prepare("
        INSERT INTO tbsession (session_name, created_by, created_at, status, join_code)
        SELECT CONCAT(session_name, ' (Copy)'), created_by, NOW(), 'inactive', CONCAT(join_code, '_copy')
        FROM tbsession WHERE sessionid = ?
    ");
    $stmt->execute([$sessionId]);
    $newSessionId = $conn->lastInsertId();

    // Copy slides
    $stmt = $conn->prepare("
        INSERT INTO tbslide (sessionid, slide_type, question_text, background_image)
        SELECT ?, slide_type, question_text, background_image
        FROM tbslide WHERE sessionid = ?
    ");
    $stmt->execute([$newSessionId, $sessionId]);

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    error_log("duplicate_session error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>