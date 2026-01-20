<?php
require 'config/db.php';

try {
    // 1. Subjects Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL
    )");
    echo "Created 'subjects' table.<br>";

    // 2. Teacher Assignments (Who teaches what to whom)
    $pdo->exec("CREATE TABLE IF NOT EXISTS teacher_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT,
        subject_id INT,
        group_id INT,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
    )");
    echo "Created 'teacher_assignments' table.<br>";

    // 3. Grades (Baholar)
    $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        oraliq INT DEFAULT 0,
        mustaqil INT DEFAULT 0,
        yakuniy INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    )");
    echo "Created 'grades' table.<br>";

    // 4. Schedule (Dars Jadvali)
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT,
        day_of_week VARCHAR(20) NOT NULL, -- Monday, Tuesday...
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        subject_id INT,
        teacher_id INT,
        room VARCHAR(50) DEFAULT NULL,
        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Add room column if it doesn't exist (for existing tables)
    try {
        $pdo->exec("ALTER TABLE schedule ADD COLUMN room VARCHAR(50) DEFAULT NULL");
        echo "Added 'room' column to schedule table.<br>";
    } catch (Exception $e) {
        // Column likely exists
    }

    echo "Created 'schedule' table.<br>";

    echo "<h3>System Updated Successfully! (V2)</h3>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
