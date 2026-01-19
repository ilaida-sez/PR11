<?php
session_start();
require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Обработка формы бронирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_email = trim($_POST['customer_email']);
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];
    $guests = intval($_POST['guests']);
    $comment = trim($_POST['comment']);
    
    // Валидация
    if (empty($customer_name) || empty($customer_phone) || empty($reservation_date) || empty($reservation_time)) {
        $error = "Пожалуйста, заполните все обязательные поля";
    } elseif ($guests < 1 || $guests > 20) {
        $error = "Количество гостей должно быть от 1 до 20";
    } else {
        try {
            // Проверяем, не забронировано ли уже это время
            $check_query = "SELECT COUNT(*) as count FROM reservations 
                           WHERE reservation_date = ? AND reservation_time = ? 
                           AND status IN ('pending', 'confirmed')";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$reservation_date, $reservation_time]);
            $existing_reservations = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_reservations['count'] > 0) {
                $error = "На это время уже есть бронирование. Пожалуйста, выберите другое время.";
            } else {
                // Создаем бронирование
                $insert_query = "INSERT INTO reservations 
                                (customer_name, customer_phone, customer_email, 
                                 reservation_date, reservation_time, guests, comment, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
                $insert_stmt = $db->prepare($insert_query);
                
                if ($insert_stmt->execute([
                    $customer_name, 
                    $customer_phone, 
                    $customer_email,
                    $reservation_date, 
                    $reservation_time, 
                    $guests, 
                    $comment
                ])) {
                    $success = "Бронирование успешно создано! Мы свяжемся с вами для подтверждения.";
                    // Очищаем форму
                    $_POST = [];
                } else {
                    $error = "Ошибка при создании бронирования. Пожалуйста, попробуйте еще раз.";
                }
            }
        } catch (Exception $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование столика - Ресторан TATMAK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        background: #F4F3F5;
        line-height: 1.6;
    }

    .header {
        background: #D90A16;
        color: white;
        padding: 1rem 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo a {
        color: white;
        text-decoration: none;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .nav-menu {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .nav-menu a {
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 5px;
        transition: background 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .nav-menu a:hover {
        background: rgba(255,255,255,0.2);
    }

    .container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 20px;
    }

    .page-header {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        text-align: center;
    }

    .page-header h1 {
        color: #D90A16;
        margin-bottom: 0.5rem;
    }

    .booking-form {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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

    .form-group label.required::after {
        content: " *";
        color: #D90A16;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: #D90A16;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background: #D90A16;
        color: white;
    }

    .btn-primary:hover {
        background: #b30812;
    }

    .btn-block {
        width: 100%;
        justify-content: center;
    }

    .info-box {
        background: #e8f4fd;
        border: 1px solid #b6d7f7;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .info-box h4 {
        color: #2c3e50;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    @media (max-width: 1024px) {
    /* Планшет */
    .container {
        margin: 1.5rem auto;
        padding: 0 15px;
        max-width: 700px;
    }
    
    .page-header {
        padding: 1.5rem;
    }
    
    .booking-form {
        padding: 1.5rem;
    }
    
    .info-box {
        padding: 0.8rem;
        margin-bottom: 1.25rem;
    }
}

@media (max-width: 768px) {
    /* Мобильные устройства */
    .nav-container {
        flex-direction: column;
        gap: 1rem;
        padding: 0 15px;
    }
    
    .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .nav-menu a {
        padding: 6px 12px;
        font-size: 0.9rem;
    }
    
    .container {
        margin: 1rem auto;
        padding: 0 10px;
        max-width: none;
    }
    
    .page-header {
        padding: 1.25rem;
        text-align: center;
        margin-bottom: 1rem;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .booking-form {
        padding: 1.25rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-control {
        padding: 10px;
        font-size: 0.95rem;
    }
    
    textarea.form-control {
        min-height: 80px;
    }
    
    .btn {
        padding: 10px 20px;
        font-size: 0.95rem;
        width: 100%;
    }
    
    .alert {
        padding: 0.8rem;
        margin-bottom: 0.8rem;
    }
    
    .info-box {
        padding: 0.8rem;
        margin-bottom: 1rem;
    }
    
    .info-box h4 {
        font-size: 1rem;
    }
    
    .info-box p {
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    /* Маленькие мобильные устройства */
    .logo a {
        font-size: 1.3rem;
    }
    
    .nav-menu {
        flex-direction: column;
        width: 100%;
    }
    
    .nav-menu a {
        width: 100%;
        justify-content: center;
    }
    
    .page-header h1 {
        font-size: 1.3rem;
    }
    
    .page-header p {
        font-size: 0.9rem;
    }
    
    .form-control {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
    
    select.form-control {
        font-size: 0.9rem;
    }
    
    .btn {
        padding: 8px 16px;
        font-size: 0.9rem;
    }
}
</style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <a href="index.php">TATMAK</a>
            </div>
            <div class="nav-menu">
                <a href="../index.php"><i class="fas fa-home"></i> На главную</a>
                <a href="profile.php"><i class="fas fa-user"></i> Профиль</a>
                <a href="delivery.php"><i class="fas fa-utensils"></i> Заказать</a>
                <?php if (isset($_SESSION['user_logged_in'])): ?>
                    <a href="orders.php">
                <i class="fas fa-shopping-cart"></i> Мои заказы
                <?php if (!empty($notifications)): ?>
                    <span class="notification-badge"><?php echo count($notifications); ?></span>
                <?php endif; ?>
            </a>
            <a href="reservations.php"><i class="fas fa-calendar-check"></i> Бронирования</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Войти</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-plus"></i> Бронирование столика</h1>
            <p>Забронируйте столик в нашем ресторане</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h4><i class="fas fa-info-circle"></i> Информация о бронировании</h4>
            <p>После отправки формы наш администратор свяжется с вами для подтверждения бронирования в течение 30 минут.</p>
        </div>

        <form method="POST" class="booking-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="customer_name" class="required">Ваше имя</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_phone" class="required">Телефон</label>
                    <input type="tel" id="customer_phone" name="customer_phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="customer_email">Email</label>
                <input type="email" id="customer_email" name="customer_email" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="reservation_date" class="required">Дата бронирования</label>
                    <input type="date" id="reservation_date" name="reservation_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['reservation_date'] ?? ''); ?>" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="reservation_time" class="required">Время</label>
                    <select id="reservation_time" name="reservation_time" class="form-control" required>
                        <option value="">Выберите время</option>
                        <?php
                        // Генерируем варианты времени с 10:00 до 22:00 с интервалом в 30 минут
                        for ($hour = 10; $hour <= 22; $hour++) {
                            for ($minute = 0; $minute < 60; $minute += 30) {
                                if ($hour == 22 && $minute > 0) break;
                                $time = sprintf('%02d:%02d', $hour, $minute);
                                $selected = ($_POST['reservation_time'] ?? '') === $time ? 'selected' : '';
                                echo "<option value='$time' $selected>$time</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="guests" class="required">Количество гостей</label>
                <select id="guests" name="guests" class="form-control" required>
                    <option value="">Выберите количество гостей</option>
                    <?php for ($i = 1; $i <= 20; $i++): ?>
                        <option value="<?php echo $i; ?>" 
                            <?php echo ($_POST['guests'] ?? '') == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?> <?php echo $i == 1 ? 'гость' : ($i <= 4 ? 'гостя' : 'гостей'); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="comment">Дополнительные пожелания</label>
                <textarea id="comment" name="comment" class="form-control" 
                          placeholder="Укажите особые пожелания (аллергии, праздник, особые требования и т.д.)"><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-calendar-check"></i> Забронировать столик
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Устанавливаем минимальную дату как сегодня
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('reservation_date').min = today;
            
            // Если дата не выбрана, устанавливаем сегодняшнюю
            const dateInput = document.getElementById('reservation_date');
            if (!dateInput.value) {
                dateInput.value = today;
            }
        });
    </script>
</body>
</html>