<?php
session_start();
require '../config/db.php';
require '../config/notifications_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// Approve/Reject User
if (isset($_POST['approve_user'])) {
    $uid = $_POST['user_id'];
    $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$uid]);
    
    // Notify User
    sendNotification($pdo, $uid, "Hisobingiz tasdiqlandi! 🎉", "Sizning hisobingiz admin tomonidan tasdiqlandi. Endi barcha imkoniyatlardan foydalanishingiz mumkin.", "dashboard.php");
    
    $message = "Foydalanuvchi tasdiqlandi!";
}
if (isset($_POST['delete_user'])) {
    $uid = $_POST['user_id'];
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
    $message = "Foydalanuvchi o'chirildi!";
}

// Add Group
if (isset($_POST['add_group'])) {
    $name = trim($_POST['group_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO `groups` (name) VALUES (?)");
        $stmt->execute([$name]);
        $message = "Guruh qo'shildi!";
    }
}

// Add Student to Group
if (isset($_POST['assign_group'])) {
    $user_id = $_POST['user_id'];
    $group_id = $_POST['group_id'];
    
    // Check if assignment exists
    $check = $pdo->prepare("SELECT * FROM user_groups WHERE user_id = ? AND group_id = ?");
    $check->execute([$user_id, $group_id]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $group_id]);
        
        // Notify Student
        $group_stmt = $pdo->prepare("SELECT name FROM `groups` WHERE id = ?");
        $group_stmt->execute([$group_id]);
        $g_name = $group_stmt->fetchColumn();
        
        sendNotification($pdo, $user_id, "Yangi Guruh! 🏫", "Siz $g_name guruhiga qo'shildingiz.", "dashboard.php");
        
        $message = "Talaba guruhga qo'shildi!";
    }
}

// Update User Role
if (isset($_POST['update_role'])) {
    $uid = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    // Prevent changing own role or super admin if implemented strictly, but for now allow except basic checks
    if ($uid != $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $uid]);
        $message = "Rol o'zgartirildi!";
    } else {
        $message = "O'z rolingizni o'zgartira olmaysiz.";
    }
}

// Search Logic
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

$sql = "SELECT * FROM users WHERE status != 'pending'";
$params = [];

if ($search_query) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if ($role_filter) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$all_users = $stmt->fetchAll();

// Restore missing variables
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending'")->fetchAll();
$groups = $pdo->query("SELECT * FROM `groups` ORDER BY id DESC")->fetchAll();
$students = $pdo->query("SELECT * FROM users WHERE role = 'student' AND status = 'active' ORDER BY id DESC")->fetchAll();

// Create New User (Admin/Teacher/Student) Logic
if (isset($_POST['create_user'])) {
    $name = trim($_POST['new_name']);
    $email = trim($_POST['new_email']);
    $password = $_POST['new_password'];
    $role = $_POST['new_role'];
    
    // Check if email exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $message = "Bu email allaqachon mavjud!";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // Created by admin -> active immediately
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
        if ($stmt->execute([$name, $email, $hash, $role])) {
            $message = "Yangi foydalanuvchi ($role) yaratildi!";
            // Refresh grid
            header("Refresh:0");
        }
    }
}
// Delete Group
if (isset($_POST['delete_group'])) {
    $gid = $_POST['group_id'];
    $pdo->prepare("DELETE FROM `groups` WHERE id = ?")->execute([$gid]);
    $message = "Guruh o'chirildi!";
    // Refresh to reflect changes
    header("Refresh:0");
}

