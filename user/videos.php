<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$videos = $pdo->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Videolar - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2 style="margin-bottom: 2rem; font-weight: 800; color: var(--text-dark);">Video Darsliklar</h2>
        
        <div class="grid">
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <video controls poster="">
                        <source src="../<?= $video['file_path'] ?>" type="video/mp4">
                    </video>
                    <div class="video-card-body">
                        <span class="badge badge-success">
                            <?= htmlspecialchars($video['type'] == 'game' ? "O'YIN" : "MAHORAT") ?>
                        </span>
                        <h4 class="video-card-title"><?= htmlspecialchars($video['title']) ?></h4>
                        <p class="video-card-text">
                            <?= htmlspecialchars($video['description']) ?>
                        </p>
                        <div style="margin-top: auto; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.8rem; color: var(--text-light);">EduVision Original</span>
                            <button onclick="this.parentElement.parentElement.previousElementSibling.play()" class="btn" style="width: auto; padding: 0.5rem 1rem; font-size: 0.8rem;">Tomosha qilish</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
