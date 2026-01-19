<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Функция отправки email уведомления
function sendOrderReadyEmail($customer_email, $customer_name, $order_id) {
    $subject = "Ваш заказ готов! - Ресторан TATMAK";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { background: #9b59b6; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .order-number { color: #9b59b6; font-size: 18px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>TATMAK</h1>
        </div>
        <div class='content'>
            <h2>Уважаемый(ая) {$customer_name}!</h2>
            <p>Ваш заказ <span class='order-number'>#{$order_id}</span> готов и ожидает вас.</p>
            <p>Вы можете забрать его в ресторане или ожидайте доставку в ближайшее время.</p>
            <p>Спасибо, что выбрали нас!</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: restaurant@vkusno-tochka.ru" . "\r\n";
    
    return mail($customer_email, $subject, $message, $headers);
}

// Обновление статуса заказа
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Получаем текущий статус заказа
    $current_order_query = "SELECT * FROM orders WHERE id = ?";
    $current_order_stmt = $db->prepare($current_order_query);
    $current_order_stmt->execute([$order_id]);
    $current_order = $current_order_stmt->fetch(PDO::FETCH_ASSOC);
    
    $query = "UPDATE orders SET status = ?";
    $params = [$status];
    
    // Если заказ переведен в статус "готов", отправляем уведомление
    if ($status === 'completed' && $current_order['status'] !== 'completed') {
        // Находим пользователя по телефону для отправки уведомления
        $user_query = "SELECT * FROM users WHERE phone = ?";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([$current_order['customer_phone']]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['email'])) {
            // Отправляем email уведомление
            sendOrderReadyEmail($user['email'], $current_order['customer_name'], $order_id);
            
            // Сохраняем уведомление в сессии пользователя
            if (!isset($_SESSION['user_notifications'])) {
                $_SESSION['user_notifications'] = [];
            }
            
            $_SESSION['user_notifications'][] = [
                'type' => 'order_ready',
                'order_id' => $order_id,
                'message' => "Ваш заказ #{$order_id} готов!",
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    $query .= " WHERE id = ?";
    $params[] = $order_id;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    header('Location: orders.php');
    exit;
}

// Параметры сортировки
$sort_column = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'desc';

// Допустимые колонки для сортировки
$allowed_columns = ['id', 'customer_name', 'customer_phone', 'total_amount', 'status', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'created_at';
}

// Допустимые направления сортировки
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_order, $allowed_orders)) {
    $sort_order = 'desc';
}

