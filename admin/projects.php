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
$user_id = $_SESSION['user_id'];

// Approval Action
if (isset($_POST['approve_project'])) {
    $id = $_POST['project_id'];
    $pdo->prepare("UPDATE projects SET status='approved' WHERE id=?")->execute([$id]);
    
    // Get owner
    $stmtOwner = $pdo->prepare("SELECT user_id, title FROM projects WHERE id = ?");
    $stmtOwner->execute([$id]);
    $project = $stmtOwner->fetch();
    if ($project && $project['user_id']) {
        sendNotification($pdo, $project['user_id'], "Loyiha Tasdiqlandi!", "'{$project['title']}' loyihangiz admin tomonidan tasdiqlandi.", "projects.php");
    }
    
    $message = "Loyiha tasdiqlandi!";
}

if (isset($_POST['save_project'])) {
    $project_id = isset($_POST['project_id']) ? $_POST['project_id'] : null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    $image_path = isset($_POST['current_image']) ? $_POST['current_image'] : '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/images/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $original_name = $_FILES['image']['name'];
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $filename = time() . '_' . $safe_name . '.' . $extension;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = 'uploads/images/' . $filename;
            if ($project_id && isset($_POST['current_image']) && file_exists('../' . $_POST['current_image'])) {
                unlink('../' . $_POST['current_image']);
            }
        }
    }

    if ($project_id) {
        $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, image_path=? WHERE id=?");
        $stmt->execute([$title, $description, $image_path, $project_id]);
        $message = "Loyiha yangilandi!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects (title, description, image_path, user_id, status) VALUES (?, ?, ?, ?, 'approved')");
        $stmt->execute([$title, $description, $image_path, $user_id]);
        $message = "Loyiha qo'shildi!";
    }

    if ($project_id) {
        echo "<script>window.location.href='projects.php';</script>";
        exit;
    }
}

// Delete
if (isset($_POST['delete_project'])) {
    $id = $_POST['project_id'];
    $stmt = $pdo->prepare("SELECT image_path FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists('../' . $img)) unlink('../' . $img);
    
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    $message = "O'chirildi!";
}

// Fetch for Edit
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}

$projects = [];
$db_error = false;
try {
    $projects = $pdo->query("
        SELECT p.*, u.name as creator_name, u.role as creator_role 
        FROM projects p 
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.status DESC, p.id DESC
    ")->fetchAll();
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') {
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
    <title>Loyihalar - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include ($_SESSION['role'] == 'admin' ? 'sidebar.php' : '../teacher/sidebar.php'); ?>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="margin: 0;">Loyihalar va G'oyalar Boshqaruvi</h2>
            <button onclick="document.getElementById('idea-modal').style.display='flex'" class="btn-plus-idea">
                + Yangi Loyiha/G'oya
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
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
            <?php foreach ($projects as $project): ?>
                <div class="project-card" style="background: var(--bg-card); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); border: 1px solid var(--border-color); display: flex; flex-direction: column;">
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 12px; width: 100%; flex-wrap: wrap; gap: 10px;">
                            <h4 style="margin: 0; font-size: 1.25rem; font-weight: 700;"><?= htmlspecialchars($project['title']) ?></h4>
                            <?php if($project['status'] == 'pending'): ?>
                                <span class="badge badge-warning">KUTILMOQDA</span>
                            <?php endif; ?>
                        </div>
                        
                        <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 20px; line-height: 1.6; flex: 1;">
                            <?= nl2br(htmlspecialchars($project['description'])) ?>
                        </p>

                        <div style="display: flex; flex-direction: column; align-items: center; padding-top: 15px; border-top: 1px solid var(--border-color); margin-bottom: 15px; width: 100%;">
                            <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), #7209b7); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; margin-bottom: 10px; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);">
                                <?= substr(htmlspecialchars(isset($project['creator_name']) ? $project['creator_name'] : 'A'), 0, 1) ?>
                            </div>
                            <div style="font-size: 0.9rem;">
                                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 3px;"><?= htmlspecialchars(isset($project['creator_name']) ? $project['creator_name'] : 'Admin') ?></div>
                                <div style="color: var(--text-light); font-size: 0.8rem;">Yaratuvchi</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center;">
                            <div>
                                <a href="?edit=<?= $project['id'] ?>" title="Tahrirlash" style="text-decoration: none; font-size: 1.2rem; margin-right: 10px;">✏️</a>
                                <form method="POST" onsubmit="return confirm('O\'chirilsinmi?');" style="display: inline;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <button type="submit" name="delete_project" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 1.2rem;">🗑️</button>
                                </form>
                            </div>
                            
                            <?php if($project['status'] == 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                    <button type="submit" name="approve_project" class="btn" style="padding: 8px 15px; font-size: 0.85rem; background: #10b981; width: auto;">✅ Tasdiqlash</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Submission Modal -->
    <div id="idea-modal" style="display: <?= $edit_item ? 'flex' : 'none' ?>; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="max-width: 500px; width: 100%; position: relative; animation: slideUp 0.3s ease;">
            <?php if (!$edit_item): ?>
                <button onclick="document.getElementById('idea-modal').style.display='none'" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            <?php else: ?>
                <a href="projects.php" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: inherit; text-decoration: none;">&times;</a>
            <?php endif; ?>

            <h3><?= $edit_item ? 'Loyihani Tahrirlash' : 'Yangi Loyiha/G\'oya Qo\'shish' ?></h3>
            
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="project_id" value="<?= $edit_item['id'] ?>">
                    <input type="hidden" name="current_image" value="<?= $edit_item['image_path'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Loyiha nomi</label>
                    <input type="text" name="title" class="form-control" value="<?= $edit_item ? htmlspecialchars($edit_item['title']) : '' ?>" placeholder="Masalan: Aqlli Sug'orish Tizimi" required>
                </div>
                <div class="form-group">
                    <label>Rasm (Logo/Skrinshot)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if($edit_item && $edit_item['image_path']): ?>
                        <small>Hozirgi: <a href="../<?= $edit_item['image_path'] ?>" target="_blank">Ko'rish</a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Batafsil tavsif</label>
                    <textarea name="description" class="form-control" rows="5" placeholder="Loyiha haqida batafsil ma'lumot bering..." required><?= $edit_item ? htmlspecialchars($edit_item['description']) : '' ?></textarea>
                </div>
                <button type="submit" name="save_project" class="btn" style="width: 100%;"><?= $edit_item ? 'Yangilash' : 'Saqlash' ?></button>
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
