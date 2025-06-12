<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (isset($_SESSION['user_id'])) {
    // ถ้าล็อกอินแล้วให้ไปหน้า dashboard
    header('Location: pages/dashboard.php');
    exit();
} else {
    // ถ้ายังไม่ได้ล็อกอินให้ไปหน้า login
    header('Location: auth/login.php');
    exit();
}
?>