<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');
ob_start();

require_once '../includes/functions.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $slide_id = isset($input['slide_id']) ? intval($input['slide_id']) : 0;
    if ($slide_id <= 0) {
        throw new Exception('Invalid slide ID');
    }

    $conn = getDBConnection();
    $conn->beginTransaction();

    // Verify slide exists and session is active
    $stmt = $conn->prepare("
        SELECT s.slideid
        FROM tbslide s
        JOIN tbsession sess ON s.sessionid = sess.sessionid
        WHERE s.slideid = ? AND sess.status = 'active'
    ");
    $stmt->execute([$slide_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Slide not found or session not active');
    }

    // Insert response based on slide type
    $stmt = $conn->prepare("
        INSERT INTO tbresponse (slideid, choiceid, response_text, response_value, coordinates, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $choice_id = isset($input['choice_id']) ? intval($input['choice_id']) : null;
    $response_text = isset($input['response_text']) ? sanitize($input['response_text']) : null;
    $response_value = isset($input['response_value']) ? intval($input['response_value']) : null;
    $coordinates = isset($input['coordinates']) ? json_encode($input['coordinates']) : null;

    $stmt->execute([$slide_id, $choice_id, $response_text, $response_value, $coordinates]);

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Vote recorded successfully';
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Vote error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

ob_end_clean();
echo json_encode($response);
exit;
?>