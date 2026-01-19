<?php
session_start();
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// Получаем все категории (используем id вместо sort_order)
$categories_query = "SELECT * FROM categories ORDER BY id ASC";
$categories = $db->query($categories_query)->fetchAll(PDO::FETCH_ASSOC);

// Получаем параметры фильтрации
$category_id = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'name';

// Базовый запрос для блюд
$dishes_query = "
    SELECT d.*, c.name as category_name 
    FROM dishes d 
    LEFT JOIN categories c ON d.category_id = c.id 
    WHERE 1=1
";

$params = [];

// Фильтрация по категории
if (!empty($category_id) && is_numeric($category_id)) {
    $dishes_query .= " AND d.category_id = ?";
    $params[] = $category_id;
}

// Поиск
if (!empty($search_query)) {
    $dishes_query .= " AND (d.name LIKE ? OR d.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
}

// Сортировка
$sort_options = [
    'name' => 'd.name ASC',
    'name_desc' => 'd.name DESC',
    'price' => 'd.price ASC',
    'price_desc' => 'd.price DESC',
    'newest' => 'd.created_at DESC'
];

$dishes_query .= " ORDER BY " . ($sort_options[$sort_by] ?? 'd.name ASC');

// Выполняем запрос
$dishes_stmt = $db->prepare($dishes_query);
$dishes_stmt->execute($params);
$dishes = $dishes_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Меню - Ресторан TATMAK";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        line-height: 1.6;
        color: #333;
        background: #F4F3F5;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Шапка */
    .header {
        background: #F4F3F5;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }

    .nav-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 2rem;
    }

    .logo a {
        text-decoration: none;
        color: #D90A16;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .nav-menu {
        display: flex;
        list-style: none;
        gap: 2rem;
        align-items: center;
    }

    .nav-menu a {
        text-decoration: none;
        color: #333;
        transition: color 0.3s;
        font-weight: 500;
    }

    .nav-menu a:hover,
    .nav-menu a.active {
        color: #D90A16;
    }

    /* Кнопки входа */
    .auth-buttons {
        display: flex;
        gap: 1rem;
        align-items: center;
        color: white;
    }

    .auth-btn {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
    /* УБЕРИ ЭТУ СТРОКУ: color: white; */
}

    .admin-btn {
        background: #2c3e50;
        color: white;
    }

    .admin-btn:hover {
        background: #1a252f;
        transform: translateY(-2px);
    }

    .admin-btn.admin-logged {
    background: #D90A16;
    color: white !important;
}


    .user-btn {
        background: #2c3e50;
        color: white;
    }

    .user-btn:hover {
        background: #1a252f;
        transform: translateY(-2px);
    }

    .user-btn.user-logged {
        background: #D90A16;
        color: white;
    }

    .user-btn.user-logged:hover {
        background: #D90A16;
        color: black;
    }

    /* Основной контент */
    .main-content {
        margin-top: 80px;
        padding: 2rem 0;
    }

    .page-header {
        text-align: center;
        margin-bottom: 3rem;
        padding: 2rem 0;
    }

    .page-header h1 {
        font-size: 3rem;
        color: #2c3e50;
        margin-bottom: 1rem;
    }

    .page-header p {
        font-size: 1.2rem;
        color: #6c757d;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Фильтры и поиск */
    .filters-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-label {
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }

    .form-control {
        padding: 12px;
        border: 2px solid #e1e5e9;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s;
        width: 100%;
    }

    .form-control:focus {
        outline: none;
        border-color: #D90A16;
    }

    /* Кнопки входа */
.auth-buttons {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.auth-btn {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
    color: white !important;
}

.admin-btn {
    background: #2c3e50;
}

.admin-btn:hover {
    background: #1a252f;
    transform: translateY(-2px);
}

/* ФИКС ЦВЕТА ТЕКСТА КНОПОК */
.auth-btn,
.admin-btn,
.user-btn,
.admin-btn.admin-logged,
.user-btn.user-logged,
.admin-btn:hover,
.user-btn:hover,
.admin-btn.admin-logged:hover,
.user-btn.user-logged:hover {
    color: white !important;
    background: #2c3e50 !important;
}

.admin-btn.admin-logged,
.user-btn.user-logged {
    background: #D90A16 !important;
}

.admin-btn:hover,
.user-btn:hover {
    background: #1a252f !important;
}

.admin-btn.admin-logged:hover,
.user-btn.user-logged:hover {
    background: #b30812 !important;
}

    /* Сетка блюд */
    .dishes-section {
        margin-bottom: 3rem;
    }

    .category-section {
        margin-bottom: 4rem;
    }

    .category-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid #D90A16;
    }

    .category-header h2 {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .category-header p {
        color: #6c757d;
        font-size: 1.1rem;
    }

    .dishes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
    }

    .dish-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
    }

    .dish-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .dish-image {
        height: 250px;
        overflow: hidden;
        position: relative;
    }

    .dish-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .dish-card:hover .dish-image img {
        transform: scale(1.1);
    }

    .dish-category {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: rgba(217, 10, 22, 0.9);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .dish-info {
        padding: 1.5rem;
    }

    .dish-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        gap: 1rem;
    }

    .dish-title {
        flex: 1;
    }

    .dish-title h3 {
        font-size: 1.3rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }

    .dish-price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #D90A16;
        white-space: nowrap;
    }

    .dish-description {
        color: #6c757d;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .dish-weight {
        color: #95a5a6;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .dish-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Состояние пустого результата */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
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
        font-size: 1.5rem;
    }

    .empty-state p {
        color: #888;
        margin-bottom: 2rem;
    }

    /* Подвал сайта */
    .footer {
        background: #2c3e50;
        color: white;
        padding: 3rem 0 1rem;
        margin-top: 4rem;
    }

    .footer-content {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 3rem;
        margin-bottom: 2rem;
    }

    .footer-section h3 {
        color: #D90A16;
        margin-bottom: 1rem;
        font-size: 1.5rem;
    }

    .footer-section h4 {
        color: #ecf0f1;
        margin-bottom: 1rem;
        font-size: 1.2rem;
    }

    .footer-section p {
        color: #bdc3c7;
        line-height: 1.6;
    }

    .footer-section ul {
        list-style: none;
    }

    .footer-section ul li {
        margin-bottom: 0.5rem;
    }

    .footer-section ul li a {
        color: #bdc3c7;
        text-decoration: none;
        transition: color 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .footer-section ul li a:hover {
        color: #D90A16;
    }

    .social-links {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
    }

    .social-links a:hover {
        background: #D90A16;
        transform: translateY(-3px);
    }

    .footer-bottom {
        border-top: 1px solid #34495e;
        padding-top: 1.5rem;
        text-align: center;
        color: #95a5a6;
    }

    /* Адаптивность */
@media (max-width: 1024px) {
    /* Планшет */
    .filters-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .dishes-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    
    .footer-content {
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .category-header h2 {
        font-size: 2rem;
    }
}

@media (max-width: 768px) {
    /* Мобильные устройства */
    .nav-menu {
        display: none;
    }
    
    .mobile-menu-btn {
        display: block;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .dishes-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .page-header {
        padding: 1rem 0;
        margin-bottom: 2rem;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .page-header p {
        font-size: 1rem;
        padding: 0 1rem;
    }
    
    .filters-section {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .category-header {
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
    }
    
    .category-header h2 {
        font-size: 1.5rem;
    }
    
    .dish-card {
        margin-bottom: 1rem;
    }
    
    .dish-info {
        padding: 1rem;
    }
    
    .dish-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .dish-price {
        align-self: flex-start;
    }
    
    .footer-content {
        grid-template-columns: 1fr;
        gap: 2rem;
        text-align: center;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .footer-section ul li a {
        justify-content: center;
    }
    
    .auth-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .auth-btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    /* Маленькие мобильные */
    .container {
        padding: 0 15px;
    }
    
    .nav-container {
        padding: 0.75rem 1rem;
    }
    
    .main-content {
        margin-top: 70px;
        padding: 1rem 0;
    }
    
    .page-header h1 {
        font-size: 1.8rem;
    }
    
    .filters-section {
        padding: 1rem;
    }
    
    .form-control {
        padding: 10px;
        font-size: 16px;
    }
    
    .dish-image {
        height: 200px;
    }
    
    .dish-title h3 {
        font-size: 1.1rem;
    }
    
    .dish-price {
        font-size: 1.3rem;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-state i {
        font-size: 3rem;
    }
    
    .empty-state h3 {
        font-size: 1.3rem;
    }
}
</style>
</head>
<body>
    <!-- Шапка сайта -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <h2><a href="index.php">TATMAK</a></h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="menu.php" class="active">Меню</a></li>
                    <li><a href="user/login.php">Бронирование</a></li>
                    <li><a href="user/login.php">Доставка</a></li>
                    <li><a href="#contacts">Контакты</a></li>
                    <!-- Кнопки входа -->
                    <li class="auth-buttons">
                        <?php if (isset($_SESSION['user_logged_in'])): ?>
                            <a href="user/profile.php" class="auth-btn user-btn user-logged">
                                <i class="fas fa-user"></i> Личный кабинет
                            </a>
                        <?php else: ?>
                            <a href="user/login.php" class="auth-btn user-btn">
                                <i class="fas fa-sign-in-alt"></i> Войти
                            </a>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['admin_logged_in'])): ?>
                            <a href="admin/index.php" class="auth-btn admin-btn admin-logged">
                                <i class="fas fa-cog"></i> Админ панель
                            </a>
                        <?php else: ?>
                            <a href="admin/login.php" class="auth-btn admin-btn">
                                <i class="fas fa-user-shield"></i> Для персонала
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="container">
            <!-- Заголовок страницы -->
            <div class="page-header">
                <h1><i class="fas fa-utensils"></i> Наше меню</h1>
                <p>Откройте для себя богатый выбор блюд от наших шеф-поваров. Только свежие ингредиенты и авторские рецепты.</p>
            </div>

            <!-- Фильтры и поиск -->
            <section class="filters-section">
                <form method="GET" action="menu.php" id="filters-form">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label" for="search">
                                <i class="fas fa-search"></i> Поиск блюд
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   placeholder="Название блюда или ингредиенты..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label" for="category">
                                <i class="fas fa-filter"></i> Категория
                            </label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Все категории</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label" for="sort">
                                <i class="fas fa-sort"></i> Сортировка
                            </label>
                            <select class="form-control" id="sort" name="sort">
                                <option value="name" <?php echo ($sort_by == 'name') ? 'selected' : ''; ?>>По названию (А-Я)</option>
                                <option value="name_desc" <?php echo ($sort_by == 'name_desc') ? 'selected' : ''; ?>>По названию (Я-А)</option>
                                <option value="price" <?php echo ($sort_by == 'price') ? 'selected' : ''; ?>>По цене (сначала дешевые)</option>
                                <option value="price_desc" <?php echo ($sort_by == 'price_desc') ? 'selected' : ''; ?>>По цене (сначала дорогие)</option>
                                <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Сначала новые</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Применить
                            </button>
                            <?php if (!empty($category_id) || !empty($search_query)): ?>
                                <a href="menu.php" class="btn btn-outline" style="margin-top: 0.5rem; text-align: center;">
                                    <i class="fas fa-times"></i> Сбросить
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Секция с блюдами -->
            <section class="dishes-section">
                <?php if (empty($dishes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <h3>Блюда не найдены</h3>
                        <p>Попробуйте изменить параметры поиска или выбрать другую категорию</p>
                        <a href="menu.php" class="btn btn-primary">
                            <i class="fas fa-undo"></i> Показать все блюда
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Группировка по категориям -->
                    <?php 
                    $grouped_dishes = [];
                    foreach ($dishes as $dish) {
                        $category_name = $dish['category_name'] ?? 'Без категории';
                        $grouped_dishes[$category_name][] = $dish;
                    }
                    
                    foreach ($grouped_dishes as $category_name => $category_dishes): 
                    ?>
                        <div class="category-section">
                            <div class="category-header">
                                <h2><?php echo htmlspecialchars($category_name); ?></h2>
                                <p><?php echo count($category_dishes); ?> <?php echo getRussianWord(count($category_dishes), 'блюдо', 'блюда', 'блюд'); ?></p>
                            </div>
                            
                            <div class="dishes-grid">
                                <?php foreach ($category_dishes as $dish): ?>
                                    <div class="dish-card">
                                        <div class="dish-image">
                                            <?php if (!empty($dish['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($dish['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($dish['name']); ?>">
                                            <?php else: ?>
                                                <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
                                                          height: 100%; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                                    <i class="fas fa-utensils" style="font-size: 3rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="dish-category">
                                                <?php echo htmlspecialchars($dish['category_name']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="dish-info">
                                            <div class="dish-header">
                                                <div class="dish-title">
                                                    <h3><?php echo htmlspecialchars($dish['name']); ?></h3>
                                                </div>
                                                <div class="dish-price">
                                                    <?php echo number_format($dish['price'], 0, '', ' '); ?> ₽
                                                </div>
                                            </div>
                                            
                                            <p class="dish-description">
                                                <?php echo htmlspecialchars($dish['description']); ?>
                                            </p>
                                            
                                            <?php if (!empty($dish['weight'])): ?>
                                                <div class="dish-weight">
                                                    <i class="fas fa-weight-hanging"></i> 
                                                    Вес: <?php echo htmlspecialchars($dish['weight']); ?> г
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="dish-actions">
                                                <?php if (isset($_SESSION['user_logged_in'])): ?>
                                                    <!-- Если пользователь авторизован - ссылка на доставку -->
                                                    <a href="delivery.php" class="btn-order">
                                                        <i class="fas fa-utensils"></i> Заказать
                                                    </a>
                                                <?php else: ?>
                                                    <!-- Если не авторизован - ссылка на регистрацию -->
                                                    <a href="user/login.php" class="btn-order">
                                                        <i class="fas fa-user-plus"></i> Зарегистрироваться для заказа
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <div style="display: flex; gap: 10px;">
                                                    <?php if (isset($dish['is_spicy']) && $dish['is_spicy']): ?>
                                                        <span style="color: #e74c3c;" title="Острое блюдо">
                                                            <i class="fas fa-pepper-hot"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($dish['is_vegetarian']) && $dish['is_vegetarian']): ?>
                                                        <span style="color: #27ae60;" title="Вегетарианское блюдо">
                                                            <i class="fas fa-leaf"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Подвал сайта -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Ресторан "TATMAK"</h3>
                    <p>Лучшие блюда европейской кухни в самом центре города с доставкой и уютным залом</p>
                </div>
                <div class="footer-section">
                    <h4>Быстрые ссылки</h4>
                    <ul>
                        <li><a href="menu.php">Мену</a></li>
                        <li><a href="booking.php">Бронирование</a></li>
                        <li><a href="delivery.php">Доставка</a></li>
                        <?php if (isset($_SESSION['user_logged_in'])): ?>
                            <li><a href="user/profile.php" style="color: #9b59b6;"><i class="fas fa-user"></i> Личный кабинет</a></li>
                        <?php else: ?>
                            <li><a href="user/register.php" style="color: #9b59b6;"><i class="fas fa-user-plus"></i> Регистрация</a></li>
                            <li><a href="user/login.php" style="color: #9b59b6;"><i class="fas fa-sign-in-alt"></i> Вход для клиентов</a></li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['admin_logged_in'])): ?>
                            <li><a href="admin/index.php" style="color: #3498db;"><i class="fas fa-cog"></i> Админ панель</a></li>
                        <?php else: ?>
                            <li><a href="admin/login.php" style="color: #3498db;"><i class="fas fa-user-shield"></i> Вход для персонала</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Мы в соцсетях</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-vk"></i></a>
                        <a href="#"><i class="fab fa-telegram"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Ресторан "TATMAK". Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        // Авто-отправка формы при изменении фильтров
        document.getElementById('category').addEventListener('change', function() {
            document.getElementById('filters-form').submit();
        });

        document.getElementById('sort').addEventListener('change', function() {
            document.getElementById('filters-form').submit();
        });

        // Поиск с задержкой
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filters-form').submit();
            }, 500);
        });
    </script>
</body>
</html>

<?php
// Функция для правильного склонения слов
function getRussianWord($number, $one, $two, $five) {
    $number = abs($number) % 100;
    $number1 = $number % 10;
    if ($number > 10 && $number < 20) return $five;
    if ($number1 > 1 && $number1 < 5) return $two;
    if ($number1 == 1) return $one;
    return $five;
}
?>