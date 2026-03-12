<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$sessionId = $_POST['session_id'] ?? '';

if (empty($sessionId)) {
    echo json_encode(['success' => false]);
    exit;
}

// Create status file
$statusFile = LOGS_DIR . $sessionId . '.status';
file_put_contents($statusFile, 'chat');

// Create empty chat file if not exists
$chatFile = LOGS_DIR . $sessionId . '.chat';
if (!file_exists($chatFile)) {
    file_put_contents($chatFile, '');
}

// Get user info
$realIp = getRealIpAddress();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Send notification to Telegram
$telegramMsg = "🆕 <b>НОВЫЙ ЧАТ #" . $sessionId . "</b>\n\n";
$telegramMsg .= "💬 Пользователь открыл чат поддержки\n\n";
$telegramMsg .= "🆔 ID: <code>" . $sessionId . "</code>\n";
$telegramMsg .= "🌐 IP: <code>" . $realIp . "</code>\n";
$telegramMsg .= "🕐 Время: " . date('H:i:s d.m.Y') . "\n\n";
$telegramMsg .= "━━━━━━━━━━━━━━━━━━━━\n";
$telegramMsg .= "<b>Как ответить:</b>\n";
$telegramMsg .= "Отправьте: <code>" . $sessionId . " Ваше сообщение</code>\n";
$telegramMsg .= "Пример: <code>" . $sessionId . " Здравствуйте!</code>";

sendTelegramMessage($telegramMsg, null);

echo json_encode(['success' => true, 'session_id' => $sessionId]);


