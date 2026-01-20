<?php
require 'config/db.php';

echo "<h2>Task Subject Link Check</h2>";

$stmt = $pdo->query("SELECT id, title, subject_id FROM tasks WHERE subject_id IS NULL");
$orphaned = $stmt->fetchAll();

if (empty($orphaned)) {
    echo "<p style='color: green;'>All tasks have subjects linked.</p>";
} else {
    echo "<p style='color: red;'>Found " . count($orphaned) . " tasks without a subject linked:</p>";
    echo "<ul>";
    foreach ($orphaned as $t) {
        echo "<li>ID: {$t['id']} - Title: " . htmlspecialchars($t['title']) . " (Subject ID is NULL)</li>";
    }
    echo "</ul>";
    echo "<p>These tasks will NOT synchronize to the gradebook because they don't belong to any subject.</p>";
    
    // Suggest first subject if possible
    $subj = $pdo->query("SELECT id, name FROM subjects LIMIT 1")->fetch();
    if ($subj) {
        echo "<p>To fix this, go to 'Vazifalar' and edit these tasks, or you can run an update to link them to <b>{$subj['name']}</b>.</p>";
    }
}
?>
