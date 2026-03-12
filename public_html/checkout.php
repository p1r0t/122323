<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$name = htmlspecialchars($_POST['name'] ?? '');
$email = htmlspecialchars($_POST['email'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$order_id = $_SESSION['order_id'];

$_SESSION['order_data'] = [
    'name' => $name,
    'email' => $email,
    'amount' => $amount,
    'order_id' => $order_id,
    'status' => 'pending'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата заказа</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Оплата заказа #<?php echo substr($order_id, -8); ?></h1>
        
        <div class="order-summary">
            <p><strong>Имя:</strong> <?php echo $name; ?></p>
            <p><strong>Email:</strong> <?php echo $email; ?></p>
            <p><strong>Сумма:</strong> <?php echo number_format($amount, 2); ?> ₽</p>
        </div>
        
        <form id="paymentForm" action="processing.php" method="POST">
            <div class="form-group">
                <label for="card_number">Номер карты:</label>
                <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="expiry">Срок действия:</label>
                    <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                </div>
                
                <div class="form-group">
                    <label for="cvv">CVV:</label>
                    <input type="text" id="cvv" name="cvv" placeholder="123" required>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Оплатить <?php echo number_format($amount, 2); ?> ₽</button>
        </form>
        
        <div id="statusMessage" class="status-message"></div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
