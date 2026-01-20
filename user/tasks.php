<?php
session_start();
require '../config/db.php';
require '../config/notifications_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle Submission
if (isset($_POST['submit_task'])) {
    $task_id = $_POST['task_id'];
    $comment = $_POST['comment'];
    
    $file_path = '';
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
        $upload_dir = '../uploads/files/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $original_name = $_FILES['submission_file']['name'];
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $filename = time() . '_sub_' . $safe_name . '.' . $extension;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $upload_dir . $filename)) {
            $file_path = 'uploads/files/' . $filename;
        }
    }

    // Check if already submitted
    $check = $pdo->prepare("SELECT id FROM submissions WHERE task_id = ? AND user_id = ?");
    $check->execute([$task_id, $user_id]);
    
    if ($check->fetch()) {
        $message = "Siz allaqachon javob yuborgansiz.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO submissions (task_id, user_id, file_path, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$task_id, $user_id, $file_path, $comment]);
        
        // Notify Admins and Teacher (Creator)
        $u_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Talaba';
        $t_stmt = $pdo->prepare("SELECT title, created_by FROM tasks WHERE id = ?");
        $t_stmt->execute([$task_id]);
        $task_info = $t_stmt->fetch();
        
        if ($task_info) {
            // Notify Creator
            sendNotification($pdo, $task_info['created_by'], "Vazifa topshirildi! ✅", "$u_name '$task_info[title]' vazifasini topshirdi.", "tasks.php");
            // Notify other admins if creator is teacher
            notifyAdmins($pdo, "Vazifa topshirildi! ✅", "$u_name '$task_info[title]' vazifasini topshirdi.", "tasks.php");
        }
        
        $message = "Javob yuborildi!";
    }
}

// Fetch Tasks assigned to user OR their groups
$query = "
    SELECT DISTINCT t.*, s.id as submission_id 
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN user_groups ug ON ug.group_id = ta.group_id
    LEFT JOIN submissions s ON t.id = s.task_id AND s.user_id = :uid
    WHERE ta.user_id = :uid OR ug.user_id = :uid
    ORDER BY t.deadline ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute(['uid' => $user_id]);
