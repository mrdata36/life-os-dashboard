<?php
require_once '../config/db.php';

// ================================
// ADD NEW HABIT
// ================================
require_login();
$user_id = get_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'General');

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO habits (user_id, name, category) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $name, $category]);
    }

    header("Location: index.php");
    exit;
}