function toggleNotifications() {
    const dropdown = document.getElementById('notification-dropdown');
    dropdown.classList.toggle('active');
    if (dropdown.classList.contains('active')) {
        loadNotifications();
    }
}

function loadNotifications() {
    fetch('../api/notifications.php?action=fetch')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('notification-list');
            const count = document.getElementById('notification-unread-count');

            // Update count
            if (data.unreadCount > 0) {
                count.innerText = data.unreadCount;
                count.style.display = 'block';
            } else {
                count.style.display = 'none';
            }

            // Update list
            if (data.notifications.length === 0) {
                list.innerHTML = '<div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 0.85rem;">Bildirishnomalar yo\'q</div>';
            } else {
                list.innerHTML = data.notifications.map(n => `
                    <a href="${n.link}" onclick="markAsRead(${n.id})" class="notification-item ${n.is_read == 0 ? 'unread' : ''}">
                        <h5>${escapeHtml(n.title)}</h5>
                        <p>${escapeHtml(n.message)}</p>
                        <span class="time">${new Date(n.created_at).toLocaleString()}</span>
                    </a>
                `).join('');
            }
        });
}

function markAsRead(id) {
    fetch(`../api/notifications.php?action=mark_read&id=${id}`)
        .then(() => loadNotifications());
}

function markAllRead() {
    fetch('../api/notifications.php?action=mark_read')
        .then(() => loadNotifications());
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initial check
document.addEventListener('DOMContentLoaded', () => {
    loadNotifications();
    // Refresh every 30 seconds
    setInterval(loadNotifications, 30000);

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        const wrapper = document.querySelector('.notification-wrapper');
        const dropdown = document.getElementById('notification-dropdown');
        if (wrapper && !wrapper.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
});
