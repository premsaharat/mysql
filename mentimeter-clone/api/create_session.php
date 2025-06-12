<?php
ob_start(); // Start output buffering
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log'); // Log errors to file

$response = ['success' => false, 'message' => '', 'session_id' => null];

try {
    // Validate input
    if (!isset($_POST['title']) || empty(trim($_POST['title']))) {
        throw new Exception('กรุณาระบุชื่อ Presentation');
    }

    $user_id = $_SESSION['user_id'];
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $max_participants = isset($_POST['max_participants']) ? intval($_POST['max_participants']) : 1000;
    $question_type = sanitize($_POST['question_type'] ?? 'poll');
    $question = sanitize($_POST['question'] ?? '');
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

    $db = getDBConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

    // Generate unique join code
    $stmt = $db->prepare("CALL GenerateJoinCode(@new_code)");
    $stmt->execute();
    $stmt = $db->query("SELECT @new_code AS join_code");
    $join_code = $stmt->fetch(PDO::FETCH_ASSOC)['join_code'];
    if (empty($join_code)) {
        throw new Exception('ไม่สามารถสร้าง join_code ได้');
    }

    // Insert into tbsession
    $stmt = $db->prepare("
        INSERT INTO tbsession (session_name, description, join_code, created_by, is_public, max_participants, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'draft', NOW())
    ");
    $stmt->execute([$title, $description, $join_code, $user_id, $is_public, $max_participants]);
    $session_id = $db->lastInsertId();

    // Insert first slide
    $stmt = $db->prepare("
        INSERT INTO tbslide (sessionid, slide_title, slide_type, question_text, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, $title, $question_type, $question]);
    $slide_id = $db->lastInsertId();

    // Map question type to displaytypeid
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
        throw new Exception('ประเภทคำถามไม่ถูกต้อง');
    }

    // Insert into tbslidedisplay
    $stmt = $db->prepare("
        INSERT INTO tbslidedisplay (slideid, displaytypeid, settings, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$slide_id, $displaytypeid, json_encode(array_filter($settings, fn($v) => !is_null($v)))]);

    // Handle slide-specific data
    if (in_array($question_type, ['poll', 'ranking']) && !empty($options)) {
        $stmt = $db->prepare("INSERT INTO tbchoice (slideid, choice_text, choice_order, created_at) VALUES (?, ?, ?, NOW())");
        foreach ($options as $index => $option) {
            if (!empty(trim($option))) {
                $stmt->execute([$slide_id, $option, $index + 1]);
            }
        }
    } elseif ($question_type === 'pinimage' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../Uploads/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            throw new Exception('ไม่สามารถสร้างโฟลเดอร์ Uploads ได้');
        }
        $image_name = uniqid() . '_' . preg_replace("/[^A-Za-z0-9._-]/", "", $_FILES['image']['name']);
        $image_path = $upload_dir . $image_name;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            throw new Exception('ไม่สามารถอัปโหลดรูปภาพได้');
        }
        $stmt = $db->prepare("UPDATE tbslide SET background_image = ? WHERE slideid = ?");
        $stmt->execute([$image_path, $slide_id]);
    }

    $db->commit();
    $response['success'] = true;
    $response['message'] = 'สร้าง Presentation เรียบร้อย';
    $response['session_id'] = $session_id;
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Create session error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

ob_end_clean(); // Clear output buffer
echo json_encode($response);
exit;
?>