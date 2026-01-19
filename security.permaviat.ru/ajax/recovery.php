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

function PasswordGeneration() {
    $chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
    $max = 10;
    $size = StrLen($chars) - 1;
    $password = "";
    
    while($max--) {
        $password .= $chars[rand(0, $size)];
    }
    
    return $password;
}

// Получаем и расшифровываем данные
$login_encrypted = $_POST['login'] ?? '';
$login = decryptAES($login_encrypted, $secretKey);

// Проверяем успешность расшифровки
if($login === false) {
    echo "-1";
    exit();
}

// Экранируем логин перед использованием в SQL
$escaped_login = $mysqli->real_escape_string($login);

// Ищем пользователя
$query_user = $mysqli->query("SELECT * FROM `users` WHERE `login`='" . $escaped_login . "'");
$id = -1;

if($user_read = $query_user->fetch_row()) {
    $id = $user_read[0];
    
    // Генерируем новый пароль
    do {
        $password = PasswordGeneration();
        $hashed_password = md5($password);
        
        // Проверяем, не используется ли пароль другим пользователем
        $query_password = $mysqli->query("SELECT * FROM `users` WHERE `password`= '" . $hashed_password . "'");
        $password_exists = ($query_password->num_rows > 0);
    } while ($password_exists);
    
    // Обновляем пароль
    $mysqli->query("UPDATE `users` SET `password`='" . $hashed_password . "' WHERE `login` = '" . $escaped_login . "'");
    
    // Отправляем письмо (раскомментировать когда будет настроена почта)
    // $subject = 'Безопасность web-приложений КГАПОУ "Авиатехникум"';
    // $message = "Ваш пароль был только что изменён. Новый пароль: " . $password;
    // mail($login, $subject, $message);
    
    // Для тестирования - выводим пароль в консоль
    error_log("Новый пароль для {$login}: {$password}");
}

echo $id;
?>