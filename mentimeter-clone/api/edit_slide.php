<?php
ob_start();
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

$response = ['success' => false, 'message' => '', 'image_url' => null];

try {
    $slide_id = isset($_POST['slide_id']) ? intval($_POST['slide_id']) : 0;
    $question_type = sanitize($_POST['question_type'] ?? 'poll');
    $question_text = sanitize($_POST['question'] ?? '');
    $options = isset($_POST['options']) && is_array($_POST['options']) ? array_map('sanitize', $_POST['options']) : [];
    $settings = [
        'allow_anonymous' => isset($_POST['allow_anonymous']) ? 1 : 0,
        'show_results' => isset($_POST['show_results']) ? 1 : 0,
        'require_name' => isset($_POST['require_name']) ? 1 : 0,
        'one_response_only' => isset($_POST['one_response_only']) ? 1 : 0,
        'max_words' => isset($_POST['max_words']) ? intval($_POST['max_words']) : null,
        'char_limit' => isset($_POST['char_limit']) ? intval($_POST['char_limit']) : null,
        'scale_min' => isset($_POST['scale_min']) ? intval($_POST['scale_min']) : null,
        'scale_max' => isset($_POST['scale_max']) ? intval($_POST['scale_max']) : null
    ];

    if ($slide_id <= 0) {
        throw new Exception('Invalid slide ID');
    }
    if (empty($question_text)) {
        throw new Exception('Question text is required');
    }

    $db = getDBConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

    // Verify slide ownership
    $stmt = $db->prepare("
        SELECT s.slideid
        FROM tbslide s
        JOIN tbsession sess ON s.sessionid = sess.sessionid
        WHERE s.slideid = ? AND sess.created_by = ?
    ");
    $stmt->execute([$slide_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Slide not found or unauthorized');
    }

    // Update slide
    $stmt = $db->prepare("
        UPDATE tbslide
        SET slide_title = ?, slide_type = ?, question_text = ?, updated_at = NOW()
        WHERE slideid = ?
    ");
    $stmt->execute([$question_text, $question_type, $question_text, $slide_id]);

    // Update display settings
    $display_types = [
        'poll' => 'Multiple Choice',
        'wordcloud' => 'Word Cloud',
        'openended' => 'Open Ended',
        'scales' => 'Scales',
        'ranking' => 'Ranking',
        'pinimage' => 'Pin on Image'
    ];
    $stmt = $db->prepare("SELECT displaytypeid FROM tbdisplaytype WHERE display_name = ?");
    $stmt->execute([$display_types[$question_type]]);
    $displaytypeid = $stmt->fetchColumn();
    if (!$displaytypeid) {
        throw new Exception('Invalid slide type');
    }

    $stmt = $db->prepare("
        UPDATE tbslidedisplay
        SET displaytypeid = ?, settings = ?, updated_at = NOW()
        WHERE slideid = ?
    ");
    $stmt->execute([$displaytypeid, json_encode(array_filter($settings, fn($v) => !is_null($v))), $slide_id]);

    // Update choices for poll/ranking
    if (in_array($question_type, ['poll', 'ranking'])) {
        // Delete existing choices
        $stmt = $db->prepare("DELETE FROM tbchoice WHERE slideid = ?");
        $stmt->execute([$slide_id]);

        // Insert new choices
        if (!empty($options)) {
            $stmt = $db->prepare("INSERT INTO tbchoice (slideid, choice_text, choice_order, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($options as $index => $option) {
                if (!empty(trim($option))) {
                    $stmt->execute([$slide_id, $option, $index + 1]);
                }
            }
        }
    }

    // Handle image upload for pinimage
    if ($question_type === 'pinimage' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../Uploads/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            throw new Exception('Cannot create Uploads directory');
        }
        $image_name = uniqid() . '_' . preg_replace("/[^A-Za-z0-9._-]/", "", $_FILES['image']['name']);
        $image_path = $upload_dir . $image_name;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            throw new Exception('Failed to upload image');
        }
        $stmt = $db->prepare("UPDATE tbslide SET background_image = ? WHERE slideid = ?");
        $stmt->execute([$image_path, $slide_id]);
        $response['image_url'] = $image_path;
    }

    $db->commit();
    $response['success'] = true;
    $response['message'] = 'Slide updated successfully';
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Edit slide error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

ob_end_clean();
echo json_encode($response);
exit;
?>