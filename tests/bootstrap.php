<?php

// phpunit starts here instead of vendor/autoload.php, so the test database
// exists before the first test needs it. Saves everybody a manual CREATE DATABASE.

require __DIR__.'/../vendor/autoload.php';

// reuse the MySQL host and login from .env, the same ones the app uses
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $password);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS pegal_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
} catch (PDOException $e) {
    fwrite(STDERR, "Could not reach MySQL to create the pegal_test database: {$e->getMessage()}\n");
    exit(1);
}
