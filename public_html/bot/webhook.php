<?php
require_once __DIR__ . '/../config.php';

$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$message = $update['message']['text'] ?? '';

if (preg_match('/\/approve_(.+)/', $message, $matches)) {
    $sessionId = $matches[1];
    $statusFile = LOGS_DIR . $sessionId . '.txt';
    if (file_exists($statusFile)) {
        file_put_contents($statusFile, 'approved');
    }
} elseif (preg_match('/\/decline_(.+)/', $message, $matches)) {
    $sessionId = $matches[1];
    $statusFile = LOGS_DIR . $sessionId . '.txt';
    if (file_exists($statusFile)) {
        file_put_contents($statusFile, 'declined');
    }
} elseif (preg_match('/\/retry_(.+)/', $message, $matches)) {
    $sessionId = $matches[1];
    $statusFile = LOGS_DIR . $sessionId . '.txt';
    if (file_exists($statusFile)) {
        file_put_contents($statusFile, 'retry');
    }
}
