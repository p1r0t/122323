<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$sessionId = $_GET['id'] ?? '';

if (empty($sessionId)) {
    echo json_encode(['status' => 'error']);
    exit;
}

$statusFile = LOGS_DIR . $sessionId . '.status';

if (!file_exists($statusFile)) {
    echo json_encode(['status' => 'error']);
    exit;
}

$status = trim(file_get_contents($statusFile));

echo json_encode(['status' => $status]);
