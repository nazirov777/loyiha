<?php
require 'config/db.php';

try {
    // Add status column if not exists
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch();
    if (!$columns) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'pending') NOT NULL DEFAULT 'pending'");
        echo "Added 'status' column.<br>";
    }
    
    // Make sure existing default admin is active
    $pdo->exec("UPDATE users SET status = 'active' WHERE role = 'admin'");
    echo "Updated Admin status to active.<br>";
    
    // Check if role enum has teacher (difficult to alter enum in one go without raw sql, 
    // but for now we can just use string or map teacher in logic if enum restricts.
    // The current schema says: role ENUM('admin', 'student').
    // We need to add 'teacher'.
    
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'student', 'teacher') NOT NULL");
    echo "Updated 'role' column to include 'teacher'.<br>";

    // Add image_path to projects
    $columns = $pdo->query("SHOW COLUMNS FROM projects LIKE 'image_path'")->fetch();
    if (!$columns) {
        $pdo->exec("ALTER TABLE projects ADD COLUMN image_path VARCHAR(255) NULL");
        echo "Added 'image_path' column to projects.<br>";
    }

    echo "<h3>Update Complete.</h3>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
