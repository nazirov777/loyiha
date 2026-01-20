<?php
require 'config/db.php';

try {
    // 1. Subjects table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // 2. Teacher Assignments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS teacher_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        subject_id INT NOT NULL,
        group_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // 3. Grades table
    $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id INT NOT NULL,
        oraliq INT DEFAULT 0,
        mustaqil INT DEFAULT 0,
        yakuniy INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    echo "✅ Grading system tables created/verified successfully!<br>";
    echo "<a href='teacher/dashboard.php'>Back to Dashboard</a>";
} catch (PDOException $e) {
    die("<b style='color:red;'>Error: " . $e->getMessage() . "</b>");
}
?>
