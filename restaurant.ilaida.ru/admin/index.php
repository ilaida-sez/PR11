<?php
session_start();
require_once '../config/db.php'; // Обратите внимание на ../

$database = new Database();
$db = $database->getConnection();

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Получаем статистику
$dishes_count = $db->query("SELECT COUNT(*) FROM dishes")->fetchColumn();
$orders_count = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$reservations_count = $db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель - Ресторан</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #F4F3F5; }
    
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
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border-left: 4px solid #D90A16;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
        border-top: 4px solid #D90A16;
        transition: transform 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        color: #D90A16;
        margin: 10px 0;
    }
    
    .btn {
        background: #D90A16;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        display: inline-block;
        margin: 5px;
        transition: background 0.3s;
    }
    
    .btn:hover {
        background: #b30812;
    }
    
    .logout-btn {
        background: #95a5a6;
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
    
    .stats-grid {
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
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .btn {
        padding: 12px 15px;
        margin: 3px;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .admin-menu a {
        padding: 8px 10px;
        min-width: 100px;
        font-size: 0.9rem;
    }
    
    .admin-header h1 {
        font-size: 1.5rem;
    }
    
    .btn {
        width: 100%;
        margin: 5px 0;
        text-align: center;
    }
    
    .stat-number {
        font-size: 1.8rem;
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
                <li><a href="index.php" class="active">
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
                <li><a href="reservations.php">
                    <i class="fas fa-calendar-check"></i> Бронирования
                </a></li>
            </ul>
        </aside>

        <!-- Основное содержимое -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Панель управления</h1>
                <div>
                    <a href="logout.php" class="btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </div>
            </div>

            <h2>Статистика</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div>Блюд в меню</div>
                    <div class="stat-number"><?php echo $dishes_count; ?></div>
                </div>
                <div class="stat-card">
                    <div>Заказов</div>
                    <div class="stat-number"><?php echo $orders_count; ?></div>
                </div>
                <div class="stat-card">
                    <div>Бронирований</div>
                    <div class="stat-number"><?php echo $reservations_count; ?></div>
                </div>
            </div>

            <h2>Быстрые действия</h2>
            <div>
                <a href="dishes.php?action=add" class="btn">Добавить блюдо</a>
                <a href="categories.php?action=add" class="btn">Добавить категорию</a>
                <a href="orders.php" class="btn">Заказы</a>
                <a href="reservations.php" class="btn">Бронирования</a>
            </div>
        </main>
    </div>
</body>
</html>