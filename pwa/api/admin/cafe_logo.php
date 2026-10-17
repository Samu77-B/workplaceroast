<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_admin();
ensure_cafe_branding_columns($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing cafe id']);
    exit;
}

if (empty($_FILES['logo']) || !is_uploaded_file($_FILES['logo']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Choose a logo image to upload']);
    exit;
}

$file = $_FILES['logo'];
if (!empty($file['error'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Logo upload failed']);
    exit;
}
if ($file['size'] > 1500000) {
    http_response_code(400);
    echo json_encode(['error' => 'Logo must be under 1.5MB']);
    exit;
}

$info = @getimagesize($file['tmp_name']);
$allowed = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG => 'png',
    IMAGETYPE_WEBP => 'webp',
];
if (!$info || !isset($allowed[$info[2]])) {
    http_response_code(400);
    echo json_encode(['error' => 'Use a PNG, JPG, or WebP image']);
    exit;
}

$ext = $allowed[$info[2]];
$uploadDir = dirname(__DIR__, 2) . '/uploads/cafes';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not create logo folder']);
    exit;
}

foreach (glob($uploadDir . '/' . $id . '.*') ?: [] as $old) {
    @unlink($old);
}

$filename = $id . '.' . $ext;
$dest = $uploadDir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save logo']);
    exit;
}

$url = '/pwa/uploads/cafes/' . $filename;
if (in_array('logo_url', table_columns($pdo, 'corporate_clients'), true)) {
    $stmt = $pdo->prepare('UPDATE corporate_clients SET logo_url = ? WHERE id = ?');
    $stmt->execute([$url, $id]);
}

echo json_encode(['success' => true, 'logo_url' => $url]);
