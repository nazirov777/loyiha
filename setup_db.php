<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/db.php';

echo "<h2>Database Setup Log</h2>";

try {
    // 1. Read SQL file
    $sqlKey = 'database.sql';
    if (!file_exists($sqlKey)) {
        die("<p style='color:red'>Error: database.sql not found in " . __DIR__ . "</p>");
    }
    
    $sql = file_get_contents($sqlKey);
    if (empty($sql)) {
        die("<p style='color:red'>Error: database.sql is empty</p>");
    }

    // 2. Execute SQL
    // Split by semicolon to execute statement by statement for better error tracking
    // Note: This is a simple split, might break on complex triggers but fine for this schema
    
    // Actually, let's just use exec for the whole block if possible, but PDO sometimes requires splitting.
    // Let's try executing the whole thing, if it fails we see why.
    // But since the user had issues, let's try to Force create tables.
    
    $pdo->exec($sql);
    echo "<p style='color:green'>SQL Schema executed successfully.</p>";
    
    // 3. Create Admin
    $adminEmail = 'admin@eduvision.uz';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$adminEmail]);
    if ($check->fetch()) {
        echo "<p style='color:orange'>Default Admin already exists.</p>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        if ($stmt->execute(['Super Admin', $adminEmail, $password])) {
             echo "<p style='color:green'>Default Admin created: <b>admin@eduvision.uz</b> / <b>admin123</b></p>";
        } else {
             echo "<p style='color:red'>Failed to create default admin.</p>";
        }
    }
    
    echo "<h3>Setup Complete. <a href='login.php'>Go to Login</a></h3>";

} catch (PDOException $e) {
    echo "<p style='color:red'>Critical Error: " . $e->getMessage() . "</p>";
    // Attempt to list tables to see what exists
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Existing tables: " . implode(', ', $tables) . "</p>";
    } catch (Exception $ex) {}
}
?>
