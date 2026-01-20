<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';
require '../config/notifications_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = '';

// Handle Grading
if (isset($_POST['grade_submission'])) {
    $submission_id = $_POST['submission_id'];
    $score = intval($_POST['score']);
    $comment = $_POST['teacher_comment'];
    
    // Update Submission
    $stmt = $pdo->prepare("UPDATE submissions SET score = ?, teacher_comment = ? WHERE id = ?");
    if ($stmt->execute([$score, $comment, $submission_id])) {
        
        // 1. Get submission details to update central grades table
        $stmtS = $pdo->prepare("
            SELECT s.user_id as student_id, t.subject_id, t.title as task_title
            FROM submissions s
            JOIN tasks t ON s.task_id = t.id
            WHERE s.id = ?
        ");
        $stmtS->execute([$submission_id]);
        $sub = $stmtS->fetch();
        
        if ($sub) {
            // 2. Notify Student
            sendNotification($pdo, $sub['student_id'], "Vazifa Baxolandi! ⭐", "'$sub[task_title]' vazifangiz baxolandi. Ball: $score", "tasks.php");
        }
        
        $message = "Baho muvaffaqiyatli qo'yildi!";
    }
}

// Filters
$group_filter = isset($_GET['group_id']) ? intval($_GET['group_id']) : '';

// Fetch Groups for filter (only groups that have students with submissions for this teacher's tasks)
$groups_query = "
    SELECT DISTINCT g.id, g.name 
    FROM `groups` g
    JOIN user_groups ug ON g.id = ug.group_id
    JOIN submissions s ON ug.user_id = s.user_id
    JOIN tasks t ON s.task_id = t.id
    WHERE t.created_by = ?
    ORDER BY g.name
";
$stmtGroups = $pdo->prepare($groups_query);
$stmtGroups->execute([$teacher_id]);
$teacher_groups = $stmtGroups->fetchAll();

$where_clauses = ["t.created_by = ?"];
$params = [$teacher_id];

if ($group_filter) {
    $where_clauses[] = "g.id = ?";
    $params[] = $group_filter;
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch Submissions
$query = "
    SELECT s.*, u.name as student_name, t.title as task_title, t.max_attempts, sub.name as subject_name, g.name as group_name
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    JOIN tasks t ON s.task_id = t.id
    LEFT JOIN subjects sub ON t.subject_id = sub.id
    LEFT JOIN user_groups ug ON u.id = ug.user_id
    LEFT JOIN `groups` g ON ug.group_id = g.id
    WHERE $where_sql
    ORDER BY s.created_at DESC
";
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll();
    $db_error = false;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Column not found') !== false || strpos($e->getMessage(), '1054') !== false) {
        $db_error = true;
        $submissions = [];
    } else {
        throw $e;
    }
}

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>O'quvchi Topshiriqlari - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
            <h2 style="display: flex; align-items: center; gap: 15px; margin: 0;">
                <span style="font-size: 2rem;">📥</span> O'quvchilar Topshiriqlari
            </h2>
            
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <!-- Filter Form -->
                <form method="GET" style="display: flex; align-items: center; gap: 10px; background: var(--bg-card); padding: 5px 15px; border-radius: 12px; border: 1px solid var(--border-color);">
                    <label style="font-size: 0.85rem; color: var(--text-light); white-space: nowrap;">Guruh bo'yicha:</label>
                    <select name="group_id" class="form-control" style="width: auto; padding: 5px 10px; height: 35px;" onchange="this.form.submit()">
                        <option value="">Barchasi</option>
                        <?php foreach($teacher_groups as $tg): ?>
                            <option value="<?= $tg['id'] ?>" <?= $group_filter == $tg['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tg['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div style="background: var(--bg-card); padding: 8px 20px; border-radius: 30px; border: 1px solid var(--border-color); font-size: 0.9rem;">
                    Natija: <strong><?= count($submissions) ?></strong> ta
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            <?php foreach ($submissions as $s): 
                $is_graded = !is_null($s['score']);
            ?>
                <div class="card submission-card" style="border: 1px solid <?= $is_graded ? 'var(--border-color)' : 'var(--primary-color)' ?>; position: relative; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); <?= !$is_graded ? 'box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15); background: linear-gradient(to bottom, rgba(67, 97, 238, 0.02), var(--bg-card));' : '' ?> overflow: hidden;">
                    <?php if (!$is_graded): ?>
                        <div style="position: absolute; top: 20px; right: 20px; background: linear-gradient(135deg, var(--primary-color), #7209b7); color: white; padding: 6px 16px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3); z-index: 10;">✨ Yangi</div>
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
                        <!-- Student & Task Info -->
                        <div>
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color), #7209b7); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);">
                                    <?= substr($s['student_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 5px 0; font-size: 1.15rem; font-weight: 700;"><?= htmlspecialchars($s['student_name']) ?></h4>
                                    <span style="font-size: 0.85rem; color: var(--text-light); display: flex; align-items: center; gap: 8px;">📚 <?= htmlspecialchars($s['group_name']) ?> <span style="color: var(--border-color);">•</span> <?= htmlspecialchars($s['subject_name']) ?></span>
                                </div>
                            </div>

                            <div style="background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(114, 9, 183, 0.03)); padding: 18px; border-radius: 16px; margin-bottom: 20px; border: 1px solid rgba(67, 97, 238, 0.15); position: relative; overflow: hidden;">
                                <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: radial-gradient(circle, rgba(67, 97, 238, 0.1), transparent); border-radius: 50%;"></div>
                                <strong style="display: flex; align-items: center; gap: 10px; color: var(--primary-color); margin-bottom: 10px; font-size: 1rem; position: relative; z-index: 1;">
                                    📝 Vazifa: <?= htmlspecialchars($s['task_title']) ?>
                                    <?php if (!$s['subject_name']): ?>
                                        <span class="badge badge-danger" style="font-size: 0.65rem;">⚠️ Bog'lanmagan</span>
                                    <?php endif; ?>
                                </strong>
                                <p style="font-size: 0.95rem; margin-bottom: 0; line-height: 1.6; color: var(--text-dark); position: relative; z-index: 1;"><?= nl2br(htmlspecialchars($s['comment'])) ?></p>
                            </div>

                            <?php if ($s['file_path']): ?>
                                <a href="../<?= $s['file_path'] ?>" class="btn btn-secondary" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; padding: 8px 15px;">
                                    📂 Biriktirilgan faylni ko'rish
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.85rem; color: var(--text-light); italic">Fayl biriktirilmagan</span>
                            <?php endif; ?>
                            
                            <div style="margin-top: 20px; font-size: 0.8rem; color: var(--text-light); display: flex; align-items: center; gap: 8px; background: rgba(100, 116, 139, 0.05); padding: 10px 15px; border-radius: 10px;">
                                <span style="font-size: 1rem;">⏱️</span>
                                <span>Topshirildi: <strong><?= date('d.m.Y H:i', strtotime($s['created_at'])) ?></strong></span>
                            </div>
                        </div>

                        <!-- Grading Section -->
                        <div style="border-left: 1px solid var(--border-color); padding-left: 40px;">
                            <?php if ($is_graded): ?>
                                <div style="text-align: center; padding: 20px; background: rgba(16, 185, 129, 0.05); border-radius: 16px; border: 1px dashed #10b981;">
                                    <div style="font-size: 1.5rem; font-weight: 800; color: #10b981; margin-bottom: 5px;">Baho: <?= $s['score'] ?></div>
                                    <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 10px;">Izoh: <?= htmlspecialchars($s['teacher_comment']) ?></p>
                                    <button onclick="this.parentElement.nextElementSibling.style.display='block'; this.parentElement.style.display='none';" class="btn" style="padding: 5px 15px; font-size: 0.8rem; background: var(--text-light);">Qayta baholash</button>
                                </div>
                            <?php endif; ?>

                            <div style="<?= $is_graded ? 'display: none;' : '' ?>">
                                <h4 style="margin: 0 0 15px 0;">Baholash (Mustaqil ta'lim)</h4>
                                <form method="POST">
                                    <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="font-size: 0.85rem; color: var(--text-light);">Ball (Max 30)</label>
                                        <input type="number" name="score" class="form-control" value="<?= isset($s['score']) ? $s['score'] : '' ?>" min="0" max="30" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="font-size: 0.85rem; color: var(--text-light);">O'qituvchi izohi</label>
                                        <textarea name="teacher_comment" class="form-control" style="height: 80px; resize: none;" placeholder="Talabaga tavsiyalar..."><?= htmlspecialchars(isset($s['teacher_comment']) ? $s['teacher_comment'] : '') ?></textarea>
                                    </div>
                                    <button type="submit" name="grade_submission" class="btn" style="width: 100%;">⭐ Baholash</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($db_error): ?>
                <div class="card" style="text-align: center; padding: 40px; border: 2px dashed #ef4444; background: rgba(239, 68, 68, 0.05);">
                    <div style="font-size: 3rem; margin-bottom: 15px;">⚠️</div>
                    <h3 style="color: #ef4444;">Ma'lumotlar bazasida xatolik!</h3>
                    <p>Tizim yangilanganligi sababli bazaga o'zgartirish kiritish kerak.</p>
                    <a href="../fix_database.php" class="btn" style="background: #ef4444; border-color: #ef4444; margin-top: 15px;">Bazani tuzatish (Repair Database)</a>
                </div>
            <?php elseif (empty($submissions)): ?>
                <div class="card" style="text-align: center; padding: 50px;">
                    <p style="color: var(--text-light);">Hozircha hech qanday topshiriq kelib tushmagan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
