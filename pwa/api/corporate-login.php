<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = json_input();
$accessCode = trim((string) ($body['access_code'] ?? ''));
if ($accessCode === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Access code is required']);
    exit;
}

$cafe = find_cafe_by_access_code($accessCode);
if (!$cafe || empty($cafe['is_active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid access code. Please try again.']);
    exit;
}

echo json_encode([
    'success' => true,
    'corporate_client_id' => (int) $cafe['id'],
    'client_name' => $cafe['name'],
    'brand_primary' => $cafe['brand_primary'] ?? null,
    'brand_accent' => $cafe['brand_accent'] ?? null,
    'logo_url' => $cafe['logo_url'] ?? null,
]);
