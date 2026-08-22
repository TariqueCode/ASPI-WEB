<?php
require_once 'auth.php';
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    sendJsonResponse(['success' => false, 'message' => 'No file']);
}

$file = $_FILES['file'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
if (!in_array($file['type'], $allowed) || $file['size'] > 2 * 1024 * 1024) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid file']);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newName = uniqid() . '.' . $ext;
$dest = __DIR__ . '/../../assets/uploads/' . $newName;
if (move_uploaded_file($file['tmp_name'], $dest)) {
    sendJsonResponse(['success' => true, 'url' => 'assets/uploads/' . $newName]);
} else {
    sendJsonResponse(['success' => false, 'message' => 'Upload failed']);
}
?>