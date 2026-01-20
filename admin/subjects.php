<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// Add Subject
if (isset($_POST['add_subject'])) {
    $name = trim($_POST['subject_name']);
    if (!empty($name)) {
        $pdo->prepare("INSERT INTO subjects (name) VALUES (?)")->execute([$name]);
        $message = "Fan qo'shildi!";
    }
}

// Assign Teacher
if (isset($_POST['assign_teacher'])) {
    $teacher_id = $_POST['teacher_id'];
    $subject_ids = $_POST['subject_ids']; // Array
    $group_ids = $_POST['group_ids']; // Array
    
    $assigned_count = 0;
    foreach ($subject_ids as $subject_id) {
        foreach ($group_ids as $group_id) {
            // Check duplication
            $check = $pdo->prepare("SELECT id FROM teacher_assignments WHERE teacher_id=? AND subject_id=? AND group_id=?");
            $check->execute([$teacher_id, $subject_id, $group_id]);
            if (!$check->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, group_id) VALUES (?, ?, ?)");
                $stmt->execute([$teacher_id, $subject_id, $group_id]);
                $assigned_count++;
                
                // Notify Teacher
                require_once '../config/notifications_helper.php';
                $group_stmt = $pdo->prepare("SELECT name FROM `groups` WHERE id = ?");
                $group_stmt->execute([$group_id]);
                $g_name = $group_stmt->fetchColumn();
                
                $subj_stmt = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
                $subj_stmt->execute([$subject_id]);
                $s_name = $subj_stmt->fetchColumn();
                
                sendNotification($pdo, $teacher_id, "Yangi Biriktirish!", "Sizga yangi fan ($s_name) va guruh ($g_name) biriktirildi.", "grades.php");
            }
        }
    }
    $message = "O'qituvchiga $assigned_count ta yangi biriktirish amalga oshirildi!";
}

// Delete Assignment
if (isset($_POST['delete_assignment'])) {
    $id = $_POST['assignment_id'];
    $pdo->prepare("DELETE FROM teacher_assignments WHERE id = ?")->execute([$id]);
    $message = "Biriktirish o'chirildi!";
}

// Data Fetching
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
$teachers = $pdo->query("SELECT * FROM users WHERE role IN ('teacher', 'admin') AND status='active'")->fetchAll();
$groups = $pdo->query("SELECT * FROM `groups` ORDER BY name")->fetchAll();

$assignments = $pdo->query("
    SELECT ta.id, u.name as teacher_name, s.name as subject_name, g.name as group_name
    FROM teacher_assignments ta
    JOIN users u ON ta.teacher_id = u.id
    JOIN subjects s ON ta.subject_id = s.id
    JOIN `groups` g ON ta.group_id = g.id
    ORDER BY g.name, s.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Fanlar va O'qituvchilar - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display: flex; gap: 20px;">
            <!-- Add Subject -->
            <div class="card" style="flex: 1;">
                <h3>Yangi Fan Qo'shish</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Fan nomi</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <button type="submit" name="add_subject" class="btn">Qo'shish</button>
                </form>
                
                <h4 style="margin-top: 20px;">Mavjud Fanlar</h4>
                <ul style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($subjects as $s): ?>
                        <li><?= htmlspecialchars($s['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Assign Teacher -->
            <div class="card" style="flex: 2;">
                <h3>Fan va Guruhlarni biriktirish</h3>
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>O'qituvchini tanlang</label>
                        <select name="teacher_id" class="form-control" required>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; display: block; margin-bottom: 10px;">Fanlarni tanlang:</label>
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                                <?php foreach ($subjects as $s): ?>
                                    <label style="display: block; margin-bottom: 5px; cursor: pointer;">
                                        <input type="checkbox" name="subject_ids[]" value="<?= $s['id'] ?>"> <?= htmlspecialchars($s['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <label style="font-weight: bold; display: block; margin-bottom: 10px;">Guruhlarni tanlang:</label>
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                                <?php foreach ($groups as $g): ?>
                                    <label style="display: block; margin-bottom: 5px; cursor: pointer;">
                                        <input type="checkbox" name="group_ids[]" value="<?= $g['id'] ?>"> <?= htmlspecialchars($g['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="assign_teacher" class="btn">Biriktirishni Saqlash</button>
                </form>

                <h4 style="margin-top: 20px;">Biriktirilganlar</h4>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #ddd;">
                                <th style="padding: 10px;">Guruh</th>
                                <th style="padding: 10px;">Fan</th>
                                <th style="padding: 10px;">O'qituvchi</th>
                                <th style="padding: 10px;">Amal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $a): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars($a['group_name']) ?></td>
                                    <td style="padding: 10px;"><?= htmlspecialchars($a['subject_name']) ?></td>
                                    <td style="padding: 10px;"><?= htmlspecialchars($a['teacher_name']) ?></td>
                                    <td style="padding: 10px;">
                                        <form method="POST" onsubmit="return confirm('O\'chirilsinmi?');">
                                            <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                            <button type="submit" name="delete_assignment" style="color: red; border: none; background: none; cursor: pointer;">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
