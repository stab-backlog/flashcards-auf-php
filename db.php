<?php
$host = 'localhost';
$user = 'root';
$pass = ''; 
$dbname = 'anki_web_db';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4");
    $pdo->exec("USE `$dbname` ");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS decks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        is_public TINYINT(1) DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deck_id INT NOT NULL,
        front TEXT NOT NULL,
        back TEXT NOT NULL
    )");
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
