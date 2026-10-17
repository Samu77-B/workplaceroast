<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = json_input();
$accessCode = trim((string) ($body['access_code'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($accessCode === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Access code and password are required']);
    exit;
}

$cafe = find_cafe_by_access_code($accessCode);
if (!$cafe) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid access code or password']);
    exit;
}

if (empty($cafe['is_active'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'This cafe is inactive']);
    exit;
}

$hash = isset($cafe['owner_password']) ? (string) $cafe['owner_password'] : '';
if ($hash === '') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Owner password is not set. Reset it from Super Dashboard.']);
    exit;
}

if (!verify_owner_password($hash, $password)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid access code or password']);
    exit;
}

echo json_encode([
    'success' => true,
    'corporate_client_id' => (int) $cafe['id'],
    'client_name' => $cafe['name'],
    'access_code' => $cafe['access_code'],
]);
