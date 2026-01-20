<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: ../login.php");
    exit;
}

$message = '';

$contracts = $pdo->query("SELECT * FROM contracts ORDER BY id DESC")->fetchAll();
?>
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Shartnomalar - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include ($_SESSION['role'] == 'admin' ? 'sidebar.php' : '../teacher/sidebar.php'); ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Shartnomalar Majmuasi</h3>
            <p>Ushbu bo'limda siz mavjud shartnomalarni ko'rib chiqishingiz va yuklab olishingiz mumkin.</p>
        </div>

        <div class="card">
            <h3>Mavjud Shartnomalar</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding: 10px;">
                <?php foreach ($contracts as $contract): ?>
                    <div class="doc-card" style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.3s; box-shadow: var(--shadow);">
                        <div style="height: 120px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 3rem; color: white;">📄</span>
                        </div>
                        <div style="padding: 15px; flex: 1; display: flex; flex-direction: column;">
                            <h4 style="margin-bottom: 5px;"><?= htmlspecialchars($contract['title']) ?></h4>
                            <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px;">Fayl turi: PDF</p>
                            
                            <a href="../<?= $contract['file_path'] ?>" target="_blank" class="btn" style="width: 100%; text-align: center; text-decoration: none; margin-bottom: 10px; font-size: 0.9rem;">To'liq ko'rish</a>
                            
                            <div style="margin-top: auto; display: flex; justify-content: center; border-top: 1px solid var(--border-color); padding-top: 10px;">
                                <span style="color: var(--text-light); font-size: 0.8rem;">Faqat ko'rish uchun</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
