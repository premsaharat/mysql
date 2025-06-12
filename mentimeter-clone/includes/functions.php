<?php
session_start();
require_once '../config/database.php';

// ฟังก์ชันตรวจสอบการล็อกอิน
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ฟังก์ชันเปลี่ยนเส้นทางถ้ายังไม่ได้ล็อกอิน
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

// ฟังก์ชันเข้ารหัสรหัสผ่าน
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// ฟังก์ชันตรวจสอบรหัสผ่าน
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ฟังก์ชันล้างข้อมูลที่รับเข้ามา
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// ฟังก์ชันตรวจสอบ email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ฟังก์ชันสร้าง join code
function generateJoinCode() {
    return str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
}

// ฟังก์ชันแสดงข้อความ alert
function setAlert($type, $message) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_message'] = $message;
}

// ฟังก์ชันแสดง alert และลบออกจาก session
function displayAlert() {
    if (isset($_SESSION['alert_message'])) {
        $type = $_SESSION['alert_type'] ?? 'info';
        $message = $_SESSION['alert_message'];
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        
        unset($_SESSION['alert_message']);
        unset($_SESSION['alert_type']);
    }
}

// ฟังก์ชันดึงข้อมูล user
function getUserData($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT userid, username, email, created_at FROM tbuser WHERE userid = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ฟังก์ชันดึง sessions ของ user

function getUserSessions($user_id) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("
            SELECT 
                sess.sessionid AS id, 
                sess.session_name AS title, 
                sess.created_at, 
                sess.status, 
                sess.join_code,
                COALESCE(COUNT(r.responseid), 0) AS responses
            FROM tbsession sess
            LEFT JOIN tbslide s ON sess.sessionid = s.sessionid
            LEFT JOIN tbresponse r ON s.slideid = r.slideid
            WHERE sess.created_by = ?
            GROUP BY sess.sessionid, sess.session_name, sess.created_at, sess.status, sess.join_code
            ORDER BY sess.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getUserSessions error: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันดึงเทมเพลต
function getTemplates() {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM tbtemplate WHERE is_public = 1 ORDER BY usage_count DESC, created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันดึงข้อมูล session ตาม ID
function getSessionData($session_id) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT sessionid, session_name, description, join_code, created_by, is_public, max_participants, status
            FROM tbsession
            WHERE sessionid = ? AND status != 'deleted'
        ");
        $stmt->execute([$session_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get session data error: " . $e->getMessage());
        return null;
    }
}

// ฟังก์ชันดึง slides ของ session
function getSlides($session_id) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT slideid, sessionid, slide_title, slide_type, question_text, background_image
            FROM tbslide
            WHERE sessionid = ?
            ORDER BY slideid
        ");
        $stmt->execute([$session_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get slides error: " . $e->getMessage());
        return [];
    }
}
?>