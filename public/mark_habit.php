<?php
require '../config/db.php';

header('Content-Type: application/json');

$habit_id = (int) $_POST['habit_id'];
$date = $_POST['date']; // Validate date format in a real pro app
$completed = (int) ($_POST['completed'] ?? 0);

// Upsert habit log
$stmt = $pdo->prepare("INSERT INTO habit_logs (habit_id, log_date, completed)
VALUES (?, ?, ?)
ON CONFLICT (habit_id, log_date) DO UPDATE SET completed=EXCLUDED.completed");
$stmt->execute([$habit_id, $date, $completed ? 'true' : 'false']);

echo json_encode(['status'=>'success']);
