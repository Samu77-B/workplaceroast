<?php
require_once __DIR__ . '/_bootstrap.php';

$slug = strtolower(trim((string) ($_GET['slug'] ?? '')));
$slug = preg_replace('/[^a-z0-9-]/', '', $slug);
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing slug']);
    exit;
}

if (!table_exists($pdo, 'corporate_clients')) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$cols = table_columns($pdo, 'corporate_clients');
$hasSlug = in_array('slug', $cols, true);
$compact = str_replace('-', '', $slug);

$sql = 'SELECT id, name, access_code, is_active';
if ($hasSlug) {
    $sql .= ', slug';
}
if (in_array('tier', $cols, true)) {
    $sql .= ', tier';
}
$sql .= ' FROM corporate_clients WHERE is_active = 1 AND (';
$params = [];
if ($hasSlug) {
    $sql .= 'LOWER(slug) = ? OR ';
    $params[] = $slug;
}
$sql .= 'LOWER(REPLACE(name, \' \', \'\')) = ?)';
$params[] = $compact;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Cafe not found']);
    exit;
}

echo json_encode([
    'id' => (int) $row['id'],
    'name' => $row['name'],
    'slug' => $row['slug'] ?? $slug,
    'tier' => $row['tier'] ?? 'basic',
    'access_code' => $row['access_code'],
]);
