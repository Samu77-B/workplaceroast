<?php
require_once __DIR__ . '/_bootstrap.php';

function ensure_addon_tables(PDO $pdo): void
{
    if (!table_exists($pdo, 'addons')) {
        $pdo->exec(
            'CREATE TABLE addons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                corporate_client_id INT NULL,
                name VARCHAR(120) NOT NULL,
                type VARCHAR(40) NOT NULL DEFAULT \'other\',
                price DECIMAL(8,2) NOT NULL DEFAULT 0,
                display_order INT NOT NULL DEFAULT 0,
                is_available TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    } else {
        $cols = table_columns($pdo, 'addons');
        $definitions = [
            'corporate_client_id' => 'INT NULL',
            'name' => 'VARCHAR(120) NOT NULL',
            'type' => "VARCHAR(40) NOT NULL DEFAULT 'other'",
            'price' => 'DECIMAL(8,2) NOT NULL DEFAULT 0',
            'display_order' => 'INT NOT NULL DEFAULT 0',
            'is_available' => 'TINYINT(1) NOT NULL DEFAULT 1',
        ];
        foreach ($definitions as $column => $ddl) {
            if (!in_array($column, $cols, true)) {
                $pdo->exec("ALTER TABLE addons ADD COLUMN `{$column}` {$ddl}");
            }
        }
    }
    if (!table_exists($pdo, 'product_addons')) {
        $pdo->exec(
            'CREATE TABLE product_addons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                addon_id INT NOT NULL,
                UNIQUE KEY product_addon (product_id, addon_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}

try {
    ensure_addon_tables($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not prepare add-ons tables']);
    exit;
}

$cols = table_columns($pdo, 'addons');
$has = static function (string $col) use ($cols): bool {
    return in_array($col, $cols, true);
};
$method = $_SERVER['REQUEST_METHOD'];

function format_addon(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'corporate_client_id' => isset($row['corporate_client_id']) && $row['corporate_client_id'] !== null
            ? (int) $row['corporate_client_id'] : null,
        'name' => $row['name'] ?? '',
        'type' => $row['type'] ?? 'other',
        'price' => isset($row['price']) ? (float) $row['price'] : 0,
        'display_order' => isset($row['display_order']) ? (int) $row['display_order'] : 0,
        'is_available' => !isset($row['is_available']) || !empty($row['is_available']) ? 1 : 0,
    ];
}

if ($method === 'GET') {
    $params = [];
    $productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
    $cafeId = isset($_GET['cafe_id']) ? (int) $_GET['cafe_id'] : 0;
    $orderCol = $has('display_order') ? 'display_order, name' : 'name';

    if ($productId > 0 && table_exists($pdo, 'product_addons')) {
        $sql = 'SELECT a.* FROM addons a
            INNER JOIN product_addons pa ON pa.addon_id = a.id
            WHERE pa.product_id = ?
            ORDER BY a.' . str_replace(', ', ', a.', $orderCol);
        $params[] = $productId;
    } else {
        $sql = 'SELECT * FROM addons';
        $where = [];
        if ($cafeId > 0 && $has('corporate_client_id')) {
            $where[] = '(corporate_client_id = ? OR corporate_client_id IS NULL)';
            $params[] = $cafeId;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY ' . $orderCol;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'addons' => array_map('format_addon', $stmt->fetchAll())]);
    exit;
}

$cafe = require_admin_or_cafe();
$body = json_input();

if ($method === 'POST' && isset($body['product_id']) && array_key_exists('addon_ids', $body)) {
    $productId = (int) $body['product_id'];
    $addonIds = array_values(array_filter(array_map('intval', (array) $body['addon_ids'])));
    if ($productId < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing product_id']);
        exit;
    }
    $pdo->prepare('DELETE FROM product_addons WHERE product_id = ?')->execute([$productId]);
    $insert = $pdo->prepare('INSERT INTO product_addons (product_id, addon_id) VALUES (?, ?)');
    foreach ($addonIds as $addonId) {
        if ($addonId > 0) {
            $insert->execute([$productId, $addonId]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'POST' || $method === 'PUT') {
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Add-on name is required']);
        exit;
    }
    $fields = [
        'name' => $name,
        'type' => trim((string) ($body['type'] ?? 'other')) ?: 'other',
        'price' => (float) ($body['price'] ?? 0),
    ];
    if ($has('display_order')) {
        $fields['display_order'] = (int) ($body['display_order'] ?? 0);
    }
    if ($has('is_available')) {
        $fields['is_available'] = !empty($body['is_available']) ? 1 : 0;
    }
    if ($has('corporate_client_id') && $cafe) {
        $fields['corporate_client_id'] = (int) $cafe['id'];
    }

    $editId = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    if ($method === 'PUT' || $editId > 0) {
        if ($editId < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing id']);
            exit;
        }
        $sets = [];
        $params = [];
        foreach ($fields as $col => $value) {
            $sets[] = "`{$col}` = ?";
            $params[] = $value;
        }
        if ($has('updated_at')) {
            $sets[] = 'updated_at = NOW()';
        }
        $params[] = $editId;
        $pdo->prepare('UPDATE addons SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
        echo json_encode(['success' => true, 'id' => $editId]);
        exit;
    }

    $colsSql = '`' . implode('`, `', array_keys($fields)) . '`';
    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $pdo->prepare("INSERT INTO addons ({$colsSql}) VALUES ({$placeholders})")->execute(array_values($fields));
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing id']);
        exit;
    }
    if (table_exists($pdo, 'product_addons')) {
        $pdo->prepare('DELETE FROM product_addons WHERE addon_id = ?')->execute([$id]);
    }
    $pdo->prepare('DELETE FROM addons WHERE id = ?')->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
