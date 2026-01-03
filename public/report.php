<?php
require '../config/db.php';

// Fetch Roadmap Stats
try {
    $total_roadmap = $pdo->query("SELECT COUNT(*) FROM roadmap")->fetchColumn();
    $completed_roadmap = $pdo->query("SELECT COUNT(*) FROM roadmap WHERE status = 'completed'")->fetchColumn();
    $missed_rescheduled = $pdo->query("SELECT SUM(missed_count) FROM roadmap")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    // If table doesn't exist, suggest running installer
    if ($e->getCode() === '42P01') {
        die("<div style='font-family:sans-serif;padding:20px;text-align:center;margin-top:50px;'><strong>Roadmap table missing.</strong><br><br><a href='install.php' style='color:white;background:#2563eb;padding:10px 20px;text-decoration:none;border-radius:5px;'>Update Database</a></div>");
    }
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

// Fetch Habit Stats (Last 30 days)
$habit_logs_count = $pdo->query("SELECT COUNT(*) FROM habit_logs WHERE completed = true AND log_date > NOW() - INTERVAL '30 days'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 p-6">

    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-home"></i> Dashboard</a>
        <a href="roadmap.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-map"></i> Roadmap</a>
        <a href="finance.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-wallet"></i> Finance</a>
        <a href="report.php" class="text-blue-600 font-bold border-b-2 border-blue-600"><i class="fas fa-chart-pie"></i> Report</a>
    </nav>

    <h1 class="text-2xl font-bold mb-6">Performance Report</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1: Roadmap Progress -->
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-gray-500 font-bold uppercase text-xs mb-2">Roadmap Completion</h3>
            <div class="text-4xl font-bold text-blue-600">
                <?= $total_roadmap > 0 ? round(($completed_roadmap / $total_roadmap) * 100) : 0 ?>%
            </div>
            <p class="text-sm text-gray-400 mt-2"><?= $completed_roadmap ?> out of <?= $total_roadmap ?> topics done</p>
        </div>

        <!-- Card 2: Consistency Score -->
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-gray-500 font-bold uppercase text-xs mb-2">Missed/Rescheduled</h3>
            <div class="text-4xl font-bold text-orange-500">
                <?= $missed_rescheduled ?>
            </div>
            <p class="text-sm text-gray-400 mt-2">Times you postponed study</p>
        </div>

        <!-- Card 3: Habits Volume -->
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-gray-500 font-bold uppercase text-xs mb-2">30-Day Habit Streak</h3>
            <div class="text-4xl font-bold text-green-600">
                <?= $habit_logs_count ?>
            </div>
            <p class="text-sm text-gray-400 mt-2">Total habits completed</p>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="mt-8 bg-white p-6 rounded shadow">
        <h2 class="font-bold mb-4">Roadmap Overview</h2>
        <div class="h-64">
            <canvas id="roadmapChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('roadmapChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Completed', 'Pending', 'Rescheduled Events'],
                datasets: [{
                    label: 'Topics',
                    data: [<?= $completed_roadmap ?>, <?= $total_roadmap - $completed_roadmap ?>, <?= $missed_rescheduled ?>],
                    backgroundColor: ['#22c55e', '#eab308', '#f97316']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    </script>
</body>
</html>