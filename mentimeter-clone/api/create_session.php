<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Validate input
    if (!isset($_POST['title']) || empty($_POST['title'])) {
        throw new Exception('กรุณาระบุชื่อ Presentation');
    }

    $user_id = $_SESSION['user_id'];
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description'] ?? '');
    $template_id = !empty($_POST['template_id']) ? intval($_POST['template_id']) : null;
    $category = sanitize($_POST['category'] ?? 'other');
    $allow_anonymous = isset($_POST['allow_anonymous']) ? 1 : 0;
    $show_results = isset($_POST['show_results']) ? 1 : 0;
    $require_name = isset($_POST['require_name']) ? 1 : 0;
    $one_response_only = isset($_POST['one_response_only']) ? 1 : 0;
    $question_type = sanitize($_POST['question_type'] ?? 'poll');
    $question = sanitize($_POST['question'] ?? '');
    $join_code = generateJoinCode();

    // Start database transaction
    $db = getDBConnection();
    $db->beginTransaction();

    // Create session
    $stmt = $db->prepare("
        INSERT INTO tbsession (created_by, title, description, template_id, category, 
                              allow_anonymous, show_results, require_name, one_response_only, 
                              join_code, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id, $title, $description, $template_id, $category,
        $allow_anonymous, $show_results, $require_name, $one_response_only, $join_code
    ]);
    $session_id = $db->lastInsertId();

    // Create first slide
    $stmt = $db->prepare("
        INSERT INTO tbslide (sessionid, type, question, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, $question_type, $question]);
    $slide_id = $db->lastInsertId();

    // Handle slide-specific data
    switch ($question_type) {
        case 'poll':
            if (isset($_POST['options']) && is_array($_POST['options'])) {
                $stmt = $db->prepare("INSERT INTO tbslideoption (slideid, option_text) VALUES (?, ?)");
                foreach ($_POST['options'] as $option) {
                    if (!empty($option)) {
                        $stmt->execute([$slide_id, sanitize($option)]);
                    }
                }
            }
            break;
        case 'wordcloud':
            $max_words = isset($_POST['max_words']) ? intval($_POST['max_words']) : 3;
            $stmt = $db->prepare("UPDATE tbslide SET max_words = ? WHERE slideid = ?");
            $stmt->execute([$max_words, $slide_id]);
            break;
        case 'openended':
            $char_limit = !empty($_POST['char_limit']) ? intval($_POST['char_limit']) : null;
            $stmt = $db->prepare("UPDATE tbslide SET char_limit = ? WHERE slideid = ?");
            $stmt->execute([$char_limit, $slide_id]);
            break;
        case 'scales':
            $scale_min = isset($_POST['scale_min']) ? intval($_POST['scale_min']) : 1;
            $scale_max = isset($_POST['scale_max']) ? intval($_POST['scale_max']) : 5;
            $stmt = $db->prepare("UPDATE tbslide SET scale_min = ?, scale_max = ? WHERE slideid = ?");
            $stmt->execute([$scale_min, $scale_max, $slide_id]);
            break;
        case 'ranking':
            if (isset($_POST['ranking_options']) && is_array($_POST['ranking_options'])) {
                $stmt = $db->prepare("INSERT INTO tbslideoption (slideid, option_text) VALUES (?, ?)");
                foreach ($_POST['ranking_options'] as $option) {
                    if (!empty($option)) {
                        $stmt->execute([$slide_id, sanitize($option)]);
                    }
                }
            }
            break;
        case 'pinimage':
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $image_name = uniqid() . '_' . $_FILES['image']['name'];
                $image_path = $upload_dir . $image_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $stmt = $db->prepare("UPDATE tbslide SET image_url = ? WHERE slideid = ?");
                    $stmt->execute([$image_path, $slide_id]);
                }
            }
            break;
    }

    $db->commit();

    $response['success'] = true;
    $response['session_id'] = $session_id;
    $response['message'] = 'สร้าง Presentation เรียบร้อย';
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>