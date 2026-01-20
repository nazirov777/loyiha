<?php
require 'config/db.php';

try {
    // Add group_id to messages
    $pdo->exec("ALTER TABLE messages ADD COLUMN group_id INT DEFAULT NULL AFTER user_id");
    // Add foreign key if groups table exists
    $pdo->exec("ALTER TABLE messages ADD CONSTRAINT fk_message_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE");
    
    echo "Database updated successfully! group_id added to messages table.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
