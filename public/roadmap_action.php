<?php
require '../config/db.php';

require_login();
$user_id = get_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $topic = trim($_POST['topic']);
        $date = $_POST['date'];
        $desc = trim($_POST['description'] ?? '');
        
        if(!empty($topic) && !empty($date)) {
            $stmt = $pdo->prepare("INSERT INTO roadmap (user_id, topic, description, scheduled_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $topic, $desc, $date]);
        }
    }
    elseif ($action === 'complete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE roadmap SET status = 'completed' WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    }
    elseif ($action === 'reschedule') {
        $id = $_POST['id'];
        $reason = trim($_POST['reason']);
        $new_date = $_POST['new_date'];
        
        // Update: Set new date, save reason, increment missed count, reset status to pending
        $stmt = $pdo->prepare("UPDATE roadmap SET scheduled_date = ?, reason_missed = ?, missed_count = missed_count + 1, status = 'pending' WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_date, $reason, $id, $user_id]);
    }
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM roadmap WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    }

    header("Location: roadmap.php");
    exit;
}