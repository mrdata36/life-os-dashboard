<?php
require '../config/db.php';

header('Content-Type: application/json');

$task_id = (int) $_POST['task_id'];
$completed = (int) ($_POST['completed'] ?? 0);
$today = date('Y-m-d');

$stmt = $pdo->prepare("INSERT INTO task_logs (task_id, log_date, completed)
VALUES (?, ?, ?)
ON CONFLICT (task_id, log_date) DO UPDATE SET completed=EXCLUDED.completed");
$stmt->execute([$task_id, $today, $completed ? 'true' : 'false']);

echo json_encode(['status'=>'success']);