// Edit User Logic
if (isset($_POST['edit_user'])) {
    $uid = $_POST['user_id'];
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);
    $password = $_POST['edit_password'];
    
    // Check if email exists for other users
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $uid]);
    if ($stmt->fetch()) {
        $message = "Xatolik: Bu email boshqa foydalanuvchi tomonidan foydalanilmoqda!";
    } else {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $hash, $uid]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $uid]);
        }
        $message = "Foydalanuvchi ma'lumotlari yangilandi!";
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Foydalanuvchilar - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2 style="margin-bottom: 2rem; font-weight: 800; color: var(--text-dark);">Foydalanuvchilar Boshqaruvi</h2>

        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Pending Approvals -->
        <?php if (!empty($pending_users)): ?>
        <div class="card" style="border-left: 5px solid var(--warning); background: #fffcf0;">
            <h3 style="color: #92400e; margin-bottom: 1.5rem;">⏳ Tasdiqlashni kutayotganlar</h3>
            <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
                <?php foreach ($pending_users as $user): ?>
                    <div class="card" style="margin-bottom: 0; padding: 1.5rem; display: flex; flex-direction: column; gap: 10px;">
                        <div>
                            <div style="font-weight: 700; font-size: 1.1rem;"><?= htmlspecialchars($user['name']) ?></div>
                            <div style="color: var(--text-light); font-size: 0.9rem;"><?= $user['email'] ?></div>
                            <div class="badge badge-primary" style="margin-top: 5px;"><?= $user['role'] ?></div>
                        </div>
                        <form method="POST" style="display:flex; gap: 10px; margin-top: 10px;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="approve_user" class="btn" style="flex: 1; padding: 10px;">Tasdiqlash</button>
                            <button type="submit" name="delete_user" class="btn btn-secondary" style="background: var(--danger); flex: 1; padding: 10px;">Rad etish</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search & Filter Area -->
        <div class="card">
            <h3 style="margin-bottom: 1.5rem;">Qidiruv va Filtr</h3>
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <input type="text" name="search" class="form-control" placeholder="Ism yoki Email..." value="<?= htmlspecialchars($search_query) ?>">
                <select name="role" class="form-control">
                    <option value="">Barcha rollar</option>
                    <option value="student" <?= $role_filter == 'student' ? 'selected' : '' ?>>Talaba</option>
                    <option value="teacher" <?= $role_filter == 'teacher' ? 'selected' : '' ?>>O'qituvchi</option>
                    <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn" style="flex: 1;">Qidirish</button>
                    <a href="users.php" class="btn btn-secondary" style="flex: 1; text-align: center; display: flex; align-items: center; justify-content: center;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">Barcha Foydalanuvchilar</h3>
                <span class="badge badge-primary"><?= count($all_users) ?> ta jami</span>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">
                    <thead>
                        <tr style="text-align: left; color: var(--text-light); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                            <th style="padding: 12px 15px;">ID</th>
                            <th style="padding: 12px 15px;">Foydalanuvchi</th>
                            <th style="padding: 12px 15px;">Rol Boshqaruvi</th>
                            <th style="padding: 12px 15px;">Holat</th>
                            <th style="padding: 12px 15px; text-align: right;">Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $u): ?>
                        <tr style="background: var(--secondary-color); transition: transform 0.2s;">
                            <td style="padding: 15px; border-radius: 12px 0 0 12px; font-weight: 600; color: var(--text-light);">#<?= $u['id'] ?></td>
                            <td style="padding: 15px;">
                                <div style="font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars($u['name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-light);"><?= htmlspecialchars($u['email']) ?></div>
                            </td>
                            <td style="padding: 15px;">
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="new_role" onchange="this.form.submit()" class="form-control" style="padding: 5px 10px; font-size: 0.85rem; height: auto; width: auto; min-width: 120px;">
                                        <option value="student" <?= $u['role'] == 'student' ? 'selected' : '' ?>>Talaba</option>
                                        <option value="teacher" <?= $u['role'] == 'teacher' ? 'selected' : '' ?>>O'qituvchi</option>
                                        <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <input type="hidden" name="update_role" value="1">
                                </form>
                                <?php else: ?>
                                    <span class="badge badge-primary">Siz (Admin)</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;">
                                <span class="badge <?= $u['status']=='active'?'badge-success':'badge-danger' ?>">
                                    <?= $u['status']=='active'?'Faol':'Nofaol' ?>
                                </span>
                            </td>
                            <td style="padding: 15px; border-radius: 0 12px 12px 0; text-align: right;">
                                <?php if($u['role'] !== 'admin' || $u['id'] != $_SESSION['user_id']): ?>
                                <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)" style="background: none; border: none; color: var(--primary-color); cursor: pointer; font-weight: 600; font-size: 0.9rem; margin-right: 15px;">
                                    ✎ Tahrirlash
                                </button>
                                <form method="POST" onsubmit="return confirm('Rostdan ham o\'chirmoqchimisiz?');" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" name="delete_user" style="background: none; border: none; color: var(--danger); cursor: pointer; font-weight: 600; font-size: 0.9rem;">
                                        ✕ O'chirish
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Yangi Foydalanuvchi Yaratish (Admin/Student/Teacher)</h3>
            <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="text" name="new_name" class="form-control" placeholder="Ism Familiya" required>
                <input type="email" name="new_email" class="form-control" placeholder="Email" required>
                <input type="password" name="new_password" class="form-control" placeholder="Parol" required>
                <select name="new_role" class="form-control">
                    <option value="student">Talaba</option>
                    <option value="teacher">O'qituvchi</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="create_user" class="btn" style="grid-column: span 2;">Yaratish</button>
            </form>
        </div>

        <!-- Groups Management -->
        <div style="display: flex; gap: 20px;">
            <div class="card" style="flex: 1;">
                <h3>Guruhlarni Boshqarish</h3>
                <form method="POST" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <input type="text" name="group_name" class="form-control" placeholder="Yangi guruh nomi" required>
                    <button type="submit" name="add_group" class="btn" style="width: auto;">Qo'shish</button>
                </form>

                <ul style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($groups as $group): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                            <span><?= htmlspecialchars($group['name']) ?></span>
                            <form method="POST" onsubmit="return confirm('Guruhni o\'chirmoqchimisiz? Ichidagi barcha ma\'lumotlar o\'chishi mumkin.');">
                                <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                <button type="submit" name="delete_group" style="background: none; border: none; color: #ef4444; cursor: pointer;">🗑️ O'chirish</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="card" style="flex: 1;">
                <h3>Talabani Guruhga Biriktirish</h3>
                <form method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                    <select name="user_id" class="form-control" required>
                        <option value="">Talabani tanlang</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="group_id" class="form-control" required>
                        <option value="">Guruhni tanlang</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign_group" class="btn">Biriktirish</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card" style="width: 100%; max-width: 500px; margin: 20px; position: relative; animation: slideUp 0.3s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Foydalanuvchini tahrirlash</h3>
                <button onclick="closeEditModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-light);">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-light); font-size: 0.85rem;">F.I.SH</label>
                        <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-light); font-size: 0.85rem;">Email</label>
                        <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-light); font-size: 0.85rem;">Yangi parol (ixtiyoriy)</label>
                        <input type="password" name="edit_password" id="edit_password" class="form-control" placeholder="O'zgartirish uchun kiriting">
                        <small style="color: var(--text-light); font-size: 0.75rem;">Bo'sh qoldirilsa parol o'zgarmaydi.</small>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="submit" name="edit_user" class="btn" style="flex: 2;">Saqlash</button>
                        <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="flex: 1;">Bekor qilish</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    </style>

    <script>
    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_password').value = '';
        document.getElementById('editUserModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }

    // Close on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('editUserModal');
        if (event.target == modal) {
            closeEditModal();
        }
    }
    </script>
</body>
</html>
