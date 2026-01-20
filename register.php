<?php
session_start();
require 'config/db.php';
require 'config/notifications_helper.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Iltimos, barcha maydonlarni to'ldiring.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Bu email allaqachon ro'yxatdan o'tgan.";
        } else {
            // Status is pending by default in DB, but we can be explicit
            $status = 'pending'; 
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $role, $status])) {
                // Notify Admins
                notifyAdmins($pdo, "Yangi Ro'yxatdan o'tish! 👤", "$name ($role) tizimdan ro'yxatdan o'tdi va tasdiqlash kutmoqda.", "users.php");
                
                $_SESSION['success'] = "Siz muvaffaqiyatli ro'yxatdan o'tdingiz. Admin tasdiqlashini kuting.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Xatolik yuz berdi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ro'yxatdan o'tish - EduVision</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Ro'yxatdan o'tish</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Ism va Familiya</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Parol</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Rolni tanlang</label>
                    <select name="role" class="form-control" required>
                        <option value="student">Talaba / O'quvchi</option>
                        <option value="teacher">O'qituvchi</option>
                    </select>
                </div>
                <button type="submit" class="btn">Ro'yxatdan o'tish</button>
            </form>
            <a href="login.php" class="auth-link">Akkauntingiz bormi? Kirish</a>
        </div>
    </div>
</body>
</html>
