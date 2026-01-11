<?php
require '../config/db.php';
require_login();
$user_id = get_user_id();

// Handle Add Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $desc = $_POST['description'];
    
    // Insert with user_id
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category, description, transaction_date) VALUES (?, ?, ?, ?, ?, CURRENT_DATE)");
    $stmt->execute([$user_id, $type, $amount, $category, $desc]);
    set_flash('success', 'Transaction added!');
    header("Location: finance.php");
    exit;
}

// Handle Add Savings Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $name = $_POST['name'];
    $target = $_POST['target'];
    $deadline = $_POST['deadline'];
    
    // Insert with user_id
    $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, name, target_amount, deadline) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $target, $deadline]);
    set_flash('success', 'Savings goal added!');
    header("Location: finance.php");
    exit;
}

// Fetch Data (FILTERED BY USER ID)
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 50");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id = ?");
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll();

// Calculate Totals
$income = 0;
$expense = 0;
foreach ($transactions as $t) {
    if ($t['type'] == 'income') $income += $t['amount'];
    else $expense += $t['amount'];
}
$balance = $income - $expense;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Finance - Life OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-home"></i> <?= __('nav_dashboard') ?></a>
        <a href="projects.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-briefcase"></i> <?= __('nav_projects') ?></a>
        <a href="roadmap.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-map"></i> <?= __('nav_roadmap') ?></a>
        <a href="mistakes.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-bug"></i> Analysis</a>
        <a href="finance.php" class="text-blue-600 font-bold border-b-2 border-blue-600"><i class="fas fa-wallet"></i> <?= __('nav_finance') ?></a>
        <a href="profile.php" class="text-gray-600 hover:text-blue-600 font-bold ml-auto"><i class="fas fa-user"></i> <?= __('nav_profile') ?></a>
    </nav>

    <?php display_flash(); ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-green-100 p-6 rounded-lg shadow border-l-4 border-green-500">
            <h3 class="text-green-800 font-bold"><?= __('income') ?></h3>
            <p class="text-2xl font-bold text-green-600">+<?= number_format($income) ?> <?= APP_CURRENCY ?></p>
        </div>
        <div class="bg-red-100 p-6 rounded-lg shadow border-l-4 border-red-500">
            <h3 class="text-red-800 font-bold"><?= __('expenses') ?></h3>
            <p class="text-2xl font-bold text-red-600">-<?= number_format($expense) ?> <?= APP_CURRENCY ?></p>
        </div>
        <div class="bg-blue-100 p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-blue-800 font-bold"><?= __('balance') ?></h3>
            <p class="text-2xl font-bold text-blue-600"><?= number_format($balance) ?> <?= APP_CURRENCY ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Transactions Section -->
        <div>
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h2 class="font-bold text-xl mb-4"><?= __('add_transaction') ?></h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_transaction" value="1">
                    <div class="grid grid-cols-2 gap-4">
                        <select name="type" class="border p-2 rounded w-full">
                            <option value="expense"><?= __('expenses') ?></option>
                            <option value="income"><?= __('income') ?></option>
                        </select>
                        <input type="number" name="amount" placeholder="<?= __('amount_placeholder') ?>" class="border p-2 rounded w-full" required>
                    </div>
                    <input type="text" name="category" placeholder="<?= __('category_placeholder') ?>" class="border p-2 rounded w-full" required>
                    <input type="text" name="description" placeholder="<?= __('description_placeholder') ?>" class="border p-2 rounded w-full">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded w-full font-bold hover:bg-blue-700"><?= __('save_transaction') ?></button>
                </form>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="font-bold text-xl mb-4"><?= __('recent_history') ?></h2>
                <div class="space-y-3">
                    <?php foreach($transactions as $t): ?>
                    <div class="flex justify-between items-center border-b pb-2">
                        <div>
                            <p class="font-bold text-gray-800"><?= htmlspecialchars($t['category']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($t['description']) ?> • <?= $t['transaction_date'] ?></p>
                        </div>
                        <span class="font-bold <?= $t['type'] == 'income' ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $t['type'] == 'income' ? '+' : '-' ?><?= number_format($t['amount']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Savings Goals Section -->
        <div>
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h2 class="font-bold text-xl mb-4"><?= __('new_savings_goal') ?></h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_goal" value="1">
                    <input type="text" name="name" placeholder="<?= __('goal_name_placeholder') ?>" class="border p-2 rounded w-full" required>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="number" name="target" placeholder="<?= __('target_amount_placeholder') ?>" class="border p-2 rounded w-full" required>
                        <input type="date" name="deadline" class="border p-2 rounded w-full">
                    </div>
                    <button class="bg-purple-600 text-white px-4 py-2 rounded w-full font-bold hover:bg-purple-700"><?= __('set_goal') ?></button>
                </form>
            </div>

            <div class="space-y-4">
                <?php foreach($goals as $g): 
                    $percent = $g['target_amount'] > 0 ? ($g['current_amount'] / $g['target_amount']) * 100 : 0;
                ?>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex justify-between mb-2">
                        <span class="font-bold"><?= htmlspecialchars($g['name']) ?></span>
                        <span class="text-sm text-gray-600"><?= number_format($g['current_amount']) ?> / <?= number_format($g['target_amount']) ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-purple-600 h-2.5 rounded-full" style="width: <?= min(100, $percent) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>