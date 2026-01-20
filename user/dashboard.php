<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get assigned tasks count
$taskCount = $pdo->query("
    SELECT COUNT(*) FROM tasks t
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN user_groups ug ON ug.group_id = ta.group_id
    WHERE ta.user_id = $user_id OR ug.user_id = $user_id
")->fetchColumn();

// Get other counts
$videoCount = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$articleCount = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2 style="margin-bottom: 2rem; font-weight: 800; color: var(--text-dark);">Xush kelibsiz!</h2>
        
        <div class="grid">
            <div class="stat-card">
                <h4>Mening Vazifalarim</h4>
                <h3><?= $taskCount ?></h3>
                <div style="font-size: 0.85rem; color: var(--warning); font-weight: 600;">Faol topshiriqlar</div>
            </div>
            <div class="stat-card">
                <h4>Videolar</h4>
                <h3><?= $videoCount ?></h3>
                <div style="font-size: 0.85rem; color: var(--primary-color); font-weight: 600;">O'quv videolari</div>
            </div>
            <div class="stat-card">
                <h4>Qo'llanmalar</h4>
                <h3><?= $articleCount ?></h3>
                <div style="font-size: 0.85rem; color: var(--success); font-weight: 600;">Maqolalar va darsliklar</div>
            </div>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <p style="color: var(--text-light); line-height: 1.6;">O'z bilimingizni oshirish uchun resurslardan foydalaning. EduVision sizga sifatli ta'lim olishda yordam beradi.</p>
        </div>
    </div>
</body>
</html>
