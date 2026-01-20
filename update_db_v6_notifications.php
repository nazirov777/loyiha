<?php
require 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        link VARCHAR(255) DEFAULT '#',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "✅ Notifications table created successfully.<br>";
    echo "<br><b style='color:green;'>System updated!</b>";
    echo "<br><a href='index.php'>Bosh sahifaga qaytish</a>";
} catch (PDOException $e) {
    die("<b style='color:red;'>Error: " . $e->getMessage() . "</b>");
}
?>
