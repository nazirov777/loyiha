<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Counts
$studentCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$groupCount = $pdo->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();
$taskCount = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$videoCount = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2 style="margin-bottom: 2rem; font-weight: 800; color: var(--text-dark);">Boshqaruv Paneli</h2>
        
        <div class="grid">
            <div class="stat-card">
                <h4>Talabalar</h4>
                <h3><?= $studentCount ?></h3>
                <div style="font-size: 0.85rem; color: var(--success); font-weight: 600;">Jami ro'yxatdan o'tganlar</div>
            </div>
            <div class="stat-card">
                <h4>Guruhlar</h4>
                <h3><?= $groupCount ?></h3>
                <div style="font-size: 0.85rem; color: var(--primary-color); font-weight: 600;">O'quv guruhlari soni</div>
            </div>
            <div class="stat-card">
                <h4>Vazifalar</h4>
                <h3><?= $taskCount ?></h3>
                <div style="font-size: 0.85rem; color: var(--warning); font-weight: 600;">Berilgan topshiriqlar</div>
            </div>
            <div class="stat-card">
                <h4>Videolar</h4>
                <h3><?= $videoCount ?></h3>
                <div style="font-size: 0.85rem; color: var(--danger); font-weight: 600;">Darslik videolari</div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 20px;">Platforma Statistikasi</h3>
            <p style="color: var(--text-light); line-height: 1.6;">EduVision platformasi orqali talabalar va o'qituvchilar o'rtasidagi hamkorlikni yanada samaraliroq boshqarishingiz mumkin. Yuqoridagi ko'rsatkichlar real vaqt rejimida yangilanadi.</p>
        </div>
    </div>
</body>
</html>
