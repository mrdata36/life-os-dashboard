<?php
require '../config/db.php';

// Fetch Totals
try {
    $income = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'income'")->fetchColumn() ?: 0;
    $expense = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'expense'")->fetchColumn() ?: 0;
    $balance = $income - $expense;

    // Fetch Recent Transactions
    $transactions = $pdo->query("SELECT * FROM transactions ORDER BY transaction_date DESC, id DESC LIMIT 10")->fetchAll();

    // Fetch Goals
    $goals = $pdo->query("SELECT * FROM savings_goals ORDER BY id ASC")->fetchAll();

    // NEW: Fetch Expenses by Category for Chart
    $stmt = $pdo->query("SELECT category, SUM(amount) as total FROM transactions WHERE type = 'expense' GROUP BY category");
    $expense_chart_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    if ($e->getCode() === '42P01') {
        die("<div class='p-10 text-center'><strong>Finance tables missing.</strong><br><a href='install.php' class='text-blue-600 underline'>Update Database</a></div>");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 p-6">

    <!-- Navigation -->
    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow overflow-x-auto">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold whitespace-nowrap"><i class="fas fa-home"></i> Dashboard</a>
        <a href="roadmap.php" class="text-gray-600 hover:text-blue-600 font-bold whitespace-nowrap"><i class="fas fa-map"></i> Roadmap</a>
        <a href="finance.php" class="text-blue-600 font-bold border-b-2 border-blue-600 whitespace-nowrap"><i class="fas fa-wallet"></i> Finance</a>
        <a href="report.php" class="text-gray-600 hover:text-blue-600 font-bold whitespace-nowrap"><i class="fas fa-chart-pie"></i> Report</a>
    </nav>

    <!-- Flash Messages -->
    <?php display_flash(); ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded shadow border-l-4 border-green-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase">Total Balance</h3>
            <div class="text-3xl font-bold text-gray-800"><?= number_format($balance, 2) ?> <small class="text-sm"><?= APP_CURRENCY ?></small></div>
        </div>
        <div class="bg-white p-6 rounded shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase">Total Income</h3>
            <div class="text-3xl font-bold text-blue-600">+<?= number_format($income, 2) ?> <small class="text-sm"><?= APP_CURRENCY ?></small></div>
        </div>
        <div class="bg-white p-6 rounded shadow border-l-4 border-red-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase">Total Expenses</h3>
            <div class="text-3xl font-bold text-red-600">-<?= number_format($expense, 2) ?> <small class="text-sm"><?= APP_CURRENCY ?></small></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT: Add Transaction & Recent List -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Add Transaction Form -->
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-bold mb-4 text-gray-700">Add Transaction</h2>
                <form action="finance_action.php" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-2">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="add_transaction">
                    <select name="type" class="border p-2 rounded bg-gray-50">
                        <option value="expense">Expense (-)</option>
                        <option value="income">Income (+)</option>
                    </select>
                    <input type="number" step="0.01" name="amount" placeholder="Amount" class="border p-2 rounded" required>
                    <input type="text" name="category" placeholder="Category (e.g. Food)" class="border p-2 rounded" required>
                    <input type="text" name="description" placeholder="Description" class="border p-2 rounded">
                    <button type="submit" class="bg-gray-800 text-white p-2 rounded hover:bg-black">Add</button>
                </form>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-bold mb-4 text-gray-700">Recent Transactions</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Desc</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $t): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $t['transaction_date'] ?></td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($t['category']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($t['description']) ?></td>
                                <td class="px-4 py-3 text-right font-bold <?= $t['type'] == 'income' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $t['type'] == 'income' ? '+' : '-' ?><?= number_format($t['amount'], 2) ?> <?= APP_CURRENCY ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button class="delete-btn text-gray-300 hover:text-red-500" data-type="transaction" data-id="<?= $t['id'] ?>"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT: Savings Goals -->
        <div class="space-y-6">
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-bold mb-4 text-gray-700"><i class="fas fa-piggy-bank text-pink-500 mr-2"></i>Savings Goals</h2>
                
                <!-- Add Goal Form -->
                <form action="finance_action.php" method="POST" class="mb-6 p-3 bg-gray-50 rounded border">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="add_goal">
                    <div class="space-y-2">
                        <input type="text" name="name" placeholder="Goal Name (e.g. New Laptop)" class="w-full border p-2 rounded text-sm" required>
                        <div class="flex gap-2">
                            <input type="number" name="target" placeholder="Target Amount" class="w-full border p-2 rounded text-sm" required>
                            <button type="submit" class="bg-pink-500 text-white px-3 rounded text-sm font-bold">+</button>
                        </div>
                    </div>
                </form>

                <!-- Goals List -->
                <div class="space-y-4">
                    <?php foreach($goals as $goal): 
                        $percent = $goal['target_amount'] > 0 ? min(100, round(($goal['current_amount'] / $goal['target_amount']) * 100)) : 0;
                    ?>
                    <div class="border-b pb-4 last:border-0">
                        <div class="flex justify-between items-end mb-1">
                            <span class="font-bold text-gray-700"><?= htmlspecialchars($goal['name']) ?></span>
                            <span class="text-xs text-gray-500"><?= number_format($goal['current_amount']) ?> / <?= number_format($goal['target_amount']) ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                            <div class="bg-pink-500 h-2.5 rounded-full" style="width: <?= $percent ?>%"></div>
                        </div>
                        <!-- Quick Add to Goal -->
                        <form action="finance_action.php" method="POST" class="flex gap-1">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="update_goal">
                            <input type="hidden" name="id" value="<?= $goal['id'] ?>">
                            <input type="number" name="amount_added" placeholder="Add Amount" class="border p-1 text-xs rounded w-24">
                            <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded text-xs">Save</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Delete Functionality
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if(!confirm('Are you sure you want to delete this transaction?')) return;
                
                const id = this.dataset.id;
                const type = this.dataset.type;

                fetch('delete_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `type=${type}&id=${id}`
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Error deleting item');
                    }
                })
                .catch(err => console.error(err));
            });
        });

        // Expense Chart
        const ctx = document.getElementById('expenseChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_keys($expense_chart_data)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($expense_chart_data)) ?>,
                        backgroundColor: ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    </script>
</body>
</html>