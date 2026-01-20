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

if (isset($_POST['save_contract'])) {
    $title = trim($_POST['title']);
    $contract_id = isset($_POST['contract_id']) ? $_POST['contract_id'] : null;
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
            if ($contract_id && isset($_POST['current_file']) && file_exists('../' . $_POST['current_file'])) {
                unlink('../' . $_POST['current_file']);
            }
        }
    }

    if ($contract_id) {
        $stmt = $pdo->prepare("UPDATE contracts SET title=?, file_path=? WHERE id=?");
        $stmt->execute([$title, $file_path, $contract_id]);
        $message = "Shartnoma yangilandi!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO contracts (title, file_path) VALUES (?, ?)");
        $stmt->execute([$title, $file_path]);
        
        // Notify Students
        notifyRole($pdo, 'student', "Yangi Shartnoma! 📋", "Tizimga yangi shartnoma yuklandi: $title", "contracts.php");
        
        $message = "Shartnoma yuklandi!";
    }
    
    if($contract_id) {
        echo "<script>window.location.href='contracts.php';</script>";
        exit;
    }
}

// Delete
if (isset($_POST['delete_contract'])) {
    $id = $_POST['contract_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM contracts WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    if ($file && file_exists('../' . $file)) unlink('../' . $file);
    
    $pdo->prepare("DELETE FROM contracts WHERE id = ?")->execute([$id]);
    $message = "O'chirildi!";
}

// Fetch for Edit
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}

$contracts = $pdo->query("SELECT * FROM contracts ORDER BY id DESC")->fetchAll();
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
            <h3><?= $edit_item ? 'Shartnomani Tahrirlash' : 'Yangi Shartnoma Yuklash' ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="contract_id" value="<?= $edit_item['id'] ?>">
                    <input type="hidden" name="current_file" value="<?= $edit_item['file_path'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Nomi</label>
                    <input type="text" name="title" class="form-control" value="<?= $edit_item ? htmlspecialchars($edit_item['title']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Fayl (PDF)</label>
                    <input type="file" name="file" class="form-control" accept="application/pdf" <?= $edit_item ? '' : 'required' ?>>
                    <?php if($edit_item): ?>
                        <small>Hozirgi: <a href="../<?= $edit_item['file_path'] ?>" target="_blank">Ko'rish</a></small>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="save_contract" class="btn"><?= $edit_item ? 'Yangilash' : 'Yuklash' ?></button>
                    <?php if ($edit_item): ?>
                        <a href="contracts.php" class="btn btn-secondary" style="background: #ccc; text-decoration: none; padding: 10px 20px; border-radius: 5px; color: black;">Bekor qilish</a>
                    <?php endif; ?>
                </div>
            </form>
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
                            
                            <div style="margin-top: auto; display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 10px;">
                                <a href="?edit=<?= $contract['id'] ?>" title="Tahrirlash" style="text-decoration: none; font-weight: 600;">✏️</a>
                                <form method="POST" onsubmit="return confirm('O\'chirilsinmi?');" style="display: inline;">
                                    <input type="hidden" name="contract_id" value="<?= $contract['id'] ?>">
                                    <button type="submit" name="delete_contract" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-weight: 600;">🗑️</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
