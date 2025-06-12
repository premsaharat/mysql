<?php
require_once '../includes/functions.php';
requireLogin();

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

    $stmt = $conn->prepare("
        SELECT sessionid FROM tbslide WHERE slideid = ? AND sessionid IN (
            SELECT sessionid FROM tbsession WHERE created_by = ?
        )
    ");
    $stmt->execute([$slide_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Slide not found or unauthorized');
    }

    $stmt = $conn->prepare("DELETE FROM tbchoice WHERE slideid = ?");
    $stmt->execute([$slide_id]);

    $stmt = $conn->prepare("DELETE FROM tbslidedisplay WHERE slideid = ?");
    $stmt->execute([$slide_id]);

    $stmt = $conn->prepare("DELETE FROM tbslide WHERE slideid = ?");
    $stmt->execute([$slide_id]);

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Slide deleted successfully';
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Delete slide error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
exit;
?>