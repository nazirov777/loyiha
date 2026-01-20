<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Get Schedule
// We assume admin creates schedule in 'schedule' table
// teacher needs to see where they have to be
$sql = "
    SELECT sch.*, g.name as group_name, s.name as subject_name 
    FROM schedule sch
    JOIN `groups` g ON sch.group_id = g.id
    JOIN subjects s ON sch.subject_id = s.id
    WHERE sch.teacher_id = ?
    ORDER BY field(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$teacher_id]);
$schedule_items = $stmt->fetchAll();

// Organize by Day
$week_schedule = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
foreach ($days as $day) {
    // Localize day names if needed in future
    $week_schedule[$day] = [];
}

foreach ($schedule_items as $item) {
    $week_schedule[$item['day_of_week']][] = $item;
}

// Translations for Days
$day_names = [
    'Monday' => 'Dushanba', 'Tuesday' => 'Seshanba', 'Wednesday' => 'Chorshanba',
    'Thursday' => 'Payshanba', 'Friday' => 'Juma', 'Saturday' => 'Shanba'
];
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Dars Jadvali - O'qituvchi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="card">
            <h3>Mening Dars Jadvalim</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($days as $day): ?>
                    <div class="stat-card" style="align-items: flex-start;">
                        <h4 style="color: var(--primary-color); font-weight: bold; margin-bottom: 15px;">
                            <?= $day_names[$day] ?>
                        </h4>
                        
                        <?php if (empty($week_schedule[$day])): ?>
                            <p style="color: #ccc; font-style: italic;">Dars yo'q</p>
                        <?php else: ?>
                            <ul style="width: 100%;">
                                <?php foreach ($week_schedule[$day] as $lesson): ?>
                                    <li style="display: block; margin-bottom: 10px; border-bottom: 1px dashed #eee; padding-bottom: 5px;">
                                        <div style="font-weight: bold;"><?= date('H:i', strtotime($lesson['start_time'])) ?> - <?= date('H:i', strtotime($lesson['end_time'])) ?></div>
                                        <div>
                                            Fan: <?= htmlspecialchars($lesson['subject_name']) ?>
                                            <?= isset($lesson['room']) && $lesson['room'] ? '<span style="color: var(--primary-color); font-size: 0.8rem; margin-left: 5px;">(Xona: '.htmlspecialchars($lesson['room']).')</span>' : '' ?>
                                        </div>
                                        <div style="color: var(--text-light); font-size: 0.9rem;">Guruh: <?= htmlspecialchars($lesson['group_name']) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
