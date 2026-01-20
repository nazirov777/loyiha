<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

if (isset($_POST['submit_idea'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/images/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = 'uploads/images/' . $filename;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO projects (title, description, image_path, user_id, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$title, $description, $image_path, $user_id]);
    $message = "G'oyangiz yuborildi va admin tasdig'ini kutmoqda!";
}

$projects = [];
$db_error = false;
try {
    $projects = $pdo->query("
        SELECT p.*, u.name as creator_name, u.role as creator_role 
        FROM projects p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'approved'
        ORDER BY p.id DESC
    ")->fetchAll();
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') { // Column not found
        $db_error = true;
    } else {
        throw $e;
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>G'oyalar va Loyihalar - O'qituvchi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="margin: 0;">Jamoa Loyihalari va G'oyalar</h2>
            <button onclick="document.getElementById('idea-modal').style.display='flex'" class="btn-plus-idea">
                + Yangi G'oya
            </button>
        </div>

        <?php if ($db_error): ?>
            <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 20px; border-radius: 12px; margin-bottom: 30px;">
                <h3 style="margin-top: 0;">⚠️ Ma'lumotlar bazasida xatolik!</h3>
                <p>Loyihalar tizimi uchun zarur bo'lgan ustunlar bazada topilmadi.</p>
                <a href="../fix_database.php" class="btn" style="background: #ef4444; color: white; text-decoration: none; display: inline-block; margin-top: 10px;">Bazani tuzatish (Fix Database)</a>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
            <?php foreach ($projects as $project): ?>
                <div class="project-card" style="background: var(--bg-card); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); border: 1px solid var(--border-color); display: flex; flex-direction: column; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div class="project-img-wrapper">
                        <?php if($project['image_path']): ?>
                            <img src="../<?= $project['image_path'] ?>" class="project-img-content">
                        <?php else: ?>
                            <div style="font-size: 3rem; color: #94a3b8;">💡</div>
                        <?php endif; ?>
                        <div class="project-img-overlay">
                            <?= htmlspecialchars(strtoupper(isset($project['creator_role']) ? $project['creator_role'] : 'admin')) ?>
                        </div>
                    </div>
                    
                    <div style="padding: 25px 20px; flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <h4 style="margin: 0 0 12px 0; font-size: 1.25rem; color: var(--text-color); font-weight: 700;"><?= htmlspecialchars($project['title']) ?></h4>
                        <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 20px; line-height: 1.6; flex: 1;">
                            <?= nl2br(htmlspecialchars($project['description'])) ?>
                        </p>
                        
                        <div style="display: flex; flex-direction: column; align-items: center; padding-top: 15px; border-top: 1px solid var(--border-color); width: 100%;">
                            <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), #7209b7); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; margin-bottom: 10px; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);">
                                <?= substr(htmlspecialchars(isset($project['creator_name']) ? $project['creator_name'] : 'A'), 0, 1) ?>
                            </div>
                            <div style="font-size: 0.9rem;">
                                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 3px;"><?= htmlspecialchars(isset($project['creator_name']) ? $project['creator_name'] : 'Admin') ?></div>
                                <div style="color: var(--text-light); font-size: 0.8rem;">Yaratuvchi</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Submission Modal -->
    <div id="idea-modal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="max-width: 500px; width: 100%; position: relative; animation: slideUp 0.3s ease;">
            <button onclick="document.getElementById('idea-modal').style.display='none'" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            <h3>Yangi G'oya Yuborish</h3>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 20px;">G'oyangizni tavsiflang va admin tasdiqlaganidan so'ng u hammaga ko'rinadi.</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>G'oya/Loyiha nomi</label>
                    <input type="text" name="title" class="form-control" placeholder="Masalan: Aqlli Sug'orish Tizimi" required>
                </div>
                <div class="form-group">
                    <label>Rasm yoki Logo</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Batafsil tavsif</label>
                    <textarea name="description" class="form-control" rows="5" placeholder="G'oyangiz haqida batafsil ma'lumot bering..." required></textarea>
                </div>
                <button type="submit" name="submit_idea" class="btn" style="width: 100%;">Yuborish</button>
            </form>
        </div>
    </div>

    <style>
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</body>
</html>
