<?php
require_once 'db.php';

$lifetime = 604800;
session_set_cookie_params($lifetime);
ini_set('session.gc_maxlifetime', $lifetime);
session_start();

if (isset($_POST['register'])) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    try {
        $stmt->execute([$_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT)]);
        $auth_msg = "Registered. Please login.";
    } catch (Exception $e) {
        $auth_msg = "Error: Username taken.";
    }
}

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
        $auth_msg = "Invalid login.";
    }
}

function checkAuth() {
    return isset($_SESSION['user_id']);
}
?>
