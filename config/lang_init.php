<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'uz';
}

// Change language if requested
if (isset($_GET['lang'])) {
    if (in_array($_GET['lang'], ['uz', 'en', 'ru'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    // Remove lang param from URL to clean it up (optional, but good for UX)
    // header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    // exit;
}

// Load language file
$lang_file = __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    require $lang_file;
} else {
    require __DIR__ . '/../lang/uz.php';
}
?>
