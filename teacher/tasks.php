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

// Proactive Database Check
$db_error = false;
try {
    $pdo->query("SELECT created_by FROM tasks LIMIT 1");
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') { $db_error = true; }
}

// Create Task
if (isset($_POST['create_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $deadline = $_POST['deadline'];
    $assign_type = $_POST['assign_type']; 
    $subject_id = $_POST['subject_id'];
    $max_attempts = intval($_POST['max_attempts']);    
    $file_path = '';
    if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] == 0) {
        $upload_dir = '../uploads/files/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $original_name = $_FILES['task_file']['name'];
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $filename = time() . '_' . $safe_name . '.' . $extension;

        if (move_uploaded_file($_FILES['task_file']['tmp_name'], $upload_dir . $filename)) {
            $file_path = 'uploads/files/' . $filename;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, type, file_path, deadline, created_by, subject_id, max_attempts) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $type, $file_path, $deadline, $user_id, $subject_id, $max_attempts]);
    $task_id = $pdo->lastInsertId();

    if ($assign_type == 'user') {
        $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)");
        $stmt->execute([$task_id, $_POST['assign_id_user']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, group_id) VALUES (?, ?)");
        $stmt->execute([$task_id, $_POST['assign_id_group']]);
    }

    // Send Notifications
    if ($assign_type == 'user') {
        sendNotification($pdo, $_POST['assign_id_user'], "Yangi Vazifa!", "Sizga yangi shaxsiy vazifa yuklandi: $title", "tasks.php");
    } else {
        $group_id = $_POST['assign_id_group'];
        $stmtUsers = $pdo->prepare("SELECT user_id FROM user_groups WHERE group_id = ?");
        $stmtUsers->execute([$group_id]);
        while ($row = $stmtUsers->fetch()) {
            sendNotification($pdo, $row['user_id'], "Guruhga yangi vazifa!", "Guruhingizga yangi vazifa yuklandi: $title", "tasks.php");
        }
    }

    $message = "Vazifa yaratildi!";
}

// Update Task
if (isset($_POST['update_task'])) {
    $task_id = $_POST['task_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $deadline = $_POST['deadline'];
    $assign_type = $_POST['assign_type'];
    $subject_id = $_POST['subject_id'];
    $max_attempts = intval($_POST['max_attempts']);

    // Get current file path
    $stmt = $pdo->prepare("SELECT file_path FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $current_file = $stmt->fetchColumn();
    $file_path = $current_file;

    if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] == 0) {
        $upload_dir = '../uploads/files/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $original_name = $_FILES['task_file']['name'];
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $filename = time() . '_' . $safe_name . '.' . $extension;

        if (move_uploaded_file($_FILES['task_file']['tmp_name'], $upload_dir . $filename)) {
            $file_path = 'uploads/files/' . $filename;
            // Optionally delete old file
            if ($current_file && file_exists('../' . $current_file)) unlink('../' . $current_file);
        }
    }

    $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, type=?, file_path=?, deadline=?, subject_id=?, max_attempts=? WHERE id=?");
    $stmt->execute([$title, $description, $type, $file_path, $deadline, $subject_id, $max_attempts, $task_id]);

    // Update Assignments: Delete old and add new
    $pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?")->execute([$task_id]);
    if ($assign_type == 'user') {
        $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)");
        $stmt->execute([$task_id, $_POST['assign_id_user']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, group_id) VALUES (?, ?)");
        $stmt->execute([$task_id, $_POST['assign_id_group']]);
    }

    $message = "Vazifa yangilandi!";
    echo "<script>window.location.href='tasks.php';</script>";
    exit;
}

// Delete Task
if (isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $file = $stmt->fetchColumn();
    if ($file && file_exists('../' . $file)) unlink('../' . $file);
    
    $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);
    $message = "O'chirildi!";
}

// Fetch for Edit
$edit_task = null;
$current_assign = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_task = $stmt->fetch();
    
    if ($edit_task) {
        $stmt = $pdo->prepare("SELECT * FROM task_assignments WHERE task_id = ?");
        $stmt->execute([$edit_task['id']]);
        $current_assign = $stmt->fetch();
    }
}

// Lists
$students = $pdo->query("SELECT * FROM users WHERE role = 'student'")->fetchAll();
$groups = $pdo->query("SELECT * FROM `groups`")->fetchAll();
$subjects_list = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();


