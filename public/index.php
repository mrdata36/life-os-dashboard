<?php
require '../config/db.php';

// Phase 1: Authentication Check
require_login();
$user_id = get_user_id();

// Fetch User Profile for Header
$current_user = ['username' => $_SESSION['username'] ?? 'User', 'profile_image' => null];
try {
    $stmt = $pdo->prepare("SELECT username, profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $fetched_user = $stmt->fetch();
    if ($fetched_user) $current_user = $fetched_user;
} catch (PDOException $e) {
    // Ignore error if column missing, allow page to load so we can show "Update Database" later
}

// Get all habits
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_days = [];
for($i=0;$i<7;$i++){
    $week_days[] = date('Y-m-d', strtotime("+$i days", strtotime($week_start)));
}
try {
    // Filter by User ID
    $stmt = $pdo->prepare("SELECT * FROM habits WHERE user_id = ? ORDER BY category, id ASC");
    $stmt->execute([$user_id]);
    $habits = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll();

    // OPTIMIZATION: Fetch all habit_logs for this week in ONE query (avoids N+1 problem)
    $habit_logs = [];
    // Join with habits to ensure we only get logs for this user's habits
    $stmt = $pdo->prepare("SELECT hl.habit_id, hl.log_date, hl.completed FROM habit_logs hl JOIN habits h ON hl.habit_id = h.id WHERE h.user_id = ? AND hl.log_date >= ? AND hl.log_date <= ?");
    $stmt->execute([$user_id, $week_start, end($week_days)]);
    $raw_logs = $stmt->fetchAll();

    foreach ($raw_logs as $log) {
        $habit_logs[$log['habit_id']][$log['log_date']] = $log['completed'];
    }

    // OPTIMIZATION: Fetch all task logs for this week (Changed from just today)
    $task_logs = [];
    $stmt = $pdo->prepare("SELECT tl.task_id, tl.log_date, tl.completed FROM task_logs tl JOIN tasks t ON tl.task_id = t.id WHERE t.user_id = ? AND tl.log_date >= ? AND tl.log_date <= ?");
    $stmt->execute([$user_id, $week_start, end($week_days)]);
    $raw_task_logs = $stmt->fetchAll();
    foreach ($raw_task_logs as $log) {
        $task_logs[$log['task_id']][$log['log_date']] = $log['completed'];
    }

    // NEW: Fetch pending roadmap items (Today + Overdue)
    $stmt = $pdo->prepare("SELECT * FROM roadmap WHERE user_id = ? AND status = 'pending' AND scheduled_date <= ? ORDER BY scheduled_date ASC");
    $stmt->execute([$user_id, $today]);
    $roadmap_todos = $stmt->fetchAll();

    // NEW: Calculate 30-Day Challenge Progress
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM roadmap WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_challenge_items = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM roadmap WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $completed_challenge_items = $stmt->fetchColumn();
    
    $challenge_progress = ($total_challenge_items > 0) ? round(($completed_challenge_items / $total_challenge_items) * 100) : 0;

} catch (PDOException $e) {
    // If tables don't exist (42P01) OR columns missing (42703), suggest update
    if ($e->getCode() === '42P01' || $e->getCode() === '42703') {
        die("<div style='font-family:sans-serif;padding:20px;text-align:center;margin-top:50px;'><strong>Database needs update.</strong><br><br><a href='install.php' style='color:white;background:#2563eb;padding:10px 20px;text-decoration:none;border-radius:5px;'>Update Database</a></div>");
    }
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

// Calculate Daily Stats for Charts
$daily_stats = [];
$daily_counts = []; // Raw count for JS updates
$total_habits = count($habits);
$total_tasks = count($tasks);
$total_items = $total_habits + $total_tasks; // Combine Habits + Tasks for denominator

foreach ($week_days as $day) {
    $completed_count = 0;
    foreach ($habits as $habit) {
        if (!empty($habit_logs[$habit['id']][$day])) {
            $completed_count++;
        }
    }
    foreach ($tasks as $task) {
        if (!empty($task_logs[$task['id']][$day])) {
            $completed_count++;
        }
    }
    $daily_counts[$day] = $completed_count;
    $daily_stats[$day] = $total_items > 0 ? round(($completed_count / $total_items) * 100) : 0;
}

$weekly_avg = count($daily_stats) > 0 ? round(array_sum($daily_stats) / count($daily_stats)) : 0;

// ================================
// ADVANCED ANALYTICS
// ================================
$best_category = 'N/A';
$cat_counts = [];
foreach($habits as $h) {
    $cat = $h['category'] ?: 'General';
    if(!isset($cat_counts[$cat])) $cat_counts[$cat] = 0;
    foreach($week_days as $d) {
        if(!empty($habit_logs[$h['id']][$d])) $cat_counts[$cat]++;
    }
}
if(!empty($cat_counts)) {
    // Find category with max completions
    $best_category = array_search(max($cat_counts), $cat_counts);
}

// ================================
// SMART AI INSIGHTS (Predictive Analytics)
// ================================
$ai_insights = [];

// 1. Expense Projection (Linear Regression Lite)
$month_start = date('Y-m-01');
$current_day = date('j');
$days_in_month = date('t');
try {
    // Check if transactions table exists
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type='expense' AND transaction_date >= ?");
    $stmt->execute([$user_id, $month_start]);
    $monthly_spend = $stmt->fetchColumn() ?: 0;

    if($current_day > 0 && $monthly_spend > 0) {
        $projected = ($monthly_spend / $current_day) * $days_in_month;
        $ai_insights[] = [
            'icon' => 'fa-chart-line',
            'color' => 'text-blue-600',
            'title' => 'Spending Forecast',
            'text' => "At this rate, you'll spend <strong>" . number_format($projected) . " " . APP_CURRENCY . "</strong> by month end."
        ];
    }
} catch (Exception $e) { /* Ignore if finance not ready */ }

// 2. Habit Optimization (Identify Weakest Link)
$weakest_habit = null;
$min_completion = 100;
foreach($habits as $h) {
    $h_logs = 0;
    foreach($week_days as $d) if(!empty($habit_logs[$h['id']][$d])) $h_logs++;
    $rate = ($h_logs / 7) * 100;
    if($rate < $min_completion) { $min_completion = $rate; $weakest_habit = $h['name']; }
}
if($weakest_habit && $min_completion < 60) {
    $ai_insights[] = [ 'icon' => 'fa-lightbulb', 'color' => 'text-yellow-500', 'title' => 'Smart Suggestion', 'text' => "Struggling with <strong>$weakest_habit</strong>? Try moving it to your 'Focus Time' block." ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Task Monitor Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="app.js" defer></script>
<link href="../assets/css/style.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow">
    <a href="index.php" class="text-blue-600 font-bold border-b-2 border-blue-600"><i class="fas fa-home"></i> Dashboard</a>
    <a href="roadmap.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-map"></i> Roadmap</a>
    <a href="finance.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-wallet"></i> Finance</a>
    <a href="report.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-chart-pie"></i> Report</a>
    <div class="ml-auto flex items-center gap-3">
        <a href="profile.php" class="flex items-center gap-2 text-gray-700 font-bold hover:text-blue-600">
            <?php if(!empty($current_user['profile_image'])): ?>
                <img src="uploads/<?= htmlspecialchars($current_user['profile_image']) ?>?v=<?= time() ?>" class="w-8 h-8 rounded-full object-cover border">
            <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center"><i class="fas fa-user text-xs"></i></div>
            <?php endif; ?>
            <span><?= htmlspecialchars($current_user['username']) ?></span>
        </a>
    </div>
</nav>

<!-- Flash Messages -->
<?php display_flash(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Success Dashboard</h1>
    <div class="flex gap-4">
        <!-- Analytics Card 1 -->
        <div class="bg-white px-4 py-2 rounded-lg shadow text-center min-w-[100px]">
            <span class="text-xs text-gray-500 uppercase font-bold"><i class="fas fa-trophy text-yellow-500 mr-1"></i> Best Focus</span>
            <div class="text-lg font-bold text-gray-700"><?= htmlspecialchars($best_category) ?></div>
        </div>
        <!-- Analytics Card 2 -->
        <div class="bg-white px-4 py-2 rounded-lg shadow text-center min-w-[100px]">
            <span class="text-xs text-gray-500 uppercase font-bold"><i class="fas fa-chart-line text-blue-500 mr-1"></i> Rate</span>
            <div class="text-lg font-bold <?= $weekly_avg >= 80 ? 'text-green-600' : ($weekly_avg >= 50 ? 'text-yellow-600' : 'text-red-600') ?>">
                <?= $weekly_avg ?>%
            </div>
        </div>
    </div>
</div>

<!-- SMART AI INSIGHTS -->
<?php if(!empty($ai_insights)): ?>
<div class="mb-8 bg-gradient-to-r from-slate-800 to-slate-900 rounded-lg shadow-lg p-6 text-white border border-slate-700">
    <h2 class="font-bold text-lg mb-4 flex items-center text-blue-400"><i class="fas fa-robot mr-2"></i> AI Assistant Insights</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach($ai_insights as $insight): ?>
        <div class="bg-white/5 p-4 rounded-lg backdrop-blur-sm border border-white/10 flex items-start gap-4 hover:bg-white/10 transition">
            <div class="bg-white p-3 rounded-full shadow-sm <?= $insight['color'] ?>"><i class="fas <?= $insight['icon'] ?> text-xl"></i></div>
            <div>
                <h3 class="font-bold text-sm text-gray-200 mb-1"><?= $insight['title'] ?></h3>
                <p class="text-sm text-gray-400 leading-relaxed"><?= $insight['text'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- QUICK ADD FORMS -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Add Habit Form -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
        <h2 class="font-semibold mb-2 text-gray-700"><i class="fas fa-plus-circle mr-2"></i>Add New Habit</h2>
        <form action="add_habit.php" method="POST" class="flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="text" name="name" placeholder="Habit name (e.g. Read 10 pages)" class="border p-2 rounded w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-200" required>
            <select name="category" class="border p-2 rounded text-sm bg-gray-50">
                <option value="Health">Health</option>
                <option value="Work">Work</option>
                <option value="Mindset">Mindset</option>
                <option value="General">General</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-bold transition"><i class="fas fa-save"></i></button>
        </form>
    </div>

    <!-- Add Task Form -->
    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
        <h2 class="font-semibold mb-2 text-gray-700"><i class="fas fa-tasks mr-2"></i>Add Task for Today</h2>
        <form action="add_task.php" method="POST" class="flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="text" name="title" placeholder="Task title (e.g. Email Client)" class="border p-2 rounded w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-200" required>
            <input type="text" name="category" placeholder="Category" class="border p-2 rounded w-1/3 text-sm">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 font-bold transition"><i class="fas fa-save"></i></button>
        </form>
    </div>
</div>

<!-- POMODORO TIMER & CHALLENGE PROGRESS -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Pomodoro Timer -->
    <div class="lg:col-span-1 bg-white p-4 rounded-lg shadow border-l-4 border-red-500 flex flex-col items-center justify-center">
        <h2 class="font-semibold mb-2 text-gray-700"><i class="fas fa-clock mr-2"></i>Focus Timer</h2>
        <div class="flex gap-2 mb-3">
            <button class="timer-mode active ring-2 ring-offset-1 bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold hover:bg-red-200" data-time="25">Focus</button>
            <button class="timer-mode bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold hover:bg-blue-200" data-time="5">Short</button>
            <button class="timer-mode bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs font-bold hover:bg-indigo-200" data-time="15">Long</button>
        </div>
        <div id="timer-display" class="text-5xl font-mono font-bold text-gray-800">25:00</div>
        <div class="flex gap-2 mt-3">
            <button id="start-btn" class="bg-green-500 text-white px-4 py-1 rounded hover:bg-green-600"><i class="fas fa-play"></i></button>
            <button id="pause-btn" class="bg-yellow-500 text-white px-4 py-1 rounded hover:bg-yellow-600"><i class="fas fa-pause"></i></button>
            <button id="reset-btn" class="bg-red-500 text-white px-4 py-1 rounded hover:bg-red-600"><i class="fas fa-sync-alt"></i></button>
        </div>
        <audio id="alarm-sound" src="https://www.soundjay.com/buttons/sounds/button-16.mp3" preload="auto"></audio>
    </div>
    <!-- 30-Day Challenge Progress -->
    <div class="lg:col-span-2 bg-white p-4 rounded-lg shadow border-l-4 border-purple-500 flex flex-col justify-center">
        <h2 class="font-semibold mb-2 text-gray-700"><i class="fas fa-rocket mr-2"></i>30-Day Challenge Progress</h2>
        <div class="w-full bg-gray-200 rounded-full h-4">
            <div class="bg-purple-600 h-4 rounded-full text-center text-white text-xs font-bold" style="width: <?= $challenge_progress ?>%"><?= $challenge_progress ?>%</div>
        </div>
    </div>
</div>

<!-- ROADMAP TRACKER (30-Day Challenge) -->
<?php if(!empty($roadmap_todos)): ?>
<section class="mb-8">
    <h2 class="font-semibold mb-2 text-purple-700"><i class="fas fa-rocket mr-2"></i>30-Day Challenge (Pending)</h2>
    <div class="grid grid-cols-1 gap-4">
        <?php foreach($roadmap_todos as $item): 
            $is_overdue = $item['scheduled_date'] < $today;
        ?>
        <div class="bg-white p-4 rounded-lg shadow flex items-center justify-between border-l-4 <?= $is_overdue ? 'border-red-500' : 'border-purple-500' ?> transition hover:shadow-md">
            <div class="flex flex-col">
                <span class="font-bold text-gray-800"><?= htmlspecialchars($item['topic']) ?></span>
                <div class="text-sm text-gray-600 whitespace-pre-line mt-1"><?= htmlspecialchars($item['description']) ?></div>
                <?php if($is_overdue): ?>
                    <span class="text-xs text-red-600 font-bold mt-2 bg-red-100 px-2 py-1 rounded w-fit"><i class="fas fa-exclamation-triangle mr-1"></i> Overdue (<?= $item['scheduled_date'] ?>)</span>
                <?php else: ?>
                    <span class="text-xs text-purple-600 font-bold mt-2"><i class="fas fa-calendar-day mr-1"></i> Today</span>
                <?php endif; ?>
            </div>
            <!-- Checkbox to complete -->
            <input type="checkbox" data-id="<?= $item['id'] ?>" class="roadmap-checkbox w-6 h-6 cursor-pointer accent-purple-600 ml-4">
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- HABITS TRACKER -->
<section class="mb-8">
    <h2 class="font-semibold mb-2">Habits Tracker (Weekly)</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach($habits as $habit): ?>
        <?php 
            // Calculate persistence (days done this week)
            $persistence = count(array_filter($habit_logs[$habit['id']] ?? []));
        ?>
        <div class="bg-white p-4 rounded-lg shadow relative group">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                    <?= htmlspecialchars($habit['name']) ?> 
                    <span class="text-xs text-gray-400 uppercase bg-gray-100 px-1 rounded"><?= htmlspecialchars($habit['category']) ?></span>
                </h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold bg-blue-50 text-blue-600 px-2 py-1 rounded"><?= $persistence ?>/7</span>
                    <button class="delete-btn text-gray-300 hover:text-red-500 transition" data-type="habit" data-id="<?= $habit['id'] ?>" title="Delete Habit">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
            <div class="flex space-x-2">
                <?php foreach($week_days as $day): 
                    $done = $habit_logs[$habit['id']][$day] ?? false;
                ?>
                <div class="flex flex-col items-center">
                    <input type="checkbox" data-habit="<?= $habit['id'] ?>" data-date="<?= $day ?>" class="habit-checkbox accent-blue-600 w-5 h-5 cursor-pointer" <?= $done ? 'checked' : '' ?>>
                    <small><?= date('D', strtotime($day)) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- TASKS TODAY -->
<section>
    <h2 class="font-semibold mb-2">Tasks Today (<?= $today ?>)</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach($tasks as $task): 
            $done = $task_logs[$task['id']][$today] ?? false;
        ?>
        <div class="bg-white p-4 rounded-lg shadow flex items-center justify-between group transition hover:shadow-md">
            <div class="flex items-center gap-3">
                <input type="checkbox" data-task="<?= $task['id'] ?>" data-date="<?= $today ?>" class="task-checkbox accent-green-600 w-6 h-6 cursor-pointer" <?= $done ? 'checked' : '' ?>>
                <span class="task-text <?= $done ? 'line-through text-gray-400' : 'text-gray-700' ?>"><?= htmlspecialchars($task['title']) ?> <small class="text-gray-400">(<?= htmlspecialchars($task['category']) ?>)</small></span>
            </div>
            <button class="delete-btn text-gray-300 hover:text-red-500 transition opacity-0 group-hover:opacity-100" data-type="task" data-id="<?= $task['id'] ?>" title="Delete Task">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- DAILY DONUT CHARTS -->
<section class="mt-8">
    <h2 class="font-semibold mb-2">Daily Completion %</h2>
    <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
        <?php foreach($week_days as $day): ?>
        <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
            <div class="w-24 h-24 relative">
                <canvas id="chart-<?= $day ?>"></canvas>
            </div>
            <span class="mt-2"><?= date('D', strtotime($day)) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Inject Data for JS -->
<script>
    const dailyStats = <?= json_encode($daily_stats) ?>;
    const dailyCounts = <?= json_encode($daily_counts) ?>;
    const totalItems = <?= $total_items ?>;
    const weekDays = <?= json_encode($week_days) ?>;
</script>
</body>
</html>
