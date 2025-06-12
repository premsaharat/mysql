<?php
header('Content-Type: application/json');
require_once '../includes/database.php';
require_once '../includes/functions.php';

try {
    $session_id = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
    $last_update = filter_input(INPUT_GET, 'last_update', FILTER_SANITIZE_STRING);

    if (!$session_id || !$last_update) {
        throw new Exception("Invalid parameters");
    }

    $conn = getDBConnection();
    
    // Check for new responses
    $stmt = $conn->prepare("
        SELECT COUNT(*) as new_responses
        FROM tbresponse
        WHERE slideid IN (SELECT slideid FROM tbslide WHERE sessionid = ?)
        AND created_at > ?
    ");
    $stmt->execute([$session_id, $last_update]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $has_updates = $result['new_responses'] > 0;

    echo json_encode(['success' => true, 'has_updates' => $has_updates]);
} catch (Exception $e) {
    error_log("Live update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>