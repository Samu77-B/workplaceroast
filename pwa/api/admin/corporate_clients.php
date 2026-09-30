<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin();

$method = $_SERVER['REQUEST_METHOD'];

if (!table_exists($pdo, 'corporate_clients')) {
    http_response_code(500);
    echo json_encode(['error' => 'corporate_clients table not found']);
    exit;
}

$clientCols = table_columns($pdo, 'corporate_clients');
$has = static function (string $col) use ($clientCols): bool {
    return in_array($col, $clientCols, true);
};

$orderCols = table_exists($pdo, 'orders') ? table_columns($pdo, 'orders') : [];
$productCols = table_exists($pdo, 'products') ? table_columns($pdo, 'products') : [];

$orderCafeCol = pick_column($orderCols, ['corporate_client_id', 'cafe_id', 'client_id']);
$orderTotalCol = pick_column($orderCols, ['total', 'total_amount', 'amount', 'grand_total']);
$orderDateCol = pick_column($orderCols, ['created_at', 'order_date', 'created']);
$productCafeCol = pick_column($productCols, ['corporate_client_id', 'cafe_id', 'client_id']);

function health_payload(array $row, array $stats): array
{
    $active = !empty($row['is_active']);
    $hasPassword = !empty($row['has_owner_password']);
    $ordersTotal = (int) ($stats['orders_total'] ?? 0);
    $orders7 = (int) ($stats['orders_7d'] ?? 0);
    $orders30 = (int) ($stats['orders_30d'] ?? 0);
    $last = $stats['last_order_at'] ?? null;
    $days = null;
    if ($last) {
        $days = (int) floor((time() - strtotime($last)) / 86400);
    }

    $reasons = [];
    $status = 'healthy';
    $score = 80;

    if (!$active) {
        $status = 'inactive';
        $score = 20;
        $reasons[] = 'Marked inactive';
    } elseif (!$hasPassword) {
        $status = 'setup';
        $score = 40;
        $reasons[] = 'Cafe owner password not set';
    } elseif ($ordersTotal === 0) {
        $status = 'setup';
        $score = 45;
        $reasons[] = 'No orders recorded yet';
    } elseif ($days !== null && $days > 30) {
        $status = 'at_risk';
        $score = 35;
        $reasons[] = 'No orders in the last 30 days';
    } elseif ($days !== null && $days > 7) {
        $status = 'attention';
        $score = 60;
        $reasons[] = 'Quiet for more than 7 days';
    } else {
        $reasons[] = 'Orders in the last 7 days';
        $score = min(100, 70 + min(30, $orders7 * 5));
    }

    if ((int) ($stats['product_count'] ?? 0) === 0 && $status !== 'inactive') {
        $reasons[] = 'No cafe-specific products (may be using shared menu)';
    }

    return [
        'status' => $status,
        'score' => $score,
        'reasons' => $reasons,
        'orders_total' => $ordersTotal,
        'orders_7d' => $orders7,
        'orders_30d' => $orders30,
        'revenue_30d' => round((float) ($stats['revenue_30d'] ?? 0), 2),
        'last_order_at' => $last,
        'days_since_order' => $days,
        'product_count' => (int) ($stats['product_count'] ?? 0),
    ];
}

