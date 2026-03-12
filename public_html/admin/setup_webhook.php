<?php
require_once __DIR__ . '/../config.php';

// Get the webhook URL
$webhookUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
              "://" . $_SERVER['HTTP_HOST'] . 
              dirname(dirname($_SERVER['PHP_SELF'])) . "/bot/bot_handler.php";

echo "<h1>Telegram Webhook Setup</h1>";
echo "<p><strong>Webhook URL:</strong> <code>" . htmlspecialchars($webhookUrl) . "</code></p>";

// For localhost, show ngrok instructions
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚠️ Localhost Detected</h3>";
    echo "<p>Telegram webhook не работает на localhost. Используйте один из вариантов:</p>";
    echo "<ol>";
    echo "<li><strong>ngrok:</strong> Установите ngrok и запустите: <code>ngrok http 8000</code></li>";
    echo "<li><strong>Manual Reply Tool:</strong> <a href='manual_reply.php'>Открыть manual_reply.php</a></li>";
    echo "</ol>";
    echo "</div>";
} else {
    // Try to set webhook
    $setWebhookUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode($webhookUrl);
    
    echo "<h3>Setting Webhook...</h3>";
    $result = @file_get_contents($setWebhookUrl);
    
    if ($result) {
        $data = json_decode($result, true);
        if ($data['ok']) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 6px; color: #155724;'>";
            echo "✅ Webhook установлен успешно!";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 6px; color: #721c24;'>";
            echo "❌ Ошибка: " . htmlspecialchars($data['description'] ?? 'Unknown error');
            echo "</div>";
        }
    }
}

// Get webhook info
echo "<h3>Current Webhook Info:</h3>";
$webhookInfo = @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo");
if ($webhookInfo) {
    $info = json_decode($webhookInfo, true);
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 6px; overflow: auto;'>";
    echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
}

echo "<hr>";
echo "<h3>📝 Для тестирования на localhost:</h3>";
echo "<p>Используйте <a href='manual_reply.php' style='color: #002d62; font-weight: bold;'>Manual Reply Tool</a></p>";
?>
