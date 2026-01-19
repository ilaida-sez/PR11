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

// Получаем данные пользователя
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем последние заказы
$orders_query = "SELECT * FROM orders WHERE customer_phone = ? ORDER BY created_at DESC LIMIT 5";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([$user['phone']]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем готовые заказы для уведомлений (без проверки ready_at)
$ready_orders_query = "SELECT * FROM orders WHERE customer_phone = ? AND status = 'completed' ORDER BY created_at DESC";
$ready_orders_stmt = $db->prepare($ready_orders_query);
$ready_orders_stmt->execute([$user['phone']]);
$ready_orders = $ready_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Проверяем уведомления в сессии
$notifications = $_SESSION['user_notifications'] ?? [];

// Добавляем уведомления о готовых заказах
foreach ($ready_orders as $order) {
    $notification_exists = false;
    foreach ($notifications as $notification) {
        if ($notification['order_id'] == $order['id'] && $notification['type'] == 'order_ready') {
            $notification_exists = true;
            break;
        }
    }
    
    if (!$notification_exists) {
        $notifications[] = [
            'type' => 'order_ready',
            'order_id' => $order['id'],
            'message' => "Ваш заказ #{$order['id']} готов и ожидает вас!",
            'timestamp' => $order['created_at'] // Используем created_at вместо ready_at
        ];
    }
}

// Сохраняем обновленные уведомления в сессии
$_SESSION['user_notifications'] = $notifications;

// Получаем последние бронирования
$reservations_query = "SELECT * FROM reservations WHERE customer_phone = ? ORDER BY created_at DESC LIMIT 5";
$reservations_stmt = $db->prepare($reservations_query);
$reservations_stmt->execute([$user['phone']]);
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка закрытия уведомления
if (isset($_POST['dismiss_notification'])) {
    $notification_index = $_POST['notification_index'];
    if (isset($_SESSION['user_notifications'][$notification_index])) {
        unset($_SESSION['user_notifications'][$notification_index]);
        $_SESSION['user_notifications'] = array_values($_SESSION['user_notifications']);
    }
    header('Location: profile.php');
    exit;
}

// Обновление данных профиля
if ($_POST && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    
    if (!empty($name)) {
        $update_query = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        if ($update_stmt->execute([$name, $phone, $user_id])) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $phone;
            $user['name'] = $name;
            $user['phone'] = $phone;
            $success = 'Данные профиля успешно обновлены!';
        } else {
            $error = 'Ошибка при обновлении данных. Попробуйте позже.';
        }
    }
}

