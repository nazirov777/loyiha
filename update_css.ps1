$path = "d:\OpenServer\OSPanel\domains\loyiha\assets\css\style.css"
$content = Get-Content $path -Raw

# 1. Update Design Tokens (Glassmorphism & Fixed Contrast)
$rootOld = ':root \{[\s\S]*?--header-height: 80px;\s*?\}'
$rootNew = ':root {
    --primary-color: #4361ee;
    --primary-hover: #3a56d4;
    --primary-soft: rgba(67, 97, 238, 0.1);
    --secondary-color: #f8faff;
    --sidebar-bg: #ffffff;
    --sidebar-text: #64748b;
    --sidebar-active: #4361ee;
    --sidebar-active-bg: rgba(67, 97, 238, 0.08);
    --text-dark: #0f172a;
    --text-light: #64748b;
    --danger: #ef4444;
    --success: #10b981;
    --warning: #f59e0b;
    --white: #ffffff;
    --border-color: #e2e8f0;
    --bg-card: #ffffff;
    --glass-bg: rgba(255, 255, 255, 0.7);
    --glass-border: rgba(255, 255, 255, 0.3);
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.02);
    --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
    --shadow-premium: 0 20px 40px -10px rgba(67, 97, 238, 0.2);
    --radius-md: 16px;
    --radius-lg: 24px;
    --sidebar-width: 280px;
    --header-height: 80px;
}'
$content = [regex]::Replace($content, $rootOld, $rootNew)

# 2. Update Dark Mode Tokens
$darkOld = 'body\.dark-mode \{[\s\S]*?--glass-border: rgba\(255, 255, 255, 0\.1\);\s*?\}'
$darkNew = 'body.dark-mode {
    --secondary-color: #0f172a;
    --sidebar-bg: #1e293b;
    --text-dark: #f8fafc;
    --text-light: #94a3b8;
    --sidebar-text: #cbd5e1;
    --sidebar-active-bg: rgba(255, 255, 255, 0.05);
    --bg-card: #1e293b;
    --border-color: #334155;
    --glass-bg: rgba(30, 41, 59, 0.7);
    --glass-border: rgba(255, 255, 255, 0.1);
}'
$content = [regex]::Replace($content, $darkOld, $darkNew)

# 3. Update Sidebar Brand (3D Logo)
$brandOld = '\.sidebar-brand \{[\s\S]*?\.sidebar-brand::before \{[\s\S]*?\}'
$brandNew = '.sidebar-brand {
    padding: 2.5rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-icon {
    font-size: 2.2rem;
    display: inline-block;
    animation: rotate3d 10s linear infinite;
    transform-style: preserve-3d;
    filter: drop-shadow(0 0 15px var(--primary-soft));
}

@keyframes rotate3d {
    0% { transform: perspective(1000px) rotateY(0deg); }
    100% { transform: perspective(1000px) rotateY(360deg); }
}

.brand-text {
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-color), #7209b7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -1px;
}'
$content = [regex]::Replace($content, $brandOld, $brandNew)

# 4. Redesign Notification Dropdown
$notifyOld = '\.notification-dropdown \{[\s\S]*?\.notification-dropdown\.active \{[\s\S]*?\}'
$notifyNew = '.notification-dropdown {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    width: 380px;
    background: var(--glass-bg);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: none;
    flex-direction: column;
    z-index: 1001;
    overflow: hidden;
    animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

body.dark-mode .notification-dropdown {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(255, 255, 255, 0.1);
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.notification-dropdown.active {
    display: flex;
}'
$content = [regex]::Replace($content, $notifyOld, $notifyNew)

# 5. Fix Notification Header & Items
$headerOld = '\.notification-header \{[\s\S]*?\.notification-header h4 \{[\s\S]*?\}'
$headerNew = '.notification-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: transparent;
}

.notification-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-dark);
}

.notification-header a {
    font-size: 0.85rem;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
    transition: 0.2s;
}

.notification-header a:hover {
    text-decoration: underline;
}

.notification-list {
    max-height: 420px;
    overflow-y: auto;
}

.notification-item {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    text-decoration: none;
    color: var(--text-dark);
    display: flex;
    gap: 15px;
    transition: all 0.2s;
    background: transparent;
}

.notification-item:hover {
    background: rgba(67, 97, 238, 0.05);
}

.notification-item.unread {
    background: rgba(67, 97, 238, 0.08);
}

.notification-item.unread::before {
    content: "";
    width: 6px;
    height: 6px;
    background: var(--primary-color);
    border-radius: 50%;
    margin-top: 8px;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-item h5 {
    margin: 0 0 4px 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-dark);
}

.notification-item p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--text-light);
    line-height: 1.5;
}

.notification-item .time {
    font-size: 0.75rem;
    color: var(--text-light);
    opacity: 0.7;
    margin-top: 6px;
    display: block;
}

.notification-footer {
    padding: 1rem;
    text-align: center;
    border-top: 1px solid var(--border-color);
    background: rgba(67, 97, 238, 0.03);
}

.notification-footer a {
    font-size: 0.9rem;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}'
$content = [regex]::Replace($content, $headerOld, $headerNew)

$content | Set-Content $path -NoNewline
