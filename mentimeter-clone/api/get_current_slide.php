<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');
ob_start();

require_once '../includes/functions.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
    if ($session_id <= 0) {
        throw new Exception('Invalid session ID');
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT s.slideid, s.sessionid, s.slide_type, s.question_text, s.background_image, d.settings
        FROM tbslide s
        LEFT JOIN tbslidedisplay d ON s.slideid = d.slideid
        WHERE s.sessionid = ?
        ORDER BY s.slideid ASC
        LIMIT 1
    ");
    $stmt->execute([$session_id]);
    $slide = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($slide) {
        $stmt = $conn->prepare("
            SELECT choiceid, choice_text
            FROM tbchoice
            WHERE slideid = ?
            ORDER BY choice_order
        ");
        $stmt->execute([$slide['slideid']]);
        $slide['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $slide['settings'] = json_decode($slide['settings'], true) ?? [];

        $response['success'] = true;
        $response['data'] = $slide;
    } else {
        $response['message'] = 'No active slide found';
    }
} catch (Exception $e) {
    error_log("Get current slide error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

ob_end_clean();
echo json_encode($response);
exit;
?>