<?php
require '../config/db.php';

$today = date('Y-m-d');

// Fetch Roadmap Items
try {
    $query = "SELECT * FROM roadmap ORDER BY scheduled_date ASC";
    $roadmap_items = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist (Postgres error 42P01), suggest running installer
    if ($e->getCode() === '42P01') {
        die("<div style='font-family:sans-serif;padding:20px;text-align:center;margin-top:50px;'><strong>Roadmap table missing.</strong><br><br><a href='install.php' style='color:white;background:#2563eb;padding:10px 20px;text-decoration:none;border-radius:5px;'>Update Database</a></div>");
    }
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

$missed = [];
$today_items = [];
$upcoming = [];
$completed = [];

foreach ($roadmap_items as $item) {
    if ($item['status'] === 'completed') {
        $completed[] = $item;
    } elseif ($item['scheduled_date'] < $today) {
        $missed[] = $item;
    } elseif ($item['scheduled_date'] === $today) {
        $today_items[] = $item;
    } else {
        $upcoming[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Study Roadmap</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

    <!-- Navigation -->
    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-home"></i> Dashboard</a>
        <a href="roadmap.php" class="text-blue-600 font-bold border-b-2 border-blue-600"><i class="fas fa-map"></i> Roadmap</a>
        <a href="finance.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-wallet"></i> Finance</a>
        <a href="report.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-chart-pie"></i> Report</a>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT: Add New & Missed -->
        <div class="space-y-6">
            <!-- Add Form -->
            <div class="bg-white p-4 rounded shadow border-l-4 border-purple-500">
                <h2 class="font-bold mb-3">Add Study Topic</h2>
                <form action="roadmap_action.php" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="topic" placeholder="Topic (e.g. PHP Arrays)" class="w-full border p-2 rounded" required>
                    <input type="date" name="date" class="w-full border p-2 rounded" required>
                    <textarea name="description" placeholder="Notes/Resources" class="w-full border p-2 rounded"></textarea>
                    <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded font-bold">Add to Roadmap</button>
                </form>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="setup_challenge.php" class="block w-full text-center bg-yellow-500 text-white py-2 rounded font-bold hover:bg-yellow-600"><i class="fas fa-rocket mr-2"></i>Start 30-Day Challenge</a>
                </div>
            </div>

            <!-- Missed Items (Alert) -->
            <?php if (!empty($missed)): ?>
            <div class="bg-red-50 p-4 rounded shadow border border-red-200">
                <h2 class="font-bold text-red-600 mb-3"><i class="fas fa-exclamation-triangle"></i> Missed Topics</h2>
                <?php foreach ($missed as $item): ?>
                <div class="bg-white p-3 rounded mb-2 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-gray-800"><?= htmlspecialchars($item['topic']) ?></h3>
                            <p class="text-xs text-red-500">Due: <?= $item['scheduled_date'] ?></p>
                        </div>
                        <span class="text-red-500 font-bold text-xl"><i class="fas fa-times-circle"></i></span>
                    </div>
                    <!-- Reschedule Form -->
                    <form action="roadmap_action.php" method="POST" class="mt-3 border-t pt-2">
                        <input type="hidden" name="action" value="reschedule">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <p class="text-xs font-bold text-gray-600 mb-1">Why didn't you study?</p>
                        <input type="text" name="reason" placeholder="Reason (e.g. Was sick)" class="w-full border p-1 text-sm rounded mb-2" required>
                        <p class="text-xs font-bold text-gray-600 mb-1">Reschedule to:</p>
                        <div class="flex gap-2">
                            <input type="date" name="new_date" class="w-full border p-1 text-sm rounded" required>
                            <button type="submit" class="bg-blue-600 text-white px-3 rounded text-xs">Save</button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- CENTER: Today & Upcoming -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Today -->
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-bold text-xl mb-4 text-green-700"><i class="fas fa-calendar-day"></i> Study Today</h2>
                <?php if (empty($today_items)): ?>
                    <p class="text-gray-400 italic">No topics scheduled for today.</p>
                <?php else: ?>
                    <?php foreach ($today_items as $item): ?>
                    <div class="flex justify-between items-center bg-green-50 p-4 rounded mb-2 border border-green-100">
                        <div>
                            <h3 class="font-bold text-lg"><?= htmlspecialchars($item['topic']) ?></h3>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($item['description']) ?></p>
                        </div>
                        <form action="roadmap_action.php" method="POST">
                            <input type="hidden" name="action" value="complete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700">
                                <i class="fas fa-check"></i> Done
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Upcoming -->
            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-bold text-gray-700 mb-4">Upcoming</h2>
                <div class="space-y-2">
                    <?php foreach ($upcoming as $item): ?>
                    <div class="flex justify-between items-center p-3 border-b">
                        <div>
                            <span class="font-semibold"><?= htmlspecialchars($item['topic']) ?></span>
                            <span class="text-xs bg-gray-200 px-2 py-1 rounded ml-2"><?= $item['scheduled_date'] ?></span>
                        </div>
                        <form action="roadmap_action.php" method="POST" onsubmit="return confirm('Delete this topic?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button class="text-gray-300 hover:text-red-500"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Completed History (Collapsible or simple list) -->
            <div class="mt-8">
                <h3 class="font-bold text-gray-500">Recently Completed</h3>
                <?php foreach (array_slice($completed, 0, 5) as $item): ?>
                    <div class="text-sm text-gray-400 line-through"><?= htmlspecialchars($item['topic']) ?> (<?= $item['scheduled_date'] ?>)</div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>