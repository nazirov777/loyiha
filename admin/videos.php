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

if (isset($_POST['save_video'])) {
    $video_id = isset($_POST['video_id']) ? $_POST['video_id'] : null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    
    $file_path = isset($_POST['current_file']) ? $_POST['current_file'] : '';

    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
        $upload_dir = '../uploads/videos/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $original_name = $_FILES['video_file']['name'];
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $filename = time() . '_' . $safe_name . '.' . $extension;

        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $upload_dir . $filename)) {
            $file_path = 'uploads/videos/' . $filename;
            if ($video_id && isset($_POST['current_file']) && file_exists('../' . $_POST['current_file'])) {
                unlink('../' . $_POST['current_file']);
            }
        }
    }

    if ($video_id) {
        $stmt = $pdo->prepare("UPDATE videos SET title=?, description=?, file_path=?, type=? WHERE id=?");
        $stmt->execute([$title, $description, $file_path, $type, $video_id]);
        $message = "Video yangilandi!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO videos (title, description, file_path, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $file_path, $type]);
        
        // Notify Students
        notifyRole($pdo, 'student', "Yangi Video! 🎬", "Yangi video yuklandi: $title", "videos.php");
        
        $message = "Video yuklandi!";
    }

    if ($video_id) {
        echo "<script>window.location.href='videos.php';</script>";
        exit;
    }
}

// Delete
if (isset($_POST['delete_video'])) {
    $id = $_POST['video_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    if ($file && file_exists('../' . $file)) unlink('../' . $file);
    
    $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$id]);
    $message = "O'chirildi!";
}

// Fetch for Edit
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
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
    <?php include ($_SESSION['role'] == 'admin' ? 'sidebar.php' : '../teacher/sidebar.php'); ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><?= $edit_item ? 'Video Tahrirlash' : 'Video Yuklash' ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="video_id" value="<?= $edit_item['id'] ?>">
                    <input type="hidden" name="current_file" value="<?= $edit_item['file_path'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Sarlavha</label>
                    <input type="text" name="title" class="form-control" value="<?= $edit_item ? htmlspecialchars($edit_item['title']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Tavsif</label>
                    <textarea name="description" class="form-control" required rows="3"><?= $edit_item ? htmlspecialchars($edit_item['description']) : '' ?></textarea>
                </div>
                <div class="form-group">
                    <label>Yo'nalish</label>
                    <select name="type" class="form-control">
                        <option value="game" <?= ($edit_item && $edit_item['type'] == 'game') ? 'selected' : '' ?>>O'yin Video Yozuvlari</option>
                        <option value="skill" <?= ($edit_item && $edit_item['type'] == 'skill') ? 'selected' : '' ?>>Mahorat Darslari</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Video Fayl</label>
                    <input type="file" name="video_file" class="form-control" accept="video/*">
                    <?php if($edit_item): ?>
                        <small>Hozirgi: <a href="../<?= $edit_item['file_path'] ?>" target="_blank">Ko'rish</a></small>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="save_video" class="btn"><?= $edit_item ? 'Yangilash' : 'Yuklash' ?></button>
                    <?php if ($edit_item): ?>
                        <a href="videos.php" class="btn btn-secondary" style="background: #ccc; text-decoration: none; padding: 10px 20px; border-radius: 5px; color: black;">Bekor qilish</a>
                    <?php endif; ?>
                </div>
            </form>
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
                        
                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 10px;">
                            <a href="?edit=<?= $video['id'] ?>" style="text-decoration: none; font-weight: 600; color: var(--secondary-color);">✏️ Tahrirlash</a>
                            <form method="POST" onsubmit="return confirm('O\'chirilsinmi?');" style="display: inline;">
                                <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                <button type="submit" name="delete_video" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-weight: 600;">🗑️ O'chirish</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
