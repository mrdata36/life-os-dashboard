<?php
require '../config/db.php';

header('Content-Type: application/json');

$id = (int) ($_POST['id'] ?? 0);
$completed = (int) ($_POST['completed'] ?? 0);

if ($id > 0) {
    $status = $completed ? 'completed' : 'pending';
    $stmt = $pdo->prepare("UPDATE roadmap SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['status' => 'error']);