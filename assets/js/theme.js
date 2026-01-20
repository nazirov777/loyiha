function toggleTheme() {
    const body = document.body;
    body.classList.toggle('dark-mode');
    
    // Save preference
    if (body.classList.contains('dark-mode')) {
        localStorage.setItem('theme', 'dark');
        document.getElementById('theme-icon').textContent = '☀️';
    } else {
        localStorage.setItem('theme', 'light');
        document.getElementById('theme-icon').textContent = '🌙';
    }
}

// Apply saved theme on load
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        const icon = document.getElementById('theme-icon');
        if(icon) icon.textContent = '☀️';
    }
});
