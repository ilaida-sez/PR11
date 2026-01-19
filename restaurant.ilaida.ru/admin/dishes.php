<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Добавление/редактирование блюда
if ($_POST) {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $weight = $_POST['weight'];
    
    // Обработка загрузки изображения
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/dishes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        // Проверка типа файла
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image = 'uploads/dishes/' . $file_name;
            }
        }
    }
    
    if (isset($_POST['id'])) {
        // Редактирование
        if ($image) {
            // Если загружено новое изображение, обновляем путь
            $query = "UPDATE dishes SET name=?, category_id=?, description=?, price=?, weight=?, image=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $category_id, $description, $price, $weight, $image, $_POST['id']]);
        } else {
            // Если изображение не загружено, оставляем старое
            $query = "UPDATE dishes SET name=?, category_id=?, description=?, price=?, weight=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $category_id, $description, $price, $weight, $_POST['id']]);
        }
    } else {
        // Добавление
        $query = "INSERT INTO dishes (name, category_id, description, price, weight, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $category_id, $description, $price, $weight, $image]);
    }
    
    header('Location: dishes.php');
    exit;
}

// Удаление блюда
if (isset($_GET['delete'])) {
    // Сначала получаем информацию о блюде для удаления изображения
    $dish = $db->query("SELECT image FROM dishes WHERE id = " . $_GET['delete'])->fetch(PDO::FETCH_ASSOC);
    
    // Удаляем файл изображения если он существует
    if ($dish['image'] && file_exists('../' . $dish['image'])) {
        unlink('../' . $dish['image']);
    }
    
    $query = "DELETE FROM dishes WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['delete']]);
    header('Location: dishes.php');
    exit;
}

// Удаление изображения блюда
if (isset($_GET['delete_image'])) {
    $dish_id = $_GET['delete_image'];
    $dish = $db->query("SELECT image FROM dishes WHERE id = " . $dish_id)->fetch(PDO::FETCH_ASSOC);
    
    if ($dish['image'] && file_exists('../' . $dish['image'])) {
        unlink('../' . $dish['image']);
    }
    
    $query = "UPDATE dishes SET image = NULL WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$dish_id]);
    
    header('Location: dishes.php?edit=' . $dish_id);
    exit;
}

// Параметры сортировки
$sort_column = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'desc';

// Допустимые колонки для сортировки
$allowed_columns = ['id', 'name', 'category_name', 'price', 'weight', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'id';
}

// Допустимые направления сортировки
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_order, $allowed_orders)) {
    $sort_order = 'desc';
}

