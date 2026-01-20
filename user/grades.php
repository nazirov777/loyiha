<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Grades
$sql = "
    SELECT s.name as subject, g.oraliq, g.mustaqil, g.yakuniy, 
           (IFNULL(g.oraliq,0) + IFNULL(g.mustaqil,0) + IFNULL(g.yakuniy,0)) as total,
           g.created_at
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    WHERE g.student_id = ?
    ORDER BY g.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$grades = $stmt->fetchAll();

// Calculate averages or totals if needed
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>O'zlashtirish - Talaba</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="card">
            <h3>Mening Baholarim (O'zlashtirish)</h3>
            
            <?php if (empty($grades)): ?>
                <p>Hozircha baholar yo'q.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #ddd;">
                                <th style="padding: 10px;">Fan</th>
                                <th style="padding: 10px;">Oraliq (20)</th>
                                <th style="padding: 10px;">Mustaqil (30)</th>
                                <th style="padding: 10px;">Yakuniy (50)</th>
                                <th style="padding: 10px;">Jami (100)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars($grade['subject']) ?></td>
                                    <td style="padding: 10px;"><?= $grade['oraliq'] ?></td>
                                    <td style="padding: 10px;"><?= $grade['mustaqil'] ?></td>
                                    <td style="padding: 10px;"><?= $grade['yakuniy'] ?></td>
                                    <td style="padding: 10px;">
                                        <span style="padding: 5px 10px; border-radius: 5px; background: <?= $grade['total']>=60 ? 'var(--success)' : 'var(--danger)' ?>; color: white; font-weight: bold;">
                                            <?= $grade['total'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
