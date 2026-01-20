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
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
$message = '';

// Handle Bulk Sync
if (isset($_POST['sync_now']) && $subject_id && $group_id) {
    // 1. Get all students in the group
    $stmtUsers = $pdo->prepare("
        SELECT u.id 
        FROM users u 
        JOIN user_groups ug ON u.id = ug.user_id 
        WHERE ug.group_id = ? AND u.role = 'student'
    ");
    $stmtUsers->execute([$group_id]);
    $student_ids = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

    $count = 0;
    foreach ($student_ids as $sid) {
        // 2. Find max score for this student in this subject across all tasks
        $stmtSync = $pdo->prepare("
            SELECT MAX(s.score) as max_score 
            FROM submissions s
            JOIN tasks t ON s.task_id = t.id
            WHERE s.user_id = ? AND t.subject_id = ?
        ");
        $stmtSync->execute([$sid, $subject_id]);
        $sync_data = $stmtSync->fetch();
        $max_score = ($sync_data && !is_null($sync_data['max_score'])) ? intval($sync_data['max_score']) : 0;

        // 3. Update or Insert into Grades Table
        $check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND subject_id = ?");
        $check->execute([$sid, $subject_id]);
        $existing = $check->fetch();

        if ($existing) {
            $pdo->prepare("UPDATE grades SET mustaqil = ? WHERE id = ?")->execute([$max_score, $existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO grades (student_id, subject_id, mustaqil) VALUES (?, ?, ?)")
                ->execute([$sid, $subject_id, $max_score]);
        }
        $count++;
    }
    $message = "$count ta o'quvchining baholari muvaffaqiyatli ko'chirildi!";
}

// Handle Reset to 0
if (isset($_POST['reset_zero']) && $subject_id && $group_id) {
    $pdo->prepare("
        UPDATE grades g
        JOIN user_groups ug ON g.student_id = ug.user_id
        SET g.mustaqil = 0
        WHERE g.subject_id = ? AND ug.group_id = ?
    ")->execute([$subject_id, $group_id]);
    $message = "Barcha o'quvchilarning 'Mustaqil ta'lim' baholari 0 qilindi!";
}

// Handle Orphan Task Linking
if (isset($_POST['fix_orphans']) && $subject_id) {
    $stmtFix = $pdo->prepare("UPDATE tasks SET subject_id = ? WHERE subject_id IS NULL AND created_by = ?");
    $stmtFix->execute([$subject_id, $teacher_id]);
    $message = "Bog'lanmagan vazifalar ushbu fanga muvaffaqiyatli biriktirildi!";
}

// Handle Individual Sync
if (isset($_POST['sync_individual']) && $subject_id) {
    $sid = intval($_POST['student_id']);
    
    // 1. Find max score for this student in this subject across all tasks
    $stmtSync = $pdo->prepare("
        SELECT MAX(s.score) as max_score 
        FROM submissions s
        JOIN tasks t ON s.task_id = t.id
        WHERE s.user_id = ? AND t.subject_id = ?
    ");
    $stmtSync->execute([$sid, $subject_id]);
    $sync_data = $stmtSync->fetch();
    $max_score = ($sync_data && !is_null($sync_data['max_score'])) ? intval($sync_data['max_score']) : 0;

    // 2. Update or Insert into Grades Table
    $check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND subject_id = ?");
    $check->execute([$sid, $subject_id]);
    $existing = $check->fetch();

    if ($existing) {
        $pdo->prepare("UPDATE grades SET mustaqil = ? WHERE id = ?")->execute([$max_score, $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO grades (student_id, subject_id, mustaqil) VALUES (?, ?, ?)")
            ->execute([$sid, $subject_id, $max_score]);
    }
    $message = "Baho muvaffaqiyatli ko'chirildi!";
}

// Fetch Selection Data
$my_assignments = $pdo->prepare("
    SELECT ta.subject_id, ta.group_id, s.name as subject_name, g.name as group_name
    FROM teacher_assignments ta
    JOIN subjects s ON ta.subject_id = s.id
    JOIN `groups` g ON ta.group_id = g.id
    WHERE ta.teacher_id = ?
");
$my_assignments->execute([$teacher_id]);
$assignments = $my_assignments->fetchAll();

// If subject & group selected, fetch preview data
$preview_students = [];
$meta = null;
if ($subject_id && $group_id) {
    $stmtPreview = $pdo->prepare("
        SELECT u.id, u.name, 
               (SELECT MAX(s.score) FROM submissions s JOIN tasks t ON s.task_id = t.id WHERE s.user_id = u.id AND t.subject_id = ?) as task_score,
               g.mustaqil as current_grade
        FROM users u
        JOIN user_groups ug ON u.id = ug.user_id
        LEFT JOIN grades g ON u.id = g.student_id AND g.subject_id = ?
        WHERE ug.group_id = ? AND u.role = 'student'
        ORDER BY u.name
    ");
    $stmtPreview->execute([$subject_id, $subject_id, $group_id]);
    $preview_students = $stmtPreview->fetchAll();

    // Meta info
    $stmtMeta = $pdo->prepare("SELECT s.name as subject, g.name as group_name FROM subjects s, `groups` g WHERE s.id=? AND g.id=?");
    $stmtMeta->execute([$subject_id, $group_id]);
    $meta = $stmtMeta->fetch();

    // Check for orphaned tasks for this teacher
    $stmtOrphans = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE subject_id IS NULL AND created_by = ?");
    $stmtOrphans->execute([$teacher_id]);
    $orphan_count = $stmtOrphans->fetchColumn();
}

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Mustaqil Baholarni Ko'chirish - O'qituvchi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 30px;">
            <h2 style="font-weight: 800; color: var(--text-dark);">🔄 Mustaqil Ish Baholarini Ko'chirish</h2>
            <p style="color: var(--text-light);">Vazifalardan olingan ballarni asosiy baholash jurnaliga ("Mustaqil ta'lim" ustuniga) o'tkazish.</p>
        </div>

        <?php if (!$subject_id || !$group_id): ?>
            <div class="grid">
                <?php foreach ($assignments as $a): ?>
                    <div class="card stat-card" style="cursor: pointer; padding: 20px; transition: 0.3s;" onclick="location.href='?subject_id=<?= $a['subject_id'] ?>&group_id=<?= $a['group_id'] ?>'">
                        <span class="badge badge-primary"><?= htmlspecialchars($a['group_name']) ?></span>
                        <h3 style="margin: 10px 0;"><?= htmlspecialchars($a['subject_name']) ?></h3>
                        <div style="color: var(--primary-color); font-weight: bold;">Tanlash →</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div>
                        <h3 style="margin: 0;"><?= htmlspecialchars($meta['subject']) ?> | <?= htmlspecialchars($meta['group_name']) ?></h3>
                        <small style="color: var(--text-light);">O'quvchilar ro'yxati va ularning ballari</small>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="mustaqil_baholash.php" class="btn btn-secondary" style="background: #64748b; color: white;">🔄 Boshqa guruh</a>
                        <form method="POST" onsubmit="return confirm('DIQQAT! Barcha o\'quvchilar mustaqil baholarini 0 qilmoqchimisiz?');">
                            <button type="submit" name="reset_zero" class="btn btn-secondary" style="background: #ef4444; color: white;">🗑️ Hammasini 0 qilish</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Haqiqatdan ham vazifalardagi baholarni asosiy jurnalga ko\'chirmoqchimisiz? Vazifa topshirmaganlar uchun 0 baho qo\'yiladi.');">
                            <button type="submit" name="sync_now" class="btn">🚀 Ballarni Ko'chirish</button>
                        </form>
                    </div>
                </div>

                <?php if ($orphan_count > 0): ?>
                    <div style="background: #fff7ed; border: 1px solid #fbbf24; border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="color: #92400e;">
                            <strong style="display: block; margin-bottom: 5px;">⚠️ Diqqat! Bog'lanmagan vazifalar aniqlandi</strong>
                            <span>Sizda <b><?= $orphan_count ?></b> ta vazifa hech qaysi fanga bog'lanmagan. Shu sababli ularning baholari ko'chirilmayapti.</span>
                        </div>
                        <form method="POST">
                            <button type="submit" name="fix_orphans" class="btn btn-secondary" style="background: #fbbf24; color: #92400e; border: none;">Ushbu fanga bog'lash</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr style="text-align: left; background: var(--bg-body);">
                                <th style="padding: 12px;">O'quvchi</th>
                                <th style="padding: 12px; text-align: center;">Vazifadagi Ball (Best)</th>
                                <th style="padding: 12px; text-align: center;">Jurnaldagi Hozirgi Baho</th>
                                <th style="padding: 12px; text-align: center;">Holat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_students as $ps): 
                                $is_different = (intval($ps['task_score']) != intval($ps['current_grade']));
                            ?>
                                <tr>
                                    <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($ps['name']) ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <b style="color: var(--primary-color); font-size: 1.1rem;"><?= intval($ps['task_score']) ?></b>
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-light);">
                                        <?= is_null($ps['current_grade']) ? '<i>Baho yo\'q</i>' : $ps['current_grade'] ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php if ($is_different): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="student_id" value="<?= $ps['id'] ?>">
                                                <button type="submit" name="sync_individual" class="btn" style="padding: 5px 15px; font-size: 0.8rem; background: var(--primary-color);">📥 Ko'chirish</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #10b981; font-size: 0.85rem;">✅ Mos keladi</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
