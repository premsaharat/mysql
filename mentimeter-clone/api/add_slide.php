<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');
ob_start();

require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'slide_id' => null];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $session_id = isset($input['session_id']) ? intval($input['session_id']) : 0;
    $slide_type = isset($input['slide_type']) ? sanitize($input['slide_type']) : 'poll';

    if ($session_id <= 0) {
        throw new Exception('Invalid session ID');
    }

    $conn = getDBConnection();
    $conn->beginTransaction();

    // Verify session ownership
    $stmt = $conn->prepare("SELECT created_by FROM tbsession WHERE sessionid = ?");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$session || $session['created_by'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access');
    }

    // Insert new slide
    $stmt = $conn->prepare("
        INSERT INTO tbslide (sessionid, slide_title, slide_type, question_text, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, 'สไลด์ใหม่', $slide_type, '']);
    $slide_id = $conn->lastInsertId();

    // Insert into tbslidedisplay
    $stmt = $conn->prepare("SELECT displaytypeid FROM tbdisplaytype WHERE display_name = ?");
    $display_types = [
        'poll' => 'Multiple Choice',
        'wordcloud' => 'Word Cloud',
        'openended' => 'Open Ended',
        'scales' => 'Scales',
        'ranking' => 'Ranking',
        'pinimage' => 'Pin on Image'
    ];
    $stmt->execute([$display_types[$slide_type]]);
    $displaytypeid = $stmt->fetchColumn();

    if (!$displaytypeid) {
        throw new Exception('Invalid slide type');
    }

    $stmt = $conn->prepare("
        INSERT INTO tbslidedisplay (slideid, displaytypeid, settings, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$slide_id, $displaytypeid, json_encode([])]);

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Slide added successfully';
    $response['slide_id'] = $slide_id;
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Add slide error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

ob_end_clean();
echo json_encode($response);
exit;
?>