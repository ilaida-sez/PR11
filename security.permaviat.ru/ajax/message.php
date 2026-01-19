<?php
session_start();
include("../settings/connect_datebase.php");

$secretKey = "qazxswedcvfrgtgbn";

function decryptAES($encryptedData, $key) {
    $data = base64_decode($encryptedData);

    if($data === false || strlen($data) < 17) {
        return false;
    }

    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);

    $keyHash = md5($key);
    $keyBytes = hex2bin($keyHash);

    $decrypted = openssl_decrypt(
        $encrypted,
        'aes-128-cbc',
        $keyBytes,
        OPENSSL_RAW_DATA,
        $iv
    );

    return $decrypted;
}

function encryptAES($data, $key) {
    $keyHash = md5($key);
    $keyBytes = hex2bin($keyHash);
    
    $iv = openssl_random_pseudo_bytes(16);
    
    $encrypted = openssl_encrypt(
        $data,
        'aes-128-cbc',
        $keyBytes,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    $combined = $iv . $encrypted;
    
    return base64_encode($combined);
}

$IdUser = $_SESSION['user'] ?? 0;
$Message_encrypted = $_POST["Message"] ?? '';
$IdPost = $_POST["IdPost"] ?? 0;

// Расшифровываем сообщение
$Message = decryptAES($Message_encrypted, $secretKey);

if($Message === false || $IdUser == 0 || $IdPost == 0) {
    echo "Ошибка: неверные данные";
    exit();
}

// Экранируем данные
$escapedMessage = $mysqli->real_escape_string($Message);
$escapedIdUser = intval($IdUser);
$escapedIdPost = intval($IdPost);

// Вставляем в базу данных
$query = $mysqli->query("INSERT INTO `comments`(`IdUser`, `IdPost`, `Messages`) VALUES ({$escapedIdUser}, {$escapedIdPost}, '{$escapedMessage}')");

if($query) {
        echo "success";
    } else {
        echo "Ошибка при сохранении комментария";
    }
?>