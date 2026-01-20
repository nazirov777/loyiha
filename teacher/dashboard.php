<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

// Get teacher's assigned subjects and groups
$teacher_id = $_SESSION['user_id'];
$assignments = $pdo->prepare("
    SELECT ta.*, s.name as subject_name, g.name as group_name 
    FROM teacher_assignments ta
    JOIN subjects s ON ta.subject_id = s.id
    JOIN `groups` g ON ta.group_id = g.id
    WHERE ta.teacher_id = ?
");
$assignments->execute([$teacher_id]);
$my_assignments = $assignments->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>O'qituvchi - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2 style="margin-bottom: 2rem; font-weight: 800; color: var(--text-dark);">Mening Guruhlarim va Fanlarim</h2>
        
        <?php if (empty($my_assignments)): ?>
            <div class="card">
                <p>Sizga hozircha hech qanday guruh yoki fan biriktirilmagan.</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($my_assignments as $assign): ?>
                    <div class="stat-card">
                        <h4><?= htmlspecialchars($assign['group_name']) ?></h4>
                        <h3><?= htmlspecialchars($assign['subject_name']) ?></h3>
                        <div style="margin-top: auto; padding-top: 15px;">
                            <a href="grades.php?subject_id=<?= $assign['subject_id'] ?>&group_id=<?= $assign['group_id'] ?>" class="btn">Baholashni boshlash</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
