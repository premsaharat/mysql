<?php
header('Content-Type: application/json');
require_once '../includes/database.php';
require_once '../includes/functions.php';

try {
    $session_id = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
    if (!$session_id) {
        throw new Exception("Invalid session ID");
    }

    $conn = getDBConnection();
    
    // Get slides
    $stmt = $conn->prepare("
        SELECT slideid, question, type, options
        FROM tbslide
        WHERE sessionid = ?
        ORDER BY slideid
    ");
    $stmt->execute([$session_id]);
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($slides as $slide) {
        $slide_result = [
            'slideid' => $slide['slideid'],
            'question' => $slide['question'],
            'type' => $slide['type'],
            'responses' => []
        ];

        if ($slide['type'] === 'poll') {
            // Multiple Choice responses
            $stmt = $conn->prepare("
                SELECT c.choice_text, COUNT(r.responseid) as count
                FROM tbchoice c
                LEFT JOIN tbresponse r ON c.choiceid = r.choiceid
                WHERE c.slideid = ?
                GROUP BY c.choiceid, c.choice_text
            ");
            $stmt->execute([$slide['slideid']]);
            $slide_result['responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($slide['type'] === 'wordcloud' || $slide['type'] === 'openended') {
            // Word Cloud or Open Ended responses
            $stmt = $conn->prepare("
                SELECT response_text
                FROM tbresponse
                WHERE slideid = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$slide['slideid']]);
            $slide_result['responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $results[] = $slide_result;
    }

    echo json_encode(['success' => true, 'results' => $results]);
} catch (Exception $e) {
    error_log("Get results error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>