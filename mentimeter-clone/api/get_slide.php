<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');
ob_start();

require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $slide_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($slide_id <= 0) {
        throw new Exception('Invalid slide ID');
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT s.slideid, s.sessionid, s.slide_title, s.slide_type, s.question_text, s.background_image, d.settings
        FROM tbslide s
        LEFT JOIN tbslidedisplay d ON s.slideid = d.slideid
        WHERE s.slideid = ? AND s.sessionid IN (
            SELECT sessionid FROM tbsession WHERE created_by = ?
        )
    ");
    $stmt->execute([$slide_id, $_SESSION['user_id']]);
    $slide = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slide) {
        throw new Exception('Slide not found or unauthorized');
    }

    $stmt = $conn->prepare("SELECT choice_text FROM tbchoice WHERE slideid = ? ORDER BY choice_order");
    $stmt->execute([$slide_id]);
    $slide['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $slide['settings'] = json_decode($slide['settings'], true) ?? [];

    $response['success'] = true;
    $response['data'] = $slide;
} catch (Exception $e) {
    error_log("Get slide error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

ob_end_clean();
echo json_encode($response);
exit;
?>