// Получение блюд с сортировкой
$dishes = $db->query("
    SELECT d.*, c.name as category_name 
    FROM dishes d 
    LEFT JOIN categories c ON d.category_id = c.id 
    ORDER BY $sort_column $sort_order
")->fetchAll(PDO::FETCH_ASSOC);

// Получение категорий для формы
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Функция для генерации URL сортировки
function getSortUrl($column, $current_sort, $current_order) {
    $order = 'asc';
    if ($current_sort == $column && $current_order == 'asc') {
        $order = 'desc';
    }
    return "dishes.php?sort=$column&order=$order";
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
    <title>Управление блюдами</title>
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
    
    .dishes-container { 
        background: white; 
        padding: 2rem; 
        border-radius: 10px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        margin-bottom: 2rem;
    }
    
    .dishes-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 1rem; 
    }
    
    .dishes-table th, .dishes-table td { 
        padding: 1rem; 
        text-align: left; 
        border-bottom: 1px solid #ddd; 
    }
    
    .dishes-table th { 
        background: #f8f9fa; 
        cursor: pointer;
        user-select: none;
        position: relative;
        color: #D90A16;
    }
    
    .dishes-table th:hover {
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
    
    .action-buttons { 
        display: flex; 
        gap: 0.5rem; 
        flex-wrap: wrap;
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
    
    .btn-delete-image {
        background: #ff6b6b; 
        color: white; 
        padding: 5px 10px; 
        text-decoration: none; 
        border-radius: 3px; 
        font-size: 0.8rem; 
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
    
    .form-group input, .form-group select, .form-group textarea { 
        width: 100%; 
        padding: 0.5rem; 
        border: 1px solid #ddd; 
        border-radius: 5px; 
    }
    
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        border-color: #D90A16;
        outline: none;
        box-shadow: 0 0 0 2px rgba(217, 10, 22, 0.1);
    }
    
    .image-preview {
        max-width: 200px;
        max-height: 150px;
        margin: 10px 0;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border: 2px solid #D90A16;
    }
    
    .current-image {
        margin: 10px 0;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .dish-image {
        max-width: 100px;
        max-height: 80px;
        border-radius: 5px;
        margin-right: 10px;
        border: 2px solid #D90A16;
    }
    
    .image-info {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
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
    
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }
    
    .file-input-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
    }
    
    .file-input-label {
        background: #D90A16;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        display: inline-block;
    }
    
    .file-input-label:hover {
        background: #b30812;
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
    
    .dishes-container {
        padding: 1rem;
        overflow-x: auto;
    }
    
    .dishes-table {
        min-width: 800px;
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
    
    .image-preview {
        max-width: 150px;
        max-height: 120px;
    }
    
    .file-input-label {
        padding: 10px 15px;
        display: block;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .admin-menu a {
        padding: 8px 10px;
        min-width: 100px;
        font-size: 0.9rem;
    }
    
    .dishes-table {
        min-width: 700px;
    }
    
    .form-group input, .form-group select, .form-group textarea {
        padding: 12px;
        font-size: 16px;
    }
    
    .btn {
        padding: 12px 20px;
        width: 100%;
        margin: 5px 0;
    }
    
    .dish-image {
        max-width: 80px;
        max-height: 60px;
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
                <li><a href="dishes.php" class="active">
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
                <li><a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a></li>
            </ul>
        </aside>

        <!-- Основное содержимое -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Управление блюдами</h1>
            </div>
            
            <!-- Форма добавления/редактирования -->
            <div class="dishes-container">
                <h3><?php echo isset($_GET['edit']) ? 'Редактировать блюдо' : 'Добавить новое блюдо'; ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <?php if (isset($_GET['edit'])): ?>
                        <input type="hidden" name="id" value="<?php echo $_GET['edit']; ?>">
                        <?php 
                        $edit_dish = $db->query("SELECT * FROM dishes WHERE id = " . $_GET['edit'])->fetch(PDO::FETCH_ASSOC);
                        ?>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Название блюда:</label>
                        <input type="text" name="name" value="<?php echo $edit_dish['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Категория:</label>
                        <select name="category_id" required>
                            <option value="">Выберите категорию</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo ($edit_dish['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" rows="3"><?php echo $edit_dish['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Цена (₽):</label>
                        <input type="number" name="price" step="0.01" value="<?php echo $edit_dish['price'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Вес/объем:</label>
                        <input type="text" name="weight" value="<?php echo $edit_dish['weight'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Изображение блюда:</label>
                        
                        <?php if (isset($edit_dish) && $edit_dish['image']): ?>
                        <div class="current-image">
                            <div class="image-info">
                                <img src="../<?php echo $edit_dish['image']; ?>" alt="<?php echo htmlspecialchars($edit_dish['name']); ?>" class="dish-image">
                                <span>Текущее изображение</span>
                            </div>
                            <a href="dishes.php?delete_image=<?php echo $edit_dish['id']; ?>" class="btn-delete-image" 
                               onclick="return confirm('Удалить изображение?')">
                                <i class="fas fa-trash"></i> Удалить изображение
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="file-input-wrapper">
                            <label class="file-input-label">
                                <i class="fas fa-upload"></i> Выберите файл
                                <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                            </label>
                            <span id="file-name" style="margin-left: 10px;"></span>
                        </div>
                        
                        <div id="image-preview-container">
                            <?php if (isset($edit_dish) && $edit_dish['image']): ?>
                                <img src="../<?php echo $edit_dish['image']; ?>" alt="Preview" class="image-preview" id="image-preview">
                            <?php else: ?>
                                <img src="" alt="Preview" class="image-preview" id="image-preview" style="display: none;">
                            <?php endif; ?>
                        </div>
                        
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Допустимые форматы: JPG, PNG, GIF, WebP. Максимальный размер: 5MB.
                        </small>
                    </div>
                    
                    <button type="submit" class="btn"><?php echo isset($_GET['edit']) ? 'Обновить' : 'Добавить'; ?> блюдо</button>
                    <?php if (isset($_GET['edit'])): ?>
                        <a href="dishes.php" class="btn logout-btn">Отмена</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Список блюд -->
            <div class="dishes-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0;">Список блюд</h3>
                    <?php if ($sort_column != 'id' || $sort_order != 'desc'): ?>
                    <div class="sort-info">
                        Сортировка: 
                        <?php 
                        $column_names = [
                            'id' => 'ID',
                            'name' => 'Название',
                            'category_name' => 'Категория',
                            'price' => 'Цена',
                            'weight' => 'Вес',
                            'created_at' => 'Дата добавления'
                        ];
                        echo $column_names[$sort_column] . ' (' . ($sort_order == 'asc' ? 'по возрастанию' : 'по убыванию') . ')';
                        ?>
                        <a href="dishes.php" class="reset-sort">Сбросить</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <table class="dishes-table">
                    <thead>
                        <tr>
                            <th onclick="window.location.href='<?php echo getSortUrl('id', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    ID <?php echo getSortIcon('id', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th>Изображение</th>
                            <th onclick="window.location.href='<?php echo getSortUrl('name', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Название <?php echo getSortIcon('name', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('category_name', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Категория <?php echo getSortIcon('category_name', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('price', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Цена <?php echo getSortIcon('price', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('weight', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Вес <?php echo getSortIcon('weight', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th onclick="window.location.href='<?php echo getSortUrl('created_at', $sort_column, $sort_order); ?>'">
                                <div class="sortable-header">
                                    Дата добавления <?php echo getSortIcon('created_at', $sort_column, $sort_order); ?>
                                </div>
                            </th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dishes as $dish): ?>
                        <tr>
                            <td><?php echo $dish['id']; ?></td>
                            <td>
                                <?php if ($dish['image']): ?>
                                    <img src="../<?php echo $dish['image']; ?>" alt="<?php echo htmlspecialchars($dish['name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.8rem;">Нет фото</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($dish['name']); ?></td>
                            <td><?php echo htmlspecialchars($dish['category_name']); ?></td>
                            <td><?php echo $dish['price']; ?> ₽</td>
                            <td><?php echo $dish['weight']; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($dish['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="dishes.php?edit=<?php echo $dish['id']; ?>" class="btn-edit">Редактировать</a>
                                <a href="dishes.php?delete=<?php echo $dish['id']; ?>" class="btn-delete" 
                                   onclick="return confirm('Удалить это блюдо?')">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($dishes)): ?>
                <p style="text-align: center; padding: 2rem; color: #6c757d;">
                    Блюд пока нет. Добавьте первое блюдо!
                </p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const fileName = document.getElementById('file-name');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
                fileName.textContent = input.files[0].name;
            } else {
                preview.style.display = 'none';
                fileName.textContent = '';
            }
        }
        
        // Добавляем обработчики для клавиатуры
        document.addEventListener('DOMContentLoaded', function() {
            const sortableHeaders = document.querySelectorAll('.dishes-table th[onclick]');
            
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