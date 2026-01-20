<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'fetch';

if ($action === 'fetch') {
    // Get unread count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtCount->execute([$user_id]);
    $unreadCount = $stmtCount->fetchColumn();

    // Get latest 10 notifications
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    echo json_encode([
        'unreadCount' => $unreadCount,
        'notifications' => $notifications
    ]);
} elseif ($action === 'mark_read') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    echo json_encode(['success' => true]);
}
?>
