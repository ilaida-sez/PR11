<?php
session_start();
require_once '../config/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: user/login.php');
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

// Получаем блюда из меню
$dishes_query = "SELECT d.*, c.name as category_name 
                FROM dishes d 
                LEFT JOIN categories c ON d.category_id = c.id 
                WHERE d.is_available = 1 
                ORDER BY c.name, d.name";
$dishes_stmt = $db->query($dishes_query);
$dishes = $dishes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Группируем блюда по категориям
$categories = [];
foreach ($dishes as $dish) {
    $categories[$dish['category_name']][] = $dish;
}

$success = '';
$error = '';

// Обработка оформления заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $customer_address = trim($_POST['customer_address']);
    $comment = trim($_POST['comment'] ?? '');
    $items = $_POST['items'] ?? [];
    
    if (empty($customer_address)) {
        $error = "Пожалуйста, укажите адрес доставки";
    } else {
        // Проверяем, есть ли выбранные товары
        $has_items = false;
        foreach ($items as $quantity) {
            if ($quantity > 0) {
                $has_items = true;
                break;
            }
        }
        
        if (!$has_items) {
            $error = "Пожалуйста, выберите хотя бы одно блюдо";
        } else {
            try {
                $db->beginTransaction();
                
                // Рассчитываем общую сумму
                $items_total = 0;
                $order_items = [];
                
                foreach ($items as $dish_id => $quantity) {
                    if ($quantity > 0) {
                        $dish_query = "SELECT price, name FROM dishes WHERE id = ?";
                        $dish_stmt = $db->prepare($dish_query);
                        $dish_stmt->execute([$dish_id]);
                        $dish = $dish_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($dish) {
                            $item_total = $dish['price'] * $quantity;
                            $items_total += $item_total;
                            $order_items[] = [
                                'dish_id' => $dish_id,
                                'quantity' => $quantity,
                                'price' => $dish['price'],
                                'name' => $dish['name']
                            ];
                        }
                    }
                }
                
                // Добавляем стоимость доставки
                $delivery_cost = 200; // Базовая стоимость доставки
                $total_amount = $items_total + $delivery_cost;
                
                // Создаем заказ
                $order_query = "INSERT INTO orders (customer_name, customer_phone, customer_address, total_amount, status) 
                               VALUES (?, ?, ?, ?, 'pending')";
                $order_stmt = $db->prepare($order_query);
                $order_stmt->execute([
                    $user['name'],
                    $user['phone'],
                    $customer_address,
                    $total_amount
                ]);
                
                $order_id = $db->lastInsertId();
                
                // Добавляем товары заказа
                $item_query = "INSERT INTO order_items (order_id, dish_id, quantity, price) VALUES (?, ?, ?, ?)";
                $item_stmt = $db->prepare($item_query);
                
                foreach ($order_items as $item) {
                    $item_stmt->execute([
                        $order_id,
                        $item['dish_id'],
                        $item['quantity'],
                        $item['price']
                    ]);
                }
                
                $db->commit();
                $success = "Заказ успешно оформлен! Номер вашего заказа: #" . $order_id;
                
                // Очищаем корзину
                $_POST = [];
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Ошибка при оформлении заказа: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доставка еды - Ресторан TATMAK</title>
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
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
    }

    .page-header {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        grid-column: 1 / -1;
    }

    .page-header h1 {
        color: #D90A16;
        margin-bottom: 0.5rem;
    }

    .menu-section {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .category {
        margin-bottom: 2rem;
    }

    .category h3 {
        color: #D90A16;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f1f1f1;
    }

    .dishes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }

    .dish-card {
        border: 1px solid #e1e5e9;
        border-radius: 8px;
        padding: 1rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .dish-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .dish-name {
        font-weight: bold;
        margin-bottom: 0.5rem;
        color: #333;
    }

    .dish-description {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .dish-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dish-price {
        font-weight: bold;
        color: #D90A16;
        font-size: 1.1rem;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #D90A16;
        background: white;
        color: #D90A16;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        transition: all 0.3s;
    }

    .quantity-btn:hover {
        background: #D90A16;
        color: white;
    }

    .quantity-btn:disabled {
        border-color: #ccc;
        color: #ccc;
        cursor: not-allowed;
    }

    .quantity-btn:disabled:hover {
        background: white;
        color: #ccc;
    }

    .quantity-input {
        width: 50px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 4px;
    }

    .order-form {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        position: sticky;
        top: 2rem;
        height: fit-content;
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
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
    }

    .form-control:focus {
        outline: none;
        border-color: #D90A16;
    }

    textarea.form-control {
        min-height: 80px;
        resize: vertical;
    }

    .order-summary {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .summary-total {
        font-weight: bold;
        font-size: 1.2rem;
        color: #D90A16;
        border-top: 2px solid #e1e5e9;
        padding-top: 0.5rem;
        margin-top: 0.5rem;
    }

    .btn {
        padding: 12px 24px;
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
        width: 100%;
        justify-content: center;
    }

    .btn-primary {
        background: #D90A16;
        color: white;
    }

    .btn-primary:hover {
        background: #b30812;
    }

    .btn:disabled {
        background: #ccc;
        cursor: not-allowed;
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

    .user-info {
        background: #e8f4fd;
        border: 1px solid #b6d7f7;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .user-info h4 {
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
        gap: 1.5rem;
    }
    
    .page-header {
        padding: 1.5rem;
    }
    
    .menu-section {
        padding: 1.5rem;
    }
    
    .order-form {
        padding: 1.5rem;
        position: static;
    }
    
    .dishes-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 0.8rem;
    }
    
    .dish-card {
        padding: 0.8rem;
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
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .page-header {
        padding: 1.25rem;
        text-align: center;
        margin-bottom: 1rem;
    }
    
    .menu-section {
        padding: 1.25rem;
        order: 2;
    }
    
    .order-form {
        padding: 1.25rem;
        order: 1;
        position: static;
    }
    
    .dishes-grid {
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }
    
    .category {
        margin-bottom: 1.5rem;
    }
    
    .category h3 {
        font-size: 1.2rem;
        margin-bottom: 0.8rem;
    }
    
    .dish-footer {
        flex-direction: column;
        gap: 0.8rem;
        align-items: flex-start;
    }
    
    .quantity-controls {
        align-self: flex-end;
    }
    
    .user-info {
        padding: 0.8rem;
        margin-bottom: 1rem;
    }
    
    .order-summary {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .btn {
        padding: 10px 20px;
        font-size: 0.95rem;
    }
    
    .alert {
        padding: 0.8rem;
        margin-bottom: 0.8rem;
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
        font-size: 1.5rem;
    }
    
    .dish-name {
        font-size: 1.1rem;
    }
    
    .dish-description {
        font-size: 0.85rem;
    }
    
    .quantity-input {
        width: 45px;
    }
    
    .form-control {
        padding: 8px 10px;
        font-size: 0.95rem;
    }
    
    textarea.form-control {
        min-height: 70px;
    }
}
</style>Ф
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
            <h1><i class="fas fa-truck"></i> Доставка еды</h1>
            <p>Выберите понравившиеся блюда и оформите заказ</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success" style="grid-column: 1 / -1;">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error" style="grid-column: 1 / -1;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="orderForm">
            <div class="menu-section">
                <?php foreach ($categories as $category_name => $category_dishes): ?>
                    <div class="category">
                        <h3><?php echo htmlspecialchars($category_name); ?></h3>
                        <div class="dishes-grid">
                            <?php foreach ($category_dishes as $dish): ?>
                                <div class="dish-card">
                                    <div class="dish-name"><?php echo htmlspecialchars($dish['name']); ?></div>
                                    <div class="dish-description"><?php echo htmlspecialchars($dish['description']); ?></div>
                                    <div class="dish-footer">
                                        <div class="dish-price"><?php echo $dish['price']; ?> ₽</div>
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn minus" data-dish="<?php echo $dish['id']; ?>" disabled>-</button>
                                            <input type="number" name="items[<?php echo $dish['id']; ?>]" 
                                                   value="<?php echo $_POST['items'][$dish['id']] ?? 0; ?>" 
                                                   min="0" max="10" class="quantity-input"
                                                   data-price="<?php echo $dish['price']; ?>">
                                            <button type="button" class="quantity-btn plus" data-dish="<?php echo $dish['id']; ?>">+</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-form">
                <h3 style="margin-bottom: 1.5rem; color: #9b59b6;">Оформление заказа</h3>
                
                <div class="user-info">
                    <h4><i class="fas fa-user"></i> Ваши данные</h4>
                    <p><strong>Имя:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                </div>

                <div class="form-group">
                    <label for="customer_address"><i class="fas fa-map-marker-alt"></i> Адрес доставки *</label>
                    <input type="text" id="customer_address" name="customer_address" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['customer_address'] ?? $user['address'] ?? ''); ?>" 
                           placeholder="Укажите полный адрес доставки" required>
                </div>

                <div class="form-group">
                    <label for="comment"><i class="fas fa-comment"></i> Комментарий к заказу</label>
                    <textarea id="comment" name="comment" class="form-control" 
                              placeholder="Укажите особенности доставки, пожелания к блюдам и т.д."><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                </div>

                <div class="order-summary">
                    <h4 style="margin-bottom: 1rem;">Сумма заказа</h4>
                    <div class="summary-item">
                        <span>Товары:</span>
                        <span id="items-total">0 ₽</span>
                    </div>
                    <div class="summary-item">
                        <span>Доставка:</span>
                        <span id="delivery-cost">200 ₽</span>
                    </div>
                    <div class="summary-item summary-total">
                        <span>Итого:</span>
                        <span id="total-amount">200 ₽</span>
                    </div>
                </div>

                <button type="submit" name="place_order" class="btn btn-primary" id="submit-btn" disabled>
                    <i class="fas fa-check"></i> Оформить заказ
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const plusButtons = document.querySelectorAll('.quantity-btn.plus');
            const minusButtons = document.querySelectorAll('.quantity-btn.minus');
            const itemsTotalEl = document.getElementById('items-total');
            const totalAmountEl = document.getElementById('total-amount');
            const submitBtn = document.getElementById('submit-btn');
            const deliveryCost = 200;

            function updateOrderSummary() {
                let itemsTotal = 0;
                let hasItems = false;

                quantityInputs.forEach(input => {
                    const quantity = parseInt(input.value);
                    const price = parseFloat(input.dataset.price);
                    itemsTotal += quantity * price;
                    
                    if (quantity > 0) {
                        hasItems = true;
                    }
                });

                const totalAmount = itemsTotal + deliveryCost;

                itemsTotalEl.textContent = itemsTotal.toFixed(0) + ' ₽';
                totalAmountEl.textContent = totalAmount.toFixed(0) + ' ₽';

                // Активируем кнопку только если есть товары
                submitBtn.disabled = !hasItems;
            }

            function updateQuantityButtons() {
                quantityInputs.forEach(input => {
                    const quantity = parseInt(input.value);
                    const dishId = input.name.match(/\[(\d+)\]/)[1];
                    const minusBtn = document.querySelector(`.minus[data-dish="${dishId}"]`);
                    const plusBtn = document.querySelector(`.plus[data-dish="${dishId}"]`);
                    
                    minusBtn.disabled = quantity <= 0;
                    plusBtn.disabled = quantity >= 10;
                });
            }

            // Обработчики для кнопок "+"
            plusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const dishId = this.dataset.dish;
                    const input = document.querySelector(`input[name="items[${dishId}]"]`);
                    const currentValue = parseInt(input.value);
                    if (currentValue < 10) {
                        input.value = currentValue + 1;
                        updateOrderSummary();
                        updateQuantityButtons();
                    }
                });
            });

            // Обработчики для кнопок "-"
            minusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const dishId = this.dataset.dish;
                    const input = document.querySelector(`input[name="items[${dishId}]"]`);
                    const currentValue = parseInt(input.value);
                    if (currentValue > 0) {
                        input.value = currentValue - 1;
                        updateOrderSummary();
                        updateQuantityButtons();
                    }
                });
            });

            // Обработчики прямого ввода
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    let value = parseInt(this.value);
                    if (isNaN(value) || value < 0) value = 0;
                    if (value > 10) value = 10;
                    this.value = value;
                    updateOrderSummary();
                    updateQuantityButtons();
                });
                
                input.addEventListener('input', function() {
                    updateOrderSummary();
                    updateQuantityButtons();
                });
            });

            // Инициализация
            updateOrderSummary();
            updateQuantityButtons();
        });
    </script>
</body>
</html>