<?php
require_once '../config/db.php';

// ================================
// ADD NEW TASK
// ================================
require_login();
$user_id = get_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security Check
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Security Error: Invalid Request");
    }

    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, category) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $category]);

    set_flash('success', 'Task added successfully!');
    header("Location: index.php");
    exit;
}
?>
