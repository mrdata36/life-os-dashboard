<?php
require '../config/db.php';

$provider = $_GET['provider'] ?? '';

// Placeholder for OAuth logic
if ($provider === 'google') {
    // Redirect to Google OAuth
    // header("Location: https://accounts.google.com/...");
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h1>Google Login</h1><p>To make this work, you need to set up a <strong>Google Cloud Project</strong> and get a Client ID.</p><a href='login.php'>Back to Login</a></div>");
} elseif ($provider === 'apple') {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h1>Apple Login</h1><p>To make this work, you need an <strong>Apple Developer Account</strong> and Service ID.</p><a href='login.php'>Back to Login</a></div>");
} else {
    header("Location: login.php");
}
?>