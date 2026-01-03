<?php
require '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql_file = '../sql/database.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("SQL file not found at: $sql_file");
        }
        $sql = file_get_contents($sql_file);
        $pdo->exec($sql);
        $message = "Database initialized successfully!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-96">
        <h1 class="text-xl font-bold mb-4">Database Setup</h1>
        <?php if ($message): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?= $message ?> <a href="index.php" class="underline font-bold ml-2">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <p class="mb-4 text-gray-600">The database tables are missing. Click below to create them automatically.</p>
            <form method="POST">
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700 transition">Run Installer</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>