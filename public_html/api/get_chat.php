<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$sessionId = $_GET['id'] ?? '';

if (empty($sessionId)) {
    echo json_encode(['messages' => []]);
    exit;
}

$chatFile = LOGS_DIR . $sessionId . '.chat';

if (!file_exists($chatFile)) {
    echo json_encode(['messages' => []]);
    exit;
}

$lines = file($chatFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$messages = [];

foreach ($lines as $line) {
    $data = json_decode($line, true);
    if ($data) {
        $messages[] = $data;
    }
}

echo json_encode(['messages' => $messages]);


