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

// Получаем и расшифровываем данные
$login_encrypted = $_POST['login'] ?? '';
$password_encrypted = $_POST['password'] ?? '';

$login = decryptAES($login_encrypted, $secretKey);
$password = decryptAES($password_encrypted, $secretKey);

// Проверяем, что расшифровка прошла успешно
if($login === false || $password === false) {
    echo "-1";
    exit();
}

// Ищем пользователя
$query_user = $mysqli->query("SELECT * FROM `users` WHERE `login`='" . $mysqli->real_escape_string($login) . "'");
$id = -1;

if($user_read = $query_user->fetch_row()) {
    echo $id;
} else {
    // Экранируем данные перед вставкой в БД
    $escaped_login = $mysqli->real_escape_string($login);
    $escaped_password = $mysqli->real_escape_string($password);
    
    $mysqli->query("INSERT INTO `users`(`login`, `password`, `roll`) VALUES ('".$escaped_login."', '".$escaped_password."', 0)");
    
    $query_user = $mysqli->query("SELECT * FROM `users` WHERE `login`='".$escaped_login."' AND `password`= '".$escaped_password."';");
    $user_new = $query_user->fetch_row();
    $id = $user_new[0];
        
    if($id != -1) $_SESSION['user'] = $id; // запоминаем пользователя
    echo $id;
}
?>