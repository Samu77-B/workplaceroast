<?php
/**
 * Quick check after setup: https://workplaceroast.com/pwa/api/health.php
 * Does not expose credentials.
 */
header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'config' => 'missing',
        'hint' => 'Create pwa/api/config.php from config.example.php',
    ]);
    exit;
}

require_once $configPath;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'config' => 'invalid', 'hint' => '$pdo not defined in config.php']);
    exit;
}

try {
    $pdo->query('SELECT 1');
    $tables = $pdo->query("SHOW TABLES LIKE 'corporate_clients'")->fetchColumn();
    echo json_encode([
        'ok' => true,
        'database' => 'connected',
        'corporate_clients_table' => (bool) $tables,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'database' => 'error',
        'hint' => 'Check DB user, password, and database name in config.php',
    ]);
}
