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
