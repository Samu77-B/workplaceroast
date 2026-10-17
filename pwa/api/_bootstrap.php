<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'API config missing. Copy pwa/api/config.example.php to pwa/api/config.php and add database credentials.',
    ]);
    exit;
}

try {
    require_once $configPath;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed. Check the MySQL user and password in config.php.']);
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection ($pdo) is not defined in config.php.']);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function admin_token_ok(): bool
{
    global $ADMIN_TOKEN;
    $expected = isset($ADMIN_TOKEN) ? trim((string) $ADMIN_TOKEN) : '';
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? $_SERVER['HTTP_X_ADMIN_TOKEN']
        ?? $_SERVER['REDIRECT_HTTP_X_ADMIN_TOKEN']
        ?? '';
    $token = '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        $token = $m[1];
    } elseif ($header !== '') {
        $token = trim($header);
    } elseif (!empty($_GET['token'])) {
        $token = trim((string) $_GET['token']);
    }
    return $expected !== '' && hash_equals($expected, $token);
}

function require_admin(): void
{
    if (!admin_token_ok()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

function verify_owner_password(?string $hash, string $password): bool
{
    if ($hash === null || $hash === '') {
        return false;
    }
    if (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$2b$') === 0) {
        return password_verify($password, $hash);
    }
    return hash_equals($hash, $password);
}

function find_cafe_by_access_code(string $accessCode): ?array
{
    global $pdo;
    if (!table_exists($pdo, 'corporate_clients')) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM corporate_clients WHERE UPPER(TRIM(access_code)) = UPPER(?) LIMIT 1');
    $stmt->execute([trim($accessCode)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function cafe_owner_from_headers(): ?array
{
    $code = trim((string) ($_SERVER['HTTP_X_CAFE_ACCESS_CODE'] ?? ''));
    $password = (string) ($_SERVER['HTTP_X_CAFE_PASSWORD'] ?? '');
    if ($code === '' || $password === '') {
        return null;
    }
    $cafe = find_cafe_by_access_code($code);
    if (!$cafe || empty($cafe['is_active'])) {
        return null;
    }
    $hash = isset($cafe['owner_password']) ? (string) $cafe['owner_password'] : '';
    if (!verify_owner_password($hash, $password)) {
        return null;
    }
    return $cafe;
}

function require_admin_or_cafe(): ?array
{
    if (admin_token_ok()) {
        return null;
    }
    $cafe = cafe_owner_from_headers();
    if ($cafe) {
        return $cafe;
    }
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function table_columns(PDO $pdo, string $table): array
{
    if (!table_exists($pdo, $table)) {
        return [];
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    $cols = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cols[] = $row['Field'];
    }
    return $cols;
}

function pick_column(array $columns, array $candidates): ?string
{
    foreach ($candidates as $name) {
        if (in_array($name, $columns, true)) {
            return $name;
        }
    }
    return null;
}

function slugify(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? substr($slug, 0, 80) : 'cafe';
}

function unique_access_code(PDO $pdo): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare('SELECT id FROM corporate_clients WHERE access_code = ? LIMIT 1');
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

function default_owner_hash(): string
{
    return password_hash('CafeOwner2024', PASSWORD_BCRYPT);
}

function normalize_hex_color($value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (preg_match('/^#?([0-9a-fA-F]{6})$/', $value, $m)) {
        return '#' . strtolower($m[1]);
    }
    return null;
}

function ensure_cafe_branding_columns(PDO $pdo): void
{
    if (!table_exists($pdo, 'corporate_clients')) {
        return;
    }
    $cols = table_columns($pdo, 'corporate_clients');
    $definitions = [
        'logo_url' => 'VARCHAR(255) NULL',
        'brand_primary' => 'VARCHAR(7) NULL',
        'brand_accent' => 'VARCHAR(7) NULL',
    ];
    foreach ($definitions as $column => $ddl) {
        if (!in_array($column, $cols, true)) {
            $pdo->exec("ALTER TABLE corporate_clients ADD COLUMN `{$column}` {$ddl}");
        }
    }
}

function default_spot_labels(): array
{
    return ['One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight'];
}

function reserved_place_slugs(): array
{
    return [
        'index', 'admin', 'cafe-admin', 'super-admin', 'success', 'cancel',
        'generate-qr-codes', 'api', 'brand', 'corporate-login', 'pwa',
    ];
}

function unique_place_slug(PDO $pdo, string $base, ?int $ignorePlaceId = null, ?int $allowCafeId = null): string
{
    $base = slugify($base);
    $reserved = array_flip(reserved_place_slugs());
    $candidate = $base;
    $n = 2;
    while (true) {
        if (isset($reserved[$candidate])) {
            $candidate = $base . '-' . $n;
            $n++;
            continue;
        }
        if (table_exists($pdo, 'places')) {
            $placeSql = 'SELECT id FROM places WHERE slug = ?';
            $placeParams = [$candidate];
            if ($ignorePlaceId) {
                $placeSql .= ' AND id != ?';
                $placeParams[] = $ignorePlaceId;
            }
            $stmt = $pdo->prepare($placeSql);
            $stmt->execute($placeParams);
            if ($stmt->fetch()) {
                $candidate = $base . '-' . $n;
                $n++;
                continue;
            }
        }
        if (table_exists($pdo, 'corporate_clients') && in_array('slug', table_columns($pdo, 'corporate_clients'), true)) {
            $cafeSql = 'SELECT id FROM corporate_clients WHERE LOWER(slug) = ?';
            $cafeParams = [$candidate];
            if ($allowCafeId) {
                $cafeSql .= ' AND id != ?';
                $cafeParams[] = $allowCafeId;
            }
            $cafeStmt = $pdo->prepare($cafeSql);
            $cafeStmt->execute($cafeParams);
            if ($cafeStmt->fetch()) {
                $candidate = $base . '-' . $n;
                $n++;
                continue;
            }
        }
        return $candidate;
    }
}

function seed_default_spots(PDO $pdo, int $placeId): void
{
    $count = $pdo->prepare('SELECT COUNT(*) FROM place_spots WHERE place_id = ?');
    $count->execute([$placeId]);
    if ((int) $count->fetchColumn() > 0) {
        return;
    }
    $insert = $pdo->prepare('INSERT INTO place_spots (place_id, label, sort_order, is_active) VALUES (?, ?, ?, 1)');
    foreach (default_spot_labels() as $i => $label) {
        $insert->execute([$placeId, $label, $i + 1]);
    }
}

function seed_default_place_for_cafe(PDO $pdo, array $cafe): ?int
{
    $cafeId = (int) ($cafe['id'] ?? 0);
    if ($cafeId < 1) {
        return null;
    }
    $existing = $pdo->prepare('SELECT id FROM places WHERE cafe_id = ? ORDER BY id ASC LIMIT 1');
    $existing->execute([$cafeId]);
    $row = $existing->fetch();
    if ($row) {
        seed_default_spots($pdo, (int) $row['id']);
        return (int) $row['id'];
    }
    $name = trim((string) ($cafe['name'] ?? 'Place'));
    $baseSlug = !empty($cafe['slug']) ? (string) $cafe['slug'] : slugify($name);
    $slug = unique_place_slug($pdo, $baseSlug, null, $cafeId);
    $insert = $pdo->prepare(
        'INSERT INTO places (cafe_id, name, slug, spot_noun, show_spots, is_active) VALUES (?, ?, ?, ?, 1, 1)'
    );
    $insert->execute([$cafeId, $name !== '' ? $name : 'Place', $slug, 'Chair']);
    $placeId = (int) $pdo->lastInsertId();
    seed_default_spots($pdo, $placeId);
    return $placeId;
}

function ensure_product_catalog_columns(PDO $pdo): void
{
    if (!table_exists($pdo, 'products')) {
        return;
    }
    $widen = static function (PDO $pdo, string $column, string $ddl, int $minLen) {
        $stmt = $pdo->query('SHOW COLUMNS FROM products LIKE ' . $pdo->quote($column));
        $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!$col) {
            $pdo->exec("ALTER TABLE products ADD COLUMN `{$column}` {$ddl}");
            return;
        }
        $type = strtolower((string) ($col['Type'] ?? ''));
        $tooShort = preg_match('/varchar\((\d+)\)/', $type, $m) && (int) $m[1] < $minLen;
        if (strpos($type, 'enum') === 0 || $tooShort) {
            $pdo->exec("ALTER TABLE products MODIFY COLUMN `{$column}` {$ddl}");
        }
    };
    $widen($pdo, 'category', 'VARCHAR(80) NOT NULL DEFAULT \'\'', 80);
    $widen($pdo, 'type', 'VARCHAR(40) NULL', 40);
}

function ensure_category_cafe_column(PDO $pdo): void
{
    if (!table_exists($pdo, 'categories')) {
        return;
    }
    $cols = table_columns($pdo, 'categories');
    if (!in_array('corporate_client_id', $cols, true)) {
        $pdo->exec('ALTER TABLE categories ADD COLUMN corporate_client_id INT NULL');
    }
    $iconCol = $pdo->query("SHOW COLUMNS FROM categories LIKE 'icon'")->fetch(PDO::FETCH_ASSOC);
    if (!$iconCol) {
        $pdo->exec('ALTER TABLE categories ADD COLUMN icon VARCHAR(255) NULL');
        return;
    }
    $type = strtolower((string) ($iconCol['Type'] ?? ''));
    if (preg_match('/varchar\((\d+)\)/', $type, $m) && (int) $m[1] < 255) {
        $pdo->exec('ALTER TABLE categories MODIFY COLUMN icon VARCHAR(255) NULL');
    }
}

function ensure_places_tables(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS places (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cafe_id INT NOT NULL,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            spot_noun VARCHAR(80) NOT NULL DEFAULT \'Chair\',
            show_spots TINYINT(1) NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_places_slug (slug),
            KEY idx_places_cafe (cafe_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS place_spots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            place_id INT NOT NULL,
            label VARCHAR(80) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            KEY idx_place_spots_place (place_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    if (!table_exists($pdo, 'corporate_clients')) {
        return;
    }
    $cafeCols = table_columns($pdo, 'corporate_clients');
    $select = 'SELECT id, name';
    if (in_array('slug', $cafeCols, true)) {
        $select .= ', slug';
    }
    $select .= ' FROM corporate_clients';
    foreach ($pdo->query($select)->fetchAll() as $cafe) {
        seed_default_place_for_cafe($pdo, $cafe);
    }
}

function load_place_spots(PDO $pdo, int $placeId, bool $activeOnly = true): array
{
    $sql = 'SELECT id, place_id, label, sort_order, is_active FROM place_spots WHERE place_id = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$placeId]);
    $spots = [];
    foreach ($stmt->fetchAll() as $row) {
        $spots[] = [
            'id' => (int) $row['id'],
            'place_id' => (int) $row['place_id'],
            'label' => $row['label'],
            'sort_order' => (int) $row['sort_order'],
            'is_active' => (int) $row['is_active'] === 1,
        ];
    }
    return $spots;
}

function format_place(PDO $pdo, array $row, bool $includeInactiveSpots = false): array
{
    $placeId = (int) $row['id'];
    return [
        'id' => $placeId,
        'cafe_id' => (int) $row['cafe_id'],
        'name' => $row['name'],
        'slug' => $row['slug'],
        'spot_noun' => $row['spot_noun'] ?: 'Chair',
        'show_spots' => (int) $row['show_spots'] === 1,
        'is_active' => (int) $row['is_active'] === 1,
        'spots' => load_place_spots($pdo, $placeId, !$includeInactiveSpots),
    ];
}

function find_place_by_slug(PDO $pdo, string $slug): ?array
{
    if (!table_exists($pdo, 'places')) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM places WHERE LOWER(slug) = ? LIMIT 1');
    $stmt->execute([strtolower($slug)]);
    $row = $stmt->fetch();
    return $row ?: null;
}
