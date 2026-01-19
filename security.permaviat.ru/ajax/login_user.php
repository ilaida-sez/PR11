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
    $keyBaytes = hex2bin($keyHash);

    $decrypted = openssl_decrypt(
        $encrypted,
        'aes-128-cbc',
        $keyBaytes,
        OPENSSL_RAW_DATA,
        $iv
    );

    return $decrypted;
}

$login = decryptAES($_POST['login'], $secretKey);
$password = decryptAES($_POST['password'], $secretKey);

$query_user = $mysqli->query("SELECT * FROM `users` WHERE `login`='" . $login . "' AND `password` = '" . $password . "'");

$id = -1;
while($user_read = $query_user->fetch_row()) {
    $id = $user_read[0];
}

if($id != -1) {
    $_SESSION['user'] = $id;
	} else {
		echo md5($id);
	}
?>