<?php
session_start();
require '../config/db.php';
require '../config/notifications_helper.php';

if (isset($_POST['message']) && isset($_SESSION['user_id'])) {
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];
    $group_id = isset($_POST['group_id']) ? $_POST['group_id'] : null;

    if (!empty($message)) {
        try {
            $has_group_col = false;
            try {
                $pdo->query("SELECT group_id FROM messages LIMIT 1");
                $has_group_col = true;
            } catch (PDOException $e) {}

            if ($has_group_col) {
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, message, group_id) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $message, $group_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
                $stmt->execute([$user_id, $message]);
            }
            
            // Notify Admins if message is from non-admin
            if ($_SESSION['role'] !== 'admin') {
                $sender_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Foydalanuvchi';
                $context = $group_id ? "Guruhda" : "Umumiy chatda";
                notifyAdmins($pdo, "Yangi Xabar! 💬", "$sender_name $context yangi xabar yubordi: " . mb_substr($message, 0, 50) . "...", "achat.php");
            }
            
            echo "Sent";
        } catch (PDOException $e) {
            echo "Baza xatosi: " . $e->getMessage();
        }
    } else {
        echo "Xabar bo'sh bo'lishi mumkin emas.";
    }
} else {
    echo "Sessiya muddati tugagan yoki xabar yuborilmadi.";
}
?>
