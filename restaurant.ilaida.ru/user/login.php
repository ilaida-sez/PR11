<?php
session_start();
require_once '../config/db.php';

// CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Если авторизован - на профиль
if (isset($_SESSION['user_logged_in'])) {
    header('Location: profile.php');
    exit;
}

$db = (new Database())->getConnection();
$error = '';
$success = '';

// Вход
if ($_POST && isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Недействительный токен';
    } else {
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'user'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = htmlspecialchars($user['name']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Обновляем токен
            
            header('Location: profile.php');
            exit;
        } else {
            $error = 'Неверный email или пароль';
        }
    }
}

// Регистрация
if ($_POST && isset($_POST['register'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Недействительный токен';
    } else {
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        
        // Простая валидация
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Заполните все поля';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Некорректный email';
        } elseif ($password !== $confirm) {
            $error = 'Пароли не совпадают';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль от 6 символов';
        } else {
            // Проверка существования email
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email уже используется';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                
                if ($stmt->execute([$name, $email, $hashed])) {
                    $success = 'Регистрация успешна! Можете войти.';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Обновляем токен
                } else {
                    $error = 'Ошибка регистрации';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в личный кабинет - Ресторан TATMAK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .auth-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 900px;
        overflow: hidden;
    }

    .auth-header {
        background: #D90A16;
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .auth-header h1 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .auth-header p {
        opacity: 0.9;
    }

    .auth-tabs {
        display: flex;
        background: #b30812;
    }

    .auth-tab {
        flex: 1;
        padding: 1rem;
        text-align: center;
        color: white;
        cursor: pointer;
        transition: background 0.3s;
        border: none;
        background: none;
        font-size: 1rem;
        font-weight: 500;
    }

    .auth-tab.active {
        background: #D90A16;
    }

    .auth-tab:hover {
        background: #b30812;
    }

    .auth-content {
        padding: 2rem;
    }

    .auth-form {
        display: none;
    }

    .auth-form.active {
        display: block;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: #D90A16;
    }

    .btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-primary {
        background: #D90A16;
        color: white;
    }

    .btn-primary:hover {
        background: #b30812;
        transform: translateY(-2px);
    }

    .alert {
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }

    .alert-error {
        background: #fee;
        color: #c33;
        border: 1px solid #fcc;
    }

    .alert-success {
        background: #efe;
        color: #363;
        border: 1px solid #cfc;
    }

    .form-footer {
        text-align: center;
        margin-top: 1.5rem;
        color: #666;
    }

    .form-footer a {
        color: #D90A16;
        text-decoration: none;
        font-weight: 500;
    }

    .form-footer a:hover {
        text-decoration: underline;
    }

    @media (max-width: 1024px) {
    /* Планшет */
    .auth-container {
        max-width: 800px;
    }
    
    .auth-header {
        padding: 1.5rem;
    }
    
    .auth-content {
        padding: 1.5rem;
    }
}

@media (max-width: 768px) {
    /* Мобильные устройства */
    body {
        padding: 15px;
        align-items: flex-start;
        min-height: 100vh;
        height: auto;
    }
    
    .auth-container {
        margin: 0;
        max-width: none;
    }
    
    .auth-header {
        padding: 1.5rem 1rem;
    }
    
    .auth-header h1 {
        font-size: 1.5rem;
    }
    
    .auth-tabs {
        flex-direction: column;
    }
    
    .auth-tab {
        padding: 1rem;
        font-size: 0.95rem;
    }
    
    .auth-content {
        padding: 1.25rem;
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-control {
        padding: 10px 12px;
        font-size: 0.95rem;
    }
    
    .btn {
        padding: 10px;
        font-size: 0.95rem;
    }
    
    .alert {
        padding: 10px 12px;
        margin-bottom: 1.25rem;
        font-size: 0.9rem;
    }
    
    .form-footer {
        margin-top: 1.25rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    /* Маленькие мобильные устройства */
    body {
        padding: 10px;
    }
    
    .auth-header {
        padding: 1.25rem 0.8rem;
    }
    
    .auth-header h1 {
        font-size: 1.3rem;
    }
    
    .auth-header p {
        font-size: 0.9rem;
    }
    
    .auth-content {
        padding: 1rem;
    }
    
    .form-control {
        padding: 8px 10px;
    }
    
    .btn {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
    
    .form-footer {
        font-size: 0.85rem;
    }
}
</style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-user-circle"></i> Личный кабинет</h1>
            <p>Войдите или зарегистрируйтесь для управления заказами</p>
        </div>
        
        <div class="auth-tabs">
            <button class="auth-tab active" onclick="showTab('login')">Вход</button>
            <button class="auth-tab" onclick="showTab('register')">Регистрация</button>
        </div>
        
        <div class="auth-content">
            <!-- Форма входа -->
            <div id="login-form" class="auth-form active">
                <?php if ($error && isset($_POST['login'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="email" id="login-email" name="email" class="form-control" required 
       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Пароль</label>
                        <input type="password" id="login-password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </button>
                </form>
                
                <div class="form-footer">
                    <p>Нет аккаунта? <a href="javascript:void(0)" onclick="showTab('register')">Зарегистрируйтесь</a></p>
                </div>
            </div>
            
            <!-- Форма регистрации -->
            <div id="register-form" class="auth-form">
                <?php if ($error && isset($_POST['register'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="register-name">Имя <span style="color: #c33">*</span></label>
                        <input type="text" id="register-name" name="name" class="form-control" required
       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Email <span style="color: #c33">*</span></label>
                        <input type="email" id="register-email" name="email" class="form-control" required
       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-phone">Телефон</label>
                        <input type="tel" id="register-phone" name="phone" class="form-control"
       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Пароль <span style="color: #c33">*</span></label>
                        <input type="password" id="register-password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-confirm-password">Подтверждение пароля <span style="color: #c33">*</span></label>
                        <input type="password" id="register-confirm-password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Зарегистрироваться
                    </button>
                </form>
                
                <div class="form-footer">
                    <p>Уже есть аккаунт? <a href="javascript:void(0)" onclick="showTab('login')">Войдите</a></p>
                </div>
            </div>
        </div>
    </div>

<script>
const secretKey = "qazxswedcvfrgtgbn";

function encryptAES(data, key) {
    var keyHash = CryptoJS.MD5(key);
    var keyBytes = CryptoJS.enc.Hex.parse(keyHash.toString());
    var iv = CryptoJS.lib.WordArray.random(16);
    
    var encrypted = CryptoJS.AES.encrypt(data, keyBytes, {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7
    });
    
    var combined = iv.concat(encrypted.ciphertext);
    return CryptoJS.enc.Base64.stringify(combined);
}

// Для ВХОДА
document.getElementById('login-form').querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var email = this.querySelector('input[name="email"]').value;
    var password = this.querySelector('input[name="password"]').value;
    
    if (!email || !password) {
        this.submit();
        return;
    }
    
    var encryptedEmail = encryptAES(email, secretKey);
    var encryptedPassword = encryptAES(password, secretKey);
    
    var hiddenEmail = document.createElement('input');
    hiddenEmail.type = 'hidden';
    hiddenEmail.name = 'encrypted_email';
    hiddenEmail.value = encryptedEmail;
    
    var hiddenPassword = document.createElement('input');
    hiddenPassword.type = 'hidden';
    hiddenPassword.name = 'encrypted_password';
    hiddenPassword.value = encryptedPassword;
    
    this.querySelector('input[name="email"]').removeAttribute('name');
    this.querySelector('input[name="password"]').removeAttribute('name');
    
    this.appendChild(hiddenEmail);
    this.appendChild(hiddenPassword);
    
    this.submit();
});

// Для РЕГИСТРАЦИИ
document.getElementById('register-form').querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var name = this.querySelector('input[name="name"]').value;
    var email = this.querySelector('input[name="email"]').value;
    var password = this.querySelector('input[name="password"]').value;
    var confirm = this.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirm) {
        alert('Пароли не совпадают');
        return;
    }
    
    var encryptedName = encryptAES(name, secretKey);
    var encryptedEmail = encryptAES(email, secretKey);
    var encryptedPassword = encryptAES(password, secretKey);
    var encryptedConfirm = encryptAES(confirm, secretKey);
    
    var fields = [
        {name: 'encrypted_name', value: encryptedName},
        {name: 'encrypted_email', value: encryptedEmail},
        {name: 'encrypted_password', value: encryptedPassword},
        {name: 'encrypted_confirm', value: encryptedConfirm}
    ];
    
    fields.forEach(function(field) {
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = field.name;
        hidden.value = field.value;
        this.appendChild(hidden);
    }.bind(this));
    
    ['name', 'email', 'password', 'confirm_password'].forEach(function(field) {
        var input = this.querySelector('input[name="' + field + '"]');
        if (input) input.removeAttribute('name');
    }.bind(this));
    
    this.submit();
});

// Функция переключения вкладок
function showTab(tabName) {
    document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.remove('active');
    });
    
    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.getElementById(tabName + '-form').classList.add('active');
    
    // Находим кнопку по тексту
    document.querySelectorAll('.auth-tab').forEach(tab => {
        if (tab.textContent.toLowerCase().includes(tabName.toLowerCase())) {
            tab.classList.add('active');
        }
    });
}
</script>
</body>
</html>