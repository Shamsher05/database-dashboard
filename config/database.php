<?php
/**
 * Database Connection Configuration
 * ----------------------------------
 * Change the credentials below to match your MySQL setup.
 * This is the ONLY file you need to edit for DB connection.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'database_dashboard';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
    "mysql:host={$DB_HOST};port=3307;dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    // Never leak credentials — just a safe generic error.
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please check config/database.php.'
    ]));
}
