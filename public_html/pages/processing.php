<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
$expiry = $_POST['expiry'] ?? '';
$cvv = $_POST['cvv'] ?? '';

$order_data = $_SESSION['order_data'] ?? null;

if (!$order_data) {
    die('Ошибка: данные заказа не найдены');
}

$_SESSION['order_data']['status'] = 'processing';
$_SESSION['order_data']['card_last4'] = substr($card_number, -4);

$gateway_url = 'gateway.php';
$post_data = [
    'order_id' => $order_data['order_id'],
    'name' => $order_data['name'],
    'email' => $order_data['email'],
    'amount' => $order_data['amount'],
    'card_number' => $card_number,
    'expiry' => $expiry,
    'cvv' => $cvv
];

$ch = curl_init($gateway_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result && $result['success']) {
    $_SESSION['order_data']['status'] = 'completed';
    echo json_encode(['success' => true, 'message' => 'Оплата успешно обработана']);
} else {
    $_SESSION['order_data']['status'] = 'failed';
    echo json_encode(['success' => false, 'message' => 'Ошибка обработки платежа']);
}