$tasks = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Vazifalarim - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="margin: 0; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.8rem;">📝</span> Mening Vazifalarim
            </h2>
            <div style="font-size: 0.9rem; color: var(--text-light); background: var(--bg-card); padding: 5px 15px; border-radius: 20px; border: 1px solid var(--border-color);">
                Jami: <strong><?= count($tasks) ?></strong> ta vazifa
            </div>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="card" style="text-align: center; padding: 50px 20px;">
                <div style="font-size: 4rem; margin-bottom: 20px;">🎉</div>
                <h3>Hozircha vazifalar yo'q!</h3>
                <p style="color: var(--text-light);">Barcha vazifalar bajarilgan yoki hali yangi vazifa yuklanmagan.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr; gap: 25px;">
                <?php foreach ($tasks as $task): 
                    // Count attempts
                    $stmtAtt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE task_id = ? AND user_id = ?");
                    $stmtAtt->execute([$task['id'], $user_id]);
                    $attempt_count = $stmtAtt->fetchColumn();
                    $max_attempts = $task['max_attempts'] ?: 1;

                    // Get submission details (latest)
                    $stmtSub = $pdo->prepare("SELECT * FROM submissions WHERE task_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
                    $stmtSub->execute([$task['id'], $user_id]);
                    $latest_sub = $stmtSub->fetch();

                    $is_submitted = !empty($latest_sub);
                    $is_graded = $is_submitted && !is_null($latest_sub['score']);
                    $is_overdue = strtotime($task['deadline']) < time() && !$is_submitted;
                    $can_submit = ($attempt_count < $max_attempts) && !$is_overdue;
                    
                    $status_label = "Kutilmoqda";
                    $status_color = "#3b82f6";
                    if ($is_graded) {
                        $status_label = "Baholandi";
                        $status_color = "#8b5cf6"; // Purple
                    } elseif ($is_submitted) {
                        $status_label = "Topshirildi";
                        $status_color = "#10b981";
                    } elseif ($is_overdue) {
                        $status_label = "Muddati o'tgan";
                        $status_color = "#ef4444";
                    }
                ?>
                    <div class="task-card" style="background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: var(--shadow); transition: 0.3s; position: relative; display: flex; flex-direction: column;">
                        <!-- Status Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: <?= $status_color ?>"></span>
                                    <span style="font-weight: 700; font-size: 0.85rem; color: <?= $status_color ?>; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <?= $status_label ?>
                                    </span>
                                </div>
                                <div style="color: var(--text-light); font-size: 0.85rem; background: var(--bg-body); padding: 2px 10px; border-radius: 12px; border: 1px solid var(--border-color);">
                                    Urinishlar: <strong><?= $attempt_count ?>/<?= $max_attempts ?></strong>
                                </div>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-light); display: flex; align-items: center; gap: 5px;">
                                📅 Muddat: <strong style="color: var(--text-color);"><?= date('d.m.Y H:i', strtotime($task['deadline'])) ?></strong>
                            </div>
                        </div>

                        <div style="padding: 25px; display: grid; grid-template-columns: 1fr auto; gap: 30px;">
                            <!-- Task Details -->
                            <div>
                                <h3 style="margin: 0 0 15px 0; font-size: 1.4rem; color: var(--primary-color);"><?= htmlspecialchars($task['title']) ?></h3>
                                <div style="background: var(--bg-body); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 20px; color: var(--text-color);">
                                    <?= nl2br(htmlspecialchars($task['description'])) ?>
                                </div>

                                <?php if($task['file_path']): ?>
                                    <div style="margin-bottom: 20px;">
                                        <?php if($task['type'] == 'image'): ?>
                                            <a href="../<?= $task['file_path'] ?>" target="_blank">
                                                <img src="../<?= $task['file_path'] ?>" style="max-width: 300px; border-radius: 12px; border: 4px solid var(--bg-card); box-shadow: var(--shadow); transition: 0.3s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                                            </a>
                                        <?php else: ?>
                                            <a href="../<?= $task['file_path'] ?>" class="btn btn-secondary" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; padding: 8px 15px;">
                                                📂 Vazifa faylini yuklab olish
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($is_graded): ?>
                                    <div style="background: rgba(139, 92, 246, 0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(139, 92, 246, 0.2); margin-top: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                            <h4 style="margin: 0; color: #8b5cf6;">O'qituvchi bahosi</h4>
                                            <span style="font-size: 1.5rem; font-weight: 900; color: #8b5cf6;"><?= $latest_sub['score'] ?> ball</span>
                                        </div>
                                        <?php if ($latest_sub['teacher_comment']): ?>
                                            <p style="font-size: 0.9rem; color: var(--text-color); margin: 0; font-style: italic;">
                                                "<?= nl2br(htmlspecialchars($latest_sub['teacher_comment'])) ?>"
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Submission Form Section -->
                            <div style="width: 350px; border-left: 1px solid var(--border-color); padding-left: 30px;">
                                <?php if ($is_submitted && !$can_submit): ?>
                                    <div style="text-align: center; padding: 40px 20px; background: rgba(16, 185, 129, 0.05); border-radius: 16px; border: 1px dashed #10b981;">
                                        <div style="font-size: 3rem; margin-bottom: 10px;">✅</div>
                                        <h4 style="color: #10b981; margin-bottom: 5px;">Topshirildi</h4>
                                        <p style="font-size: 0.85rem; color: var(--text-light);">Urinishlar tugadi yoki topshiriq allaqachon qabul qilingan.</p>
                                    </div>
                                <?php elseif (!$can_submit && $is_overdue): ?>
                                    <div style="text-align: center; padding: 40px 20px; background: rgba(239, 68, 68, 0.05); border-radius: 16px; border: 1px dashed #ef4444;">
                                        <div style="font-size: 3rem; margin-bottom: 10px;">⏰</div>
                                        <h4 style="color: #ef4444; margin-bottom: 5px;">Muddati o'tgan</h4>
                                        <p style="font-size: 0.85rem; color: var(--text-light);">Ushbu vazifani topshirish muddati tugadi.</p>
                                    </div>
                                <?php else: ?>
                                    <h4 style="margin: 0 0 15px 0;">
                                        <?= $is_submitted ? 'Qayta yuborish' : 'Javob yuborish' ?>
                                    </h4>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <div class="form-group" style="margin-bottom: 15px;">
                                            <label style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 5px; display: block;">Javob izohi</label>
                                            <textarea name="comment" class="form-control" style="height: 100px; resize: none;" placeholder="Sizning javobingiz..." required></textarea>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 20px;">
                                            <input type="file" name="submission_file" class="form-control" style="font-size: 0.85rem; padding: 8px;">
                                        </div>
                                        <button type="submit" name="submit_task" class="btn" style="width: 100%;">
                                            🚀 Yuborish (<?= $attempt_count + 1 ?>-urinish)
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
