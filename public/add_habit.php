<?php
require_once '../config/db.php';

// ================================
// ADD NEW HABIT
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'General');

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO habits (name, category) VALUES (?, ?)");
        $stmt->execute([$name, $category]);
    }

    header("Location: index.php");
    exit;
}