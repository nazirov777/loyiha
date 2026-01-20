<?php
session_start();
require '../config/db.php';
require '../config/lang_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$my_id = $_SESSION['user_id'];

// Fetch groups based on role
if ($_SESSION['role'] === 'teacher') {
    // Teacher sees groups they are assigned to teach
    $stmt = $pdo->prepare("SELECT DISTINCT g.* FROM `groups` g JOIN teacher_assignments ta ON g.id = ta.group_id WHERE ta.teacher_id = ?");
} elseif ($_SESSION['role'] === 'admin') {
    // Admin sees all groups
    $stmt = $pdo->query("SELECT * FROM `groups` ORDER BY name");
    $my_groups = $stmt->fetchAll();
} else {
    // Student sees groups they are enrolled in
    $stmt = $pdo->prepare("SELECT g.* FROM `groups` g JOIN user_groups ug ON g.id = ug.group_id WHERE ug.user_id = ?");
}

if ($_SESSION['role'] !== 'admin') {
    $stmt->execute([$my_id]);
    $my_groups = $stmt->fetchAll();
}

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
            
            <?php if ($_SESSION['role'] === 'student'): ?>
                <!-- Student: Fixed Group Display -->
                <?php if (!empty($my_groups)): ?>
                    <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <span style="color: var(--text-light); font-size: 0.9rem;">Sizning guruhingiz:</span>
                        <span class="badge badge-primary" style="font-size: 1rem;"><?= htmlspecialchars($my_groups[0]['name']) ?></span>
                        <input type="hidden" id="group-selector" value="<?= $my_groups[0]['id'] ?>">
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-light); margin-bottom: 15px;">Siz hali hech qanday guruhga a'zo emassiz.</p>
                <?php endif; ?>
            <?php else: ?>
                <!-- Admin/Teacher: Group Selector -->
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
            <?php endif; ?>

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

            const isStudent = <?= ($_SESSION['role'] === 'student') ? 'true' : 'false' ?>;
            if (mode === 'group' && !isStudent) {
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
            let url = 'get_messages.php';
            let activeId = (currentChatMode === 'global') ? null : currentGroupId;
            
            if (activeId) {
                url += '?group_id=' + activeId;
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
                let data = {message: msg};
                if (activeId) {
                    data.group_id = activeId;
                }
                $.post('send_message.php', data, function(res) {
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
