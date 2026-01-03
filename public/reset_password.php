<?php
require '../config/db.php';

$token = $_GET['token'] ?? '';
$error = '';

if (!$token) {
    die("Invalid token");
}

// Verify token
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "Invalid or expired token.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        set_flash('error', 'Passwords do not match');
    } elseif (strlen($password) < 8) {
        set_flash('error', 'Password too short');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);
        
        set_flash('success', 'Password updated! Please login.');
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Set New Password - Life OS</title>
    <style>
        body { background-color: #f3f4f6; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .form-container { width: 350px; background-color: #fff; box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px; border-radius: 10px; padding: 20px 30px; }
        .title { text-align: center; font-family: sans-serif; margin: 10px 0 30px 0; font-size: 24px; font-weight: 800; }
        .form { display: flex; flex-direction: column; gap: 18px; }
        .input { border-radius: 20px; border: 1px solid #c0c0c0; padding: 12px 15px; }
        .form-btn { padding: 10px 15px; border-radius: 20px; border: 0; background: teal; color: white; cursor: pointer; font-weight: bold; }
        .error { color: red; text-align: center; font-family: sans-serif; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="form-container">
      <p class="title">New Password</p>
      <?php display_flash(); ?>
      <?php if($error): ?>
          <div class="error"><?= $error ?> <br><a href="forgot_password.php" style="color:teal;">Try again</a></div>
      <?php else: ?>
      <form class="form" method="POST">
        <input type="password" name="password" class="input" placeholder="New Password" required>
        <input type="password" name="confirm_password" class="input" placeholder="Confirm Password" required>
        <button class="form-btn">Update Password</button>
      </form>
      <?php endif; ?>
    </div>
</body>
</html>