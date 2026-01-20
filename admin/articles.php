<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';
require '../config/notifications_helper.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: ../login.php");
    exit;
}

$message = '';

if (isset($_POST['save_article'])) {
    $article_id = isset($_POST['article_id']) ? $_POST['article_id'] : null;
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $type = $_POST['type']; 
    
    $file_path = isset($_POST['current_file']) ? $_POST['current_file'] : '';
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $upload_dir = '../uploads/files/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $original_name = $_FILES['file']['name'];
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $filename = time() . '_' . $safe_name . '.' . $extension;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename)) {
            $file_path = 'uploads/files/' . $filename;
            if ($article_id && isset($_POST['current_file']) && file_exists('../' . $_POST['current_file'])) {
                unlink('../' . $_POST['current_file']);
            }
        }
    }

    if ($article_id) {
        $stmt = $pdo->prepare("UPDATE articles SET title=?, content=?, file_path=?, type=? WHERE id=?");
        $stmt->execute([$title, $content, $file_path, $type, $article_id]);
        $message = "Yangilandi!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO articles (title, content, file_path, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $file_path, $type]);
        
        $type_label = ($type == 'manual') ? "O'quv qo'llanma" : "Maqola";
        notifyRole($pdo, 'student', "Yangi $type_label! 📄", "Yangi material qo'shildi: $title", "articles.php");
        
        $message = "Saqlandi!";
    }

    if ($article_id) {
        echo "<script>window.location.href='articles.php';</script>";
        exit;
    }
}

// Delete
if (isset($_POST['delete_article'])) {
    $id = $_POST['article_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    if ($file && file_exists('../' . $file)) unlink('../' . $file);
    
    $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
    $message = "O'chirildi!";
}

// Fetch for Edit
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
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
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Qo'llanma va Maqolalar - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include ($_SESSION['role'] == 'admin' ? 'sidebar.php' : '../teacher/sidebar.php'); ?>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="font-weight: 800; color: var(--text-dark);">📚 Qo'llanma va Maqolalar</h2>
            <button onclick="toggleArticleForm()" class="btn" style="width: auto; padding: 12px 25px; background: linear-gradient(135deg, var(--primary-color), #7209b7); border: none; box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3); transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; font-weight: 700;">
                <span style="font-size: 1.2rem;">➕</span> Yangi qo'llanma/maqola
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card" id="articleFormCard" style="<?= $edit_item ? '' : 'display: none;' ?> margin-bottom: 30px; animation: slideDown 0.3s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;"><?= $edit_item ? 'Maqola/Qo\'llanmani Tahrirlash' : 'Yangi Qo\'shish' ?></h3>
                <button onclick="toggleArticleForm()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-light);">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="article_id" value="<?= $edit_item['id'] ?>">
                    <input type="hidden" name="current_file" value="<?= $edit_item['file_path'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Sarlavha</label>
                    <input type="text" name="title" class="form-control" value="<?= $edit_item ? htmlspecialchars($edit_item['title']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Turi</label>
                    <select name="type" class="form-control">
                        <option value="article" <?= ($edit_item && $edit_item['type'] == 'article') ? 'selected' : '' ?>>Maqola</option>
                        <option value="manual" <?= ($edit_item && $edit_item['type'] == 'manual') ? 'selected' : '' ?>>O'quv qo'llanma</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mazmun (Asosiy matn)</label>
                    <textarea name="content" class="form-control" rows="8"><?= $edit_item ? htmlspecialchars($edit_item['content']) : '' ?></textarea>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 12px; display: block;">Fayl yuklash (PDF/Docx) - Ixtiyoriy</label>
                    <div style="background: #f8fafc; border: 2px dashed var(--border-color); padding: 1.5rem; border-radius: 16px; text-align: center; transition: all 0.3s ease; position: relative;" onmouseover="this.style.borderColor='var(--primary-color)';" onmouseout="this.style.borderColor='var(--border-color)';">
                        <input type="file" name="file" class="form-control" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; z-index: 2;">
                        <div style="pointer-events: none;">
                            <span style="font-size: 2.5rem; display: block; margin-bottom: 10px;">📁</span>
                            <span style="font-weight: 600; color: var(--text-dark); display: block;">Faylni tanlang yoki shu yerga tashlang</span>
                            <span style="font-size: 0.8rem; color: var(--text-light);">PDF, DOCX yoki Rasmlar (Max 10MB)</span>
                        </div>
                    </div>
                    <?php if($edit_item && $edit_item['file_path']): ?>
                        <div style="margin-top: 15px; display: flex; align-items: center; justify-content: center; gap: 10px; background: var(--secondary-color); padding: 10px; border-radius: 12px;">
                            <span style="font-size: 1.2rem;">📄</span>
                            <span style="font-size: 0.85rem; font-weight: 600;">Hozirgi fayl:</span>
                            <a href="../<?= $edit_item['file_path'] ?>" target="_blank" style="color: var(--primary-color); text-decoration: none; font-size: 0.85rem; font-weight: 700;">Ko'rish</a>
                            <span style="color: var(--text-light); font-size: 0.8rem;">(O'zgartirish uchun yangisini tanlang)</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="save_article" class="btn"><?= $edit_item ? 'Yangilash' : 'Saqlash' ?></button>
                    <?php if ($edit_item): ?>
                        <a href="articles.php" class="btn btn-secondary" style="background: #ccc; text-decoration: none; padding: 10px 20px; border-radius: 5px; color: black;">Bekor qilish</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <h3 style="margin-top: 30px; margin-bottom: 15px;"><?= $lang['manuals'] ?> va Maqolalar</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php 
            $all_items = array_merge($articles, $manuals); 
            usort($all_items, function($a, $b) { return $b['id'] - $a['id']; });

            foreach ($all_items as $item): 
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
                        
                        <div style="width: 100%;">
                            <?php if($item['file_path']): ?>
                                <a href="../view_article.php?id=<?= $item['id'] ?>" class="btn" style="width: 100%; text-decoration: none; text-align: center; margin-bottom: 15px; font-size: 0.9rem;">📄 To'liq ko'rish / Yuklab olish</a>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: center; gap: 15px; align-items: center; border-top: 1px solid var(--border-color); padding-top: 15px;">
                                <a href="?edit=<?= $item['id'] ?>" style="text-decoration: none; font-weight: 600; color: var(--primary-color); display: flex; align-items: center; gap: 5px;">✏️ Tahrirlash</a>
                                <form method="POST" onsubmit="return confirm('O\'chirilsinmi?');" style="display: inline;">
                                    <input type="hidden" name="article_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="delete_article" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px;">🗑️ O'chirish</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function toggleArticleForm() {
        const form = document.getElementById('articleFormCard');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            form.style.display = 'none';
        }
    }
    </script>
    <style>
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    </style>
</body>
</html>
