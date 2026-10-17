<?php
require_once __DIR__ . '/_bootstrap.php';

if (!table_exists($pdo, 'products')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'products table not found']);
    exit;
}

ensure_product_catalog_columns($pdo);

$cols = table_columns($pdo, 'products');
$has = static function (string $col) use ($cols): bool {
    return in_array($col, $cols, true);
};
$method = $_SERVER['REQUEST_METHOD'];

function format_product(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'corporate_client_id' => isset($row['corporate_client_id']) && $row['corporate_client_id'] !== null
            ? (int) $row['corporate_client_id'] : null,
        'name' => $row['name'] ?? '',
        'category' => $row['category'] ?? '',
        'subcategory' => $row['subcategory'] ?? null,
        'type' => $row['type'] ?? '',
        'description' => $row['description'] ?? '',
        'regular_price' => isset($row['regular_price']) ? (float) $row['regular_price'] : 0,
        'large_price' => isset($row['large_price']) ? (float) $row['large_price'] : 0,
        'price' => isset($row['regular_price']) ? (float) $row['regular_price'] : 0,
        'sizes' => [
            'regular' => isset($row['regular_price']) ? (float) $row['regular_price'] : 0,
            'large' => isset($row['large_price']) ? (float) $row['large_price'] : 0,
        ],
        'active' => !isset($row['is_available']) || !empty($row['is_available']),
        'is_available' => !isset($row['is_available']) || !empty($row['is_available']),
    ];
}

if ($method === 'GET') {
    $sql = 'SELECT * FROM products';
    $params = [];
    $cafeId = isset($_GET['cafe_id']) ? (int) $_GET['cafe_id'] : 0;
    if ($cafeId > 0 && $has('corporate_client_id')) {
        $sql .= ' WHERE corporate_client_id = ?';
        $params[] = $cafeId;
    }
    $sql .= ' ORDER BY name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'products' => array_map('format_product', $stmt->fetchAll())]);
    exit;
}

$cafe = require_admin_or_cafe();
$body = json_input();

$productFields = static function (array $body, callable $hasCol, ?array $cafe) {
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product name is required']);
        exit;
    }
    $wanted = [
        'name' => $name,
        'category' => trim((string) ($body['category'] ?? '')),
        'subcategory' => ($body['subcategory'] ?? '') !== '' ? trim((string) $body['subcategory']) : null,
        'type' => trim((string) ($body['type'] ?? '')) ?: 'other',
        'description' => trim((string) ($body['description'] ?? '')),
        'regular_price' => (float) ($body['regular_price'] ?? 0),
        'large_price' => ($body['large_price'] === null || $body['large_price'] === ''
            ? 0.0
            : (float) ($body['large_price'] ?? 0)),
    ];
    $fields = [];
    foreach ($wanted as $col => $value) {
        if ($hasCol($col)) {
            $fields[$col] = $value;
        }
    }
    if ($hasCol('is_available')) {
        $fields['is_available'] = array_key_exists('is_available', $body) ? (!empty($body['is_available']) ? 1 : 0) : 1;
    }
    if ($hasCol('corporate_client_id') && $cafe) {
        $fields['corporate_client_id'] = (int) $cafe['id'];
    }
    return $fields;
};

if ($method === 'POST') {
    try {
        $fields = $productFields($body, $has, $cafe);
        $colsSql = '`' . implode('`, `', array_keys($fields)) . '`';
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO products ({$colsSql}) VALUES ({$placeholders})")->execute(array_values($fields));
        $id = (int) $pdo->lastInsertId();
        echo json_encode(['success' => true, 'product_id' => $id, 'id' => $id]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not save product']);
    }
    exit;
}

if ($method === 'PUT') {
    $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing id']);
        exit;
    }
    try {
        $fields = $productFields($body, $has, $cafe);
        $sets = [];
        $params = [];
        foreach ($fields as $col => $value) {
            $sets[] = "`{$col}` = ?";
            $params[] = $value;
        }
        if ($has('updated_at')) {
            $sets[] = 'updated_at = NOW()';
        }
        $params[] = $id;
        $pdo->prepare('UPDATE products SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not update product']);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing id']);
        exit;
    }
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