// Получение заказов с информацией о товарах и сортировкой
$orders = $db->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count,
           (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as items_total
    FROM orders o 
    ORDER BY $sort_column $sort_order
")->fetchAll(PDO::FETCH_ASSOC);

// Для каждого заказа получаем детали товаров
foreach ($orders as &$order) {
    $items_query = "SELECT oi.*, d.name, d.image 
                   FROM order_items oi 
                   LEFT JOIN dishes d ON oi.dish_id = d.id 
                   WHERE oi.order_id = ?";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([$order['id']]);
    $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($order);

// Функция для генерации URL сортировки
function getSortUrl($column, $current_sort, $current_order) {
    $order = 'asc';
    if ($current_sort == $column && $current_order == 'asc') {
        $order = 'desc';
    }
    return "orders.php?sort=$column&order=$order";
}

// Функция для отображения значка сортировки
function getSortIcon($column, $current_sort, $current_order) {
    if ($current_sort != $column) {
        return '↕️';
    }
    return $current_order == 'asc' ? '↑' : '↓';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами - Админ-панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
    }
    
    body { 
        font-family: Arial, sans-serif; 
        background: #F4F3F5; 
    }
    
    .admin-container {
        display: flex;
        min-height: 100vh;
    }
    
    .admin-sidebar {
        width: 250px;
        background: #2c3e50;
        color: white;
        position: fixed;
        height: 100vh;
    }
    
    .admin-brand {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid #34495e;
        background: #D90A16;
    }
    
    .admin-menu {
        list-style: none;
        padding: 0;
    }
    
    .admin-menu a {
        color: white;
        text-decoration: none;
        padding: 15px 20px;
        display: block;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #34495e;
        transition: background 0.3s;
    }
    
    .admin-menu a:hover, .admin-menu a.active {
        background: #D90A16;
    }
    
    .admin-content {
        flex: 1;
        margin-left: 250px;
        padding: 20px;
    }
    
    .admin-header {
        background: white;
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        border-radius: 8px;
        border-left: 4px solid #D90A16;
    }
    
    .orders-container { 
        background: white; 
        padding: 2rem; 
        border-radius: 10px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
    }
    
    .orders-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 1rem; 
    }
    
    .orders-table th, .orders-table td { 
        padding: 1rem; 
        text-align: left; 
        border-bottom: 1px solid #ddd; 
        vertical-align: top;
    }
    
    .orders-table th { 
        background: #f8f9fa; 
        font-weight: 600;
        color: #D90A16;
        cursor: pointer;
        user-select: none;
        position: relative;
    }
    
    .orders-table th:hover {
        background: #e9ecef;
    }
    
    .sortable-header {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .sort-icon {
        font-size: 12px;
        color: #D90A16;
    }
    
    .status-pending { 
        background: #fff3cd; 
        color: #856404; 
        padding: 5px 10px; 
        border-radius: 3px; 
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-confirmed { 
        background: #d1ecf1; 
        color: #0c5460; 
        padding: 5px 10px; 
        border-radius: 3px; 
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-preparing { 
        background: #ffeaa7; 
        color: #856404; 
        padding: 5px 10px; 
        border-radius: 3px; 
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-delivering { 
        background: #b8e6bf; 
        color: #155724; 
        padding: 5px 10px; 
        border-radius: 3px; 
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-completed { 
        background: #d4edda; 
        color: #155724; 
        padding: 5px 10px; 
        border-radius: 3px; 
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-cancelled { 
        background: #f8d7da; 
        color: #721c24; 
        padding: 5px 10px; 
        border-radius: 3px; 
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .btn { 
        background: #D90A16; 
        color: white; 
        padding: 8px 16px; 
        text-decoration: none; 
        border-radius: 5px; 
        display: inline-block; 
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background 0.3s;
    }
    
    .btn:hover {
        background: #b30812;
    }
    
    .logout-btn {
        background: #95a5a6;
    }
    
    .logout-btn:hover {
        background: #7f8c8d;
    }
    
    .form-group { 
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    select {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        text-align: center;
        border-top: 3px solid #D90A16;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #D90A16;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #7f8c8d;
        font-size: 0.9rem;
    }
    
    .order-details {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        margin-top: 0.5rem;
        display: none;
        border-left: 3px solid #D90A16;
    }
    
    .order-details.show {
        display: block;
    }
    
    .order-items {
        margin-bottom: 1rem;
    }
    
    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e1e5e9;
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
        width: 40px;
        height: 40px;
        border-radius: 4px;
        object-fit: cover;
    }
    
    .no-image {
        width: 40px;
        height: 40px;
        background: #ddd;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 0.8rem;
    }
    
    .toggle-details {
        background: none;
        border: none;
        color: #D90A16;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 5px;
    }
    
    .address-cell {
        max-width: 200px;
        word-wrap: break-word;
    }
    
    .revenue-highlight {
        color: #D90A16;
        font-weight: bold;
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
        margin-left: 5px;
    }
    
    .ready-time {
        color: #D90A16;
        font-size: 0.8rem;
        margin-top: 5px;
    }
    
    .sort-info {
        background: #ffe6e8;
        border: 1px solid #D90A16;
        color: #D90A16;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    
    .reset-sort {
        color: #D90A16;
        text-decoration: none;
        margin-left: 10px;
        font-size: 0.8rem;
        font-weight: bold;
    }
    
    .reset-sort:hover {
        text-decoration: underline;
    }

    /* Адаптация для мобильных устройств и планшетов */
@media (max-width: 1200px) {
    .admin-container {
        flex-direction: column;
    }
    
    .admin-sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }
    
    .admin-content {
        margin-left: 0;
        padding: 15px;
    }
    
    .stats-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .order-details > div {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .admin-menu {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .admin-menu a {
        padding: 10px 15px;
        flex: 1;
        min-width: 120px;
        text-align: center;
        justify-content: center;
    }
    
    .admin-content {
        padding: 10px;
    }
    
    .admin-header {
        padding: 15px;
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .stats-cards {
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }
    
    .orders-container {
        padding: 1rem;
        overflow-x: auto;
    }
    
    .orders-table {
        min-width: 900px;
        font-size: 0.9rem;
    }
    
    .form-group {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 0.8rem;
    }
    
    select {
        font-size: 0.8rem;
        padding: 4px 8px;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .toggle-details {
        min-height: 44px;
        min-width: 44px;
    }
}

@media (max-width: 480px) {
    .admin-menu a {
        padding: 8px 10px;
        min-width: 100px;
        font-size: 0.9rem;
    }
    
    .orders-table {
        min-width: 800px;
    }
    
    .stat-card {
        padding: 0.8rem;
    }
    
    .address-cell {
        max-width: 120px;
    }
}
</style>
</head>
<body>
    <div class="admin-container">
        <!-- Боковое меню -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <h2>Ресторан</h2>
                <p>TATMAK</p>
            </div>
            
            <ul class="admin-menu">
                <li><a href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Главная
                </a></li>
                <li><a href="dishes.php">
                    <i class="fas fa-utensils"></i> Блюда
                </a></li>
                <li><a href="categories.php">
                    <i class="fas fa-list"></i> Категории
                </a></li>
                <li><a href="orders.php" class="active">
                    <i class="fas fa-shopping-cart"></i> Заказы
                </a></li>
                <li><a href="reservations.php">
                    <i class="fas fa-calendar-check"></i> Бронирования
                </a></li>
                <li><a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a></li>
            </ul>
        </aside>

        <!-- Основное содержимое -->
        <main class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-shopping-cart"></i> Управление заказами</h1>
            </div>
            
            <!-- Статистика -->
            <div class="stats-cards">
                <?php
                $total_orders = count($orders);
                $pending_count = 0;
                $confirmed_count = 0;
                $preparing_count = 0;
                $delivering_count = 0;
                $completed_count = 0;
                $cancelled_count = 0;
                $total_revenue = 0;
                
                foreach ($orders as $order) {
                    switch ($order['status']) {
                        case 'pending': $pending_count++; break;
                        case 'confirmed': $confirmed_count++; break;
                        case 'preparing': $preparing_count++; break;
                        case 'delivering': $delivering_count++; break;
                        case 'completed': 
                            $completed_count++; 
                            $total_revenue += $order['total_amount'];
                            break;
                        case 'cancelled': $cancelled_count++; break;
                    }
                }
                ?>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Всего заказов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_count + $confirmed_count + $preparing_count + $delivering_count; ?></div>
                    <div class="stat-label">Активные заказы</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $completed_count; ?></div>
                    <div class="stat-label">Завершенные заказы</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number revenue-highlight"><?php echo number_format($total_revenue, 0, '', ' '); ?> ₽</div>
                    <div class="stat-label">Общая выручка</div>
                </div>
            </div>
            
            <!-- Список заказов -->
            <div class="orders-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0;">Список заказов</h3>
                    <?php if ($sort_column != 'created_at' || $sort_order != 'desc'): ?>
                    <div class="sort-info">
                        Сортировка: 
                        <?php 
                        $column_names = [
                            'id' => 'ID',
                            'customer_name' => 'Клиент',
                            'customer_phone' => 'Телефон',
                            'total_amount' => 'Сумма',
                            'status' => 'Статус',
                            'created_at' => 'Дата заказа'
                        ];
                        echo $column_names[$sort_column] . ' (' . ($sort_order == 'asc' ? 'по возрастанию' : 'по убыванию') . ')';
                        ?>
                        <a href="orders.php" class="reset-sort">Сбросить</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th onclick="window.location.href='<?php echo getSortUrl('id', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    ID <?php echo getSortIcon('id', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('customer_name', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Клиент <?php echo getSortIcon('customer_name', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('customer_phone', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Телефон <?php echo getSortIcon('customer_phone', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th>Адрес</th>
                            <th onclick="window.location.href='<?php echo getSortUrl('total_amount', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Сумма <?php echo getSortIcon('total_amount', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th>Товаров</th>
                            <th onclick="window.location.href='<?php echo getSortUrl('status', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Статус <?php echo getSortIcon('status', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('created_at', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Дата <?php echo getSortIcon('created_at', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $order['id']; ?></strong>
                                <?php if ($order['status'] === 'completed'): ?>
                                    <span class="notification-badge" title="Заказ завершен">✓</span>
                                <?php endif; ?>
                                <button class="toggle-details" data-order="<?php echo $order['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Не указано'); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_phone'] ?? 'Не указан'); ?></td>
                            <td class="address-cell"><?php echo htmlspecialchars($order['customer_address'] ?? 'Самовывоз'); ?></td>
                            <td><strong><?php echo $order['total_amount'] ?? '0'; ?> ₽</strong></td>
                            <td><?php echo $order['items_count']; ?></td>
                            <td>
                                <span class="status-<?php echo $order['status']; ?>">
                                    <?php 
                                    $status_names = [
                                        'pending' => 'Ожидание',
                                        'confirmed' => 'Подтвержден',
                                        'preparing' => 'Готовится',
                                        'delivering' => 'Доставляется',
                                        'completed' => 'Завершен',
                                        'cancelled' => 'Отменен'
                                    ];
                                    echo $status_names[$order['status']];
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <form method="POST" class="form-group">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                        <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Подтвержден</option>
                                        <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Готовится</option>
                                        <option value="delivering" <?php echo $order['status'] == 'delivering' ? 'selected' : ''; ?>>Доставляется</option>
                                        <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn">Обновить</button>
                                </form>
                            </td>
                        </tr>
                        
                        <!-- Детали заказа -->
                        <tr>
                            <td colspan="9">
                                <div class="order-details" id="details-<?php echo $order['id']; ?>">
                                    <div class="order-items">
                                        <h4>Состав заказа:</h4>
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
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div>
                                            <h4>Информация о доставке:</h4>
                                            <p><strong>Адрес:</strong> <?php echo htmlspecialchars($order['customer_address'] ?? 'Самовывоз'); ?></p>
                                            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? 'Не указан'); ?></p>
                                        </div>
                                        <div>
                                            <h4>Стоимость:</h4>
                                            <p><strong>Товары:</strong> <?php echo $order['items_total'] ?? '0'; ?> ₽</p>
                                            <p><strong>Доставка:</strong> 
                                                <?php 
                                                $delivery_cost = $order['total_amount'] - $order['items_total'];
                                                echo $delivery_cost > 0 ? $delivery_cost . ' ₽' : 'Бесплатно';
                                                ?>
                                            </p>
                                            <p><strong>Итого:</strong> <?php echo $order['total_amount']; ?> ₽</p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($orders)): ?>
                <p style="text-align: center; padding: 2rem; color: #6c757d;">
                    <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                    Заказов пока нет
                </p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-details');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.dataset.order;
                    const details = document.getElementById(`details-${orderId}`);
                    const icon = this.querySelector('i');
                    
                    details.classList.toggle('show');
                    
                    if (details.classList.contains('show')) {
                        icon.className = 'fas fa-eye-slash';
                    } else {
                        icon.className = 'fas fa-eye';
                    }
                });
            });

            // Добавляем обработчики для клавиатуры на заголовки сортировки
            const sortableHeaders = document.querySelectorAll('.orders-table th[onclick]');
            
            sortableHeaders.forEach(header => {
                header.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const onClick = this.getAttribute('onclick');
                        if (onClick) {
                            const url = onClick.match(/window\.location\.href='([^']+)'/)[1];
                            window.location.href = url;
                        }
                    }
                });
                
                // Делаем заголовки доступными для таб-навигации
                header.setAttribute('tabindex', '0');
                header.setAttribute('role', 'button');
                header.setAttribute('aria-label', 'Сортировка по колонке');
            });
        });
    </script>
</body>
</html>