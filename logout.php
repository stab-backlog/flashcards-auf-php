<?php
// Start the session so it can be cleared.
session_start();

// Remove all session variables from memory.
session_unset();

// Destroy the server-side session data.
session_destroy();

// Expire the session cookie in the browser.
setcookie(session_name(), '', time() - 3600, '/');

// Return the user to the landing page.
header("Location: index.php");
exit;
?>