<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';
require '../config/notifications_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// Delete Grade
if (isset($_POST['delete_grade'])) {
    $id = $_POST['grade_id'];
    $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Baho o'chirildi!";
    }
}

// Update Grade
if (isset($_POST['update_grade'])) {
    $id = $_POST['grade_id'];
    $oraliq = intval($_POST['oraliq']);
    $mustaqil = intval($_POST['mustaqil']);
    $yakuniy = intval($_POST['yakuniy']);
    
    $stmt = $pdo->prepare("UPDATE grades SET oraliq=?, mustaqil=?, yakuniy=? WHERE id=?");
    if ($stmt->execute([$oraliq, $mustaqil, $yakuniy, $id])) {
        $message = "Baho yangilandi!";
        
        // Notify student
        $stmtS = $pdo->prepare("SELECT student_id FROM grades WHERE id = ?");
        $stmtS->execute([$id]);
        $s_id = $stmtS->fetchColumn();
        if ($s_id) {
            sendNotification($pdo, $s_id, "Baho Yangilandi!", "Admin tomonidan baholaringiz yangilandi.", "grades.php");
        }
    }
}

// Fetch Lists for Filters
$groups = $pdo->query("SELECT * FROM `groups` ORDER BY name")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();

// Filters
$group_filter = isset($_GET['group_id']) ? $_GET['group_id'] : '';
$subject_filter = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';

$where_clauses = [];
$params = [];

if ($group_filter) {
    $where_clauses[] = "ug.group_id = ?";
    $params[] = $group_filter;
}
if ($subject_filter) {
    $where_clauses[] = "g.subject_id = ?";
    $params[] = $subject_filter;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch Grades
$query = "
    SELECT g.*, u.name as student_name, s.name as subject_name, gr.name as group_name
    FROM grades g
    JOIN users u ON g.student_id = u.id
    JOIN subjects s ON g.subject_id = s.id
    JOIN user_groups ug ON u.id = ug.user_id
    JOIN `groups` gr ON ug.group_id = gr.id
    $where_sql
    ORDER BY gr.name, u.name
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$grades = $stmt->fetchAll();

// Edit Item Fetch
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("
        SELECT g.*, u.name as student_name, s.name as subject_name
        FROM grades g
        JOIN users u ON g.student_id = u.id
        JOIN subjects s ON g.subject_id = s.id
        WHERE g.id = ?
    ");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Baholar Boshqaruvi - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .grade-input {
            width: 70px;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-color);
            text-align: center;
        }
        .total-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .score-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2>Baholar Boshqaruvi</h2>
            <div style="display: flex; gap: 10px;">
                <select onchange="location.href='?group_id='+this.value+'&subject_id=<?= $subject_filter ?>'" class="form-control" style="width: auto;">
                    <option value="">Barcha guruhlar</option>
                    <?php foreach ($groups as $gr): ?>
                        <option value="<?= $gr['id'] ?>" <?= $group_filter == $gr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($gr['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select onchange="location.href='?subject_id='+this.value+'&group_id=<?= $group_filter ?>'" class="form-control" style="width: auto;">
                    <option value="">Barcha fanlar</option>
                    <?php foreach ($subjects as $sb): ?>
                        <option value="<?= $sb['id'] ?>" <?= $subject_filter == $sb['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sb['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($edit_item): ?>
            <div class="card" style="max-width: 500px; margin-bottom: 30px; border: 2px solid var(--primary-color);">
                <h3>Bahoni Tahrirlash</h3>
                <p style="color: var(--text-light); margin-bottom: 20px;">
                    <strong>Talaba:</strong> <?= htmlspecialchars($edit_item['student_name']) ?><br>
                    <strong>Fan:</strong> <?= htmlspecialchars($edit_item['subject_name']) ?>
                </p>
                <form method="POST">
                    <input type="hidden" name="grade_id" value="<?= $edit_item['id'] ?>">
                    <div class="form-group">
                        <label>Oraliq (max 30)</label>
                        <input type="number" name="oraliq" class="form-control" value="<?= $edit_item['oraliq'] ?>" min="0" max="30" required>
                    </div>
                    <div class="form-group">
                        <label>Mustaqil (max 20)</label>
                        <input type="number" name="mustaqil" class="form-control" value="<?= $edit_item['mustaqil'] ?>" min="0" max="20" required>
                    </div>
                    <div class="form-group">
                        <label>Yakuniy (max 50)</label>
                        <input type="number" name="yakuniy" class="form-control" value="<?= $edit_item['yakuniy'] ?>" min="0" max="50" required>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" name="update_grade" class="btn">Saqlash</button>
                        <a href="grades.php" class="btn btn-secondary" style="background: #ccc; text-decoration: none; padding: 10px 20px; border-radius: 5px; color: black;">Bekor qilish</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="card">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 15px;">Guruh</th>
                            <th style="padding: 15px;">Talaba</th>
                            <th style="padding: 15px;">Fan</th>
                            <th style="padding: 15px; text-align: center;">Oraliq</th>
                            <th style="padding: 15px; text-align: center;">Mustaqil</th>
                            <th style="padding: 15px; text-align: center;">Yakuniy</th>
                            <th style="padding: 15px; text-align: center;">Jami</th>
                            <th style="padding: 15px; text-align: right;">Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $g): 
                            $total = $g['oraliq'] + $g['mustaqil'] + $g['yakuniy'];
                            $color = '#64748b';
                            if ($total >= 86) $color = '#10b981';
                            elseif ($total >= 71) $color = '#3b82f6';
                            elseif ($total >= 56) $color = '#f59e0b';
                            else $color = '#ef4444';
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-color); transition: 0.2s;" onmouseover="this.style.background='rgba(0,0,0,0.02)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 15px;"><span class="badge" style="background: #e2e8f0; color: #475569;"><?= htmlspecialchars($g['group_name']) ?></span></td>
                                <td style="padding: 15px; font-weight: 600;"><?= htmlspecialchars($g['student_name']) ?></td>
                                <td style="padding: 15px; color: var(--text-light);"><?= htmlspecialchars($g['subject_name']) ?></td>
                                <td style="padding: 15px; text-align: center; font-weight: bold;"><?= $g['oraliq'] ?></td>
                                <td style="padding: 15px; text-align: center; font-weight: bold;"><?= $g['mustaqil'] ?></td>
                                <td style="padding: 15px; text-align: center; font-weight: bold;"><?= $g['yakuniy'] ?></td>
                                <td style="padding: 15px; text-align: center;">
                                    <span class="total-badge" style="background: <?= $color ?>20; color: <?= $color ?>; border: 1px solid <?= $color ?>;">
                                        <?= $total ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: right;">
                                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                        <a href="?edit=<?= $g['id'] ?>&group_id=<?= $group_filter ?>&subject_id=<?= $subject_filter ?>" title="Tahrirlash" style="text-decoration: none; font-size: 1.2rem;">✏️</a>
                                        <form method="POST" onsubmit="return confirm('Haqiqatdan ham ushbu bahoni o\'chirmoqchimisiz?');" style="display: inline;">
                                            <input type="hidden" name="grade_id" value="<?= $g['id'] ?>">
                                            <button type="submit" name="delete_grade" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 1.2rem;">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($grades)): ?>
                            <tr>
                                <td colspan="8" style="padding: 30px; text-align: center; color: var(--text-light);">Ma'lumotlar topilmadi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
