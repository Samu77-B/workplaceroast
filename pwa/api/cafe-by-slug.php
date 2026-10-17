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

ensure_cafe_branding_columns($pdo);
ensure_places_tables($pdo);

$cols = table_columns($pdo, 'corporate_clients');
$hasSlug = in_array('slug', $cols, true);
$compact = str_replace('-', '', $slug);

function cafe_public_payload(array $row, string $fallbackSlug): array
{
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'slug' => $row['slug'] ?? $fallbackSlug,
        'tier' => $row['tier'] ?? 'basic',
        'access_code' => $row['access_code'] ?? null,
        'brand_primary' => $row['brand_primary'] ?? null,
        'brand_accent' => $row['brand_accent'] ?? null,
        'logo_url' => $row['logo_url'] ?? null,
    ];
}

function load_cafe_by_id(PDO $pdo, array $cols, int $cafeId): ?array
{
    $sql = 'SELECT id, name, access_code, is_active';
    foreach (['slug', 'tier', 'brand_primary', 'brand_accent', 'logo_url'] as $col) {
        if (in_array($col, $cols, true)) {
            $sql .= ', ' . $col;
        }
    }
    $sql .= ' FROM corporate_clients WHERE id = ? AND is_active = 1 LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cafeId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

$placeRow = find_place_by_slug($pdo, $slug);
$cafeRow = null;

if ($placeRow && !empty($placeRow['is_active'])) {
    $cafeRow = load_cafe_by_id($pdo, $cols, (int) $placeRow['cafe_id']);
}

if (!$cafeRow) {
    $sql = 'SELECT id, name, access_code, is_active';
    foreach (['slug', 'tier', 'brand_primary', 'brand_accent', 'logo_url'] as $col) {
        if (in_array($col, $cols, true)) {
            $sql .= ', ' . $col;
        }
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
    $cafeRow = $stmt->fetch() ?: null;
    if ($cafeRow) {
        $placeId = seed_default_place_for_cafe($pdo, $cafeRow);
        if ($placeId) {
            $placeStmt = $pdo->prepare('SELECT * FROM places WHERE id = ? LIMIT 1');
            $placeStmt->execute([$placeId]);
            $placeRow = $placeStmt->fetch() ?: $placeRow;
        }
    }
}

if (!$cafeRow) {
    http_response_code(404);
    echo json_encode(['error' => 'Cafe not found']);
    exit;
}

$payload = cafe_public_payload($cafeRow, $slug);
if ($placeRow) {
    $payload['place'] = format_place($pdo, $placeRow);
}

echo json_encode($payload);
