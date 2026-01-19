<?php
session_start();

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/db.php'; // Подключаем базу данных

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "Логин и пароль обязательны для заполнения";
    } else {
        $query = "SELECT * FROM users WHERE name = ? AND role = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Неверный логин или пароль";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход для администратора</title>
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
        font-family: Arial, sans-serif; 
        background: linear-gradient(135deg, #F4F3F5 0%, #e8e6eb 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .login-container {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
        border-top: 4px solid #D90A16;
    }
    .login-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .login-header h1 {
        color: #D90A16;
        margin-bottom: 0.5rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    .form-group input:focus {
        border-color: #D90A16;
        outline: none;
        box-shadow: 0 0 0 2px rgba(217, 10, 22, 0.1);
    }
    .btn-login {
        width: 100%;
        padding: 12px;
        background: #D90A16;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 1rem;
        transition: background 0.3s;
    }
    .btn-login:hover {
        background: #b30812;
    }
    .error-message {
        background: #D90A16;
        color: white;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 1rem;
        text-align: center;
    }
    .test-info {
        margin-top: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 5px;
        font-size: 0.9rem;
        color: #6c757d;
        border-left: 3px solid #D90A16;
    }

    /* Адаптация для мобильных устройств и планшетов */
@media (max-width: 768px) {
    body {
        padding: 10px;
        align-items: flex-start;
        padding-top: 20px;
    }
    
    .login-container {
        margin: 0;
        padding: 1.5rem;
        width: 100%;
        max-width: none;
    }
    
    .login-header h1 {
        font-size: 1.5rem;
    }
    
    .form-group input {
        padding: 14px;
        font-size: 16px;
    
    .btn-login {
        padding: 14px;
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .login-container {
        padding: 1rem;
    }
    
    .login-header h1 {
        font-size: 1.3rem;
    }
    
    .test-info {
        font-size: 0.8rem;
        padding: 0.8rem;
    }
}
}
</style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Вход для администратора</h1>
            <p>Ресторан "TATMAK"</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Имя пользователя" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            
            <button type="submit" class="btn-login">Войти</button>
        </form>
    </div>
</body>
</html>