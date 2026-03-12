<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$sessionId = $_POST['session_id'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($sessionId) || empty($status)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$statusFile = LOGS_DIR . $sessionId . '.status';

if (!file_exists($statusFile)) {
    echo json_encode(['success' => false, 'error' => 'Session not found']);
    exit;
}

// Update status
file_put_contents($statusFile, $status);

echo json_encode(['success' => true, 'status' => $status]);
