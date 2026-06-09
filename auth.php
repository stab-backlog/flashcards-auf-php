<?php
// auth.php is the central authentication bootstrap.
// Every page that needs the database or login state includes this file first.
require_once 'db.php';

// Configure how long PHP session cookies should live.
// 604800 seconds = 7 days.
$lifetime = 604800;

// Tell PHP to make the session cookie persist for $lifetime seconds.
session_set_cookie_params($lifetime);

// Set the server-side garbage collection lifetime.
// PHP may remove idle session data after this time.
ini_set('session.gc_maxlifetime', $lifetime);

// Start the session so $_SESSION is available on this request.
session_start();

// Registration flow:
// If the form submitted a field named "register", treat this as a new-user sign-up.
if (isset($_POST['register'])) {
    // Prepare an INSERT statement to add a new user.
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");

    try {
        // Store the username and a hashed password, not plaintext.
        // password_hash() generates a secure one-way hash.
        $stmt->execute([
            $_POST['username'],
            password_hash($_POST['password'], PASSWORD_DEFAULT)
        ]);

        // Friendly success message shown back on the login page.
        $auth_msg = "Registered. Please login.";
    } catch (Exception $e) {
        // This assumes the insert failed because the username already exists.
        $auth_msg = "Error: Username taken.";
    }
}

// Login flow:
// If the form submitted a field named "login", validate credentials.
if (isset($_POST['login'])) {
    // Fetch the user row by username.
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    // If a user row exists and the submitted password matches the stored hash, log in.
    if ($user && password_verify($_POST['password'], $user['password'])) {
        // Store the user identity in the session.
        // Other pages use this to decide whether the request is authenticated.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Redirect to the home page after successful login.
        header("Location: index.php");
        exit;
    } else {
        // Generic failure message to avoid revealing whether the username or password was wrong.
        $auth_msg = "Invalid login.";
    }
}

// Small helper used throughout the app.
// Returns true if the session has a logged-in user attached to it.
function checkAuth() {
    return isset($_SESSION['user_id']);
}
?>