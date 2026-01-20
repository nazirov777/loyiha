<?php
/**
 * Helper to send notifications to users
 */
function sendNotification($pdo, $user_id, $title, $message, $link = '#') {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $link]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Notify all administrators
 */
function notifyAdmins($pdo, $title, $message, $link = '#') {
    try {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $admin_id) {
            sendNotification($pdo, $admin_id, $title, $message, $link);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}
/**
 * Notify all users with a specific role
 */
function notifyRole($pdo, $role, $title, $message, $link = '#') {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ? AND status = 'active'");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($users as $u_id) {
            sendNotification($pdo, $u_id, $title, $message, $link);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Notify everyone except the person doing the action
 */
function notifyAll($pdo, $title, $message, $link = '#', $exclude_id = null) {
    try {
        $sql = "SELECT id FROM users WHERE status = 'active'";
        $params = [];
        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($users as $u_id) {
            sendNotification($pdo, $u_id, $title, $message, $link);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}
