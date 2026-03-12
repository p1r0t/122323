#!/bin/bash

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Установка Payment System с Telegram Bot             ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

# Проверка прав root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ Запустите скрипт с правами root: sudo ./install.sh${NC}"
    exit 1
fi

echo -e "${YELLOW}📦 Обновление системы...${NC}"
apt update && apt upgrade -y

echo -e "${YELLOW}📦 Установка необходимых пакетов...${NC}"
apt install -y nginx php-fpm php-cli php-curl php-mbstring php-json certbot python3-certbot-nginx git curl

# Определяем версию PHP
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null || echo "7.4")
echo -e "${GREEN}✅ Обнаружен PHP $PHP_VERSION${NC}"

echo -e "${YELLOW}📁 Создание директорий...${NC}"
mkdir -p /var/www/payment

# Копирование файлов проекта
echo -e "${YELLOW}📋 Копирование файлов проекта...${NC}"
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
if [ -d "$SCRIPT_DIR/public_html" ]; then
    cp -r "$SCRIPT_DIR/public_html" /var/www/payment/
    echo -e "${GREEN}✅ Файлы проекта скопированы${NC}"
else
    echo -e "${RED}❌ Папка public_html не найдена в $SCRIPT_DIR${NC}"
    exit 1
fi

# Настройка прав доступа
echo -e "${YELLOW}🔐 Настройка прав доступа...${NC}"
chown -R www-data:www-data /var/www/payment
chmod -R 755 /var/www/payment
chmod -R 777 /var/www/payment/public_html/logs

# Запрос данных
echo ""
echo -e "${YELLOW}📝 Введите данные для настройки:${NC}"
read -p "Домен (например: example.com): " DOMAIN
read -p "Telegram Bot Token: " BOT_TOKEN
read -p "Telegram Chat ID: " CHAT_ID

# Обновление config.php
echo -e "${YELLOW}⚙️  Настройка конфигурации...${NC}"
cat > /var/www/payment/public_html/config.php << EOF
<?php
// Telegram Bot Configuration
define('BOT_TOKEN', '$BOT_TOKEN');
define('CHAT_ID', '$CHAT_ID');

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
    \$ipHeaders = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach (\$ipHeaders as \$header) {
        if (!empty(\$_SERVER[\$header])) {
            \$ip = \$_SERVER[\$header];
            if (strpos(\$ip, ',') !== false) {
                \$ips = explode(',', \$ip);
                \$ip = trim(\$ips[0]);
            }
            if (filter_var(\$ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return \$ip;
            }
        }
    }
    
    return \$_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

// Telegram API Helper
function sendTelegramMessage(\$text, \$keyboard = null) {
    \$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    
    \$data = [
        'chat_id' => CHAT_ID,
        'text' => \$text,
        'parse_mode' => 'HTML'
    ];
    
    if (\$keyboard !== null) {
        \$data['reply_markup'] = \$keyboard;
    }
    
    \$options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode(\$data),
            'ignore_errors' => true
        ]
    ];
    
    \$context = stream_context_create(\$options);
    \$result = @file_get_contents(\$url, false, \$context);
    
    return \$result;
}

function getSessionId() {
    return md5(getRealIpAddress() . time() . rand(1000, 9999));
}
EOF

# Настройка Nginx
echo -e "${YELLOW}🌐 Настройка Nginx...${NC}"
cat > /etc/nginx/sites-available/$DOMAIN << EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root /var/www/payment/public_html;
    index index.php index.html;

    # Логи
    access_log /var/log/nginx/${DOMAIN}_access.log;
    error_log /var/log/nginx/${DOMAIN}_error.log;

    # Защита логов
    location /logs {
        deny all;
        return 404;
    }

    # PHP обработка
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Статические файлы
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Запрет доступа к скрытым файлам
    location ~ /\. {
        deny all;
    }
}
EOF

# Активация сайта
ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Проверка конфигурации Nginx
nginx -t
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Конфигурация Nginx корректна${NC}"
    systemctl restart nginx
else
    echo -e "${RED}❌ Ошибка в конфигурации Nginx${NC}"
    exit 1
fi

# SSL сертификат
echo ""
echo -e "${YELLOW}🔒 Установка SSL сертификата...${NC}"
read -p "Установить SSL сертификат? (y/n): " INSTALL_SSL

if [ "$INSTALL_SSL" = "y" ]; then
    read -p "Email для Let's Encrypt: " EMAIL
    certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos -m $EMAIL
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ SSL сертификат установлен${NC}"
    else
        echo -e "${RED}❌ Ошибка установки SSL${NC}"
    fi
fi

# Настройка webhook
echo ""
echo -e "${YELLOW}🤖 Настройка Telegram webhook...${NC}"
WEBHOOK_URL="https://$DOMAIN/bot/bot_handler.php"
curl -s "https://api.telegram.org/bot$BOT_TOKEN/setWebhook?url=$WEBHOOK_URL" > /dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Webhook установлен: $WEBHOOK_URL${NC}"
else
    echo -e "${RED}❌ Ошибка установки webhook${NC}"
fi

# Создание systemd сервиса для polling (резервный вариант)
echo -e "${YELLOW}🔧 Создание systemd сервиса для бота...${NC}"
cat > /etc/systemd/system/telegram-bot.service << EOF
[Unit]
Description=Telegram Bot Polling Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/payment/public_html
ExecStart=/usr/bin/php /var/www/payment/public_html/bot/polling.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable telegram-bot.service

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              ✅ Установка завершена!                   ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📋 Информация:${NC}"
echo -e "   Сайт: https://$DOMAIN"
echo -e "   Админ панель: https://$DOMAIN/admin/manual_reply.php"
echo -e "   Webhook: $WEBHOOK_URL"
echo ""
echo -e "${YELLOW}🤖 Управление ботом:${NC}"
echo -e "   Webhook работает автоматически"
echo -e "   Резервный polling: sudo systemctl start telegram-bot"
echo ""
echo -e "${YELLOW}📝 Логи:${NC}"
echo -e "   Nginx: /var/log/nginx/${DOMAIN}_*.log"
echo -e "   Бот: /var/www/payment/public_html/logs/bot_updates.log"
echo -e "   Сессии: /var/www/payment/public_html/logs/*.status"
echo ""
echo -e "${GREEN}✅ Готово! Откройте https://$DOMAIN в браузере${NC}"
