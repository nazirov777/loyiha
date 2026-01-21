<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$groups = $pdo->query("SELECT * FROM `groups` ORDER BY name ASC")->fetchAll();

// Proactive Database Check
$db_error = false;
try {
    $pdo->query("SELECT group_id FROM messages LIMIT 1");
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') { // Column not found
        $db_error = true;
    }
}

// AJAX Handler: Get Messages
if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    $group_id = isset($_GET['group_id']) ? $_GET['group_id'] : null;
    $has_group_col = false;
    try { $pdo->query("SELECT group_id FROM messages LIMIT 1"); $has_group_col = true; } catch (PDOException $e) {}

    $sql = "SELECT m.id as msg_id, m.message, m.created_at, u.name, u.role, u.id as user_id" . ($has_group_col ? ", g.name as group_name" : "") . " 
            FROM messages m 
            JOIN users u ON m.user_id = u.id";
    if ($has_group_col) $sql .= " LEFT JOIN `groups` g ON m.group_id = g.id";
    if ($has_group_col && $group_id === 'all') { /* No filter */ } 
    elseif ($has_group_col && $group_id) $sql .= " WHERE m.group_id = :gid";
    else if ($has_group_col) $sql .= " WHERE m.group_id IS NULL";
    
    $sql .= " ORDER BY m.created_at ASC";
    $stmt = $pdo->prepare($sql);
    if ($has_group_col && $group_id && $group_id !== 'all') $stmt->bindParam(':gid', $group_id);
    $stmt->execute();
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

