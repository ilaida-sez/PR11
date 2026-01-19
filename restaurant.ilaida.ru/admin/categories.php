<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Добавление/редактирование категории
if ($_POST) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    if (isset($_POST['id'])) {
        // Редактирование
        $query = "UPDATE categories SET name=?, description=? WHERE id=?";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $description, $_POST['id']]);
    } else {
        // Добавление
        $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $description]);
    }
    
    header('Location: categories.php');
    exit;
}

// Удаление категории
if (isset($_GET['delete'])) {
    // Сначала обнуляем категорию у блюд
    $db->query("UPDATE dishes SET category_id = NULL WHERE category_id = " . $_GET['delete']);
    
    // Затем удаляем категорию
    $query = "DELETE FROM categories WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['delete']]);
    
    header('Location: categories.php');
    exit;
}

// Определение направления сортировки
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'desc';

// Проверка валидности направления сортировки
if (!in_array($order, ['asc', 'desc'])) {
    $order = 'desc';
}

// Проверка валидности поля для сортировки
$allowed_sorts = ['id', 'name', 'description'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'id';
}

// Получение категорий с сортировкой
$query = "SELECT * FROM categories ORDER BY $sort $order";
$categories = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Функция для генерации URL с сортировкой
function getSortUrl($field) {
    global $sort, $order;
    
    $newOrder = 'asc';
    if ($sort == $field && $order == 'asc') {
        $newOrder = 'desc';
    }
    
    $params = $_GET;
    $params['sort'] = $field;
    $params['order'] = $newOrder;
    
    return 'categories.php?' . http_build_query($params);
}

// Функция для отображения значка сортировки
function getSortIcon($field) {
    global $sort, $order;
    
    if ($sort == $field) {
        return $order == 'asc' ? '↑' : '↓';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление категориями</title>
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
        margin-bottom: 20px;
        border-left: 4px solid #D90A16;
    }
    
    .categories-container { 
        background: white; 
        padding: 2rem; 
        border-radius: 10px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        margin-bottom: 2rem;
    }
    
    .categories-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 1rem; 
    }
    
    .categories-table th, .categories-table td { 
        padding: 1rem; 
        text-align: left; 
        border-bottom: 1px solid #ddd; 
    }
    
    .categories-table th { 
        background: #f8f9fa; 
        cursor: pointer;
        color: #D90A16;
    }
    
    .categories-table th:hover { 
        background: #e9ecef; 
    }
    
    .sortable-header {
        display: flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        color: inherit;
    }
    
    .sort-icon {
        font-weight: bold;
        color: #D90A16;
    }
    
    .action-buttons { 
        display: flex; 
        gap: 0.5rem; 
    }
    
    .btn { 
        background: #D90A16; 
        color: white; 
        padding: 10px 20px; 
        text-decoration: none; 
        border-radius: 5px; 
        display: inline-block; 
        margin: 5px; 
        border: none;
        cursor: pointer;
    }
    
    .btn:hover {
        background: #b30812;
    }
    
    .btn-edit { 
        background: #D90A16; 
        color: white; 
        padding: 5px 10px; 
        text-decoration: none; 
        border-radius: 3px; 
        font-size: 0.9rem; 
    }
    
    .btn-delete { 
        background: #2c3e50; 
        color: white; 
        padding: 5px 10px; 
        text-decoration: none; 
        border-radius: 3px; 
        font-size: 0.9rem; 
    }
    
    .logout-btn {
        background: #95a5a6;
    }
    
    .form-group { 
        margin-bottom: 1rem; 
    }
    
    .form-group label { 
        display: block; 
        margin-bottom: 0.5rem; 
        font-weight: bold; 
        color: #2c3e50;
    }
    
    .form-group input, .form-group textarea { 
        width: 100%; 
        padding: 0.5rem; 
        border: 1px solid #ddd; 
        border-radius: 5px; 
    }

    .form-group input:focus, .form-group textarea:focus {
        border-color: #D90A16;
        outline: none;
        box-shadow: 0 0 0 2px rgba(217, 10, 22, 0.1);
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
    
    .categories-container {
        padding: 1rem;
        overflow-x: auto;
    }
    
    .categories-table {
        min-width: 600px;
        font-size: 0.9rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.3rem;
    }
    
    .btn-edit, .btn-delete {
        padding: 8px 12px;
        font-size: 0.8rem;
        text-align: center;
    }
    
    .sortable-header {
        padding: 8px 0;
    }
}

