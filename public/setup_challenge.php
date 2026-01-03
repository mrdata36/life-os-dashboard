<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    
    // Define the 30-day plan blocks based on your checklist
    $blocks = [
        [
            'range' => [1, 5],
            'theme' => 'KUANZA NA BASE',
            'details' => "4:00–4:20 – Morning routine (water, mind clearing)\n4:20–5:20 – Python basics: variables, loops, functions\n5:20–6:00 – Track A: OS install / PC cleanup / data backup practice\n6:00–6:30 – Andika learned points + push GitHub\n📌 Output: Repo 1 + client ya simu / marafiki wa PC fix"
        ],
        [
            'range' => [6, 10],
            'theme' => 'KUIMARISHA BASE',
            'details' => "4:00–4:20 – Morning routine\n4:20–5:20 – Python: lists, dictionaries, reading/writing files\n5:20–6:00 – Data cleaning mini project (Excel → Python)\n6:00–6:30 – Record progress + GitHub update\n📌 Output: Repo 2 + mini data cleaning project"
        ],
        [
            'range' => [11, 15],
            'theme' => 'FREELANCE PREP',
            'details' => "4:00–4:20 – Morning routine\n4:20–5:20 – Python: functions + modules + small scripts\n5:20–6:00 – Track A: Practice WordPress install / small website / client setup\n6:00–6:30 – Write notes + prepare portfolio screenshots\n📌 Output: Ready for first paying client / showcase work"
        ],
        [
            'range' => [16, 20],
            'theme' => 'DATA ENGINEERING INTRO',
            'details' => "4:00–4:20 – Morning routine\n4:20–5:20 – SQL basics: SELECT, WHERE, JOIN, GROUP BY\n5:20–6:00 – Python + SQL: mini ETL project (CSV → SQLite → analysis)\n6:00–6:30 – Push project to GitHub + daily review\n📌 Output: GitHub repo 3 + small ETL demo"
        ],
        [
            'range' => [21, 25],
            'theme' => 'LINUX + SECURITY BASICS',
            'details' => "4:00–4:20 – Morning routine\n4:20–5:20 – Linux commands: files, permissions, networking basics\n5:20–6:00 – Track A: PC repair / install practice / client work\n6:00–6:30 – Record notes + GitHub update\n📌 Output: Confident in Linux + minor security awareness"
        ],
        [
            'range' => [26, 30],
            'theme' => 'FINAL PUSH & REVIEW',
            'details' => "4:00–4:20 – Morning routine\n4:20–5:20 – Python + SQL + Linux integration project\n5:20–6:00 – Portfolio polish: screenshots, GitHub repos, ready for clients\n6:00–6:30 – Plan next month: more complex project / freelance outreach\n📌 Output: Ready for real clients + internship application + review skills gained"
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO roadmap (topic, description, scheduled_date) VALUES (?, ?, ?)");

    for ($day = 1; $day <= 30; $day++) {
        // Calculate date for each day
        $date = date('Y-m-d', strtotime("+" . ($day - 1) . " days", strtotime($start_date)));
        
        // Find which block this day belongs to
        $block_data = null;
        foreach ($blocks as $block) {
            if ($day >= $block['range'][0] && $day <= $block['range'][1]) {
                $block_data = $block;
                break;
            }
        }

        if ($block_data) {
            $topic = "Siku $day: " . $block_data['theme'];
            $description = $block_data['details'];
            $stmt->execute([$topic, $description, $date]);
        }
    }

    header("Location: roadmap.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup 30-Day Challenge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-96">
        <h1 class="text-xl font-bold mb-4 text-center">🚀 30-Day Challenge</h1>
        <p class="mb-4 text-gray-600 text-sm">This will populate your roadmap with the "Morning Success" plan (Python, Data, Linux).</p>
        
        <form method="POST">
            <label class="block text-sm font-bold mb-2">Start Date:</label>
            <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" class="w-full border p-2 rounded mb-4" required>
            
            <button type="submit" class="w-full bg-yellow-500 text-white p-2 rounded hover:bg-yellow-600 transition font-bold">
                Import Plan
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="roadmap.php" class="text-gray-500 text-sm hover:underline">Cancel</a>
        </div>
    </div>
</body>
</html>