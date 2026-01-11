<?php
require '../config/db.php';
require_login();
$user_id = get_user_id();

// Handle Add Roadmap Item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic = $_POST['topic'];
    $desc = $_POST['description'];
    $date = $_POST['scheduled_date'];
    
    // Insert with user_id
    $stmt = $pdo->prepare("INSERT INTO roadmap (user_id, topic, description, scheduled_date, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $topic, $desc, $date]);
    set_flash('success', 'Roadmap item added!');
    header("Location: roadmap.php");
    exit;
}

// Fetch Items (FILTERED BY USER ID)
$stmt = $pdo->prepare("SELECT * FROM roadmap WHERE user_id = ? ORDER BY scheduled_date ASC");
$stmt->execute([$user_id]);
$roadmap_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Roadmap - Life OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-home"></i> Dashboard</a>
        <a href="projects.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-briefcase"></i> Projects</a>
        <a href="roadmap.php" class="text-blue-600 font-bold border-b-2 border-blue-600"><i class="fas fa-map"></i> Roadmap</a>
        <a href="mistakes.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-bug"></i> Analysis</a>
        <a href="finance.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-wallet"></i> Finance</a>
        <a href="profile.php" class="text-gray-600 hover:text-blue-600 font-bold ml-auto"><i class="fas fa-user"></i> Profile</a>
    </nav>

    <?php display_flash(); ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Add Item Form -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow sticky top-6">
                <h2 class="font-bold text-xl mb-4 text-purple-700"><i class="fas fa-plus-circle mr-2"></i>Add to Roadmap</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Topic / Goal</label>
                        <input type="text" name="topic" placeholder="e.g. Learn React Basics" class="border p-2 rounded w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Description</label>
                        <textarea name="description" placeholder="Details..." class="border p-2 rounded w-full h-24"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="border p-2 rounded w-full" required>
                    </div>
                    <button class="bg-purple-600 text-white px-4 py-2 rounded w-full font-bold hover:bg-purple-700">Add to Plan</button>
                </form>
            </div>
        </div>

        <!-- Timeline Display -->
        <div class="lg:col-span-2">
            <h2 class="font-bold text-2xl mb-6 text-gray-800">Your 30-Day Journey</h2>
            
            <?php if(empty($roadmap_items)): ?>
                <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
                    <i class="fas fa-map-signs text-4xl mb-4 text-gray-300"></i>
                    <p>No roadmap items yet. Start planning your success!</p>
                </div>
            <?php else: ?>
                <div class="space-y-6 relative border-l-4 border-purple-200 ml-4 pl-6">
                    <?php foreach($roadmap_items as $item): 
                        $is_past = strtotime($item['scheduled_date']) < strtotime(date('Y-m-d'));
                        $status_color = $item['status'] == 'completed' ? 'bg-green-100 border-green-500' : ($is_past ? 'bg-red-50 border-red-400' : 'bg-white border-purple-500');
                    ?>
                    <div class="relative p-4 rounded-lg shadow border-l-4 <?= $status_color ?>">
                        <!-- Dot on timeline -->
                        <div class="absolute -left-[34px] top-6 w-4 h-4 rounded-full bg-purple-500 border-4 border-white shadow"></div>
                        
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide"><?= date('M d, Y', strtotime($item['scheduled_date'])) ?></span>
                                <h3 class="font-bold text-lg text-gray-800 mt-1"><?= htmlspecialchars($item['topic']) ?></h3>
                                <p class="text-gray-600 mt-2 text-sm"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded <?= $item['status'] == 'completed' ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-700' ?>"><?= ucfirst($item['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>