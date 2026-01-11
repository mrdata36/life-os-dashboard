<?php
require '../config/db.php';
require_login();
$user_id = get_user_id();

$project_id = $_GET['id'] ?? 0;

// Fetch Project
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
$stmt->execute([$project_id, $user_id]);
$project = $stmt->fetch();

if (!$project) {
    set_flash('error', 'Project not found');
    header("Location: projects.php");
    exit;
}

// Fetch Milestones
$stmt = $pdo->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY due_date ASC");
$stmt->execute([$project_id]);
$milestones = $stmt->fetchAll();

// Fetch Project Updates (Timeline)
$stmt = $pdo->prepare("SELECT * FROM project_updates WHERE project_id = ? ORDER BY created_at DESC");
$stmt->execute([$project_id]);
$updates = $stmt->fetchAll();

// Calculate Progress
$total_m = count($milestones);
$completed_m = 0;
foreach($milestones as $m) if($m['status'] == 'completed') $completed_m++;
$progress = $total_m > 0 ? round(($completed_m / $total_m) * 100) : 0;

// Calculate Time Progress & Health
$time_progress = 0;
$days_remaining = 0;
$project_health = 'on_track'; // on_track, at_risk, delayed, overdue, completed

if ($project['start_date'] && $project['end_date']) {
    $start = strtotime($project['start_date']);
    $end = strtotime($project['end_date']);
    $now = time();
    
    if ($end > $start) {
        $total_duration = $end - $start;
        $elapsed = $now - $start;
        
        if ($elapsed < 0) {
            $time_progress = 0; 
        } elseif ($elapsed > $total_duration) {
            $time_progress = 100;
        } else {
            $time_progress = round(($elapsed / $total_duration) * 100);
        }
    }
    
    $days_remaining = ceil(($end - $now) / (60 * 60 * 24));
    
    // Logic for Health
    if ($progress == 100) {
        $project_health = 'completed';
    } elseif ($now > $end && $progress < 100) {
        $project_health = 'overdue';
    } elseif (($time_progress - $progress) > 25) {
        $project_health = 'at_risk';
    } elseif (($time_progress - $progress) > 10) {
        $project_health = 'delayed';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($project['name']) ?> - Project</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-home"></i> <?= __('nav_dashboard') ?></a>
        <a href="projects.php" class="text-blue-600 font-bold border-b-2 border-blue-600"><i class="fas fa-briefcase"></i> <?= __('nav_projects') ?></a>
        <a href="roadmap.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-map"></i> <?= __('nav_roadmap') ?></a>
        <a href="mistakes.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-bug"></i> Analysis</a>
        <a href="finance.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-wallet"></i> <?= __('nav_finance') ?></a>
        <a href="profile.php" class="text-gray-600 hover:text-blue-600 font-bold ml-auto"><i class="fas fa-user"></i> <?= __('nav_profile') ?></a>
    </nav>

    <?php display_flash(); ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Project Info & AI Analysis -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($project['name']) ?></h1>
                            <button onclick="document.getElementById('editProjectModal').classList.remove('hidden')" class="text-gray-400 hover:text-blue-600 transition" title="Edit Project"><i class="fas fa-edit"></i></button>
                        </div>
                        <p class="text-gray-500 mt-1"><?= htmlspecialchars($project['description']) ?></p>
                    </div>
                    <div class="text-right">
                        <span class="block text-sm text-gray-500"><?= __('status') ?></span>
                        <span class="font-bold text-blue-600 uppercase"><?= str_replace('_', ' ', $project['status']) ?></span>
                    </div>
                </div>
                
                <!-- Monitoring Dashboard -->
                <div class="mb-4">
                    <!-- Work Progress -->
                    <div class="flex justify-between text-sm mb-1 mt-4">
                        <span class="font-bold text-gray-700"><i class="fas fa-tasks mr-1"></i> Progress (Work)</span>
                        <span class="font-bold text-blue-600"><?= $progress ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: <?= $progress ?>%"></div>
                    </div>

                    <!-- Time Progress (Only if dates set) -->
                    <?php if($project['start_date'] && $project['end_date']): ?>
                    <div class="flex justify-between text-sm mb-1 mt-4">
                        <span class="font-bold text-gray-700"><i class="fas fa-clock mr-1"></i> <?= __('time_elapsed') ?></span>
                        <span class="font-bold <?= $time_progress > $progress ? 'text-red-500' : 'text-green-600' ?>"><?= $time_progress ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 relative">
                        <div class="bg-gray-400 h-2.5 rounded-full" style="width: <?= $time_progress ?>%"></div>
                        <!-- Marker for Today -->
                        <?php if($time_progress > 0 && $time_progress < 100): ?>
                            <div class="absolute top-0 w-1 h-4 bg-red-500 -mt-1" style="left: <?= $time_progress ?>%" title="Today"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-between items-center mt-4 p-3 bg-gray-50 rounded border">
                        <div>
                            <span class="text-xs text-gray-500 uppercase font-bold"><?= __('project_health') ?></span>
                            <div class="font-bold text-lg <?= $project_health == 'on_track' || $project_health == 'completed' ? 'text-green-600' : ($project_health == 'at_risk' || $project_health == 'overdue' ? 'text-red-600' : 'text-yellow-600') ?>">
                                <?= __($project_health) ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-500 uppercase font-bold"><?= __('days_remaining') ?></span>
                            <div class="font-bold text-lg text-gray-800"><?= $days_remaining > 0 ? $days_remaining : 0 ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex gap-4 text-sm text-gray-600 border-t pt-4">
                    <span><i class="fas fa-calendar-alt mr-1"></i> <?= __('start_date') ?>: <?= $project['start_date'] ?: 'N/A' ?></span>
                    <span><i class="fas fa-flag-checkered mr-1"></i> <?= __('end_date') ?>: <?= $project['end_date'] ?: 'N/A' ?></span>
                </div>
            </div>

            <!-- AI Analysis Section -->
            <div class="bg-gradient-to-r from-indigo-900 to-purple-900 rounded-lg shadow-lg p-6 text-white">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold flex items-center"><i class="fas fa-robot mr-2"></i> <?= __('ai_project_analyst') ?></h2>
                    <button onclick="generateAnalysis()" class="bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded text-sm font-bold transition">
                        <i class="fas fa-sync-alt mr-1"></i> <?= __('refresh_analysis') ?>
                    </button>
                </div>
                
                <div id="ai-loading" class="hidden py-4 text-center">
                    <i class="fas fa-circle-notch fa-spin text-2xl"></i>
                    <p class="mt-2 text-sm"><?= __('analyzing_text') ?></p>
                </div>

                <div id="ai-content" class="space-y-4">
                    <div class="bg-white/10 p-4 rounded border border-white/10">
                        <h3 class="font-bold text-yellow-400 mb-1"><i class="fas fa-heartbeat mr-2"></i><?= __('health_analysis') ?></h3>
                        <p class="text-sm text-gray-200 leading-relaxed">
                            <?php if($project_health == 'completed'): ?>
                                <?= __('status_finish') ?>
                            <?php elseif($project_health == 'overdue'): ?>
                                <span class="text-red-300 font-bold"><i class="fas fa-exclamation-triangle"></i></span> 
                                Mradi umepitiliza muda! Unahitaji kuongeza nguvu kazi au kupunguza wigo wa kazi (scope).
                            <?php elseif($project_health == 'at_risk'): ?>
                                <span class="text-red-300 font-bold"><i class="fas fa-exclamation-circle"></i></span> 
                                Uko nyuma sana ya muda. Muda uliopita ni <strong><?= $time_progress ?>%</strong> lakini kazi ni <strong><?= $progress ?>%</strong>.
                            <?php elseif($project_health == 'delayed'): ?>
                                Unaenda polepole kidogo. Jaribu kukamilisha milestone moja wiki hii ili kurudi kwenye mstari.
                            <?php else: ?>
                                <span class="text-green-300 font-bold"><i class="fas fa-check-circle"></i></span> 
                                Kazi nzuri! Unaenda sambamba na muda. Endelea na kasi hii.
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="bg-white/10 p-4 rounded border border-white/10">
                        <h3 class="font-bold text-green-400 mb-1"><i class="fas fa-chart-line mr-2"></i><?= __('prediction') ?></h3>
                        <p class="text-sm text-gray-200">
                            <?php if(empty($project['end_date'])): ?>
                                <?= __('prediction_no_date') ?>
                            <?php else: ?>
                                <?= sprintf(__('prediction_text'), $progress > 50 ? __('prob_high') : __('prob_medium')) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Project Timeline / Updates -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="font-bold text-xl mb-4 text-gray-800"><i class="fas fa-history mr-2"></i><?= __('project_timeline') ?></h2>
                
                <!-- Add Update Form -->
                <form action="project_action.php" method="POST" class="mb-8 bg-gray-50 p-4 rounded border">
                    <input type="hidden" name="action" value="add_update">
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?= __('what_done') ?></label>
                            <textarea name="update_text" class="w-full border p-2 rounded h-20 text-sm" placeholder="..." required></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?= __('what_remaining') ?></label>
                            <input type="text" name="remaining_work" class="w-full border p-2 rounded text-sm" placeholder="...">
                        </div>
                        <button class="bg-gray-800 text-white px-4 py-2 rounded text-sm font-bold hover:bg-black w-full"><?= __('log_update') ?></button>
                    </div>
                </form>

                <!-- Timeline List -->
                <div class="space-y-6 border-l-2 border-gray-200 ml-3 pl-6 relative">
                    <?php foreach($updates as $up): ?>
                    <div class="relative">
                        <div class="absolute -left-[31px] top-0 w-4 h-4 rounded-full bg-blue-500 border-2 border-white"></div>
                        <p class="text-xs text-gray-500 mb-1"><?= date('d M Y, H:i', strtotime($up['created_at'])) ?></p>
                        <div class="bg-gray-50 p-3 rounded border">
                            <p class="text-gray-800 text-sm whitespace-pre-line"><?= htmlspecialchars($up['update_text']) ?></p>
                            <?php if($up['remaining_work']): ?>
                                <p class="text-xs text-red-500 mt-2 font-bold"><i class="fas fa-arrow-right mr-1"></i> Next: <?= htmlspecialchars($up['remaining_work']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($updates)): ?>
                        <p class="text-gray-400 text-sm italic"><?= __('no_updates') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Milestones / Tasks -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="font-bold text-xl mb-4 text-gray-800"><?= __('milestones') ?> / To-Do</h2>
                
                <form action="project_action.php" method="POST" class="mb-6 bg-gray-50 p-3 rounded border">
                    <input type="hidden" name="action" value="add_milestone">
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-500 uppercase"><?= __('add_task_milestone') ?></label>
                        <input type="text" name="title" placeholder="<?= __('new_milestone_placeholder') ?>" class="border p-2 rounded w-full text-sm" required>
                        <textarea name="description" placeholder="<?= __('milestone_desc_placeholder') ?>" class="border p-2 rounded w-full text-sm h-16"></textarea>
                        <input type="date" name="due_date" class="border p-2 rounded w-full text-sm text-gray-500">
                        <button class="bg-blue-600 text-white px-3 py-2 rounded w-full text-sm font-bold hover:bg-blue-700"><i class="fas fa-plus mr-1"></i> Add Task</button>
                    </div>
                </form>

                <div class="space-y-3">
                    <?php foreach($milestones as $m): ?>
                    <div class="flex items-center gap-3 p-3 rounded border <?= $m['status'] == 'completed' ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' ?>">
                        <!-- Checkbox Logic: If checking, open modal. If unchecking, submit reopen form. -->
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   onchange="if(this.checked){ openCompleteModal(<?= $m['id'] ?>, '<?= htmlspecialchars($m['title'], ENT_QUOTES) ?>'); this.checked=false; } else { document.getElementById('reopen-form-<?= $m['id'] ?>').submit(); }" 
                                   class="w-5 h-5 accent-green-600 cursor-pointer" 
                                   <?= $m['status'] == 'completed' ? 'checked' : '' ?>>
                        </div>
                        <!-- Hidden Reopen Form -->
                        <form id="reopen-form-<?= $m['id'] ?>" action="project_action.php" method="POST" style="display:none;">
                            <input type="hidden" name="action" value="reopen_milestone">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <input type="hidden" name="project_id" value="<?= $project_id ?>">
                        </form>
                        <div class="flex-1">
                            <p class="text-sm font-bold <?= $m['status'] == 'completed' ? 'text-gray-500 line-through' : 'text-gray-800' ?>"><?= htmlspecialchars($m['title']) ?></p>
                            <?php if(!empty($m['description'])): ?>
                                <p class="text-xs text-gray-600 mt-1"><?= nl2br(htmlspecialchars($m['description'])) ?></p>
                            <?php endif; ?>
                            <?php if($m['status'] == 'completed' && !empty($m['completion_notes'])): ?>
                                <div class="mt-2 bg-green-100 p-2 rounded text-xs text-green-800 border border-green-200">
                                    <i class="fas fa-check-circle mr-1"></i> <strong>Done:</strong> <?= nl2br(htmlspecialchars($m['completion_notes'])) ?>
                                </div>
                            <?php endif; ?>
                            <?php if($m['due_date']): ?>
                                <p class="text-xs text-blue-500 mt-1"><i class="fas fa-clock mr-1"></i> <?= $m['due_date'] ?></p>
                            <?php endif; ?>
                        </div>
                        <form action="project_action.php" method="POST" onsubmit="return confirm('Delete milestone?');">
                            <input type="hidden" name="action" value="delete_milestone">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <input type="hidden" name="project_id" value="<?= $project_id ?>">
                            <button class="text-gray-400 hover:text-red-500"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($milestones)): ?>
                        <p class="text-center text-gray-400 text-sm py-4">No milestones yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <form action="project_action.php" method="POST" onsubmit="return confirm('<?= __('delete_project_confirm') ?>');">
                    <input type="hidden" name="action" value="delete_project">
                    <input type="hidden" name="id" value="<?= $project_id ?>">
                    <button class="text-red-500 text-sm hover:underline"><?= __('delete_project') ?></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function generateAnalysis() {
            const content = document.getElementById('ai-content');
            const loading = document.getElementById('ai-loading');
            
            content.classList.add('hidden');
            loading.classList.remove('hidden');
            
            // Simulate AI processing delay
            setTimeout(() => {
                loading.classList.add('hidden');
                content.classList.remove('hidden');
            }, 1500);
        }

        function openCompleteModal(id, title) {
            document.getElementById('complete_milestone_id').value = id;
            document.getElementById('complete_milestone_title').innerText = title;
            document.getElementById('completeTaskModal').classList.remove('hidden');
        }
    </script>

    <!-- Edit Project Modal -->
    <div id="editProjectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 class="text-xl font-bold mb-4"><?= __('edit_project') ?></h2>
            <form action="project_action.php" method="POST">
                <input type="hidden" name="action" value="update_project">
                <input type="hidden" name="id" value="<?= $project['id'] ?>">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700"><?= __('project_name') ?></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($project['name']) ?>" class="w-full border p-2 rounded" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700"><?= __('description') ?></label>
                        <textarea name="description" class="w-full border p-2 rounded h-24"><?= htmlspecialchars($project['description']) ?></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700"><?= __('start_date') ?></label>
                            <input type="date" name="start_date" value="<?= $project['start_date'] ?>" class="w-full border p-2 rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700"><?= __('end_date') ?></label>
                            <input type="date" name="end_date" value="<?= $project['end_date'] ?>" class="w-full border p-2 rounded">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700"><?= __('status') ?></label>
                        <select name="status" class="w-full border p-2 rounded bg-white">
                            <option value="planned" <?= $project['status'] == 'planned' ? 'selected' : '' ?>>Planned</option>
                            <option value="in_progress" <?= $project['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="on_hold" <?= $project['status'] == 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                            <option value="completed" <?= $project['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('editProjectModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"><?= __('cancel') ?></button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded font-bold hover:bg-blue-700"><?= __('save_changes') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Complete Task Modal (With Notes) -->
    <div id="completeTaskModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 class="text-xl font-bold mb-2"><?= __('complete_task_title') ?></h2>
            <p class="text-gray-600 text-sm mb-4" id="complete_milestone_title"></p>
            
            <form action="project_action.php" method="POST">
                <input type="hidden" name="action" value="complete_milestone">
                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                <input type="hidden" name="id" id="complete_milestone_id">
                
                <label class="block text-sm font-bold text-gray-700 mb-2"><?= __('completion_notes_label') ?></label>
                <textarea name="completion_notes" class="w-full border p-2 rounded h-24 text-sm" placeholder="<?= __('completion_notes_placeholder') ?>" required></textarea>
                
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('completeTaskModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"><?= __('cancel') ?></button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700"><?= __('mark_complete') ?></button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>