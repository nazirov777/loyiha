<?php
require 'config/db.php';

echo "<h2>EduVision Database Update v3</h2>";

try {
    // 1. Ensure group_id exists in messages
    $check = $pdo->query("SHOW COLUMNS FROM messages LIKE 'group_id'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN group_id INT DEFAULT NULL AFTER user_id");
        echo "✅ Added group_id to messages table.<br>";
    } else {
        echo "ℹ️ group_id already exists in messages table.<br>";
    }

    // 2. Ensure foreign key for group_id exists
    try {
        $pdo->exec("ALTER TABLE messages ADD CONSTRAINT fk_message_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE");
        echo "✅ Added foreign key constraint to messages table.<br>";
    } catch (Exception $e) {
        echo "ℹ️ Foreign key might already exist: " . $e->getMessage() . "<br>";
    }

    // 3. Ensure room exists in schedule (just in case)
    $check = $pdo->query("SHOW COLUMNS FROM schedule LIKE 'room'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE schedule ADD COLUMN room VARCHAR(50) DEFAULT NULL AFTER day");
        echo "✅ Added room to schedule table.<br>";
    } else {
        echo "ℹ️ room already exists in schedule table.<br>";
    }

    // 4. Ensure created_by exists in tasks
    $check = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'created_by'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN created_by INT DEFAULT NULL AFTER deadline");
        $pdo->exec("ALTER TABLE tasks ADD CONSTRAINT fk_tasks_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "✅ Added created_by to tasks table.<br>";
    } else {
        echo "ℹ️ created_by already exists in tasks table.<br>";
    }

    echo "<br><b style='color:green;'>All updates completed successfully!</b>";
} catch (PDOException $e) {
    die("<b style='color:red;'>Error: " . $e->getMessage() . "</b>");
}
?>
