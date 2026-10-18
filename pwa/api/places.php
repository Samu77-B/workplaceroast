<?php
require_once __DIR__ . '/_bootstrap.php';

ensure_places_tables($pdo);

$method = $_SERVER['REQUEST_METHOD'];

function require_place_owner(?array $cafe, array $place): void
{
    if (!$cafe) {
        return;
    }
    if ((int) $place['cafe_id'] !== (int) $cafe['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
}

function replace_place_spots(PDO $pdo, int $placeId, array $spots): void
{
    $pdo->prepare('DELETE FROM place_spots WHERE place_id = ?')->execute([$placeId]);
    $insert = $pdo->prepare('INSERT INTO place_spots (place_id, label, sort_order, is_active) VALUES (?, ?, ?, ?)');
    $order = 1;
    foreach ($spots as $spot) {
        if (!is_array($spot)) {
            continue;
        }
        $label = trim((string) ($spot['label'] ?? ''));
        if ($label === '') {
            continue;
        }
        $sort = isset($spot['sort_order']) ? (int) $spot['sort_order'] : $order;
        $active = array_key_exists('is_active', $spot) ? (!empty($spot['is_active']) ? 1 : 0) : 1;
        $insert->execute([$placeId, substr($label, 0, 80), $sort, $active]);
        $order++;
    }
}

if ($method === 'GET') {
    $slug = strtolower(trim((string) ($_GET['slug'] ?? '')));
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $cafeId = isset($_GET['cafe_id']) ? (int) $_GET['cafe_id'] : 0;

    if ($slug !== '') {
        $row = find_place_by_slug($pdo, $slug);
        if (!$row || empty($row['is_active'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Place not found']);
            exit;
        }
        echo json_encode(['success' => true, 'place' => format_place($pdo, $row)]);
        exit;
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM places WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Place not found']);
            exit;
        }
        echo json_encode(['success' => true, 'place' => format_place($pdo, $row, true)]);
        exit;
    }

    $cafe = cafe_owner_from_headers();
    if ($cafe) {
        $cafeId = (int) $cafe['id'];
    } elseif ($cafeId < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing cafe_id or slug']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM places WHERE cafe_id = ? ORDER BY name ASC, id ASC');
    $stmt->execute([$cafeId]);
    $places = [];
    foreach ($stmt->fetchAll() as $row) {
        $places[] = format_place($pdo, $row, true);
    }
    echo json_encode(['success' => true, 'places' => $places]);
    exit;
}

$cafe = require_admin_or_cafe();

if ($method === 'POST' || $method === 'PUT') {
    $body = json_input();
    $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    $isUpdate = $method === 'PUT' || $id > 0;

    if ($isUpdate) {
        if ($id < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT * FROM places WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Place not found']);
            exit;
        }
        require_place_owner($cafe, $row);

        $name = array_key_exists('name', $body) ? trim((string) $body['name']) : $row['name'];
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }
        $slugInput = array_key_exists('slug', $body) ? trim((string) $body['slug']) : $row['slug'];
        $slug = unique_place_slug($pdo, $slugInput !== '' ? $slugInput : $name, $id, (int) $row['cafe_id']);
        $spotNoun = array_key_exists('spot_noun', $body) ? trim((string) $body['spot_noun']) : $row['spot_noun'];
        if ($spotNoun === '') {
            $spotNoun = 'Chair';
        }
        $showSpots = array_key_exists('show_spots', $body) ? (!empty($body['show_spots']) ? 1 : 0) : (int) $row['show_spots'];
        $isActive = array_key_exists('is_active', $body) ? (!empty($body['is_active']) ? 1 : 0) : (int) $row['is_active'];

        $update = $pdo->prepare(
            'UPDATE places SET name = ?, slug = ?, spot_noun = ?, show_spots = ?, is_active = ? WHERE id = ?'
        );
        $update->execute([$name, $slug, substr($spotNoun, 0, 80), $showSpots, $isActive, $id]);

        if (array_key_exists('spots', $body) && is_array($body['spots'])) {
            replace_place_spots($pdo, $id, $body['spots']);
        }

        $fresh = $pdo->prepare('SELECT * FROM places WHERE id = ? LIMIT 1');
        $fresh->execute([$id]);
        echo json_encode(['success' => true, 'place' => format_place($pdo, $fresh->fetch(), true)]);
        exit;
    }

    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }
    $ownerCafeId = $cafe ? (int) $cafe['id'] : (int) ($body['cafe_id'] ?? 0);
    if ($ownerCafeId < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'cafe_id is required']);
        exit;
    }
    $slugInput = trim((string) ($body['slug'] ?? ''));
    $slug = unique_place_slug($pdo, $slugInput !== '' ? $slugInput : $name, null, $ownerCafeId);
    $spotNoun = trim((string) ($body['spot_noun'] ?? 'Chair'));
    if ($spotNoun === '') {
        $spotNoun = 'Chair';
    }
    $showSpots = array_key_exists('show_spots', $body) ? (!empty($body['show_spots']) ? 1 : 0) : 1;
    $isActive = array_key_exists('is_active', $body) ? (!empty($body['is_active']) ? 1 : 0) : 1;

    $stmt = $pdo->prepare(
        'INSERT INTO places (cafe_id, name, slug, spot_noun, show_spots, is_active) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$ownerCafeId, $name, $slug, substr($spotNoun, 0, 80), $showSpots, $isActive]);
    $placeId = (int) $pdo->lastInsertId();

    if (!empty($body['spots']) && is_array($body['spots'])) {
        replace_place_spots($pdo, $placeId, $body['spots']);
    } else {
        seed_default_spots($pdo, $placeId);
    }

    $row = $pdo->query('SELECT * FROM places WHERE id = ' . $placeId)->fetch();
    echo json_encode(['success' => true, 'place' => format_place($pdo, $row, true)]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id < 1) {
        $body = json_input();
        $id = (int) ($body['id'] ?? 0);
    }
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT * FROM places WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Place not found']);
        exit;
    }
    require_place_owner($cafe, $row);

    $count = $pdo->prepare('SELECT COUNT(*) FROM places WHERE cafe_id = ?');
    $count->execute([(int) $row['cafe_id']]);
    if ((int) $count->fetchColumn() <= 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Keep at least one place for this cafe']);
        exit;
    }

    $pdo->prepare('DELETE FROM place_spots WHERE place_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM places WHERE id = ?')->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
