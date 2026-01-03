<?php
// ================================
// GLOBAL SETTINGS
// ================================
session_start(); // Start session for Flash messages & CSRF

// ================================
// DATABASE CONNECTION
// ================================

// Database credentials (TEMPLATE)
$host = 'localhost';
$db   = 'task_monitor';
$user = 'YOUR_DB_USER';     // e.g., postgres
$pass = 'YOUR_DB_PASSWORD'; // e.g., secret
$charset = 'utf8';

// DSN string for PDO
$dsn = "pgsql:host=$host;dbname=$db";

try {
    // Create PDO instance
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Error handling
    error_log($e->getMessage());
    die("Database connection failed. Please check logs.");
}

// ================================
// HELPER FUNCTIONS (Security & UX)
// ================================

// Generate CSRF Token to protect forms
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF Token
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Set Flash Message (Success/Error)
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Display Flash Message
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

// Get User Currency Preference
function get_currency() {
    global $pdo;
    if (is_logged_in()) {
        try {
            $stmt = $pdo->prepare("SELECT currency FROM user_settings WHERE user_id = ?");
            $stmt->execute([get_user_id()]);
            $currency = $stmt->fetchColumn();
            if ($currency) return $currency;
        } catch (PDOException $e) {
            // Table might not exist yet, ignore error
        }
    }
    return 'TZS'; // Default
}
define('APP_CURRENCY', get_currency());
?>