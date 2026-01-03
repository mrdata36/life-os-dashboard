<?php
require '../config/db.php';
require_login();
$user_id = get_user_id();

// Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Settings
try {
    $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();
} catch (PDOException $e) {
    $settings = []; // Fail silently if table doesn't exist yet
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Update Basic Info
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone_number = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $user_id]);

        // 2. Update Settings
        $currency = $_POST['currency'];
        $theme = $_POST['theme'];
        
        // Upsert Settings
        $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, currency, theme) VALUES (?, ?, ?) 
                               ON CONFLICT (user_id) DO UPDATE SET currency = EXCLUDED.currency, theme = EXCLUDED.theme");
        $stmt->execute([$user_id, $currency, $theme]);

        // 3. Handle Image Upload
        if (!empty($_FILES['profile_image']['name'])) {
            $target_dir = __DIR__ . "/uploads/"; // Use absolute path
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "profile_" . $user_id . "." . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->execute([$new_filename, $user_id]);
            }
        }

        set_flash('success', 'Profile updated successfully!');
        header("Location: profile.php");
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() === '42703' || $e->getCode() === '42P01') {
             die("<div style='font-family:sans-serif;padding:20px;text-align:center;margin-top:50px;'><strong>Database needs update.</strong><br><br><a href='install.php' style='color:white;background:#2563eb;padding:10px 20px;text-decoration:none;border-radius:5px;'>Update Database</a></div>");
        }
        set_flash('error', 'Update failed: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile - Life OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">

    <nav class="flex gap-4 mb-6 bg-white p-4 rounded shadow">
        <a href="index.php" class="text-gray-600 hover:text-blue-600 font-bold"><i class="fas fa-home"></i> Dashboard</a>
        <a href="profile.php" class="text-blue-600 font-bold border-b-2 border-blue-600"><i class="fas fa-user"></i> Profile</a>
        <a href="logout.php" class="text-red-500 font-bold ml-auto"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>

    <?php display_flash(); ?>

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 h-32"></div>
        <div class="px-6 py-4 relative">
            <!-- Profile Image -->
            <div class="absolute -top-16 left-6">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['profile_image']) ?>?v=<?= time() ?>" class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-lg">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full border-4 border-white bg-gray-300 flex items-center justify-center text-4xl text-gray-500 shadow-lg">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="ml-40 mt-2">
                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h1>
                <p class="text-gray-500 text-sm"><?= htmlspecialchars($user['email']) ?></p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Info -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" class="w-full border p-2 rounded">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" class="w-full border p-2 rounded">
                </div>
                
                <!-- Settings -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Preferred Currency</label>
                    <select name="currency" class="w-full border p-2 rounded bg-white">
                        <option value="TZS" <?= ($settings['currency'] ?? '') == 'TZS' ? 'selected' : '' ?>>TZS (Tanzanian Shilling)</option>
                        <option value="USD" <?= ($settings['currency'] ?? '') == 'USD' ? 'selected' : '' ?>>USD (US Dollar)</option>
                        <option value="KES" <?= ($settings['currency'] ?? '') == 'KES' ? 'selected' : '' ?>>KES (Kenyan Shilling)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Theme</label>
                    <select name="theme" class="w-full border p-2 rounded bg-white">
                        <option value="light" <?= ($settings['theme'] ?? '') == 'light' ? 'selected' : '' ?>>Light Mode</option>
                        <option value="dark" <?= ($settings['theme'] ?? '') == 'dark' ? 'selected' : '' ?>>Dark Mode</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Change Profile Picture</label>
                    <input type="file" name="profile_image" class="w-full border p-2 rounded bg-gray-50">
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">Save Changes</button>
        </form>
    </div>
</body>
</html>