if ($db_error) {
    // Fallback if created_by missing
    $tasks = $pdo->query("SELECT t.*, 'Admin' as creator_name FROM tasks t ORDER BY t.id DESC")->fetchAll();
} else {
    $tasks = $pdo->query("SELECT t.*, u.name as creator_name FROM tasks t LEFT JOIN users u ON t.created_by = u.id ORDER BY t.id DESC")->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Vazifalar - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        function toggleAssign() {
            var type = document.getElementById('assign_type').value;
            document.getElementById('user_select_container').style.display = type === 'user' ? 'block' : 'none';
            document.getElementById('group_select_container').style.display = type === 'group' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <?php include ($_SESSION['role'] == 'admin' ? 'sidebar.php' : '../teacher/sidebar.php'); ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($db_error): ?>
            <div class="alert alert-danger" style="background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>⚠️ DIQQAT: MA'LUMOTLAR BAZASI YANGILANMAGAN!</strong><br>
                Tizim to'g'ri ishlashi uchun bazani yangilash zarur. <br><br>
                <a href="../update_db_v3.php" class="btn" style="background: #ef4444; color: white; text-decoration: none; display: inline-block; width: auto; padding: 10px 20px;">
                    BAZANI HOZIR YANGILASH
                </a>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><?= $edit_task ? 'Vazifani Tahrirlash' : 'Yangi Vazifa Yaratish' ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_task): ?>
                    <input type="hidden" name="task_id" value="<?= $edit_task['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Sarlavha</label>
                    <input type="text" name="title" class="form-control" value="<?= $edit_task ? htmlspecialchars($edit_task['title']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Tavsif</label>
                    <textarea name="description" class="form-control" required><?= $edit_task ? htmlspecialchars($edit_task['description']) : '' ?></textarea>
                </div>
                <div class="form-group">
                    <label>Fan (Baho uchun zarur)</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">Fanni tanlang</option>
                        <?php foreach($subjects_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($edit_task && $edit_task['subject_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Maksimal urinishlar soni</label>
                    <input type="number" name="max_attempts" class="form-control" value="<?= $edit_task ? $edit_task['max_attempts'] : '1' ?>" min="1" max="10">
                    <small style="color: var(--text-light);">Talaba necha marta javob yubora olishi</small>
                </div>
                <div class="form-group">
                    <label>Turi</label>
                    <select name="type" class="form-control">
                        <option value="text" <?= ($edit_task && $edit_task['type'] == 'text') ? 'selected' : '' ?>>Matnli</option>
                        <option value="image" <?= ($edit_task && $edit_task['type'] == 'image') ? 'selected' : '' ?>>Rasmli</option>
                        <option value="file" <?= ($edit_task && $edit_task['type'] == 'file') ? 'selected' : '' ?>>Faylli</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fayl (Yangi yuklansa eskisi o'chadi)</label>
                    <input type="file" name="task_file" class="form-control">
                    <?php if($edit_task && $edit_task['file_path']): ?>
                        <small>Hozirgi: <a href="../<?= $edit_task['file_path'] ?>" target="_blank">Ko'rish</a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Deadline</label>
                    <input type="datetime-local" name="deadline" class="form-control" value="<?= $edit_task ? date('Y-m-d\TH:i', strtotime($edit_task['deadline'])) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Kimga</label>
                    <select name="assign_type" id="assign_type" class="form-control" onchange="toggleAssign()">
                        <option value="user" <?= ($current_assign && $current_assign['user_id']) ? 'selected' : '' ?>>Bitta talabaga</option>
                        <option value="group" <?= ($current_assign && $current_assign['group_id']) ? 'selected' : '' ?>>Guruhga</option>
                    </select>
                </div>

                <div class="form-group" id="user_select_container" style="<?= ($current_assign && $current_assign['group_id']) ? 'display:none;' : '' ?>">
                    <label>Talaba</label>
                    <select name="assign_id_user" id="user_select" class="form-control">
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>" <?= ($current_assign && $current_assign['user_id'] == $student['id']) ? 'selected' : '' ?>><?= htmlspecialchars($student['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="group_select_container" style="<?= ($current_assign && $current_assign['group_id']) ? '' : 'display:none;' ?>">
                    <label>Guruh</label>
                    <select name="assign_id_group" id="group_select" class="form-control">
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>" <?= ($current_assign && $current_assign['group_id'] == $group['id']) ? 'selected' : '' ?>><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="<?= $edit_task ? 'update_task' : 'create_task' ?>" class="btn">
                        <?= $edit_task ? 'Saqlash' : 'Vazifa Yaratish' ?>
                    </button>
                    <?php if ($edit_task): ?>
                        <a href="tasks.php" class="btn btn-secondary" style="background: #ccc; text-decoration: none; padding: 10px 20px; border-radius: 5px; color: black;">Bekor qilish</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Vazifalar Ro'yxati</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #ddd;">
                            <th style="padding: 10px;">Sarlavha</th>
                            <th style="padding: 10px;">Turi</th>
                            <th style="padding: 10px;">Muddat</th>
                            <th style="padding: 10px;">Kim yaratgan</th>
                            <th style="padding: 10px;">Amal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?= htmlspecialchars($task['title']) ?></td>
                                <td style="padding: 10px;"><?= $task['type'] ?></td>
                                <td style="padding: 10px;"><?= $task['deadline'] ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars($task['creator_name']) ?></td>
                                <td style="padding: 10px; display: flex; gap: 10px;">
                                    <a href="?edit=<?= $task['id'] ?>" title="Tahrirlash" style="text-decoration: none; font-size: 1.2rem;">✏️</a>
                                    <form method="POST" onsubmit="return confirm('O\'chirilsinmi?');">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <button type="submit" name="delete_task" style="color: red; border: none; background: none; cursor: pointer; font-size: 1.2rem;">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
