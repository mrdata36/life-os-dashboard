<?php
require '../config/db.php';
require_login();
$user_id = get_user_id();

// 1. Fetch Overdue Roadmap Items (Critical Mistakes)
$stmt = $pdo->prepare("SELECT * FROM roadmap WHERE user_id = ? AND status = 'pending' AND scheduled_date < CURRENT_DATE ORDER BY scheduled_date ASC");
$stmt->execute([$user_id]);
$overdue_roadmap = $stmt->fetchAll();

// 2. Fetch Overdue Project Milestones (Project Failures)
$stmt = $pdo->prepare("SELECT m.*, p.name as project_name FROM project_milestones m JOIN projects p ON m.project_id = p.id WHERE p.user_id = ? AND m.status = 'pending' AND m.due_date < CURRENT_DATE ORDER BY m.due_date ASC");
$stmt->execute([$user_id]);
$overdue_milestones = $stmt->fetchAll();

// 3. Analyze Habits (Last 7 Days Consistency)
$week_start = date('Y-m-d', strtotime('-7 days'));
$stmt = $pdo->prepare("SELECT * FROM habits WHERE user_id = ?");
$stmt->execute([$user_id]);
$habits = $stmt->fetchAll();

$habit_misses = [];
foreach($habits as $habit) {
    // Count logs in last 7 days
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM habit_logs WHERE habit_id = ? AND log_date >= ? AND completed = true");
    $stmt->execute([$habit['id'], $week_start]);
    $count = $stmt->fetchColumn();
    
    // If done less than 4 times a week, it's a "miss"
    if ($count < 4) { 
        $habit_misses[] = [
            'name' => $habit['name'],
            'count' => $count,
            'category' => $habit['category']
        ];
    }
}

// 4. Generate "Smart Insights"
$insights = [];
if (count($overdue_roadmap) > 3) {
    $insights[] = ['type' => 'critical', 'icon' => 'fa-exclamation-triangle', 'color' => 'red', 'msg' => 'You have ' . count($overdue_roadmap) . ' overdue study topics. You are falling behind on your roadmap.'];
}
if (!empty($habit_misses)) {
    $insights[] = ['type' => 'warning', 'icon' => 'fa-chart-bar', 'color' => 'yellow', 'msg' => 'Consistency Alert: ' . count($habit_misses) . ' habits are below 50% completion this week.'];
}
if (count($overdue_milestones) > 0) {
    $insights[] = ['type' => 'info', 'icon' => 'fa-briefcase', 'color' => 'blue', 'msg' => 'Project Alert: You have ' . count($overdue_milestones) . ' milestones overdue.'];
}
if (empty($insights)) {
    $insights[] = ['type' => 'success', 'icon' => 'fa-check-circle', 'color' => 'green', 'msg' => 'Great job! No critical mistakes or overdue items detected.'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Analysis - Task Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6 font-sans">

    <!-- Navigation -->
    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow overflow-x-auto items-center">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold whitespace-nowrap"><i class="fas fa-home"></i> Dashboard</a>
        <a href="projects.php" class="text-gray-600 hover:text-blue-600 font-bold whitespace-nowrap"><i class="fas fa-briefcase"></i> Projects</a>
        <a href="roadmap.php" class="text-gray-600 hover:text-blue-600 font-bold whitespace-nowrap"><i class="fas fa-map"></i> Roadmap</a>
        <a href="mistakes.php" class="text-blue-600 font-bold border-b-2 border-blue-600 whitespace-nowrap"><i class="fas fa-bug"></i> Smart Analysis</a>
        <a href="finance.php" class="text-gray-600 hover:text-blue-600 font-bold whitespace-nowrap"><i class="fas fa-wallet"></i> Finance</a>
        <a href="report.php" class="text-gray-600 hover:text-blue-600 font-bold whitespace-nowrap"><i class="fas fa-chart-pie"></i> Report</a>
    </nav>

    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-6"><i class="fas fa-brain text-purple-600 mr-2"></i> Automated Failure Analysis</h1>

        <!-- AI Insights Section -->
        <div class="grid grid-cols-1 gap-4 mb-8">
            <?php foreach($insights as $insight): ?>
            <div class="bg-white border-l-4 border-<?= $insight['color'] ?>-500 p-4 rounded shadow flex items-center gap-4">
                <div class="bg-<?= $insight['color'] ?>-100 p-3 rounded-full text-<?= $insight['color'] ?>-600">
                    <i class="fas <?= $insight['icon'] ?> text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 uppercase text-xs"><?= ucfirst($insight['type']) ?> Insight</h3>
                    <p class="text-gray-700"><?= $insight['msg'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Overdue Roadmap (Study Mistakes) -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="font-bold text-xl mb-4 text-red-600"><i class="fas fa-book-dead mr-2"></i> Missed Study Topics</h2>
                <?php if(empty($overdue_roadmap)): ?>
                    <p class="text-gray-500 italic">No overdue topics. You are on track!</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach($overdue_roadmap as $item): ?>
                        <div class="border-l-4 border-red-400 bg-red-50 p-3 rounded">
                            <div class="flex justify-between">
                                <span class="font-bold text-gray-800"><?= htmlspecialchars($item['topic']) ?></span>
                                <span class="text-xs font-bold text-red-600"><?= $item['scheduled_date'] ?></span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($item['description']) ?></p>
                            <div class="mt-2 text-right">
                                <a href="roadmap.php" class="text-xs bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">Fix / Reschedule</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Habit Failures -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="font-bold text-xl mb-4 text-yellow-600"><i class="fas fa-battery-quarter mr-2"></i> Inconsistent Habits</h2>
                <?php if(empty($habit_misses)): ?>
                    <p class="text-gray-500 italic">Your consistency is excellent!</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach($habit_misses as $habit): ?>
                        <div class="flex justify-between items-center border-b pb-2">
                            <div>
                                <p class="font-bold text-gray-800"><?= htmlspecialchars($habit['name']) ?></p>
                                <span class="text-xs bg-gray-200 px-2 py-0.5 rounded text-gray-600"><?= htmlspecialchars($habit['category']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block font-bold text-yellow-600"><?= $habit['count'] ?>/7 days</span>
                                <span class="text-xs text-gray-400">Low Consistency</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Overdue Projects -->
            <div class="bg-white p-6 rounded-lg shadow md:col-span-2">
                <h2 class="font-bold text-xl mb-4 text-blue-600"><i class="fas fa-project-diagram mr-2"></i> Project Delays</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase">
                            <tr><th class="p-3">Milestone</th><th class="p-3">Project</th><th class="p-3">Due Date</th><th class="p-3">Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($overdue_milestones as $m): ?>
                            <tr class="border-b">
                                <td class="p-3 font-bold text-gray-700"><?= htmlspecialchars($m['title']) ?></td>
                                <td class="p-3 text-gray-600"><?= htmlspecialchars($m['project_name']) ?></td>
                                <td class="p-3 text-red-600 font-bold"><?= $m['due_date'] ?></td>
                                <td class="p-3"><a href="project_details.php?id=<?= $m['project_id'] ?>" class="text-blue-600 hover:underline">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($overdue_milestones)): ?>
                                <tr><td colspan="4" class="p-3 text-center text-gray-500">No overdue milestones.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>