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
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Получаем заказы пользователя
$orders_query = "SELECT * FROM orders WHERE customer_phone = ? ORDER BY created_at DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([$user['phone']]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Для каждого заказа получаем товары
foreach ($orders as &$order) {
    $items_query = "SELECT oi.*, d.name, d.price, d.image FROM order_items oi 
                   LEFT JOIN dishes d ON oi.dish_id = d.id 
                   WHERE oi.order_id = ?";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([$order['id']]);
    $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($order);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы - Ресторан TATMAK</title>
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

    .new-order-btn {
        position: absolute;
        right: 2rem;
        top: 50%;
        transform: translateY(-50%);
    }

    .orders-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .order-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .order-header {
        background: #f8f9fa;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e1e5e9;
    }

    .order-info {
        display: flex;
        gap: 2rem;
        align-items: center;
    }

    .order-number {
        font-size: 1.2rem;
        font-weight: bold;
        color: #D90A16;
    }

    .order-date {
        color: #666;
    }

    .order-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d1ecf1; color: #0c5460; }
    .status-preparing { background: #cce7ff; color: #004085; }
    .status-delivering { background: #ffe6cc; color: #663c00; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    .order-details {
        padding: 1.5rem;
    }

    .order-items {
        margin-bottom: 1.5rem;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .item-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .item-image {
        width: 50px;
        height: 50px;
        border-radius: 4px;
        object-fit: cover;
    }

    .no-image {
        width: 50px;
        height: 50px;
        background: #ddd;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 0.8rem;
    }

    .item-name {
        font-weight: 500;
    }

    .item-quantity {
        color: #666;
        font-size: 0.9rem;
    }

    .item-price {
        font-weight: 500;
        color: #D90A16;
    }

    .order-address {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .order-total {
        text-align: right;
        font-size: 1.2rem;
        font-weight: bold;
        color: #D90A16;
        padding-top: 1rem;
        border-top: 2px solid #e1e5e9;
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

    .ready-badge {
        background: #D90A16;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        margin-left: 10px;
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
    
    .order-header {
        padding: 1.25rem;
    }
    
    .order-info {
        gap: 1rem;
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
    
    .new-order-btn {
        position: static;
        transform: none;
        margin-top: 1rem;
        width: 100%;
        justify-content: center;
    }
    
    .order-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
        padding: 1rem;
    }
    
    .order-info {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .order-details {
        padding: 1rem;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.8rem;
        padding: 0.8rem 0;
    }
    
    .item-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        width: 100%;
    }
    
    .item-price {
        align-self: flex-end;
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
    
    .order-number {
        font-size: 1.1rem;
    }
    
    .order-total {
        font-size: 1.1rem;
        text-align: center;
    }
    
    .empty-state i {
        font-size: 3rem;
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
                <a href="profile.php"><i class="fas fa-user"></i> Профиль</a>
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
                <h1><i class="fas fa-shopping-cart"></i> Мои заказы</h1>
                <p>История всех ваших заказов</p>
            </div>
            <a href="delivery.php" class="btn btn-success new-order-btn">
                <i class="fas fa-plus"></i> Новый заказ
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>У вас пока нет заказов</h3>
                <p>Сделайте свой первый заказ и он появится здесь</p>
                <a href="delivery.php" class="btn btn-primary">
                    <i class="fas fa-utensils"></i> Сделать первый заказ
                </a>
                
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
                        <i class="fas fa-calendar-check"></i>
                        <h3>Бронирование</h3>
                        <p>Забронируйте столик в нашем ресторане</p>
                        <a href="reservations.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Забронировать
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
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-number">
                                    Заказ #<?php echo $order['id']; ?>
                                    <?php if ($order['status'] === 'completed'): ?>
                                        <span class="ready-badge">Готов!</span>
                                    <?php endif; ?>
                                </div>
                                <div class="order-date">
                                    <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
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
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <?php if (!empty($order['customer_address'])): ?>
                                <div class="order-address">
                                    <strong><i class="fas fa-map-marker-alt"></i> Адрес доставки:</strong> 
                                    <?php echo htmlspecialchars($order['customer_address']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'completed'): ?>
                                <div class="order-address" style="background: #d4edda; border-color: #c3e6cb;">
                                    <strong><i class="fas fa-check-circle"></i> Заказ завершен:</strong> 
                                    <?php echo date('d.m.Y в H:i', strtotime($order['created_at'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <div class="item-info">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="../<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                                            <?php else: ?>
                                                <div class="no-image">Нет фото</div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="item-quantity">× <?php echo $item['quantity']; ?></div>
                                            </div>
                                        </div>
                                        <div class="item-price"><?php echo $item['price'] * $item['quantity']; ?> ₽</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-total">
                                Итого: <?php echo $order['total_amount']; ?> ₽
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="delivery.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Сделать новый заказ
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>