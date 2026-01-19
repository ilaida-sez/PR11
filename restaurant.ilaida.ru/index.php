<?php
session_start();
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// Получаем популярные блюда из БД
$popular_dishes = $db->query("
    SELECT d.*, c.name as category_name 
    FROM dishes d 
    LEFT JOIN categories c ON d.category_id = c.id 
    WHERE d.is_available = TRUE 
    ORDER BY d.created_at DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Главная - Ресторан TATMAK";
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
        background-color: #F4F3F5;
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

    .cart-icon {
        position: relative;
    }

    .cart-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #D90A16;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
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

    /* Герой секция */
    .hero {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 80vh;
        align-items: center;
        margin-top: 80px;
        background: linear-gradient(135deg, #F4F3F5 0%, #e9e7eb 100%);
        padding: 2rem;
    }

    .hero-content h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #2c3e50;
    }

    .hero-content p {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        color: #6c757d;
    }

    .hero-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .btn {
        padding: 12px 24px;
        text-decoration: none;
        border-radius: 8px;
        font-weight: bold;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }

    .btn-primary {
        background: #D90A16;
        color: white;
    }

    .btn-secondary {
        background: transparent;
        color: #2c3e50;
        border: 2px solid #2c3e50;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(217, 10, 22, 0.3);
    }

    .btn-primary:hover {
        background: #b30812;
    }

    .btn-secondary:hover {
        background: #2c3e50;
        color: white;
    }

    /* Админ панель в футере */
    .admin-panel-footer {
        background: #2c3e50;
        color: white;
        padding: 1rem 0;
        margin-top: 2rem;
        border-top: 3px solid #D90A16;
    }

    .admin-panel-footer .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .admin-panel-footer h3 {
        color: #D90A16;
        margin-bottom: 0.5rem;
    }

    .admin-panel-footer p {
        color: #bdc3c7;
        font-size: 0.9rem;
    }

    /* Остальные стили... */
    .features {
        padding: 5rem 0;
        background: white;
    }

    .features h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 3rem;
        color: #2c3e50;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .feature-card {
        padding: 2rem;
        text-align: center;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        background: white;
    }

    .feature-card:hover {
        transform: translateY(-5px);
    }

    .feature-card i {
        font-size: 3rem;
        color: #D90A16;
        margin-bottom: 1rem;
    }

    /* Популярные блюда */
    .popular-dishes {
        padding: 5rem 0;
        background: #F4F3F5;
    }

    .popular-dishes h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 3rem;
        color: #2c3e50;
    }

    .dishes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .dish-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }

    .dish-card:hover {
        transform: translateY(-5px);
    }

    .dish-image {
        height: 200px;
        overflow: hidden;
    }

    .dish-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .dish-card:hover .dish-image img {
        transform: scale(1.1);
    }

    .dish-info {
        padding: 1.5rem;
    }

    .dish-info h3 {
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }

    .dish-info p {
        color: #6c757d;
        margin-bottom: 1rem;
    }

    .dish-price {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dish-price span {
        font-size: 1.2rem;
        font-weight: bold;
        color: #D90A16;
    }

    .btn-add-cart {
        background: #D90A16;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }

    .btn-add-cart:hover {
        background: #b30812;
    }

    .text-center {
        text-align: center;
    }

    .btn-outline {
        background: transparent;
        color: #D90A16;
        border: 2px solid #D90A16;
        padding: 12px 30px;
    }

    .btn-outline:hover {
        background: #D90A16;
        color: white;
    }

    /* Подвал */
    .footer {
        background: #2c3e50;
        color: white;
        padding: 3rem 0 1rem;
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .footer-section h3,
    .footer-section h4 {
        margin-bottom: 1rem;
        color: #D90A16;
    }

    .footer-section ul {
        list-style: none;
    }

    .footer-section ul li {
        margin-bottom: 0.5rem;
    }

    .footer-section a {
        color: #bdc3c7;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-section a:hover {
        color: #D90A16;
    }

    .social-links {
        display: flex;
        gap: 1rem;
    }

    .social-links a {
        font-size: 1.5rem;
    }

    .footer-bottom {
        text-align: center;
        padding-top: 2rem;
        border-top: 1px solid #34495e;
        color: #bdc3c7;
    }

   /* Адаптивность*/
@media (max-width: 1024px) {
    /* Планшет */
    .hero {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 2rem;
        padding: 1.5rem;
    }
    
    .features-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .dishes-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .footer-content {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
    }
    
    .hero-content h1 {
        font-size: 2.5rem;
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
    
    .hero {
        min-height: auto;
        padding: 1rem;
        margin-top: 70px;
    }
    
    .hero-content h1 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .hero-content p {
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .hero-buttons {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
        padding: 14px 20px;
    }
    
    .features {
        padding: 3rem 0;
    }
    
    .features h2 {
        font-size: 2rem;
        margin-bottom: 2rem;
    }
    
    .feature-card {
        padding: 1.5rem;
    }
    
    .popular-dishes {
        padding: 3rem 0;
    }
    
    .popular-dishes h2 {
        font-size: 2rem;
        margin-bottom: 2rem;
    }
    
    .dishes-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .dish-card {
        margin-bottom: 1rem;
    }
    
    .admin-panel-footer .container {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .auth-buttons {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .auth-btn {
        width: 100%;
        justify-content: center;
    }
    
    .footer {
        padding: 2rem 0 1rem;
    }
    
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 2rem;
    }
    
    .social-links {
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
    
    .hero-content h1 {
        font-size: 1.8rem;
    }
    
    .features h2,
    .popular-dishes h2 {
        font-size: 1.8rem;
    }
    
    .feature-card {
        padding: 1rem;
    }
    
    .feature-card i {
        font-size: 2.5rem;
    }
    
    .dish-image {
        height: 180px;
    }
    
    .dish-info {
        padding: 1rem;
    }
    
    .dish-info h3 {
        font-size: 1.1rem;
    }
    
    .btn-outline {
        width: 100%;
        justify-content: center;
    }
    
    .admin-panel-footer {
        padding: 0.75rem 0;
    }
    
    .admin-panel-footer h3 {
        font-size: 1.1rem;
    }
    
    .footer-bottom p {
        font-size: 0.9rem;
    }
}

/* Общие стили для мобильной навигации */
.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #333;
    cursor: pointer;
    padding: 0.5rem;
}

.mobile-menu {
    display: none;
    position: fixed;
    top: 70px;
    left: 0;
    width: 100%;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 999;
}

.mobile-menu.active {
    display: block;
}

.mobile-menu ul {
    list-style: none;
    padding: 1rem;
}

.mobile-menu ul li {
    margin-bottom: 0.5rem;
}

.mobile-menu ul li a {
    display: block;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: #333;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.mobile-menu ul li a:hover,
.mobile-menu ul li a.active {
    background-color: #f8f9fa;
    color: #D90A16;
}

.mobile-auth-buttons {
    padding: 1rem;
    border-top: 1px solid #e1e5e9;
}

@media (max-width: 768px) {
    input,
    select,
    textarea {
        font-size: 16px !important;
    }
}

@media (max-width: 768px) {
    html {
        -webkit-overflow-scrolling: touch;
    }
    
    body {
        -webkit-text-size-adjust: 100%;
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
                    <li><a href="index.php" class="active">Главная</a></li>
                    <li><a href="menu.php">Меню</a></li>
                    <li><a href="user/login.php">Бронирование</a></li>
                    <li><a href="user/login.php">Доставка</a></li>
                    <li><a href="#contacts">Контакты</a></li>
                    <li class="cart-icon">
                    </li>
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

    <main>
        <!-- Герой секция -->
        <section class="hero">
            <div class="hero-content">
                <h1>Добро пожаловать в наш ресторан</h1>
                <p>Насладитесь изысканной кухней от лучших шеф-поваров с доставкой на дом или посетите наш уютный зал</p>
                <div class="hero-buttons">
                    <a href="user/login.php" class="btn btn-primary">
                        <i class="fas fa-utensils"></i> Заказать доставку
                    </a>
                    <a href="user/login.php" class="btn btn-secondary">
                        <i class="fas fa-calendar-check"></i> Забронировать столик
                    </a>
                </div>
        
            </div>
            <div class="hero-image">
            </div>
        </section>

        <!-- Секция преимуществ -->
        <section class="features">
            <div class="container">
                <h2>Почему выбирают нас</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <i class="fas fa-star"></i>
                        <h3>Премиум качество</h3>
                        <p>Только свежие продукты и авторские рецепты от нашего шеф-повара</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-shipping-fast"></i>
                        <h3>Быстрая доставка</h3>
                        <p>Доставим ваш заказ в течение 60 минут или сделаем скидку 20%</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-utensils"></i>
                        <h3>Уютная атмосфера</h3>
                        <p>Идеальное место для романтических встреч, семейных праздников и бизнес-ланчей</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Секция популярных блюд -->
        <section class="popular-dishes">
            <div class="container">
                <h2>Популярные блюда</h2>
                <div class="dishes-grid">
                    <?php foreach ($popular_dishes as $dish): ?>
                    <div class="dish-card">
                        <div class="dish-image">
                            <?php if (!empty($dish['image'])): ?>
                                <img src="<?php echo $dish['image']; ?>" alt="<?php echo htmlspecialchars($dish['name']); ?>">
                            <?php else: ?>
                                <div style="background: #f8f9fa; height: 200px; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                    <i class="fas fa-utensils" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="dish-info">
                            <h3><?php echo htmlspecialchars($dish['name']); ?></h3>
                            <p><?php echo htmlspecialchars($dish['description']); ?></p>
                            <div class="dish-price">
                                <span><?php echo $dish['price']; ?> ₽</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center">
                    <a href="menu.php" class="btn btn-outline">Смотреть всё меню</a>
                </div>
            </div>
        </section>

        <!-- Блок админ панели внизу для гостей -->
        <?php if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])): ?>
        <section class="admin-panel-footer">
            <div class="container">
                <div>
                    <h3><i class="fas fa-user-shield"></i> Панель управления</h3>
                    <p>Для сотрудников ресторана</p>
                </div>
                <div>
                    <a href="admin/login.php" class="auth-btn admin-btn" style="font-size: 1rem; padding: 10px 20px;">
                        <i class="fas fa-sign-in-alt"></i> Войти в админ панель
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>

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
                        <li><a href="menu.php">Меню</a></li>
                        <li><a href="booking.php">Бронирование</a></li>
                        <li><a href="delivery.php">Доставка</a></li>
                        <?php if (isset($_SESSION['user_logged_in'])): ?>
                            <li><a href="user/profile.php" style="color: #9b59b6;"><i class="fas fa-user"></i> Личный кабинет</a></li>
                        <?php else: ?>
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
                <?php if (isset($_SESSION['admin_logged_in'])): ?>
                    <p style="font-size: 0.8rem; color: #3498db; margin-top: 0.5rem;">
                        <i class="fas fa-user-shield"></i> Вы вошли как администратор
                    </p>
                <?php elseif (isset($_SESSION['user_logged_in'])): ?>
                    <p style="font-size: 0.8rem; color: #9b59b6; margin-top: 0.5rem;">
                        <i class="fas fa-user"></i> Вы вошли как <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'клиент'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <script>
        function addToCart(itemId, itemName, itemPrice) {
            // Временная реализация
            alert('"' + itemName + '" добавлен в корзину за ' + itemPrice + ' ₽');
            
            // Обновляем счетчик корзины
            let cartCount = document.querySelector('.cart-count');
            let currentCount = parseInt(cartCount.textContent);
            cartCount.textContent = currentCount + 1;
            
            // Анимация
            cartCount.style.transform = 'scale(1.3)';
            setTimeout(() => {
                cartCount.style.transform = 'scale(1)';
            }, 300);
        }
    </script>
</body>
</html>