function load_health_stats(PDO $pdo, ?string $orderCafeCol, ?string $orderTotalCol, ?string $orderDateCol, ?string $productCafeCol): array
{
    $stats = [];
    if ($orderCafeCol && $orderDateCol) {
        $totalExpr = $orderTotalCol ? "SUM(CASE WHEN `{$orderDateCol}` >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN `{$orderTotalCol}` ELSE 0 END)" : '0';
        $sql = "SELECT
            `{$orderCafeCol}` AS cafe_id,
            COUNT(*) AS orders_total,
            SUM(CASE WHEN `{$orderDateCol}` >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS orders_7d,
            SUM(CASE WHEN `{$orderDateCol}` >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS orders_30d,
            {$totalExpr} AS revenue_30d,
            MAX(`{$orderDateCol}`) AS last_order_at
            FROM orders
            GROUP BY `{$orderCafeCol}`";
        foreach ($pdo->query($sql) as $row) {
            $stats[(int) $row['cafe_id']] = $row;
        }
    }
    if ($productCafeCol) {
        $sql = "SELECT `{$productCafeCol}` AS cafe_id, COUNT(*) AS product_count FROM products WHERE `{$productCafeCol}` IS NOT NULL GROUP BY `{$productCafeCol}`";
        foreach ($pdo->query($sql) as $row) {
            $id = (int) $row['cafe_id'];
            if (!isset($stats[$id])) {
                $stats[$id] = [];
            }
            $stats[$id]['product_count'] = $row['product_count'];
        }
    }
    return $stats;
}

function format_client(array $row, array $statsByCafe): array
{
    $id = (int) $row['id'];
    $stats = $statsByCafe[$id] ?? [];
    $out = [
        'id' => $id,
        'name' => $row['name'],
        'access_code' => $row['access_code'],
        'is_active' => (int) $row['is_active'],
        'has_owner_password' => array_key_exists('owner_password', $row)
            ? !empty($row['owner_password'])
            : true,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
        'slug' => $row['slug'] ?? null,
        'tier' => $row['tier'] ?? 'basic',
        'contact_email' => $row['contact_email'] ?? null,
        'contact_name' => $row['contact_name'] ?? null,
        'notes' => $row['notes'] ?? null,
    ];
    $out['health'] = health_payload($out, $stats);
    return $out;
}

if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $select = ['id', 'name', 'access_code', 'is_active', 'created_at', 'updated_at'];
    if ($has('owner_password')) {
        $select[] = 'owner_password';
    }
    foreach (['slug', 'tier', 'contact_email', 'contact_name', 'notes'] as $optional) {
        if ($has($optional)) {
            $select[] = $optional;
        }
    }
    $sql = 'SELECT `' . implode('`, `', $select) . '` FROM corporate_clients';
    $params = [];
    if ($id > 0) {
        $sql .= ' WHERE id = ?';
        $params[] = $id;
    }
    $sql .= ' ORDER BY name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $stats = load_health_stats($pdo, $orderCafeCol, $orderTotalCol, $orderDateCol, $productCafeCol);
    $out = array_map(static function ($row) use ($stats) {
        return format_client($row, $stats);
    }, $rows);
    echo json_encode($id > 0 ? ($out[0] ?? ['error' => 'Not found']) : $out);
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id']);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM corporate_clients WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'POST') {
    $body = json_input();
    $editId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($body['id'] ?? 0);
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Client name is required']);
        exit;
    }

    $isActive = !empty($body['is_active']) ? 1 : 0;
    $slug = isset($body['slug']) ? slugify((string) $body['slug']) : slugify($name);
    $tier = in_array($body['tier'] ?? 'basic', ['basic', 'premium'], true) ? $body['tier'] : 'basic';
    $contactEmail = trim((string) ($body['contact_email'] ?? ''));
    $contactName = trim((string) ($body['contact_name'] ?? ''));
    $notes = trim((string) ($body['notes'] ?? ''));

    if ($editId > 0) {
        $fields = ['name = ?', 'is_active = ?'];
        $params = [$name, $isActive];
        if ($has('slug')) {
            $fields[] = 'slug = ?';
            $params[] = $slug;
        }
        if ($has('tier')) {
            $fields[] = 'tier = ?';
            $params[] = $tier;
        }
        if ($has('contact_email')) {
            $fields[] = 'contact_email = ?';
            $params[] = $contactEmail !== '' ? $contactEmail : null;
        }
        if ($has('contact_name')) {
            $fields[] = 'contact_name = ?';
            $params[] = $contactName !== '' ? $contactName : null;
        }
        if ($has('notes')) {
            $fields[] = 'notes = ?';
            $params[] = $notes !== '' ? $notes : null;
        }
        if (!empty($body['regenerate_access_code'])) {
            $fields[] = 'access_code = ?';
            $params[] = unique_access_code($pdo);
        }
        if ($has('owner_password') && !empty($body['owner_password'])) {
            $fields[] = 'owner_password = ?';
            $params[] = password_hash((string) $body['owner_password'], PASSWORD_BCRYPT);
        } elseif ($has('owner_password') && !empty($body['reset_owner_password'])) {
            $fields[] = 'owner_password = ?';
            $params[] = default_owner_hash();
        }
        if ($has('updated_at')) {
            $fields[] = 'updated_at = NOW()';
        }
        $params[] = $editId;
        $stmt = $pdo->prepare('UPDATE corporate_clients SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
        $stmt = $pdo->prepare('SELECT * FROM corporate_clients WHERE id = ?');
        $stmt->execute([$editId]);
        $row = $stmt->fetch();
        $stats = load_health_stats($pdo, $orderCafeCol, $orderTotalCol, $orderDateCol, $productCafeCol);
        echo json_encode(format_client($row, $stats));
        exit;
    }

    $code = unique_access_code($pdo);
    $cols = ['name', 'access_code', 'is_active'];
    $placeholders = ['?', '?', '?'];
    $params = [$name, $code, $isActive];
    if ($has('owner_password')) {
        $cols[] = 'owner_password';
        $placeholders[] = '?';
        $password = !empty($body['owner_password'])
            ? password_hash((string) $body['owner_password'], PASSWORD_BCRYPT)
            : default_owner_hash();
        $params[] = $password;
    }
    if ($has('slug')) {
        $cols[] = 'slug';
        $placeholders[] = '?';
        $params[] = $slug;
    }
    if ($has('tier')) {
        $cols[] = 'tier';
        $placeholders[] = '?';
        $params[] = $tier;
    }
    if ($has('contact_email')) {
        $cols[] = 'contact_email';
        $placeholders[] = '?';
        $params[] = $contactEmail !== '' ? $contactEmail : null;
    }
    if ($has('contact_name')) {
        $cols[] = 'contact_name';
        $placeholders[] = '?';
        $params[] = $contactName !== '' ? $contactName : null;
    }
    if ($has('notes')) {
        $cols[] = 'notes';
        $placeholders[] = '?';
        $params[] = $notes !== '' ? $notes : null;
    }
    $sql = 'INSERT INTO corporate_clients (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $newId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM corporate_clients WHERE id = ?');
    $stmt->execute([$newId]);
    $row = $stmt->fetch();
    $stats = load_health_stats($pdo, $orderCafeCol, $orderTotalCol, $orderDateCol, $productCafeCol);
    $formatted = format_client($row, $stats);
    $formatted['success'] = true;
    $formatted['default_owner_password'] = empty($body['owner_password']) ? 'CafeOwner2024' : null;
    echo json_encode($formatted);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
