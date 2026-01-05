<?php
require '../config/db.php';
require_login();
$user_id = get_user_id();

// Fetch Projects
$stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Projects - Life OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-home"></i> <?= __('nav_dashboard') ?></a>
        <a href="projects.php" class="text-blue-600 font-bold border-b-2 border-blue-600"><i class="fas fa-briefcase"></i> <?= __('nav_projects') ?></a>
        <a href="roadmap.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-map"></i> <?= __('nav_roadmap') ?></a>
        <a href="finance.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-wallet"></i> <?= __('nav_finance') ?></a>
        <a href="report.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-chart-pie"></i> <?= __('nav_report') ?></a>
        <a href="profile.php" class="text-gray-600 hover:text-blue-600 font-bold ml-auto"><i class="fas fa-user"></i> <?= __('nav_profile') ?></a>
    </nav>

    <?php display_flash(); ?>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?= __('project_manager') ?></h1>
        <button onclick="document.getElementById('newProjectModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded font-bold hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i><?= __('new_project') ?>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($projects as $p): 
            $status_colors = [
                'planned' => 'bg-gray-100 text-gray-600',
                'in_progress' => 'bg-blue-100 text-blue-600',
                'completed' => 'bg-green-100 text-green-600',
                'on_hold' => 'bg-yellow-100 text-yellow-600'
            ];
            $status_class = $status_colors[$p['status']] ?? 'bg-gray-100';
        ?>
        <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition cursor-pointer" onclick="window.location='project_details.php?id=<?= $p['id'] ?>'">
            <div class="flex justify-between items-start mb-4">
                <h3 class="font-bold text-xl text-gray-800"><?= htmlspecialchars($p['name']) ?></h3>
                <span class="text-xs font-bold px-2 py-1 rounded uppercase <?= $status_class ?>"><?= str_replace('_', ' ', $p['status']) ?></span>
            </div>
            <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= htmlspecialchars($p['description']) ?></p>
            <div class="text-xs text-gray-500 flex justify-between items-center">
                <span><i class="fas fa-calendar mr-1"></i> <?= $p['end_date'] ?: 'No deadline' ?></span>
                <span class="text-blue-600 font-bold"><?= __('view_details') ?> <i class="fas fa-arrow-right ml-1"></i></span>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if(empty($projects)): ?>
        <div class="col-span-full text-center py-12 text-gray-500 bg-white rounded-lg border-2 border-dashed">
            <i class="fas fa-folder-open text-4xl mb-4 text-gray-300"></i>
            <p><?= __('no_projects') ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- New Project Modal -->
    <div id="newProjectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 class="text-xl font-bold mb-4"><?= __('create_new_project') ?></h2>
            <form action="project_action.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700"><?= __('project_name') ?></label>
                        <input type="text" name="name" class="w-full border p-2 rounded" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700"><?= __('description') ?></label>
                        <textarea name="description" class="w-full border p-2 rounded h-24"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700"><?= __('start_date') ?></label>
                            <input type="date" name="start_date" class="w-full border p-2 rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700"><?= __('end_date') ?></label>
                            <input type="date" name="end_date" class="w-full border p-2 rounded">
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('newProjectModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"><?= __('cancel') ?></button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-bold hover:bg-blue-700"><?= __('create_project_btn') ?></button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>