<?php
session_start();
require_once '../config/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Получаем данные пользователя
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Обработка формы бронирования
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
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
                } else {
                    $error = "Ошибка при создании бронирования. Пожалуйста, попробуйте еще раз.";
                }
            }
        } catch (Exception $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}

// Получаем бронирования пользователя
$reservations_query = "SELECT * FROM reservations WHERE customer_phone = ? ORDER BY reservation_date DESC, reservation_time DESC";
$reservations_stmt = $db->prepare($reservations_query);
$reservations_stmt->execute([$user['phone']]);
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои бронирования - Ресторан TATMAK</title>
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

    .user-menu {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .user-menu a {
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 5px;
        transition: background 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .user-menu a:hover {
        background: rgba(255,255,255,0.2);
    }

    .container {
        max-width: 1200px;
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
        position: relative;
    }

    .page-header h1 {
        color: #D90A16;
        margin-bottom: 0.5rem;
    }

    .new-reservation-btn {
        position: absolute;
        right: 2rem;
        top: 50%;
        transform: translateY(-50%);
    }

    .reservations-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .reservation-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .reservation-header {
        background: #f8f9fa;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e1e5e9;
    }

    .reservation-info {
        display: flex;
        gap: 2rem;
        align-items: center;
    }

    .reservation-number {
        font-size: 1.2rem;
        font-weight: bold;
        color: #D90A16;
    }

    .reservation-date {
        color: #666;
    }

    .reservation-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    .reservation-details {
        padding: 1.5rem;
    }

    .reservation-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .detail-label {
        font-size: 0.9rem;
        color: #666;
        font-weight: 500;
    }

    .detail-value {
        font-size: 1.1rem;
        font-weight: 500;
    }

    .reservation-comment {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 6px;
        margin-top: 1rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        color: #666;
        margin-bottom: 1rem;
    }

    .btn {
        padding: 10px 20px;
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

    .btn-success {
        background: #D90A16;
        color: white;
    }

    .btn-success:hover {
        background: #b30812;
    }

    /* Стили для формы бронирования */
    .booking-form {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        display: none;
    }

    .booking-form.active {
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

    .quick-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .action-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-align: center;
        flex: 1;
        min-width: 200px;
        max-width: 300px;
    }

    .action-card i {
        font-size: 2.5rem;
        color: #D90A16;
        margin-bottom: 1rem;
    }

    .action-card h3 {
        color: #333;
        margin-bottom: 0.5rem;
    }

    .action-card p {
        color: #666;
        margin-bottom: 1rem;
    }

    @media (max-width: 1024px) {
    /* Планшет */
    .container {
        margin: 1.5rem auto;
        padding: 0 15px;
    }
    
    .reservation-header {
        padding: 1.25rem;
    }
    
    .reservation-details {
        padding: 1.25rem;
    }
    
    .booking-form {
        padding: 1.5rem;
    }
    
    .quick-actions {
        gap: 0.8rem;
    }
    
    .action-card {
        min-width: 180px;
        padding: 1.25rem;
    }
}

@media (max-width: 768px) {
    /* Мобильные устройства */
    .nav-container {
        flex-direction: column;
        gap: 1rem;
        padding: 0 15px;
    }
    
    .user-menu {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .user-menu a {
        padding: 6px 12px;
        font-size: 0.9rem;
    }
    
    .container {
        margin: 1rem auto;
        padding: 0 10px;
    }
    
    .page-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .new-reservation-btn {
        position: static;
        transform: none;
        margin-top: 1rem;
        width: 100%;
        justify-content: center;
    }
    
    .reservation-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
        padding: 1rem;
    }
    
    .reservation-info {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .reservation-grid {
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }
    
    .reservation-details {
        padding: 1rem;
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
    
    .quick-actions {
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }
    
    .action-card {
        width: 100%;
        max-width: none;
        min-width: auto;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    /* Маленькие мобильные устройства */
    .logo a {
        font-size: 1.3rem;
    }
    
    .user-menu {
        flex-direction: column;
        width: 100%;
    }
    
    .user-menu a {
        width: 100%;
        justify-content: center;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .reservation-number {
        font-size: 1.1rem;
    }
    
    .detail-value {
        font-size: 1rem;
    }
    
    .empty-state i {
        font-size: 3rem;
    }
    
    .info-box {
        padding: 0.8rem;
    }
    
    .alert {
        padding: 0.8rem;
    }
}
</style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <a href="../index.php">TATMAK</a>
            </div>
            <div class="user-menu">
                <a href="../index.php"><i class="fas fa-home"></i> На главную</a>
                <a href="profile.php"><i class="fas fa-user"></i>Профиль</a>
                <a href="delivery.php"><i class="fas fa-utensils"></i> Заказать</a>
                <a href="orders.php">
                <i class="fas fa-shopping-cart"></i> Мои заказы
                <?php if (!empty($notifications)): ?>
                    <span class="notification-badge"><?php echo count($notifications); ?></span>
                <?php endif; ?>
            </a>
            <a href="reservations.php"><i class="fas fa-calendar-check"></i> Бронирования</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-calendar-check"></i> Мои бронирования</h1>
                <p>История всех ваших бронирований столиков</p>
            </div>
            <button class="btn btn-success new-reservation-btn" onclick="toggleBookingForm()">
                <i class="fas fa-plus"></i> Новое бронирование
            </button>
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

        <!-- Форма бронирования -->
        <div class="booking-form" id="bookingForm">
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Информация о бронировании</h4>
                <p>После отправки формы наш администратор свяжется с вами для подтверждения бронирования в течение 30 минут.</p>
            </div>

            <form method="POST">
                <input type="hidden" name="create_reservation" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_name" class="required">Ваше имя</label>
                        <input type="text" id="customer_name" name="customer_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_phone" class="required">Телефон</label>
                        <input type="tel" id="customer_phone" name="customer_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="customer_email">Email</label>
                    <input type="email" id="customer_email" name="customer_email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
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

                <div class="form-row">
                    <button type="button" class="btn" onclick="toggleBookingForm()" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Забронировать столик
                    </button>
                </div>
            </form>
        </div>

        <?php if (empty($reservations)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>У вас пока нет бронирований</h3>
                <p>Забронируйте столик и он появится здесь</p>
                <button class="btn btn-primary" onclick="toggleBookingForm()">
                    <i class="fas fa-calendar-plus"></i> Забронировать столик
                </button>
                
                <div class="quick-actions">
                    <div class="action-card">
                        <i class="fas fa-utensils"></i>
                        <h3>Заказ еды</h3>
                        <p>Закажите вкусную еду с доставкой на дом</p>
                        <a href="delivery.php" class="btn btn-primary">
                            <i class="fas fa-utensils"></i> Заказать
                        </a>
                    </div>
                    
                    <div class="action-card">
                        <i class="fas fa-list-alt"></i>
                        <h3>Меню</h3>
                        <p>Посмотрите наше меню и выберите блюда</p>
                        <a href="../index.php#menu" class="btn btn-primary">
                            <i class="fas fa-book-open"></i> Открыть меню
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="reservations-list">
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="reservation-header">
                            <div class="reservation-info">
                                <div class="reservation-number">Бронь #<?php echo $reservation['id']; ?></div>
                                <div class="reservation-date">
                                    Создано: <?php echo date('d.m.Y H:i', strtotime($reservation['created_at'])); ?>
                                </div>
                            </div>
                            <div class="reservation-status status-<?php echo $reservation['status']; ?>">
                                <?php 
                                $status_labels = [
                                    'pending' => 'Ожидание',
                                    'confirmed' => 'Подтверждено',
                                    'cancelled' => 'Отменено'
                                ];
                                echo $status_labels[$reservation['status']] ?? $reservation['status'];
                                ?>
                            </div>
                        </div>
                        
                        <div class="reservation-details">
                            <div class="reservation-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Дата</span>
                                    <span class="detail-value"><?php echo date('d.m.Y', strtotime($reservation['reservation_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Время</span>
                                    <span class="detail-value"><?php echo date('H:i', strtotime($reservation['reservation_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Количество гостей</span>
                                    <span class="detail-value"><?php echo $reservation['guests']; ?> человек</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Имя</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($reservation['customer_name']); ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($reservation['comment'])): ?>
                                <div class="reservation-comment">
                                    <strong>Комментарий:</strong> <?php echo htmlspecialchars($reservation['comment']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button class="btn btn-success" onclick="toggleBookingForm()">
                    <i class="fas fa-plus"></i> Создать новое бронирование
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleBookingForm() {
            const form = document.getElementById('bookingForm');
            form.classList.toggle('active');
            
            if (form.classList.contains('active')) {
                // Устанавливаем минимальную дату как сегодня
                const today = new Date().toISOString().split('T')[0];
                const dateInput = document.getElementById('reservation_date');
                dateInput.min = today;
                
                // Если дата не выбрана, устанавливаем сегодняшнюю
                if (!dateInput.value) {
                    dateInput.value = today;
                }
                
                // Прокручиваем к форме
                form.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Автоматически показываем форму если есть ошибка
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($error): ?>
                toggleBookingForm();
            <?php endif; ?>
        });
    </script>
</body>
</html>