// Смена пароля
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET password = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                if ($update_stmt->execute([$hashed_password, $user_id])) {
                    $success_password = 'Пароль успешно изменен!';
                } else {
                    $error_password = 'Ошибка при изменении пароля. Попробуйте позже.';
                }
            } else {
                $error_password = 'Пароль должен содержать минимум 6 символов';
            }
        } else {
            $error_password = 'Новые пароли не совпадают';
        }
    } else {
        $error_password = 'Текущий пароль неверен';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Ресторан TATMAK</title>
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

    .welcome-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        text-align: center;
    }

    .welcome-section h1 {
        color: #D90A16;
        margin-bottom: 0.5rem;
    }

    .notifications-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .notification-item {
        background: #e8f4fd;
        border: 1px solid #b6d7f7;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-content {
        flex: 1;
    }

    .notification-message {
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .notification-time {
        color: #7f8c8d;
        font-size: 0.9rem;
    }

    .notification-badge {
        background: #D90A16;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 10px;
    }

    .btn-dismiss {
        background: #95a5a6;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: 0.8rem;
    }

    .btn-dismiss:hover {
        background: #7f8c8d;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .dashboard-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .dashboard-card h3 {
        color: #D90A16;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #D90A16;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }

    .order-item, .reservation-item {
        padding: 1rem;
        border: 1px solid #e1e5e9;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .order-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d1ecf1; color: #0c5460; }
    .status-preparing { background: #cce7ff; color: #004085; }
    .status-delivering { background: #ffe6cc; color: #663c00; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    .profile-form {
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

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #e1e5e9;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: #D90A16;
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
    }

    .btn-primary {
        background: #D90A16;
        color: white;
    }

    .btn-primary:hover {
        background: #b30812;
    }

    .btn-danger {
        background: #D90A16;
        color: white;
    }

    .btn-danger:hover {
        background: #b30812;
    }

    .alert {
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 1rem;
        font-weight: 500;
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

    .tabs {
        display: flex;
        margin-bottom: 2rem;
        border-bottom: 2px solid #e1e5e9;
    }

    .tab {
        padding: 1rem 2rem;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.3s;
        font-weight: 500;
    }

    .tab.active {
        color: #D90A16;
        border-bottom-color: #D90A16;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    @media (max-width: 1024px) {
    /* Планшет */
    .container {
        margin: 1.5rem auto;
        padding: 0 15px;
    }
    
    .dashboard-grid {
        gap: 1.5rem;
    }
    
    .dashboard-card {
        padding: 1.25rem;
    }
    
    .welcome-section,
    .notifications-section,
    .profile-form {
        padding: 1.5rem;
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
    
    .welcome-section {
        padding: 1.5rem 1rem;
        text-align: center;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.8rem;
    }
    
    .stat-item {
        padding: 0.8rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .notifications-section,
    .profile-form {
        padding: 1.25rem;
    }
    
    .notification-item {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .tabs {
        flex-direction: column;
        border-bottom: none;
    }
    
    .tab {
        text-align: center;
        border-bottom: 2px solid #e1e5e9;
        padding: 1rem;
    }
    
    .tab.active {
        border-bottom-color: #D90A16;
    }
    
    .order-item,
    .reservation-item {
        padding: 0.8rem;
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
    
    .welcome-section h1 {
        font-size: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-number {
        font-size: 1.3rem;
    }
    
    .form-control {
        padding: 8px 10px;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
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
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Выйти</a>
        </div>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <h1><i class="fas fa-user-circle"></i> Добро пожаловать, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <p>Управляйте своими заказами и бронированиями</p>
        </div>

        <!-- Секция уведомлений -->
        <?php if (!empty($notifications)): ?>
        <div class="notifications-section">
            <h3 style="color: #9b59b6; margin-bottom: 1.5rem;">
                <i class="fas fa-bell"></i> Уведомления
            </h3>
            <?php foreach ($notifications as $index => $notification): ?>
                <div class="notification-item">
                    <div class="notification-content">
                        <div class="notification-message">
                            <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                            <?php echo $notification['message']; ?>
                        </div>
                        <div class="notification-time">
                            <?php echo date('d.m.Y H:i', strtotime($notification['timestamp'])); ?>
                        </div>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="notification_index" value="<?php echo $index; ?>">
                        <button type="submit" name="dismiss_notification" class="btn-dismiss">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3><i class="fas fa-chart-line"></i> Статистика</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($orders); ?></div>
                        <div class="stat-label">Всего заказов</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($reservations); ?></div>
                        <div class="stat-label">Бронирований</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-shopping-cart"></i> Последние заказы</h3>
                <?php if (empty($orders)): ?>
                    <p style="color: #666; text-align: center;">Заказов пока нет</p>
                <?php else: ?>
                    <?php foreach (array_slice($orders, 0, 3) as $order): ?>
                        <div class="order-item">
                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
                                <strong>Заказ #<?php echo $order['id']; ?></strong>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php 
                                    $status_labels = [
                                        'pending' => 'Ожидание',
                                        'confirmed' => 'Подтвержден',
                                        'preparing' => 'Готовится',
                                        'delivering' => 'Доставляется',
                                        'completed' => 'Завершен',
                                        'cancelled' => 'Отменен'
                                    ];
                                    echo $status_labels[$order['status']] ?? $order['status'];
                                    ?>
                                </span>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?> • 
                                <?php echo $order['total_amount']; ?> ₽
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($orders) > 3): ?>
                        <a href="orders.php" style="color: #9b59b6; text-decoration: none; display: block; text-align: center; margin-top: 1rem;">
                            Показать все заказы
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-calendar-check"></i> Ближайшие бронирования</h3>
                <?php if (empty($reservations)): ?>
                    <p style="color: #666; text-align: center;">Бронирований пока нет</p>
                <?php else: ?>
                    <?php foreach (array_slice($reservations, 0, 3) as $reservation): ?>
                        <div class="reservation-item">
                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
                                <strong>Бронь #<?php echo $reservation['id']; ?></strong>
                                <span class="order-status status-<?php echo $reservation['status']; ?>">
                                    <?php 
                                    $status_labels = [
                                        'pending' => 'Ожидание',
                                        'confirmed' => 'Подтверждено',
                                        'cancelled' => 'Отменено'
                                    ];
                                    echo $status_labels[$reservation['status']] ?? $reservation['status'];
                                    ?>
                                </span>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo date('d.m.Y', strtotime($reservation['reservation_date'])); ?> в 
                                <?php echo date('H:i', strtotime($reservation['reservation_time'])); ?> • 
                                <?php echo $reservation['guests']; ?> гостей
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($reservations) > 3): ?>
                        <a href="reservations.php" style="color: #9b59b6; text-decoration: none; display: block; text-align: center; margin-top: 1rem;">
                            Показать все бронирования
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showTab('profile')">Профиль</div>
            <div class="tab" onclick="showTab('password')">Смена пароля</div>
        </div>

        <div id="profile-tab" class="tab-content active">
            <div class="profile-form">
                <h3 style="color: #9b59b6; margin-bottom: 1.5rem;">Редактирование профиля</h3>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Имя</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        <small style="color: #666;">Email нельзя изменить</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                </form>
            </div>
        </div>

        <div id="password-tab" class="tab-content">
            <div class="profile-form">
                <h3 style="color: #9b59b6; margin-bottom: 1.5rem;">Смена пароля</h3>
                
                <?php if (isset($success_password)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_password; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_password)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_password; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Текущий пароль</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Новый пароль</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Подтверждение нового пароля</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Сменить пароль
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Скрыть все вкладки
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Убрать активный класс со всех вкладок
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Показать выбранную вкладку
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Активировать выбранную вкладку
            event.target.classList.add('active');
        }
    </script>
</body>
</html>