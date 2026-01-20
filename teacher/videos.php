<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: ../login.php");
    exit;
}

$message = '';

$videos = $pdo->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll();
?>
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Videolar - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include ($_SESSION['role'] == 'admin' ? 'sidebar.php' : '../teacher/sidebar.php'); ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Videolarni Ko'rish</h3>
            <p>Siz ushbu bo'limda o'quv videolarini ko'rishingiz mumkin.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach ($videos as $video): ?>
                <div class="video-card" style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow); position: relative;">
                    <video width="100%" controls style="max-height: 180px; background: #000;">
                        <source src="../<?= $video['file_path'] ?>" type="video/mp4">
                    </video>
                    <div style="padding: 15px; flex: 1; display: flex; flex-direction: column;">
                        <span style="font-size: 0.7rem; background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 10px; align-self: flex-start; margin-bottom: 8px; font-weight: bold;">
                            <?= htmlspecialchars($video['type'] == 'game' ? "O'YIN" : "MAHORAT") ?>
                        </span>
                        <h4 style="margin-bottom: 8px; font-size: 1.1rem;"><?= htmlspecialchars($video['title']) ?></h4>
                        <p style="font-size: 0.85rem; color: var(--text-light); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 15px;">
                            <?= htmlspecialchars($video['description']) ?>
                        </p>
                        
                        <div style="margin-top: auto; display: flex; justify-content: center; border-top: 1px solid var(--border-color); padding-top: 10px;">
                            <span style="color: var(--text-light); font-size: 0.8rem;">Faqat ko'rish uchun</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
