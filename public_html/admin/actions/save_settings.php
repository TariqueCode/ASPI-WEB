<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'message' => 'Settings endpoint is ready.'
]);
