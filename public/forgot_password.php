<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        // Expires in 1 hour
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);
        
        // In a real app, send email here.
        // For dev/demo, we'll show the link directly.
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
        set_flash('success', "Password reset link (Simulated Email): <br><a href='$reset_link' class='underline font-bold text-blue-600'>Click Here to Reset Password</a>");
    } else {
        set_flash('error', 'Email not found.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password - Life OS</title>
    <style>
        body {
            background-color: #f3f4f6;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .form-container {
          width: 350px;
          min-height: 300px;
          background-color: #fff;
          box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
          border-radius: 10px;
          box-sizing: border-box;
          padding: 20px 30px;
        }
        .title {
          text-align: center;
          font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
          margin: 10px 0 30px 0;
          font-size: 24px;
          font-weight: 800;
        }
        .form {
          width: 100%;
          display: flex;
          flex-direction: column;
          gap: 18px;
          margin-bottom: 15px;
        }
        .input {
          border-radius: 20px;
          border: 1px solid #c0c0c0;
          outline: 0 !important;
          box-sizing: border-box;
          padding: 12px 15px;
        }
        .form-btn {
          padding: 10px 15px;
          font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
          border-radius: 20px;
          border: 0 !important;
          outline: 0 !important;
          background: teal;
          color: white;
          cursor: pointer;
          box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }
        .sign-up-label {
          margin: 0;
          font-size: 10px;
          color: #747474;
          font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
          text-align: center;
        }
        .sign-up-link {
          margin-left: 1px;
          font-size: 11px;
          text-decoration: underline;
          text-decoration-color: teal;
          color: teal;
          cursor: pointer;
          font-weight: 800;
        }
    </style>
</head>
<body>
    <div class="form-container">
      <p class="title">Reset Password</p>
      <?php display_flash(); ?>
      <form class="form" method="POST">
        <input type="email" name="email" class="input" placeholder="Enter your email" required>
        <button class="form-btn">Send Reset Link</button>
      </form>
      <p class="sign-up-label">
        Remembered it? <a href="login.php" class="sign-up-link">Log in</a>
      </p>
    </div>
</body>
</html>