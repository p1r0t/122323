<?php
$pageTitle = 'Chyba';
require_once __DIR__ . '/../assets/inc/header.php';
?>
    
    <div class="container">
        <div class="loading">
            <div class="error-icon">❌</div>
            <h2>Chyba platby</h2>
            <p style="text-align: center; color: #dc3545;">Platbu sa nepodarilo spracovať. Skúste to znova.</p>
            <button onclick="window.location.href='/index.php'" class="btn-primary" style="margin-top: 20px;">Späť</button>
        </div>
    </div>

<?php require_once __DIR__ . '/../assets/inc/footer.php'; ?>
