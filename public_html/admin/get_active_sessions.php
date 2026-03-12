<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$sessions = [];

// Scan logs directory for status files
$files = glob(LOGS_DIR . '*.status');

foreach ($files as $file) {
    $sessionId = basename($file, '.status');
    $status = trim(file_get_contents($file));
    
    // Only include chat sessions
    if ($status === 'chat') {
        $chatFile = LOGS_DIR . $sessionId . '.chat';
        $messageCount = 0;
        
        if (file_exists($chatFile)) {
            $lines = file($chatFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $messageCount = count($lines);
        }
        
        $sessions[] = [
            'id' => $sessionId,
            'status' => $status,
            'messages' => $messageCount
        ];
    }
}

echo json_encode(['sessions' => $sessions]);
