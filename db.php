<?php
// Database connection settings.
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'anki_web_db';

try {
    // Connect to MySQL using PDO.
    // The DSN here connects to the server first, before selecting a database.
    $pdo = new PDO("mysql:host=$host", $user, $pass);

    // Make database errors throw exceptions so they can be caught in try/catch.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the database if it does not already exist.
    // utf8mb4 is the correct modern character set for full Unicode support.
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4");

    // Switch the active connection to that database.
    $pdo->exec("USE `$dbname` ");

    // Users table:
    // - id is the primary key
    // - username must be unique
    // - password stores the hash, not the plaintext password
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    // Decks table:
    // - user_id links a deck to its owner
    // - is_public controls whether other users can view it
    $pdo->exec("CREATE TABLE IF NOT EXISTS decks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        is_public TINYINT(1) DEFAULT 0
    )");

    // Cards table:
    // - deck_id links each card to a deck
    // - difficulty and times_reviewed support spaced repetition / learning logic
    $pdo->exec("CREATE TABLE IF NOT EXISTS cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deck_id INT NOT NULL,
        front TEXT NOT NULL,
        back TEXT NOT NULL,
        difficulty INT DEFAULT 0,
        times_reviewed INT DEFAULT 0
    )");
} catch (PDOException $e) {
    // If the database cannot be created or queried, stop the app.
    die("DB Error: " . $e->getMessage());
}
?>