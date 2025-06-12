<?php
session_start();

// ลบข้อมูล session ทั้งหมด
session_unset();
session_destroy();

// เปลี่ยนเส้นทางไปหน้า login
header('Location: login.php');
exit();
?>