// Handle Admin Actions & Send Message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
        $msg_text = trim(isset($_POST['message']) ? $_POST['message'] : '');
        $group_id = isset($_POST['group_id']) ? $_POST['group_id'] : null;
        if (!empty($msg_text)) {
            $has_group_col = false;
            try { $pdo->query("SELECT group_id FROM messages LIMIT 1"); $has_group_col = true; } catch (PDOException $e) {}
            if ($has_group_col) {
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, message, group_id) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $msg_text, $group_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $msg_text]);
            }
            echo "Sent";
        } else { echo "Xabar bo'sh bo'lishi mumkin emas."; }
        exit;
    }
    if (isset($_POST['delete_msg'])) {
        $msg_id = $_POST['msg_id'];
        $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([$msg_id]);
        echo "Deleted"; exit;
    }
    if (isset($_POST['edit_msg'])) {
        $msg_id = $_POST['msg_id'];
        $new_text = trim($_POST['text']);
        $pdo->prepare("UPDATE messages SET message = ? WHERE id = ?")->execute([$new_text, $msg_id]);
        echo "Updated"; exit;
    }
    if (isset($_POST['clear_chat'])) {
        $group_id = $_POST['group_id'];
        try {
            if ($group_id == 'global') {
                $pdo->exec("DELETE FROM messages WHERE group_id IS NULL");
            } else {
                $pdo->prepare("DELETE FROM messages WHERE group_id = ?")->execute([$group_id]);
            }
            $message = "Chat tarixi tozalandi!";
        } catch (PDOException $e) {
            $message = "Xato: Ma'lumotlar bazasi yangilanmagan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Chat Boshqaruvi - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .msg-actions { display: flex; gap: 5px; font-size: 0.8rem; margin-top: 5px; }
        .msg-actions span { cursor: pointer; color: var(--secondary-color); text-decoration: underline; }
        .msg-actions .del { color: #ff4d4d; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($db_error): ?>
            <div class="alert alert-danger" style="background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>⚠️ DIQQAT: MA'LUMOTLAR BAZASI YANGILANMAGAN!</strong><br>
                Chat tizimi to'g'ri ishlashi uchun bazani yangilash zarur. <br><br>
                <a href="../update_db_v3.php" class="btn" style="background: #ef4444; color: white; text-decoration: none; display: inline-block; width: auto; padding: 10px 20px;">
                    BAZANI HOZIR YANGILASH
                </a>
            </div>
        <?php endif; ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Guruh Chatlarini Boshqarish</h3>
                <form method="POST" onsubmit="return confirm('Haqiqatdan ham ushbu guruh chatini butunlay tozalamoqchimisiz?');">
                    <input type="hidden" name="group_id" id="clear-group-id" value="global">
                    <button type="submit" name="clear_chat" class="btn" style="background: #ef4444; width: auto; font-size: 0.8rem;">Tarixni Tozalash</button>
                </form>
            </div>

            <div class="form-group">
                <label>Guruhni tanlang:</label>
                <select id="group-selector" class="form-control" onchange="updateGroup()">
                    <option value="all">Barcha xabarlar</option>
                    <option value="global" selected>Umumiy Chat</option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="chat-container" style="height: 400px; border: 1px solid var(--border-color); border-radius: 10px; padding: 15px; overflow-y: auto; background: var(--bg-card); margin-bottom: 15px;">
                <div id="admin-chat-box">
                    <!-- Messages will load here -->
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <input type="text" id="admin-msg-input" class="form-control" placeholder="Javob yozish..." autocomplete="off">
                <button id="admin-send-btn" class="btn" style="width: auto;">Yuborish</button>
            </div>
        </div>
        <div class="card" style="margin-top: 20px;">
            <h3>Xabarlar Tarixi (Batafsil)</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #ddd;">
                            <th style="padding: 10px;">Foydalanuvchi</th>
                            <th style="padding: 10px;">Xabar</th>
                            <th style="padding: 10px;">Vaqt</th>
                            <th style="padding: 10px;">Amal</th>
                        </tr>
                    </thead>
                    <tbody id="history-table-body">
                        <!-- History rows will load here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let currentGroup = 'global';

        function updateGroup() {
            currentGroup = $('#group-selector').val();
            $('#clear-group-id').val(currentGroup);
            loadMessages();
        }

        function loadMessages() {
            let url = 'achat.php?action=get_messages';
            if (currentGroup !== 'global') {
                url += '&group_id=' + currentGroup;
            }
            $.get(url, function(data) {
                let messages = (typeof data === 'object') ? data : JSON.parse(data);
                let chatHtml = '';
                let tableHtml = '';
                messages.forEach(msg => {
                    let groupContext = msg.group_name ? ` [${msg.group_name}]` : ' [Global]';
                    if (currentGroup !== 'all') groupContext = '';

                    // Escape message for safety
                    let safeMsg = $('<div>').text(msg.message).html().replace(/'/g, "&#39;").replace(/"/g, "&quot;");
                    let safeMsgForPrompt = msg.message.replace(/\\/g, '\\\\').replace(/`/g, '\\`').replace(/\$/g, '\\$');

                    // Chat Box Html
                    chatHtml += `<div class="message others" style="margin-left: 0; width: 100%; max-width: 100%;">
                                <div class="message-info"><strong>${msg.name}</strong> (${msg.role})${groupContext} - ${msg.created_at}</div>
                                <div id="msg-text-${msg.msg_id}">${safeMsg}</div>
                                <div class="msg-actions">
                                    <span onclick="editMsg(${msg.msg_id}, \`${safeMsgForPrompt}\`)">✏️ Tahrirlash</span>
                                    <span class="del" onclick="deleteMsg(${msg.msg_id})">🗑️ O'chirish</span>
                                </div>
                             </div>`;
                    
                    // Table History Html
                    tableHtml += `<tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;"><strong>${msg.name}</strong><br><small>${msg.role}</small></td>
                                    <td style="padding: 10px;">${safeMsg}${groupContext ? '<br><small style="color:blue">'+groupContext+'</small>' : ''}</td>
                                    <td style="padding: 10px;">${msg.created_at}</td>
                                    <td style="padding: 10px;">
                                        <button class="btn" style="background:none; color:#ff4d4d; border:none; cursor:pointer;" onclick="deleteMsg(${msg.msg_id})">🗑️</button>
                                    </td>
                                  </tr>`;
                });
                $('#admin-chat-box').html(chatHtml);
                $('#history-table-body').html(tableHtml);
                
                var box = document.getElementById("admin-chat-box").parentElement;
                box.scrollTop = box.scrollHeight;
            }).fail(function(err) {
                console.error("Messages load failed:", err);
            });
        }

        function deleteMsg(id) {
            if(confirm('Ushbu xabarni o\'chirmoqchimisiz?')) {
                $.post('achat.php', {delete_msg: 1, msg_id: id}, function() {
                    loadMessages();
                });
            }
        }

        function editMsg(id, oldText) {
            let newText = prompt('Xabarni tahrirlang:', oldText);
            if (newText !== null && newText.trim() !== '') {
                $.post('achat.php', {edit_msg: 1, msg_id: id, text: newText}, function() {
                    loadMessages();
                });
            }
        }

        $('#admin-send-btn').click(function() {
            let msg = $('#admin-msg-input').val();
            if(msg.trim() !== '') {
                if (currentGroup === 'all') {
                    alert('Iltimos, javob yozish uchun aniq bir guruhni yoki Umumiy chatni tanlang.');
                    return;
                }
                let data = {action: 'send_message', message: msg};
                if (currentGroup !== 'global' && currentGroup !== 'all') {
                    data.group_id = currentGroup;
                }
                $.post('achat.php', data, function(res) {
                    if (res === 'Sent') {
                        $('#admin-msg-input').val('');
                        loadMessages();
                    } else {
                        alert('Xatolik: ' + res);
                    }
                }).fail(function() {
                    alert('Server bilan aloqa uzildi. Iltimos qaytadan urinib ko\'ring.');
                });
            }
        });

        loadMessages();
        setInterval(loadMessages, 3000);
    </script>
</body>
</html>
