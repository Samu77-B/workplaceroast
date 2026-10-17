<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();

if (!table_exists($pdo, 'corporate_clients')) {
    http_response_code(500);
    echo json_encode(['error' => 'corporate_clients table not found']);
    exit;
}

$added = [];
$cols = table_columns($pdo, 'corporate_clients');

$definitions = [
    'slug' => 'VARCHAR(80) NULL',
    'tier' => "VARCHAR(20) NOT NULL DEFAULT 'basic'",
    'contact_email' => 'VARCHAR(255) NULL',
    'contact_name' => 'VARCHAR(255) NULL',
    'notes' => 'TEXT NULL',
    'logo_url' => 'VARCHAR(255) NULL',
    'brand_primary' => 'VARCHAR(7) NULL',
    'brand_accent' => 'VARCHAR(7) NULL',
];

foreach ($definitions as $column => $ddl) {
    if (!in_array($column, $cols, true)) {
        $pdo->exec("ALTER TABLE corporate_clients ADD COLUMN `{$column}` {$ddl}");
        $added[] = $column;
    }
}

if (in_array('slug', table_columns($pdo, 'corporate_clients'), true)) {
    $rows = $pdo->query('SELECT id, name, slug FROM corporate_clients')->fetchAll();
    $used = [];
    foreach ($rows as $row) {
        if (!empty($row['slug'])) {
            $used[strtolower($row['slug'])] = true;
        }
    }
    $update = $pdo->prepare('UPDATE corporate_clients SET slug = ? WHERE id = ?');
    foreach ($rows as $row) {
        if (!empty($row['slug'])) {
            continue;
        }
        $base = slugify($row['name']);
        $candidate = $base;
        $n = 2;
        while (isset($used[$candidate])) {
            $candidate = $base . '-' . $n;
            $n++;
        }
        $used[$candidate] = true;
        $update->execute([$candidate, $row['id']]);
        $added[] = 'slug:' . $candidate;
    }
}

ensure_cafe_branding_columns($pdo);
ensure_category_cafe_column($pdo);
ensure_product_catalog_columns($pdo);
ensure_places_tables($pdo);
$added[] = 'places';
$added[] = 'place_spots';

echo json_encode([
    'success' => true,
    'added_columns' => array_values(array_unique($added)),
    'message' => $added ? 'Migration applied.' : 'Schema already up to date.',
]);
