<?php
session_start();
require 'config/db.php';
require 'config/lang_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get article ID
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$article_id) {
    header("Location: index.php");
    exit;
}

// Fetch article
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch();

if (!$article) {
    header("Location: index.php");
    exit;
}

$is_manual = ($article['type'] == 'manual');
$has_file = !empty($article['file_path']);
$extension = $has_file ? strtolower(pathinfo($article['file_path'], PATHINFO_EXTENSION)) : '';
$is_image = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
$is_pdf = ($extension === 'pdf');
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - EduVision</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .viewer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .viewer-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .viewer-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .viewer-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 30px;
        }
        .file-viewer {
            width: 100%;
            min-height: 600px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        .file-viewer iframe {
            width: 100%;
            height: 800px;
            border: none;
        }
        .file-viewer img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: contain;
            padding: 30px;
        }
        .viewer-content {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            text-align: center;
        }
        .viewer-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        @media print {
            .viewer-actions, .viewer-badge { display: none; }
        }
    </style>
</head>
<body>
    <div class="viewer-container">
        <div class="viewer-header">
            <span class="viewer-badge" style="background: <?= $is_manual ? '#e3f2fd' : '#f3e5f5' ?>; color: <?= $is_manual ? '#1976d2' : '#7b1fa2' ?>;">
                <?= $is_manual ? "O'QUV QO'LLANMA" : "MAQOLA" ?>
            </span>
            <h1 class="viewer-title"><?= htmlspecialchars($article['title']) ?></h1>
        </div>

        <?php if ($has_file): ?>
            <div class="file-viewer">
                <?php if ($is_image): ?>
                    <img src="<?= $article['file_path'] ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                <?php elseif ($is_pdf): ?>
                    <iframe src="<?= $article['file_path'] ?>#view=FitH" type="application/pdf"></iframe>
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; min-height: 400px; flex-direction: column; gap: 20px;">
                        <span style="font-size: 5rem;">📄</span>
                        <p style="font-size: 1.2rem; color: var(--text-light);">Fayl ko'rinishi mavjud emas</p>
                        <a href="<?= $article['file_path'] ?>" download class="btn">Faylni yuklab olish</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($article['content'])): ?>
            <div class="viewer-content">
                <h3 style="margin-bottom: 20px; color: var(--text-dark);">Tavsif</h3>
                <p style="font-size: 1.1rem; line-height: 1.8; color: var(--text-color); white-space: pre-wrap;">
                    <?= nl2br(htmlspecialchars($article['content'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="viewer-actions">
            <?php if ($has_file): ?>
                <a href="<?= $article['file_path'] ?>" download class="btn" style="background: var(--primary-color);">
                    📥 Yuklab olish
                </a>
                <button onclick="window.print()" class="btn btn-secondary" style="background: var(--secondary-color); color: var(--text-dark);">
                    🖨️ Chop etish
                </button>
            <?php endif; ?>
            <a href="javascript:history.back()" class="btn btn-secondary" style="background: #e2e8f0; color: var(--text-dark);">
                ← Orqaga
            </a>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 30px; border-top: 1px solid var(--border-color);">
            <p style="color: var(--text-light); font-size: 0.9rem;">
                📅 Yuklangan: <?= date('d.m.Y H:i', strtotime($article['created_at'])) ?>
            </p>
        </div>
    </div>
</body>
</html>
