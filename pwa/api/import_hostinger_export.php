<?php
/**
 * Import Hostinger/PHPMyAdmin JSON export into Railway database
 * 
 * Prerequisites (run in order on fresh Railway DB):
 *   1. setup_database.php
 *   2. update_database.php
 *   3. update_database_v2.php
 *   4. update_database_v3.php
 * 
 * Usage:
 *   - Ensure config.php has Railway credentials
 *   - Run: php import_hostinger_export.php
 *   - Or visit: https://yoursite.com/pwa/api/import_hostinger_export.php
 * 
 * IMPORTANT: Delete or protect this file after import (security)
 */
header('Content-Type: text/plain; charset=utf-8');

// Path to JSON export - adjust if needed
$jsonPath = __DIR__ . '/import_data/u556329104_workplaceroast.json';
if (!file_exists($jsonPath)) {
    $jsonPath = dirname(__DIR__, 2) . '/website/u556329104_workplaceroast.json';
}
if (!file_exists($jsonPath)) {
    die("ERROR: JSON file not found. Place u556329104_workplaceroast.json in:\n  - PWA/api/import_data/ or\n  - website/\n");
}

require_once __DIR__ . '/config.php';

$raw = file_get_contents($jsonPath);
$items = json_decode($raw, true);

if (!is_array($items)) {
    die("ERROR: Invalid JSON format\n");
}

// Extract table data from PHPMyAdmin export format
$tables = [];
foreach ($items as $item) {
    if (isset($item['type']) && $item['type'] === 'table' && isset($item['name'], $item['data'])) {
        $tables[$item['name']] = $item['data'];
    }
}

if (empty($tables)) {
    die("ERROR: No table data found in JSON\n");
}

echo "Found tables: " . implode(', ', array_keys($tables)) . "\n\n";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable foreign key checks for truncate
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Truncate tables in reverse dependency order
    $truncateOrder = ['discount_products', 'product_addons', 'orders', 'discounts', 'products', 'addons', 'categories', 'corporate_clients', 'admin_users'];
    foreach ($truncateOrder as $tbl) {
        if (isset($tables[$tbl])) {
            try {
                $pdo->exec("TRUNCATE TABLE `$tbl`");
                echo "Truncated: $tbl\n";
            } catch (PDOException $e) {
                // Table might not exist yet
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    echo "Skipped truncate $tbl (table not found - run setup_database.php first)\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n";

    // Insert in dependency order
    $insertOrder = ['admin_users', 'corporate_clients', 'categories', 'products', 'discounts', 'discount_products', 'orders', 'addons', 'product_addons'];
    
    foreach ($insertOrder as $tableName) {
        if (!isset($tables[$tableName]) || empty($tables[$tableName])) {
            continue;
        }
        
        $rows = $tables[$tableName];
        $count = 0;
        
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $placeholders = array_fill(0, count($cols), '?');
            $sql = sprintf(
                "INSERT INTO `%s` (`%s`) VALUES (%s)",
                $tableName,
                implode('`, `', $cols),
                implode(', ', $placeholders)
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($row));
            $count++;
        }
        
        echo "Inserted $count rows into $tableName\n";
    }
    
    echo "\nDone. Products and related data imported into Railway database.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
