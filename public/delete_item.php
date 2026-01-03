<?php
require '../config/db.php';

header('Content-Type: application/json');

$type = $_POST['type'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

if ($id > 0 && in_array($type, ['habit', 'task', 'transaction'])) {
    $table = ($type === 'habit') ? 'habits' : (($type === 'task') ? 'tasks' : 'transactions');
    // Prepare statement to prevent SQL injection
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);