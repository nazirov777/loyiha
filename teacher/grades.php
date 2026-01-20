<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';
require '../config/notifications_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;
$group_id = isset($_GET['group_id']) ? $_GET['group_id'] : null;
$message = '';

if ($subject_id && $group_id) {
    // Save Grades
    if (isset($_POST['save_grades'])) {
        foreach ($_POST['grades'] as $student_id => $grade_data) {
            $oraliq = intval($grade_data['oraliq']);
            $mustaqil = intval($grade_data['mustaqil']);
            $yakuniy = intval($grade_data['yakuniy']);
            
            // Check if exists
            $check = $pdo->prepare("SELECT id FROM grades WHERE student_id=? AND subject_id=?");
            $check->execute([$student_id, $subject_id]);
            $existing = $check->fetch();
            
            if ($existing) {
                // Update
                $pdo->prepare("UPDATE grades SET oraliq=?, mustaqil=?, yakuniy=? WHERE id=?")
                    ->execute([$oraliq, $mustaqil, $yakuniy, $existing['id']]);
            } else {
                // Insert
                $pdo->prepare("INSERT INTO grades (student_id, subject_id, oraliq, mustaqil, yakuniy) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$student_id, $subject_id, $oraliq, $mustaqil, $yakuniy]);
            }

            // Send Notification for both update and insert
            sendNotification($pdo, $student_id, "Yangi Baho!", "Sizga yangi baho qo'yildi yoki yangilandi. Tekshirib ko'ring.", "grades.php");
        }
        
        // Notify Admins of Grading
        $t_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'O\'qituvchi';
        $subj_name = isset($meta['subject']) ? $meta['subject'] : 'fan';
        notifyAdmins($pdo, "Baholash Jarayoni ✍️", "$t_name tomonidan $subj_name fanidan baholar qo'yildi.", "subjects.php");
        
        $message = "Baholar saqlandi!";
    }


    // Get Students in Group
    $students = $pdo->prepare("
        SELECT u.id, u.name, 
               g.oraliq, g.mustaqil, g.yakuniy 
        FROM users u 
        JOIN user_groups ug ON u.id = ug.user_id 
        LEFT JOIN grades g ON u.id = g.student_id AND g.subject_id = ?
        WHERE ug.group_id = ? AND u.role = 'student'
        ORDER BY u.name
    ");
    $students->execute([$subject_id, $group_id]);
    $student_list = $students->fetchAll();

    // Get Subject and Group info
    $info = $pdo->prepare("SELECT s.name as subject, g.name as group_name FROM subjects s, `groups` g WHERE s.id=? AND g.id=?");
    $info->execute([$subject_id, $group_id]);
    $meta = $info->fetch();
}

// Fetch my assignments (for selection screen)
$user_id = $_SESSION['user_id'];
$my_assignments = $pdo->prepare("
    SELECT ta.subject_id, ta.group_id, s.name as subject_name, g.name as group_name
    FROM teacher_assignments ta
    JOIN subjects s ON ta.subject_id = s.id
    JOIN `groups` g ON ta.group_id = g.id
    WHERE ta.teacher_id = ?
");
$my_assignments->execute([$user_id]);
$my_assignments = $my_assignments->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Baholash - O'qituvchi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success" style="border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($subject_id && $group_id && $meta): ?>
            <div class="card" style="padding: 2.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; gap: 20px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; background: var(--primary-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">📊</div>
                        <div>
                            <h2 style="font-weight: 800; color: var(--text-dark); margin: 0; font-size: 1.5rem;">Baholash: <?= htmlspecialchars($meta['subject']) ?></h2>
                            <span class="badge badge-primary" style="margin-top: 5px;"><?= htmlspecialchars($meta['group_name']) ?></span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="mustaqil_baholash.php?subject_id=<?= $subject_id ?>&group_id=<?= $group_id ?>" class="btn" style="background: var(--primary-color); color: white; display: flex; align-items: center; gap: 8px;">🔄 Mustaqil Ishlarni Ko'chirish</a>
                        <a href="grades.php" class="btn btn-secondary" style="background: #64748b; color: white;">Orqaga qaytish</a>
                    </div>
                </div>
                

                <form method="POST">
                    <div style="overflow-x: auto; background: var(--bg-card); border-radius: var(--radius-md);">
                        <table class="table" style="width: 100%; border-spacing: 0 10px; border-collapse: separate;">
                            <thead>
                                <tr style="text-align: left; color: var(--text-light); font-size: 0.9rem;">
                                    <th style="padding: 1rem 1.5rem;">Talaba</th>
                                    <th style="padding: 1rem; width: 120px; text-align: center;">Oraliq (20)</th>
                                    <th style="padding: 1rem; width: 120px; text-align: center;">Mustaqil (30)</th>
                                    <th style="padding: 1rem; width: 120px; text-align: center;">Yakuniy (50)</th>
                                    <th style="padding: 1rem; width: 100px; text-align: center;">Jami</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($student_list as $student): ?>
                                    <tr class="grade-row" data-student="<?= $student['id'] ?>" style="background: var(--bg-card); transition: 0.3s; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                                        <td style="padding: 1.25rem 1.5rem; font-weight: 700; color: var(--text-dark); border-top-left-radius: 12px; border-bottom-left-radius: 12px; border: 1px solid var(--border-color); border-right: none;">
                                            <?= htmlspecialchars($student['name']) ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: center; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                                            <input type="number" 
                                                   name="grades[<?= $student['id'] ?>][oraliq]" 
                                                   value="<?= isset($student['oraliq']) ? $student['oraliq'] : 0 ?>" 
                                                   max="20" min="0" 
                                                   class="form-control grade-input" 
                                                   data-type="oraliq"
                                                   style="width: 80px; text-align: center; margin: 0 auto; border-radius: 10px;">
                                        </td>
                                        <td style="padding: 1rem; text-align: center; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                                            <input type="number" 
                                                   name="grades[<?= $student['id'] ?>][mustaqil]" 
                                                   value="<?= isset($student['mustaqil']) ? $student['mustaqil'] : 0 ?>" 
                                                   max="30" min="0" 
                                                   class="form-control grade-input" 
                                                   data-type="mustaqil"
                                                   style="width: 80px; text-align: center; margin: 0 auto; border-radius: 10px;">
                                        </td>
                                        <td style="padding: 1rem; text-align: center; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                                            <input type="number" 
                                                   name="grades[<?= $student['id'] ?>][yakuniy]" 
                                                   value="<?= isset($student['yakuniy']) ? $student['yakuniy'] : 0 ?>" 
                                                   max="50" min="0" 
                                                   class="form-control grade-input" 
                                                   data-type="yakuniy"
                                                   style="width: 80px; text-align: center; margin: 0 auto; border-radius: 10px;">
                                        </td>
                                        <td style="padding: 1.25rem 1.5rem; text-align: center; border-top-right-radius: 12px; border-bottom-right-radius: 12px; border: 1px solid var(--border-color); border-left: none;">
                                            <strong class="total-score" style="font-size: 1.1rem; color: var(--primary-color);">0</strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 2.5rem; display: flex; justify-content: flex-end;">
                        <button type="submit" name="save_grades" class="btn" style="padding: 15px 50px; font-size: 1.1rem; border-radius: 12px; box-shadow: var(--shadow-premium);">Saqlash va Tasdiqlash</button>
                    </div>
                </form>
            </div>

            <script>
            document.querySelectorAll('.grade-row').forEach(row => {
                const inputs = row.querySelectorAll('.grade-input');
                const totalDisplay = row.querySelector('.total-score');

                const calculateTotal = () => {
                    let total = 0;
                    inputs.forEach(input => {
                        total += parseInt(input.value) || 0;
                    });
                    totalDisplay.textContent = total;
                    
                    // Add color coding based on total
                    if(total >= 86) totalDisplay.style.color = 'var(--success)';
                    else if(total >= 71) totalDisplay.style.color = 'var(--primary-color)';
                    else if(total >= 55) totalDisplay.style.color = 'var(--warning)';
                    else totalDisplay.style.color = 'var(--danger)';
                };

                inputs.forEach(input => {
                    input.addEventListener('input', calculateTotal);
                });
                
                calculateTotal(); // Initial run
            });
            </script>
        <?php else: ?>
            <div style="margin-bottom: 2rem;">
                <h2 style="font-weight: 800; color: var(--text-dark);">Baholash Markazi</h2>
                <p style="color: var(--text-light);">Baholashni boshlash uchun fan va guruhni tanlang.</p>
            </div>

            <div class="grid">
                <?php if (empty($my_assignments)): ?>
                    <div class="card" style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem;">
                        <div style="font-size: 4rem; margin-bottom: 1.5rem;">🏖️</div>
                        <h3 style="color: var(--text-dark); margin-bottom: 10px;">Sizga hozircha hech qanday guruh biriktirilmagan.</h3>
                        <p style="color: var(--text-light);">Agar bu xatolik bo'lsa, iltimos admin bilan bog'laning.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($my_assignments as $assign): ?>
                        <div class="card stat-card" style="cursor: pointer; padding: 2rem; border-left: 6px solid var(--primary-color); transition: all 0.3s ease;" onclick="location.href='?subject_id=<?= $assign['subject_id'] ?>&group_id=<?= $assign['group_id'] ?>'">
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <span class="badge badge-primary" style="width: fit-content;"><?= htmlspecialchars($assign['group_name']) ?></span>
                                <h3 style="color: var(--text-dark); font-weight: 800; font-size: 1.25rem; margin: 0; line-height: 1.4;">
                                    <?= htmlspecialchars($assign['subject_name']) ?>
                                </h3>
                                <div style="margin-top: 15px; display: flex; align-items: center; gap: 8px; color: var(--primary-color); font-weight: 600; font-size: 0.9rem;">
                                    Baholashni boshlash <span>→</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
