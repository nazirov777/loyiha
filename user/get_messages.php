<?php
require '../config/db.php';

$group_id = isset($_GET['group_id']) ? $_GET['group_id'] : null;

$has_group_col = false;
try {
    $pdo->query("SELECT group_id FROM messages LIMIT 1");
    $has_group_col = true;
} catch (PDOException $e) {}

$sql = "SELECT m.id as msg_id, m.message, m.created_at, u.name, u.role, u.id as user_id" . ($has_group_col ? ", g.name as group_name" : "") . " 
        FROM messages m 
        JOIN users u ON m.user_id = u.id";

if ($has_group_col) {
    $sql .= " LEFT JOIN `groups` g ON m.group_id = g.id";
}

if ($has_group_col && $group_id === 'all') {
    // No filter
} elseif ($has_group_col && $group_id) {
    $sql .= " WHERE m.group_id = :gid";
} else {
    // If column missing, we only show global messages (which are all messages in old schema)
    if ($has_group_col) {
        $sql .= " WHERE m.group_id IS NULL";
    }
}

$sql .= " ORDER BY m.created_at ASC";

$stmt = $pdo->prepare($sql);
// Only bind if the placeholder actually exists in the query
if ($has_group_col && $group_id && $group_id !== 'all') {
    $stmt->bindParam(':gid', $group_id);
}
$stmt->execute();
$messages = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($messages);
?>
