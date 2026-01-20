<?php
require 'config/db.php';

try {
    // 1. Ensure image_path exists in projects
    $check = $pdo->query("SHOW COLUMNS FROM projects LIKE 'image_path'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE projects ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER description");
        echo "✅ Added image_path to projects table.<br>";
    }

    // 2. Add user_id (who submitted)
    $check = $pdo->query("SHOW COLUMNS FROM projects LIKE 'user_id'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE projects ADD COLUMN user_id INT DEFAULT NULL AFTER image_path");
        echo "✅ Added user_id to projects table.<br>";
    }

    // 3. Add status (pending/approved)
    $check = $pdo->query("SHOW COLUMNS FROM projects LIKE 'status'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE projects ADD COLUMN status ENUM('pending', 'approved') DEFAULT 'approved' AFTER user_id");
        echo "✅ Added status to projects table.<br>";
    }

    // 4. Update foreign key (optional but good practice)
    try {
        $pdo->exec("ALTER TABLE projects ADD CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "✅ Added foreign key constraint for user_id.<br>";
    } catch(Exception $e) {
        // Might already exist or fail if data inconsistent
    }

    echo "<br><b style='color:green;'>Project system migration completed!</b>";
    echo "<br><a href='admin/projects.php'>Admin Loyihalar</a> | <a href='user/projects.php'>Talaba Loyihalar</a>";
} catch (PDOException $e) {
    die("<b style='color:red;'>Error: " . $e->getMessage() . "</b>");
}
?>
