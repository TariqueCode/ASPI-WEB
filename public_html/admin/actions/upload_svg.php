<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No SVG file uploaded.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
if ($ext !== 'svg') {
    echo json_encode(['error' => 'Only SVG files are allowed here.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int)$file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['error' => 'SVG file size cannot exceed 5MB.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$contents = file_get_contents($file['tmp_name']);
if ($contents === false || trim($contents) === '') {
    echo json_encode(['error' => 'Invalid SVG file.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* SVGs are uploaded into the same-origin web root, so reject executable/script content. */
if (preg_match('/<\s*script\b|on[a-z]+\s*=|javascript\s*:/i', $contents)) {
    echo json_encode(['error' => 'SVG contains unsafe script content.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/<\s*svg\b/i', $contents)) {
    echo json_encode(['error' => 'The uploaded file is not a valid SVG document.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$targetDir = dirname(__DIR__, 2) . '/assets/uploads/';
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    echo json_encode(['error' => 'Upload directory could not be created.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$newName = bin2hex(random_bytes(8)) . '_' . time() . '.svg';
$target = $targetDir . $newName;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['error' => 'SVG upload failed.'], JSON_UNESCAPED_UNICODE);
    exit;
}

@chmod($target, 0644);
echo json_encode([
    'status' => 'success',
    'url' => 'assets/uploads/' . $newName,
    'type' => 'image/svg+xml'
], JSON_UNESCAPED_UNICODE);
