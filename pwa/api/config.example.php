<?php
/**
 * Copy this file to config.php on the server (config.php is gitignored).
 * Super Dashboard and other /pwa/api scripts require $pdo and $ADMIN_TOKEN.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'u556329104_workplaceroast';
$DB_USER = 'your_db_user';
$DB_PASS = 'your_db_password';

$ADMIN_TOKEN = 'teas2024'; // Must match CONFIG.ADMIN_TOKEN in config.js (production)

$pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
