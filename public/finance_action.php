<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security Check
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Security Error: Invalid Request");
    }

    $action = $_POST['action'] ?? '';

    // Add Transaction (Income or Expense)
    if ($action === 'add_transaction') {
        $type = $_POST['type']; // income or expense
        $amount = (float) $_POST['amount'];
        $category = trim($_POST['category']);
        $desc = trim($_POST['description']);
        $date = $_POST['date'] ?: date('Y-m-d');

        if ($amount > 0) {
            $stmt = $pdo->prepare("INSERT INTO transactions (type, amount, category, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$type, $amount, $category, $desc, $date]);
            set_flash('success', 'Transaction added successfully!');
        }
    }
    // Add Savings Goal
    elseif ($action === 'add_goal') {
        $name = trim($_POST['name']);
        $target = (float) $_POST['target'];
        $current = (float) ($_POST['current'] ?? 0);
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

        if (!empty($name) && $target > 0) {
            $stmt = $pdo->prepare("INSERT INTO savings_goals (name, target_amount, current_amount, deadline) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $target, $current, $deadline]);
            set_flash('success', 'Savings goal created!');
        }
    }
    // Update Goal Amount (Add savings to a goal)
    elseif ($action === 'update_goal') {
        $id = (int) $_POST['id'];
        $amount_added = (float) $_POST['amount_added'];
        
        if ($id > 0 && $amount_added != 0) {
            $stmt = $pdo->prepare("UPDATE savings_goals SET current_amount = current_amount + ? WHERE id = ?");
            $stmt->execute([$amount_added, $id]);
            set_flash('success', 'Goal updated!');
        }
    }

    header("Location: finance.php");
    exit;
}