<?php
require '../config/db.php';
require_login();
$user_id = get_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $start = $_POST['start_date'] ?: null;
        $end = $_POST['end_date'] ?: null;

        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO projects (user_id, name, description, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $desc, $start, $end]);
            set_flash('success', 'Project created successfully!');
        }
        header("Location: projects.php");
    }
    elseif ($action === 'update_project') {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $start = $_POST['start_date'] ?: null;
        $end = $_POST['end_date'] ?: null;
        $status = $_POST['status'];

        if ($name) {
            $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, start_date = ?, end_date = ?, status = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $desc, $start, $end, $status, $id, $user_id]);
            set_flash('success', 'Project updated successfully!');
        }
        header("Location: project_details.php?id=$id");
    }
    elseif ($action === 'add_milestone') {
        $project_id = $_POST['project_id'];
        $title = trim($_POST['title']);
        $desc = trim($_POST['description'] ?? '');
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        if ($stmt->fetch() && $title) {
            $stmt = $pdo->prepare("INSERT INTO project_milestones (project_id, title, description, due_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$project_id, $title, $desc, $due_date]);
        }
        header("Location: project_details.php?id=$project_id");
    }
    elseif ($action === 'complete_milestone') {
        $id = $_POST['id'];
        $project_id = $_POST['project_id'];
        $notes = trim($_POST['completion_notes']);
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE project_milestones SET status = 'completed', completion_notes = ? WHERE id = ?");
            $stmt->execute([$notes, $id]);
        }
        header("Location: project_details.php?id=$project_id");
    }
    elseif ($action === 'reopen_milestone') {
        $id = $_POST['id'];
        $project_id = $_POST['project_id'];
        
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE project_milestones SET status = 'pending' WHERE id = ?");
            $stmt->execute([$id]);
        }
        header("Location: project_details.php?id=$project_id");
    }
    elseif ($action === 'delete_milestone') {
        $id = $_POST['id'];
        $project_id = $_POST['project_id'];
        
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM project_milestones WHERE id = ?");
            $stmt->execute([$id]);
        }
        header("Location: project_details.php?id=$project_id");
    }
    elseif ($action === 'delete_project') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        set_flash('success', 'Project deleted.');
        header("Location: projects.php");
    }
    elseif ($action === 'add_update') {
        $project_id = $_POST['project_id'];
        $update_text = trim($_POST['update_text']);
        $remaining = trim($_POST['remaining_work']);
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        if ($stmt->fetch() && $update_text) {
            $stmt = $pdo->prepare("INSERT INTO project_updates (project_id, user_id, update_text, remaining_work) VALUES (?, ?, ?, ?)");
            $stmt->execute([$project_id, $user_id, $update_text, $remaining]);
        }
        header("Location: project_details.php?id=$project_id");
    }
    else {
        header("Location: projects.php");
    }
    exit;
}