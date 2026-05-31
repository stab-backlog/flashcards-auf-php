<?php
require_once 'db.php';

// set session lifetime to 7 days
$lifetime = 604800;
session_set_cookie_params($lifetime);
ini_set('session.gc_maxlifetime', $lifetime);
session_start();

// registration logic
if (isset($_POST['register'])) {
    $user = $_POST['username'];
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    try {
        $stmt->execute([$user, $hash]);
        $msg = "Registration successful! Please login.";
    } catch (Exception $e) {
        $msg = "Username already exists.";
    }
}

// log-in logic
if (isset($_POST['login'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit;
    } else {
        $msg = "Invalid credentials.";
    }
}

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    return true;
}
?>
