<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// Add Schedule Item
if (isset($_POST['add_schedule'])) {
    $group_id = $_POST['group_id'];
    $days_selected = isset($_POST['days']) ? $_POST['days'] : [];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $subject_id = $_POST['subject_id'];
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    $room = !empty($_POST['room']) ? $_POST['room'] : null;

    if (!empty($days_selected)) {
        $stmt = $pdo->prepare("INSERT INTO schedule (group_id, day_of_week, start_time, end_time, subject_id, teacher_id, room) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($days_selected as $day) {
            $stmt->execute([$group_id, $day, $start, $end, $subject_id, $teacher_id, $room]);
        }
        $message = "Dars(lar) jadvalga qo'shildi!";
    } else {
        $message = "Xatolik: Hech bo'lmaganda bitta kunni tanlang!";
    }
}

// Update Schedule Item
if (isset($_POST['update_schedule'])) {
    $id = $_POST['schedule_id'];
    $group_id = $_POST['group_id'];
    $days_selected = isset($_POST['days']) ? $_POST['days'] : [];
    $day = !empty($days_selected) ? $days_selected[0] : '';
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $subject_id = $_POST['subject_id'];
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    $room = !empty($_POST['room']) ? $_POST['room'] : null;

    $stmt = $pdo->prepare("UPDATE schedule SET group_id=?, day_of_week=?, start_time=?, end_time=?, subject_id=?, teacher_id=?, room=? WHERE id=?");
    if ($stmt->execute([$group_id, $day, $start, $end, $subject_id, $teacher_id, $room, $id])) {
        $message = "Jadval yangilandi!";
        echo "<script>window.location.href='schedule.php';</script>";
        exit;
    }
}

// Delete Schedule Item
if (isset($_POST['delete_schedule'])) {
    $id = $_POST['schedule_id'];
    $pdo->prepare("DELETE FROM schedule WHERE id = ?")->execute([$id]);
    $message = "O'chirildi!";
}

// Fetch Data for Edit
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM schedule WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}

// Fetch Lists
$groups = $pdo->query("SELECT * FROM `groups` ORDER BY name")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
$teachers = $pdo->query("SELECT * FROM users WHERE role IN ('teacher', 'admin') AND status='active'")->fetchAll();

// Get Current Schedule List
$schedule_list = $pdo->query("
    SELECT sch.*, g.name as group_name, s.name as subject_name, u.name as teacher_name
    FROM schedule sch
    JOIN `groups` g ON sch.group_id = g.id
    JOIN subjects s ON sch.subject_id = s.id
    LEFT JOIN users u ON sch.teacher_id = u.id
    ORDER BY g.name, field(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time
")->fetchAll();

$days = [
    'Monday' => 'Dushanba', 'Tuesday' => 'Seshanba', 'Wednesday' => 'Chorshanba',
    'Thursday' => 'Payshanba', 'Friday' => 'Juma', 'Saturday' => 'Shanba'
];
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Dars Jadvali - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><?= $edit_item ? 'Jadvalni Tahrirlash' : 'Dars Jadvali Tuzish' ?></h3>
            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="schedule_id" value="<?= $edit_item['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Guruh</label>
                    <select name="group_id" class="form-control" required>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= ($edit_item && $edit_item['group_id'] == $g['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fan</label>
                    <select name="subject_id" class="form-control" required>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($edit_item && $edit_item['subject_id'] == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>O'qituvchi (Ixtiyoriy)</label>
                    <select name="teacher_id" class="form-control">
                        <option value="">Tanlanmagan</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($edit_item && $edit_item['teacher_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label>Xona</label>
                    <input type="text" name="room" class="form-control" placeholder="Masalan: 204-xona" value="<?= $edit_item && isset($edit_item['room']) ? htmlspecialchars($edit_item['room']) : '' ?>">
                </div>
                <div class="form-group" style="grid-column: span 3;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 700;">Hafta kunlarini tanlang</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color);">
                        <?php foreach ($days as $eng => $uz): ?>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 5px 10px; border-radius: 8px; transition: 0.2s;" onmouseover="this.style.background='rgba(67, 97, 238, 0.1)'" onmouseout="this.style.background='transparent'">
                                <input type="checkbox" name="days[]" value="<?= $eng ?>" 
                                    <?= ($edit_item && $edit_item['day_of_week'] == $eng) ? 'checked' : '' ?>
                                    style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                                <span style="font-size: 0.95rem;"><?= $uz ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Boshlanish vaqti</label>
                    <input type="time" name="start_time" id="start_time" class="form-control" value="<?= $edit_item ? $edit_item['start_time'] : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Tugash vaqti</label>
                    <input type="time" name="end_time" id="end_time" class="form-control" value="<?= $edit_item ? $edit_item['end_time'] : '' ?>" required>
                </div>
                
                <div style="grid-column: span 3; display: flex; gap: 10px;">
                    <button type="submit" name="<?= $edit_item ? 'update_schedule' : 'add_schedule' ?>" class="btn">
                        <?= $edit_item ? 'Yangilash' : 'Qo\'shish' ?>
                    </button>
                    <?php if ($edit_item): ?>
                        <a href="schedule.php" class="btn btn-secondary" style="background: #ccc; text-decoration: none; padding: 10px 20px; border-radius: 5px;">Bekor qilish</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Mavjud Jadval</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #ddd;">
                            <th style="padding: 10px;">Guruh</th>
                            <th style="padding: 10px;">Kun</th>
                            <th style="padding: 10px;">Vaqt</th>
                            <th style="padding: 10px;">Fan</th>
                            <th style="padding: 10px;">O'qituvchi</th>
                            <th style="padding: 10px;">Xona</th>
                            <th style="padding: 10px;">Amal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule_list as $item): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars($item['group_name']) ?></td>
                                <td style="padding: 10px;"><?= $days[$item['day_of_week']] ?></td>
                                <td style="padding: 10px;"><?= date('H:i', strtotime($item['start_time'])) ?> - <?= date('H:i', strtotime($item['end_time'])) ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars($item['subject_name']) ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars(isset($item['teacher_name']) ? $item['teacher_name'] : '-') ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars(isset($item['room']) ? $item['room'] : '-') ?></td>
                                <td style="padding: 10px; display: flex; gap: 10px;">
                                    <a href="?edit=<?= $item['id'] ?>" title="Tahrirlash" style="text-decoration: none; font-size: 1.2rem;">✏️</a>
                                    <form method="POST" onsubmit="return confirm('O\'chirilsinmi?');">
                                        <input type="hidden" name="schedule_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="delete_schedule" style="color: red; border: none; background: none; cursor: pointer; font-size: 1.2rem;">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('start_time').addEventListener('change', function() {
            const startVal = this.value;
            if (startVal) {
                const [hours, minutes] = startVal.split(':').map(Number);
                const date = new Date();
                date.setHours(hours);
                date.setMinutes(minutes + 80);
                
                const endHours = String(date.getHours()).padStart(2, '0');
                const endMinutes = String(date.getMinutes()).padStart(2, '0');
                document.getElementById('end_time').value = `${endHours}:${endMinutes}`;
            }
        });
    </script>
</body>
</html>
