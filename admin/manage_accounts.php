<?php
session_start();
require '../config/db.php';
require_once '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// Handle Updates
if (isset($_POST['update_account'])) {
    $uid = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Check Email uniqueness
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$email, $uid]);
    if ($check->fetch()) {
        $message = "Xatolik: Ushbu email band!";
    } else {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $hash, $uid]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $uid]);
        }
        $message = "Muvaffaqiyatli yangilandi!";
    }
}

// Fetch Users
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT id, name, email, role FROM users WHERE status = 'active'";
if ($search) {
    $sql .= " AND (name LIKE :s OR email LIKE :s)";
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
if ($search) {
    $s_val = "%$search%";
    $stmt->bindParam(':s', $s_val);
}
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Hisoblar Boshqaruvi - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="font-weight: 800; color: var(--text-dark);">🔐 Hisoblar Boshqaruvi</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 20px;">
            <form method="GET" style="display: flex; gap: 10px;">
                <input type="text" name="search" class="form-control" placeholder="Ism yoki email bo'yicha qidirish..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn" style="width: auto;">Qidirish</button>
            </form>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background: rgba(67, 97, 238, 0.05); text-align: left;">
                            <th style="padding: 15px; font-size: 0.8rem; font-weight: 700; color: var(--text-light); text-transform: uppercase;">ID</th>
                            <th style="padding: 15px; font-size: 0.8rem; font-weight: 700; color: var(--text-light); text-transform: uppercase;">Foydalanuvchi</th>
                            <th style="padding: 15px; font-size: 0.8rem; font-weight: 700; color: var(--text-light); text-transform: uppercase;">Email</th>
                            <th style="padding: 15px; font-size: 0.8rem; font-weight: 700; color: var(--text-light); text-transform: uppercase;">Rol</th>
                            <th style="padding: 15px; font-size: 0.8rem; font-weight: 700; color: var(--text-light); text-transform: uppercase;">Yangi Parol</th>
                            <th style="padding: 15px; text-align: right; font-size: 0.8rem; font-weight: 700; color: var(--text-light); text-transform: uppercase;">Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 15px; color: var(--text-light); font-weight: 600;">#<?= $u['id'] ?></td>
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <td style="padding: 15px;">
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" style="min-width: 200px;" required>
                                    </td>
                                    <td style="padding: 15px;">
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" style="min-width: 200px;" required>
                                    </td>
                                    <td style="padding: 15px;">
                                        <span class="badge" style="background: #f3e5f5; color: #7b1fa2;"><?= strtoupper($u['role']) ?></span>
                                    </td>
                                    <td style="padding: 15px;">
                                        <input type="password" name="password" class="form-control" placeholder="Yangi parol..." style="min-width: 150px;">
                                        <div style="font-size: 0.65rem; color: #ef4444; margin-top: 4px;">* Eski parol xavfsizlik uchun hashda saqlanadi</div>
                                    </td>
                                    <td style="padding: 15px; text-align: right;">
                                        <button type="submit" name="update_account" class="btn" style="padding: 8px 15px; font-size: 0.85rem; width: auto;">💾 Saqlash</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