@media (max-width: 480px) {
    .admin-menu a {
        padding: 8px 10px;
        min-width: 100px;
        font-size: 0.9rem;
    }
    
    .categories-table {
        min-width: 500px;
    }
    
    .form-group input, .form-group textarea {
        padding: 12px;
        font-size: 16px;
    }
    
    .btn {
        padding: 12px 20px;
        width: 100%;
        margin: 5px 0;
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
                <p>TatmaK</p>
            </div>
            
            <ul class="admin-menu">
                <li><a href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Главная
                </a></li>
                <li><a href="dishes.php">
                    <i class="fas fa-utensils"></i> Блюда
                </a></li>
                <li><a href="categories.php" class="active">
                    <i class="fas fa-list"></i> Категории
                </a></li>
                <li><a href="orders.php">
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
                <h1>Управление категориями</h1>
            </div>
            
            <!-- Форма добавления/редактирования -->
            <div class="categories-container">
                <h3><?php echo isset($_GET['edit']) ? 'Редактировать категорию' : 'Добавить новую категорию'; ?></h3>
                <form method="POST">
                    <?php if (isset($_GET['edit'])): ?>
                        <input type="hidden" name="id" value="<?php echo $_GET['edit']; ?>">
                        <?php 
                        $edit_category = $db->query("SELECT * FROM categories WHERE id = " . $_GET['edit'])->fetch(PDO::FETCH_ASSOC);
                        ?>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Название категории:</label>
                        <input type="text" name="name" value="<?php echo $edit_category['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" rows="3"><?php echo $edit_category['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn"><?php echo isset($_GET['edit']) ? 'Обновить' : 'Добавить'; ?> категорию</button>
                    <?php if (isset($_GET['edit'])): ?>
                        <a href="categories.php" class="btn logout-btn">Отмена</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Список категорий -->
            <div class="categories-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0;">Список категорий</h3>
                    <?php if ($sort != 'id' || $order != 'desc'): ?>
                    <div class="sort-info">
                        Сортировка: 
                        <?php 
                        $column_names = [
                            'id' => 'ID',
                            'name' => 'Название',
                            'description' => 'Описание'
                        ];
                        echo $column_names[$sort] . ' (' . ($order == 'asc' ? 'по возрастанию' : 'по убыванию') . ')';
                        ?>
                        <a href="categories.php" class="reset-sort">Сбросить</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="<?php echo getSortUrl('id'); ?>" class="sortable-header">
                                    ID
                                    <span class="sort-icon"><?php echo getSortIcon('id'); ?></span>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortUrl('name'); ?>" class="sortable-header">
                                    Название
                                    <span class="sort-icon"><?php echo getSortIcon('name'); ?></span>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo getSortUrl('description'); ?>" class="sortable-header">
                                    Описание
                                    <span class="sort-icon"><?php echo getSortIcon('description'); ?></span>
                                </a>
                            </th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td class="action-buttons">
                                <a href="categories.php?edit=<?php echo $category['id']; ?>" class="btn-edit">Редактировать</a>
                                <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn-delete" 
                                   onclick="return confirm('Удалить эту категорию?')">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Добавляем обработчики для клавиатуры
        document.addEventListener('DOMContentLoaded', function() {
            const sortableHeaders = document.querySelectorAll('.sortable-header');
            
            sortableHeaders.forEach(header => {
                header.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        window.location.href = this.href;
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