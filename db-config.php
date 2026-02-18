<?php
// public_html/db-config.php
// Minimal, secure DB config — include this from other scripts.

$DB_HOST = 'localhost';
$DB_NAME = 'a1761528_diozs';
$DB_USER = 'a1761528_diozs';        // If you created a different user (e.g. a1761528_diozsuser), use that exact name.
$DB_PASS = 'R-rkNGPiKq{k';     // <<-- change this

// Use PDO (recommended)
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // In production, log and show a friendly message instead
    error_log('DB connect error: '.$e->getMessage());
    die('Database connection error.'); // brief for visitors
}
