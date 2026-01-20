<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Find user's group
$group_stmt = $pdo->prepare("SELECT group_id FROM user_groups WHERE user_id = ?");
$group_stmt->execute([$user_id]);
$user_group = $group_stmt->fetch();

$week_schedule = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
foreach ($days as $day) {
    $week_schedule[$day] = [];
}

if ($user_group) {
    $group_id = $user_group['group_id'];
    
    $sql = "
        SELECT sch.*, u.name as teacher_name, s.name as subject_name 
        FROM schedule sch
        LEFT JOIN users u ON sch.teacher_id = u.id
        JOIN subjects s ON sch.subject_id = s.id
        WHERE sch.group_id = ?
        ORDER BY field(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$group_id]);
    $schedule_items = $stmt->fetchAll();

    foreach ($schedule_items as $item) {
        $week_schedule[$item['day_of_week']][] = $item;
    }
}

$day_names = [
    'Monday' => 'Dushanba', 'Tuesday' => 'Seshanba', 'Wednesday' => 'Chorshanba',
    'Thursday' => 'Payshanba', 'Friday' => 'Juma', 'Saturday' => 'Shanba'
];
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Dars Jadvali - Talaba</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="card">
            <h3>Dars Jadvali</h3>
            
            <?php if (!$user_group): ?>
                <div class="alert alert-danger">Siz hali hech qaysi guruhga biriktirilmagansiz.</div>
            <?php else: ?>
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
                                            <div style="color: var(--text-dark); font-weight: 500;">
                                                <?= htmlspecialchars($lesson['subject_name']) ?> 
                                                <?= isset($lesson['room']) && $lesson['room'] ? '<span style="color: var(--primary-color); font-size: 0.8rem; margin-left: 5px;">(Xona: '.htmlspecialchars($lesson['room']).')</span>' : '' ?>
                                            </div>
                                                O'qituvchi: <?= htmlspecialchars(isset($lesson['teacher_name']) ? $lesson['teacher_name'] : 'Tayinlanmagan') ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
