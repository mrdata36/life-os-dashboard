<?php
// ================================
// GLOBAL SETTINGS
// ================================
session_start(); 

// Load Translations
require_once __DIR__ . '/translations.php';

// ================================
// DATABASE CONNECTION (FIXED FOR RENDER)
// ================================

// 1. Jaribu kusoma DATABASE_URL kutoka Render
$db_url = getenv('DATABASE_URL');

if ($db_url) {
    // MAZINGIRA YA RENDER (Production)
    $db_parts = parse_url($db_url);
    
    $host = $db_parts['host'];
    $port = $db_parts['port'] ?? 5432;
    $user = $db_parts['user'];
    $pass = $db_parts['pass'];
    $db   = ltrim($db_parts['path'], '/');
} else {
    // MAZINGIRA YA LOCALHOST (Ubuntu yako)
    $host = 'localhost';
    $db   = 'task_monitor';
    $user = 'postgres'; 
    $pass = 'mrdata@123'; 
    $port = 5432;
}

// DSN string for PDO
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    // Kama unataka kuona error halisi wakati wa kutengeneza, unaweza kutoa maoni (uncomment) hapa chini:
    // die("Connection failed: " . $e->getMessage()); 
    die("Database connection failed. Please check logs.");
}

// ================================
// HELPER FUNCTIONS (Security & UX)
// ================================

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function display_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $color = $flash['type'] === 'success' ? 'green' : 'red';
        echo "<div class='bg-{$color}-100 border border-{$color}-400 text-{$color}-700 px-4 py-3 rounded relative mb-6 shadow-sm' role='alert'>
                <strong class='font-bold'>" . ucfirst($flash['type']) . "!</strong>
                <span class='block sm:inline'>{$flash['message']}</span>
              </div>";
    }
}

// ================================
// AUTH FUNCTIONS
// ================================
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_currency() {
    global $pdo;
    if (is_logged_in()) {
        try {
            $stmt = $pdo->prepare("SELECT currency FROM user_settings WHERE user_id = ?");
            $stmt->execute([get_user_id()]);
            $currency = $stmt->fetchColumn();
            if ($currency) return $currency;
        } catch (PDOException $e) {}
    }
    return 'TZS'; 
}
define('APP_CURRENCY', get_currency());

function get_language() {
    global $pdo;
    if (is_logged_in()) {
        try {
            $stmt = $pdo->prepare("SELECT language FROM user_settings WHERE user_id = ?");
            $stmt->execute([get_user_id()]);
            $lang = $stmt->fetchColumn();
            if ($lang) return $lang;
        } catch (PDOException $e) {}
    }
    return 'sw'; 
}

function __($key) {
    global $translations;
    $lang = get_language();
    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
}
?>