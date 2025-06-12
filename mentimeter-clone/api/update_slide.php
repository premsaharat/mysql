<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');
ob_start();

require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'image_url' => null];

try {
    $slide_id = isset($_POST['slide_id']) ? intval($_POST['slide_id']) : 0;
    $question_type = isset($_POST['question_type']) ? sanitize($_POST['question_type']) : '';
    $question_text = isset($_POST['question']) ? sanitize($_POST['question']) : '';

    if ($slide_id <= 0) {
        throw new Exception('Invalid slide ID');
    }

    $conn = getDBConnection();
    $conn->beginTransaction();

    // Verify slide ownership
    $stmt = $conn->prepare("
        SELECT s.sessionid
        FROM tbslide s
        JOIN tbsession sess ON s.sessionid = sess.sessionid
        WHERE s.slideid = ? AND sess.created_by = ?
    ");
    $stmt->execute([$slide_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Slide not found or unauthorized');
    }

    // Handle image upload
    $image_url = null;
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../Uploads/';
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = 'slide_' . $slide_id . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            throw new Exception('Failed to upload image');
        }
        $image_url = $file_path;
    }

    // Update tbslide
    $stmt = $conn->prepare("
        UPDATE tbslide
        SET slide_type = ?, question_text = ?, background_image = COALESCE(?, background_image)
        WHERE slideid = ?
    ");
    $stmt->execute([$question_type, $question_text, $image_url, $slide_id]);

    // Update tbslidedisplay settings
    $settings = [];
    if ($question_type === 'wordcloud') {
        $settings['max_words'] = isset($_POST['max_words']) ? intval($_POST['max_words']) : 3;
    } elseif ($question_type === 'openended') {
        $settings['char_limit'] = isset($_POST['char_limit']) ? intval($_POST['char_limit']) : null;
    } elseif ($question_type === 'scales') {
        $settings['scale_min'] = isset($_POST['scale_min']) ? intval($_POST['scale_min']) : 1;
        $settings['scale_max'] = isset($_POST['scale_max']) ? intval($_POST['scale_max']) : 5;
    }

    $stmt = $conn->prepare("
        INSERT INTO tbslidedisplay (slideid, displaytypeid, settings, created_at)
        VALUES (?, (SELECT displaytypeid FROM tbdisplaytype WHERE display_name = ?), ?, NOW())
        ON DUPLICATE KEY UPDATE settings = ?
    ");
    $display_name = [
        'poll' => 'Multiple Choice',
        'wordcloud' => 'Word Cloud',
        'openended' => 'Open Ended',
        'scales' => 'Scales',
        'ranking' => 'Ranking',
        'pinimage' => 'Pin on Image'
    ][$question_type] ?? 'Multiple Choice';
    $settings_json = json_encode($settings);
    $stmt->execute([$slide_id, $display_name, $settings_json, $settings_json]);

    // Update tbchoice for poll or ranking
    if (in_array($question_type, ['poll', 'ranking'])) {
        $stmt = $conn->prepare("DELETE FROM tbchoice WHERE slideid = ?");
        $stmt->execute([$slide_id]);

        if (!empty($_POST['options'])) {
            $stmt = $conn->prepare("
                INSERT INTO tbchoice (slideid, choice_text, choice_order, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            foreach ($_POST['options'] as $index => $option) {
                $option = sanitize($option);
                if (!empty($option)) {
                    $stmt->execute([$slide_id, $option, $index + 1]);
                }
            }
        }
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Slide updated successfully';
    $response['image_url'] = $image_url;
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Update slide error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

ob_end_clean();
echo json_encode($response);
exit;
?>