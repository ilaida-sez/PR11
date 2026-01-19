<?php
session_start();
include("./settings/connect_datebase.php");

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
?>
<!DOCTYPE HTML>
<html>
	<head> 
		<meta charset="utf-8">
		<title> WEB-безопасность </title>
		
		<link rel="stylesheet" href="style.css">
		<script src="https://code.jquery.com/jquery-1.8.3.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/aes.js"></script>
	</head>
	<body>
		<div class="top-menu">
			<a class="button" href = "./login.php">Войти</a>
		
			<a href=#><img src = "img/logo1.png"/></a>
			<div class="name">
				<a href="index.php">
					<div class="subname">БЗОПАСНОСТЬ  ВЕБ-ПРИЛОЖЕНИЙ</div>
					Пермский авиационный техникум им. А. Д. Швецова
				</a>
			</div>
			
			
		</div>
		<div class="space"> </div>
		<div class="main">
			<div class="content">
				<div class="name">Новости:</div>
				
				<div>
					<?php
						$query_news = $mysqli->query("SELECT * FROM `news`;");
						while($read_news = $query_news->fetch_assoc()) {
							$QueryMessages = $mysqli->query("SELECT * FROM `comments` WHERE `IdPost` = {$read_news["id"]}");

							echo '
								<div class="specialty">
									<div class = "slider">
										<div class = "inner">
											<div class="name">'.$read_news["title"].'</div>
											<div class="description" style="overflow: auto;">
												<img src = "'.$read_news["img"].'" style="width: 50px; box-shadow: 0 2px 2px 0 rgba(0,0,0,.14), 0 3px 1px -2px rgba(0,0,0,.12), 0 1px 5px 0 rgba(0,0,0,.2); float: left; margin-right: 10px;">
												'.$read_news["text"].'
												
											</div>
											<div class="messages">';
											
											// Выводим расшифрованные комментарии
											while($ReadMessages = $QueryMessages->fetch_assoc()) {
												$decryptedMessage = decryptAES($ReadMessages["Messages"], $secretKey);
												if($decryptedMessage !== false) {
													echo "<div>" . htmlspecialchars($decryptedMessage) . "</div>";
												} else {
													echo "<div>Не удалось расшифровать сообщение</div>";
												}
											}
											
											echo '</div>';

											
											if (isset($_SESSION['user'])) {
												echo 
													'<div class="messages" id="'.$read_news["id"].'">
														<input type="text" placeholder="Введите комментарий">
														<div class="button" style="float: right; margin-top: 0px; margin-right: 0px;" onclick="SendMessage(this)">Отправить</div>
													</div>';
											}
											
										echo 
										'</div>
									</div>
								</div>
							';
						}
					?>
					<div class="footer">
						© КГАПОУ "Авиатехникум", 2020
						<a href=#>Конфиденциальность</a>
						<a href=#>Условия</a>
					</div>
				</div>
			</div>
		</div>
	</body>
	<script>
		const secretKey = "qazxswedcvfrgtgbn";

		function encryptAES(data, key) {
			var keyHash = CryptoJS.MD5(key);
			var keyBytes = CryptoJS.enc.Hex.parse(keyHash.toString());

			var iv = CryptoJS.lib.WordArray.random(16);

			var encrypted = CryptoJS.AES.encrypt(data, keyBytes, {
				iv : iv,
				mode: CryptoJS.mode.CBC,
				padding: CryptoJS.pad.Pkcs7
			});

			var combined = iv.concat(encrypted.ciphertext);

			return CryptoJS.enc.Base64.stringify(combined);
		}
		
		function decryptAESJS(encryptedData, key) {
			try {
				var keyHash = CryptoJS.MD5(key);
				var keyBytes = CryptoJS.enc.Hex.parse(keyHash.toString());
				
				var combined = CryptoJS.enc.Base64.parse(encryptedData);
				var iv = CryptoJS.lib.WordArray.create(combined.words.slice(0, 4));
				var ciphertext = CryptoJS.lib.WordArray.create(combined.words.slice(4));
				
				var decrypted = CryptoJS.AES.decrypt(
					{ciphertext: ciphertext},
					keyBytes,
					{iv: iv}
				);
				
				return decrypted.toString(CryptoJS.enc.Utf8);
			} catch(e) {
				console.error("Ошибка дешифрования:", e);
				return false;
			}
		}

		function SendMessage(sender) {
			let Message = sender.parentElement.children[0].value;
			let IdPost = sender.parentElement.id;
			
			if(Message == "") {
				alert("Введите сообщение");
				return;
			}

			// ШИФРУЕМ сообщение перед отправкой
			var encryptedMessage = encryptAES(Message, secretKey);
			
			var Data = new FormData();
			Data.append("Message", encryptedMessage);
			Data.append("IdPost", IdPost);
			
			$.ajax({
				url: 'ajax/message.php',
				type: 'POST',
				data: Data,
				cache: false,
				dataType: 'html',
				processData: false,
				contentType: false,
				success: function (_data) {
					console.log("Ответ сервера:", _data);
					if(_data == "success") {
						// Очищаем поле ввода
						sender.parentElement.children[0].value = "";
						
						// Добавляем новый комментарий в список (зашифрованный для передачи)
						// В реальном приложении нужно получать ответ от сервера с зашифрованным сообщением
						// или перезагружать комментарии с сервера
						
						// Для простоты добавим напрямую (в реальности нужно перезагружать с сервера)
						var messagesDiv = sender.parentElement.parentElement.querySelector('.messages:first-of-type');
						if(messagesDiv) {
							messagesDiv.innerHTML += "<div>" + Message + "</div>";
						}
					} else {
						alert("Ошибка при отправке сообщения: " + _data);
					}
				},
				error: function(xhr, status, error) {
					console.log('Системная ошибка:', error);
					alert("Системная ошибка при отправке сообщения");
				}
			});
		}
		
		// Добавляем обработку нажатия Enter в полях ввода комментариев
		document.addEventListener('DOMContentLoaded', function() {
			var inputs = document.querySelectorAll('.messages input[type="text"]');
			inputs.forEach(function(input) {
				input.addEventListener('keypress', function(e) {
					if (e.keyCode == 13) {
						var button = this.parentElement.querySelector('.button');
						if(button) {
							SendMessage(button);
						}
					}
				});
			});
		});
	</script>
</html>