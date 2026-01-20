<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$articles = $pdo->query("
    SELECT a.*, u.name as creator_name, u.role as creator_role 
    FROM articles a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.type='article' 
    ORDER BY a.id DESC
")->fetchAll();

$manuals = $pdo->query("
    SELECT a.*, u.name as creator_name, u.role as creator_role 
    FROM articles a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.type='manual' 
    ORDER BY a.id DESC
")->fetchAll();

$all_items = array_merge($articles, $manuals); 
usort($all_items, function($a, $b) { return $b['id'] - $a['id']; });

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Qo'llanma va Maqolalar - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2><?= $lang['manuals'] ?> va Maqolalar</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach ($all_items as $item): 
                $is_manual = ($item['type'] == 'manual');
            ?>
                <div class="article-card" style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color); overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow);">
                    <?php if($item['file_path']): 
                        $extension = strtolower(pathinfo($item['file_path'], PATHINFO_EXTENSION));
                        $is_image = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        $is_pdf = ($extension === 'pdf');
                        $is_word = in_array($extension, ['doc', 'docx']);
                    ?>
                        <div style="position: relative; width: 100%; height: 200px; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); display: flex; align-items: flex-start; justify-content: center; overflow: hidden; border-bottom: 1px solid var(--border-color);">
                            <?php if ($is_image): ?>
                                <img src="../<?= $item['file_path'] ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: top center; display: block;">
                            <?php elseif ($is_pdf): ?>
                                <iframe src="../<?= $item['file_path'] ?>#view=FitW&toolbar=0&navpanes=0" style="width: 100%; height: 100%; border: none;"></iframe>
                            <?php elseif ($is_word): ?>
                                <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . '/' . $item['file_path']) ?>&wdPrint=0&wdEmbedCode=0&wdStartOn=1" style="width: 100%; height: 100%; border: none;"></iframe>
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">
                                    <span style="font-size: 3rem; color: #94a3b8;">📄</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="padding: 25px 20px; flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <span style="font-size: 0.75rem; background: <?= $is_manual ? '#e3f2fd' : '#f3e5f5' ?>; color: <?= $is_manual ? '#1976d2' : '#7b1fa2' ?>; padding: 6px 14px; border-radius: 50px; margin-bottom: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px;">
                            <?= $is_manual ? "O'QUV QO'LLANMA" : "MAQOLA" ?>
                        </span>
                        <h4 style="margin-bottom: 12px; font-size: 1.25rem; font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars($item['title']) ?></h4>
                        <p style="font-size: 0.9rem; color: var(--text-light); line-height: 1.6; margin-bottom: 20px; flex: 1;">
                            <?= htmlspecialchars(strip_tags($item['content'])) ?>
                        </p>
                        
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; background: rgba(67, 97, 238, 0.05); border-radius: 12px; margin-bottom: 20px; width: 100%;">
                            <div style="width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), #7209b7); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem;">
                                <?= substr(htmlspecialchars(isset($item['creator_name']) ? $item['creator_name'] : 'A'), 0, 1) ?>
                            </div>
                            <div style="text-align: left;">
                                <div style="font-weight: 700; color: var(--text-dark); font-size: 0.9rem;"><?= htmlspecialchars(isset($item['creator_name']) ? $item['creator_name'] : 'Admin') ?></div>
                                <div style="color: var(--text-light); font-size: 0.75rem;"><?= ucfirst(isset($item['creator_role']) ? $item['creator_role'] : 'admin') ?></div>
                            </div>
                        </div>
                        
                        <?php if($item['file_path']): ?>
                            <a href="../view_article.php?id=<?= $item['id'] ?>" class="btn" style="width: 100%; text-decoration: none; text-align: center; font-size: 0.9rem;">📄 To'liq ko'rish / Yuklab olish</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
