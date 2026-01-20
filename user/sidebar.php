<?php require_once '../config/lang_init.php'; ?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🎓</span>
        <span class="brand-text"><?= $lang['system_title'] ?></span>
    </div>
    <div style="padding: 0 1.5rem; margin-bottom: 1rem; display: flex; justify-content: flex-end;" class="mobile-only">
        <button class="mobile-toggle" onclick="toggleSidebar()" style="font-size: 1.2rem; color: #64748b;">✕</button>
    </div>

    <div class="sidebar-menu" style="flex: 1; overflow-y: auto; padding: 0.5rem 1.5rem 2rem;">
        <a href="dashboard.php" title="<?= $lang['home'] ?>"><i>🏠</i><span><?= $lang['home'] ?></span></a>
        <a href="grades.php" title="O'zlashtirish"><i>📊</i><span>O'zlashtirish</span></a>
        <a href="schedule.php" title="Dars Jadvali"><i>📅</i><span>Dars Jadvali</span></a>
        <a href="tasks.php" title="<?= $lang['tasks'] ?>"><i>📝</i><span><?= $lang['tasks'] ?></span></a>
        <a href="videos.php" title="<?= $lang['videos'] ?>"><i>📹</i><span><?= $lang['videos'] ?></span></a>
        <a href="articles.php" title="<?= $lang['manuals'] ?>"><i>📚</i><span><?= $lang['manuals'] ?></span></a>
        <a href="projects.php" title="<?= $lang['projects'] ?>"><i>🚀</i><span><?= $lang['projects'] ?></span></a>
        <a href="contracts.php" title="<?= $lang['contracts'] ?>"><i>📄</i><span><?= $lang['contracts'] ?></span></a>
        <a href="chat.php" title="<?= $lang['chat'] ?>"><i>💬</i><span><?= $lang['chat'] ?></span></a>
        <a href="../logout.php" title="<?= $lang['logout'] ?>"><i>🚪</i><span><?= $lang['logout'] ?></span></a>
    </div>
</div>
<div class="header">
    <div style="display: flex; align-items: center; gap: 15px;">
        <button class="mobile-toggle" onclick="toggleSidebar()">☰</button>
        <button class="desktop-toggle" onclick="toggleDesktopSidebar()">☰</button>
        <h2><?= $lang['student_panel'] ?></h2>
    </div>
    <div style="display: flex; align-items: center; margin-left: auto; gap: 20px;">
        <!-- Language Switcher -->
        <div class="lang-dropdown">
            <select onchange="changeLanguage(this.value)">
                <option value="uz" <?= $_SESSION['lang']=='uz'?'selected':'' ?>>🇺🇿 O'zbekcha</option>
                <option value="ru" <?= $_SESSION['lang']=='ru'?'selected':'' ?>>🇷🇺 Русский</option>
                <option value="en" <?= $_SESSION['lang']=='en'?'selected':'' ?>>🇬🇧 English</option>
            </select>
        </div>

        <button class="theme-toggle" onclick="toggleTheme()">
            <span id="theme-icon">🌙</span>
        </button>

        <!-- Notifications -->
        <div class="notification-wrapper">
            <div class="notification-bell" onclick="toggleNotifications()">
                🔔
                <span class="notification-count" id="notification-unread-count" style="display: none;">0</span>
            </div>
            <div class="notification-dropdown" id="notification-dropdown">
                <div class="notification-header">
                    <h4>Bildirishnomalar</h4>
                    <button onclick="markAllRead()" style="background: none; border: none; color: var(--primary-color); font-size: 0.75rem; cursor: pointer;">Hammasini o'qilgan qilish</button>
                </div>
                <div class="notification-list" id="notification-list">
                    <!-- Loaded via JS -->
                </div>
                <div class="notification-footer">
                    <a href="#">Barcha bildirishnomalar</a>
                </div>
            </div>
        </div>

        <span class="user-info"><?= $lang['welcome'] ?>, <?= htmlspecialchars($_SESSION['name']) ?></span>
    </div>
</div>
<script src="../assets/js/theme.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
function changeLanguage(lang) {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
}
</script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}
function toggleDesktopSidebar() {
    document.body.classList.toggle('sidebar-collapsed');
    if (document.body.classList.contains('sidebar-collapsed')) {
        localStorage.setItem('sidebar-collapsed', 'true');
    } else {
        localStorage.setItem('sidebar-collapsed', 'false');
    }
}
if(localStorage.getItem('sidebar-collapsed') === 'true') {
    document.body.classList.add('sidebar-collapsed');
}
</script>
