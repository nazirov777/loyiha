<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';
require '../config/notifications_helper.php';

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['action']) || isset($_POST['action'])) {
        echo "Unauthorized"; exit;
    }
    header("Location: ../login.php");
    exit;
}

// Ensure only teachers can access
if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit;
}

$my_id = $_SESSION['user_id'];

// AJAX Handler: Get Messages
if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
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
        if ($has_group_col) {
            $sql .= " WHERE m.group_id IS NULL";
        }
    }
    $sql .= " ORDER BY m.created_at ASC";
    $stmt = $pdo->prepare($sql);
    if ($has_group_col && $group_id && $group_id !== 'all') {
        $stmt->bindParam(':gid', $group_id);
    }
    $stmt->execute();
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

// AJAX Handler: Send Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $message = trim(isset($_POST['message']) ? $_POST['message'] : '');
    $group_id = isset($_POST['group_id']) ? $_POST['group_id'] : null;

    if (!empty($message)) {
        try {
            $has_group_col = false;
            try { $pdo->query("SELECT group_id FROM messages LIMIT 1"); $has_group_col = true; } catch (PDOException $e) {}

            if ($has_group_col) {
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, message, group_id) VALUES (?, ?, ?)");
                $stmt->execute([$my_id, $message, $group_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
                $stmt->execute([$my_id, $message]);
            }
            
            $sender_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'O\'qituvchi';
            $context = $group_id ? "Guruhda" : "Umumiy chatda";
            notifyAdmins($pdo, "Yangi Xabar! 💬", "$sender_name $context yangi xabar yubordi: " . mb_substr($message, 0, 50) . "...", "achat.php");
            
            echo "Sent";
        } catch (PDOException $e) { echo "Baza xatosi: " . $e->getMessage(); }
    } else { echo "Xabar bo'sh bo'lishi mumkin emas."; }
    exit;
}

// Teacher sees groups they are assigned to teach
$stmt = $pdo->prepare("SELECT DISTINCT g.* FROM `groups` g JOIN teacher_assignments ta ON g.id = ta.group_id WHERE ta.teacher_id = ?");
$stmt->execute([$my_id]);
$my_groups = $stmt->fetchAll();

$db_error = false;
try {
    $pdo->query("SELECT group_id FROM messages LIMIT 1");
} catch (PDOException $e) {
    if ($e->getCode() == '42S22') { $db_error = true; }
}

$default_group = !empty($my_groups) ? $my_groups[0]['id'] : 'global';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Guruh Chat - EduVision</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="card">
            <div class="chat-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                <button class="btn tab-btn active" onclick="switchChat('global')" id="tab-global" style="width: auto; background: var(--primary-color); padding: 8px 20px;">🌍 Umumiy Chat</button>
                <button class="btn tab-btn" onclick="switchChat('group')" id="tab-group" style="width: auto; background: #64748b; padding: 8px 20px;">👥 Guruh Chati</button>
            </div>

            <?php if ($db_error): ?>
                <div class="alert alert-danger" style="background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>⚠️ Texnik xatolik:</strong> Ma'lumotlar bazasi yangilanmagan. Iltimos, administratorga xabar bering.
                </div>
            <?php endif; ?>
            
            <!-- Teacher: Group Selector -->
            <div id="group-selection-area" style="display: none; margin-bottom: 15px;">
                <label>Guruhni tanlang:</label>
                <select id="group-selector" class="form-control" onchange="updateGroup()">
                    <?php if (empty($my_groups)): ?>
                        <option value="">Guruh biriktirilmagan</option>
                    <?php else: ?>
                        <?php foreach ($my_groups as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="chat-container">
                <div class="chat-messages" id="chat-box" style="height: 400px; overflow-y: auto; border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; background: var(--bg-card); margin-bottom: 20px;">
                    <!-- Messages will load here -->
                </div>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="message-input" class="form-control" placeholder="Xabar yozing..." autocomplete="off">
                    <button id="send-btn" class="btn" style="width: auto;">Yuborish</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const myId = <?= $my_id ?>;
        let currentChatMode = 'global'; // 'global' or 'group'
        let currentGroupId = <?= !empty($my_groups) ? $my_groups[0]['id'] : 'null' ?>;

        function scrollBottom() {
            var chatBox = document.getElementById("chat-box");
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function switchChat(mode) {
            currentChatMode = mode;
            $('.tab-btn').removeClass('active').css('background', '#64748b');
            $(`#tab-${mode}`).addClass('active').css('background', 'var(--primary-color)');

            if (mode === 'group') {
                $('#group-selection-area').show();
            } else {
                $('#group-selection-area').hide();
            }

            loadMessages();
        }

        function updateGroup() {
            currentGroupId = $('#group-selector').val();
            loadMessages();
        }

        function loadMessages() {
            let url = 'ochat.php?action=get_messages';
            let activeId = (currentChatMode === 'global') ? null : currentGroupId;
            
            if (activeId) {
                url += '&group_id=' + activeId;
            }
            
            $.get(url, function(data) {
                let messages = (typeof data === 'object') ? data : JSON.parse(data);
                let html = '';
                messages.forEach(msg => {
                    let className = (msg.user_id == myId) ? 'mine' : 'others';
                    let safeMsg = $('<div>').text(msg.message).html();
                    html += `<div class="message ${className}">
                                <div class="message-info"><strong>${msg.name}</strong> (${msg.role}) - ${msg.created_at}</div>
                                <div>${safeMsg}</div>
                             </div>`;
                });
                $('#chat-box').html(html);
                scrollBottom();
            }).fail(function() {
                console.error("Messages load failed");
            });
        }

        $('#send-btn').click(function() {
            let msg = $('#message-input').val();
            let activeId = (currentChatMode === 'global') ? null : currentGroupId;

            if (currentChatMode === 'group' && !activeId) {
                alert('Iltimos, avval guruhni tanlang.');
                return;
            }

            if(msg.trim() !== '') {
                let data = {action: 'send_message', message: msg};
                if (activeId) {
                    data.group_id = activeId;
                }
                $.post('ochat.php', data, function(res) {
                    if (res === 'Sent') {
                        $('#message-input').val('');
                        loadMessages();
                    } else {
                        alert('Xatolik: ' + res);
                    }
                }).fail(function() {
                    alert('Server bilan aloqa uzildi.');
                });
            }
        });

        loadMessages();
        setInterval(loadMessages, 3000);
    </script>
</body>
</html>
