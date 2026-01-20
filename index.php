<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit;
} elseif ($_SESSION['role'] === 'teacher') {
    header("Location: teacher/dashboard.php");
    exit;
} else {
    header("Location: user/dashboard.php");
    exit;
}
?>
