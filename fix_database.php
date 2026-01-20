<?php
session_start();
require 'config/db.php';

echo "<h2>EduVision Baza Tuzatuvchi (Database Fixer)</h2>";
echo "<ul style='font-family: sans-serif; line-height: 1.6;'>";

try {
    // 1. PROJECTS Table Fixes
    $columns_to_add = [
        'image_path' => "VARCHAR(255) DEFAULT NULL AFTER description",
        'user_id' => "INT DEFAULT NULL AFTER image_path",
        'status' => "ENUM('pending', 'approved') DEFAULT 'approved' AFTER user_id"
    ];

    foreach ($columns_to_add as $col => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM projects LIKE '$col'");
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN $col $definition");
            echo "<li style='color: green;'>✅ 'projects' jadvaliga '$col' ustuni qo'shildi.</li>";
        } else {
            echo "<li>ℹ️ 'projects' jadvalida '$col' ustuni allaqachon mavjud.</li>";
        }
    }

    // Update existing records to 'approved' if they were NULL
    $pdo->exec("UPDATE projects SET status = 'approved' WHERE status IS NULL");

    // 2. NOTIFICATIONS Table
    $sql_notifications = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        link VARCHAR(255) DEFAULT '#',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql_notifications);
    echo "<li style='color: green;'>✅ 'notifications' jadvali tekshirildi/yaratildi.</li>";

    // 3. ARTICLES Table Fixes
    $check_article_user = $pdo->query("SHOW COLUMNS FROM articles LIKE 'user_id'");
    if (!$check_article_user->fetch()) {
        $pdo->exec("ALTER TABLE articles ADD COLUMN user_id INT DEFAULT NULL AFTER id");
        echo "<li style='color: green;'>✅ 'articles' jadvaliga 'user_id' ustuni qo'shildi.</li>";
    } else {
        echo "<li>ℹ️ 'articles' jadvalida 'user_id' ustuni allaqachon mavjud.</li>";
    }

    // 4. TASKS Table Updates
    $tasks_cols = [
        'max_attempts' => "INT DEFAULT 1",
        'subject_id' => "INT NULL"
    ];
    foreach ($tasks_cols as $col => $def) {
        $check = $pdo->query("SHOW COLUMNS FROM tasks LIKE '$col'");
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE tasks ADD COLUMN $col $def");
            echo "<li style='color: green;'>✅ 'tasks' jadvaliga '$col' ustuni qo'shildi.</li>";
        } else {
            echo "<li>ℹ️ 'tasks' jadvalida '$col' ustuni allaqachon mavjud.</li>";
        }
    }

    // 5. SUBMISSIONS Table Updates
    $sub_cols = [
        'score' => "INT DEFAULT NULL",
        'teacher_comment' => "TEXT NULL"
    ];
    foreach ($sub_cols as $col => $def) {
        $check = $pdo->query("SHOW COLUMNS FROM submissions LIKE '$col'");
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE submissions ADD COLUMN $col $def");
            echo "<li style='color: green;'>✅ 'submissions' jadvaliga '$col' ustuni qo'shildi.</li>";
        } else {
            echo "<li>ℹ️ 'submissions' jadvalida '$col' ustuni allaqachon mavjud.</li>";
        }
    }

    echo "</ul>";
    echo "<div style='margin-top: 20px; padding: 15px; background: #dcfce7; border-radius: 8px; color: #166534;'>";
    echo "<b>Muvaffaqiyatli!</b> Baza to'liq yangilandi. Endi tizimdan foydalanishingiz mumkin.";
    echo "</div>";
    echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #4361ee; color: white; text-decoration: none; border-radius: 6px;'>Bosh sahifaga qaytish</a></p>";

} catch (PDOException $e) {
    echo "</ul>";
    echo "<div style='margin-top: 20px; padding: 15px; background: #fee2e2; border-radius: 8px; color: #991b1b;'>";
    echo "<b>Xatolik yuz berdi:</b> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
