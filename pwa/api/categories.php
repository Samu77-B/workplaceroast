<?php
require_once __DIR__ . '/_bootstrap.php';

ensure_category_cafe_column($pdo);

if (!table_exists($pdo, 'categories')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'categories table not found']);
    exit;
}

$cols = table_columns($pdo, 'categories');
$has = static function (string $col) use ($cols): bool {
    return in_array($col, $cols, true);
};
$method = $_SERVER['REQUEST_METHOD'];

function format_category(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'corporate_client_id' => isset($row['corporate_client_id']) ? ($row['corporate_client_id'] !== null ? (int) $row['corporate_client_id'] : null) : null,
        'name' => $row['name'] ?? '',
        'slug' => $row['slug'] ?? '',
        'description' => $row['description'] ?? '',
        'subtitle' => $row['subtitle'] ?? '',
        'icon' => $row['icon'] ?? '',
        'order' => isset($row['display_order']) ? (int) $row['display_order'] : (int) ($row['order'] ?? 0),
        'active' => !empty($row['active']),
    ];
}

if ($method === 'GET') {
    $sql = 'SELECT * FROM categories';
    $params = [];
    $where = [];
    if (isset($_GET['id'])) {
        $where[] = 'id = ?';
        $params[] = (int) $_GET['id'];
    }
    $cafeId = isset($_GET['cafe_id']) ? (int) $_GET['cafe_id'] : 0;
    if ($cafeId > 0 && $has('corporate_client_id')) {
        $prodCols = table_exists($pdo, 'products') ? table_columns($pdo, 'products') : [];
        if (in_array('corporate_client_id', $prodCols, true) && in_array('category', $prodCols, true)) {
            $where[] = '(corporate_client_id = ? OR slug IN (SELECT DISTINCT category FROM products WHERE corporate_client_id = ? AND category IS NOT NULL AND category != \'\'))';
            $params[] = $cafeId;
            $params[] = $cafeId;
        } else {
            $where[] = 'corporate_client_id = ?';
            $params[] = $cafeId;
        }
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $orderCol = $has('display_order') ? 'display_order' : ($has('order') ? '`order`' : 'id');
    $sql .= " ORDER BY {$orderCol} ASC, name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('format_category', $stmt->fetchAll());
    echo json_encode(['success' => true, 'categories' => $rows]);
    exit;
}

$cafe = require_admin_or_cafe();
$body = json_input();

if ($method === 'POST' || $method === 'PUT') {
    $name = trim((string) ($body['name'] ?? ''));
    $slug = trim((string) ($body['slug'] ?? ''));
    if ($name === '' || $slug === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name and slug are required']);
        exit;
    }
    $fields = [
        'name' => $name,
        'slug' => $slug,
        'description' => trim((string) ($body['description'] ?? '')),
        'active' => !empty($body['active']) ? 1 : 0,
    ];
    if ($has('icon')) {
        $fields['icon'] = trim((string) ($body['icon'] ?? ''));
    }
    if ($has('corporate_client_id') && $cafe) {
        $fields['corporate_client_id'] = (int) $cafe['id'];
    }
    if ($has('subtitle')) {
        $fields['subtitle'] = trim((string) ($body['subtitle'] ?? ''));
    }
    $orderCol = $has('display_order') ? 'display_order' : ($has('order') ? 'order' : null);
    if ($orderCol) {
        $fields[$orderCol] = (int) ($body['order'] ?? $body['display_order'] ?? 0);
    }

    $editId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($body['id'] ?? 0);
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
        $pdo->prepare('UPDATE categories SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
        echo json_encode(['success' => true, 'id' => $editId]);
        exit;
    }

    $colsSql = '`' . implode('`, `', array_keys($fields)) . '`';
    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $pdo->prepare("INSERT INTO categories ({$colsSql}) VALUES ({$placeholders})")->execute(array_values($fields));
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($body['id'] ?? 0);
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing id']);
        exit;
    }
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
