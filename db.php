<?php
require_once 'config.php'; // Load .env variables

try {
    // Create a new PDO connection
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Enable error handling
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch as associative array
        PDO::ATTR_EMULATE_PREPARES => false, // Disable emulated prepared statements
    ]);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
