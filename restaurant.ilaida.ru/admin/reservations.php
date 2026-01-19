<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Обновление статуса бронирования
if (isset($_POST['update_status'])) {
    $reservation_id = $_POST['reservation_id'];
    $status = $_POST['status'];
    
    $query = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$status, $reservation_id]);
    
    header('Location: reservations.php');
    exit;
}

// Параметры сортировки
$sort_column = $_GET['sort'] ?? 'reservation_date';
$sort_order = $_GET['order'] ?? 'desc';

// Допустимые колонки для сортировки
$allowed_columns = ['id', 'customer_name', 'customer_phone', 'reservation_date', 'reservation_time', 'guests', 'status'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'reservation_date';
}

// Допустимые направления сортировки
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_order, $allowed_orders)) {
    $sort_order = 'desc';
}

// Получение бронирований с сортировкой
$reservations = $db->query("SELECT * FROM reservations ORDER BY $sort_column $sort_order, reservation_time DESC")->fetchAll(PDO::FETCH_ASSOC);

// Функция для генерации URL сортировки
function getSortUrl($column, $current_sort, $current_order) {
    $order = 'asc';
    if ($current_sort == $column && $current_order == 'asc') {
        $order = 'desc';
    }
    return "reservations.php?sort=$column&order=$order";
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
    <title>Управление бронированиями - Админ-панель</title>
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
    
    .reservations-container { 
        background: white; 
        padding: 2rem; 
        border-radius: 10px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
    }
    
    .reservations-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 1rem; 
    }
    
    .reservations-table th, .reservations-table td { 
        padding: 1rem; 
        text-align: left; 
        border-bottom: 1px solid #ddd; 
        vertical-align: top;
    }
    
    .reservations-table th { 
        background: #f8f9fa; 
        font-weight: 600;
        color: #D90A16;
        cursor: pointer;
        user-select: none;
        position: relative;
    }
    
    .reservations-table th:hover {
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
    
    .reservation-details {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        margin-top: 0.5rem;
        display: none;
        border-left: 3px solid #D90A16;
    }
    
    .reservation-details.show {
        display: block;
    }
    
    .upcoming {
        background: #e8f5e8 !important;
        border-left: 4px solid #28a745;
    }
    
    .today {
        background: #fff3cd !important;
        border-left: 4px solid #ffc107;
    }
    
    .past {
        background: #f8f9fa !important;
        opacity: 0.8;
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
    
    .datetime-cell {
        white-space: nowrap;
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
    
    .reservations-container {
        padding: 1rem;
        overflow-x: auto;
    }
    
    .reservations-table {
        min-width: 800px;
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
    
    .sort-info {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .admin-menu a {
        padding: 8px 10px;
        min-width: 100px;
        font-size: 0.9rem;
    }
    
    .reservations-table {
        min-width: 700px;
    }
    
    .stat-card {
        padding: 0.8rem;
    }
    
    .stat-number {
        font-size: 1.3rem;
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
                <li><a href="orders.php">
                    <i class="fas fa-shopping-cart"></i> Заказы
                </a></li>
                <li><a href="reservations.php" class="active">
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
                <h1><i class="fas fa-calendar-check"></i> Управление бронированиями</h1>
            </div>
            
            <!-- Статистика -->
            <div class="stats-cards">
                <?php
                $total_reservations = count($reservations);
                $pending_count = 0;
                $confirmed_count = 0;
                $completed_count = 0;
                $cancelled_count = 0;
                $today_count = 0;
                $upcoming_count = 0;
                
                $today = date('Y-m-d');
                
                foreach ($reservations as $reservation) {
                    switch ($reservation['status']) {
                        case 'pending': $pending_count++; break;
                        case 'confirmed': $confirmed_count++; break;
                        case 'completed': $completed_count++; break;
                        case 'cancelled': $cancelled_count++; break;
                    }
                    
                    if ($reservation['reservation_date'] == $today) {
                        $today_count++;
                    }
                    
                    if ($reservation['reservation_date'] > $today && $reservation['status'] == 'confirmed') {
                        $upcoming_count++;
                    }
                }
                ?>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_reservations; ?></div>
                    <div class="stat-label">Всего бронирований</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Ожидают подтверждения</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $today_count; ?></div>
                    <div class="stat-label">На сегодня</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $upcoming_count; ?></div>
                    <div class="stat-label">Предстоящие</div>
                </div>
            </div>
            
            <!-- Список бронирований -->
            <div class="reservations-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0;">Список бронирований</h3>
                    <?php if ($sort_column != 'reservation_date' || $sort_order != 'desc'): ?>
                    <div class="sort-info">
                        Сортировка: 
                        <?php 
                        $column_names = [
                            'id' => 'ID',
                            'customer_name' => 'Клиент',
                            'customer_phone' => 'Телефон',
                            'reservation_date' => 'Дата брони',
                            'reservation_time' => 'Время',
                            'guests' => 'Гостей',
                            'status' => 'Статус'
                        ];
                        echo $column_names[$sort_column] . ' (' . ($sort_order == 'asc' ? 'по возрастанию' : 'по убыванию') . ')';
                        ?>
                        <a href="reservations.php" class="reset-sort">Сбросить</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <table class="reservations-table">
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
                            <th onclick="window.location.href='<?php echo getSortUrl('guests', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Гостей <?php echo getSortIcon('guests', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('reservation_date', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Дата <?php echo getSortIcon('reservation_date', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('reservation_time', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Время <?php echo getSortIcon('reservation_time', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('status', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Статус <?php echo getSortIcon('status', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): 
                            $reservation_date = $reservation['reservation_date'];
                            $today = date('Y-m-d');
                            $row_class = '';
                            
                            if ($reservation_date == $today) {
                                $row_class = 'today';
                            } elseif ($reservation_date > $today && $reservation['status'] == 'confirmed') {
                                $row_class = 'upcoming';
                            } elseif ($reservation_date < $today) {
                                $row_class = 'past';
                            }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td>
                                <strong>#<?php echo $reservation['id']; ?></strong>
                                <?php if ($reservation['status'] === 'confirmed' && $reservation_date >= $today): ?>
                                    <span class="notification-badge" title="Активное бронирование">!</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($reservation['customer_name'] ?? 'Не указано'); ?></td>
                            <td><?php echo htmlspecialchars($reservation['customer_phone'] ?? 'Не указан'); ?></td>
                            <td><?php echo $reservation['guests']; ?> чел.</td>
                            <td class="datetime-cell">
                                <?php echo date('d.m.Y', strtotime($reservation['reservation_date'])); ?>
                                <?php if ($reservation_date == $today): ?>
                                    <br><small style="color: #D90A16;">(Сегодня)</small>
                                <?php endif; ?>
                            </td>
                            <td class="datetime-cell"><?php echo $reservation['reservation_time']; ?></td>
                            <td>
                                <span class="status-<?php echo $reservation['status']; ?>">
                                    <?php 
                                    $status_names = [
                                        'pending' => 'Ожидание',
                                        'confirmed' => 'Подтверждено',
                                        'completed' => 'Завершено',
                                        'cancelled' => 'Отменено'
                                    ];
                                    echo $status_names[$reservation['status']];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="form-group">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <select name="status">
                                        <option value="pending" <?php echo $reservation['status'] == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                        <option value="confirmed" <?php echo $reservation['status'] == 'confirmed' ? 'selected' : ''; ?>>Подтверждено</option>
                                        <option value="completed" <?php echo $reservation['status'] == 'completed' ? 'selected' : ''; ?>>Завершено</option>
                                        <option value="cancelled" <?php echo $reservation['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменено</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn">Обновить</button>
                                </form>
                            </td>
                        </tr>
                        
                        <!-- Детали бронирования -->
                        <?php if (!empty($reservation['special_requests'])): ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td colspan="8">
                                <div class="reservation-details show">
                                    <h4>Особые пожелания:</h4>
                                    <p><?php echo htmlspecialchars($reservation['special_requests']); ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($reservations)): ?>
                <p style="text-align: center; padding: 2rem; color: #6c757d;">
                    <i class="fas fa-calendar-check" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                    Бронирований пока нет
                </p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Добавляем обработчики для клавиатуры на заголовки сортировки
            const sortableHeaders = document.querySelectorAll('.reservations-table th[onclick]');
            
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