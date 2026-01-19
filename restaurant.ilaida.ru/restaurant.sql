-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Окт 30 2025 г., 09:58
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `restaurant`
--

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Первые блюда', 'супы', '2025-10-22 10:32:37'),
(2, 'Мясные блюда', 'шашлыки, кебабы', '2025-10-22 10:33:14'),
(3, 'Рыбные блюда', 'рыба, морские продукты', '2025-10-22 10:33:38'),
(4, 'Овощные блюда', 'баялды, долма', '2025-10-22 10:34:01'),
(5, 'Несладкая выпечка', 'лепёшки, питы', '2025-10-22 10:34:15'),
(6, 'Закуски', 'холодные соусы/закуски', '2025-10-22 10:34:43'),
(7, 'Сладости', 'сладкие', '2025-10-22 10:35:07'),
(8, 'Безалкогольные напитки', 'чаи, кофе, айран', '2025-10-22 10:35:26'),
(9, 'Алкогольные напитки', 'холодные алкогольные напитки', '2025-10-22 10:35:58');

-- --------------------------------------------------------

--
-- Структура таблицы `dishes`
--

CREATE TABLE `dishes` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `dishes`
--

INSERT INTO `dishes` (`id`, `category_id`, `name`, `description`, `price`, `weight`, `image`, `is_available`, `created_at`) VALUES
(1, 1, 'Тархана', 'Традиционный турецкий суп из специальной высушенной смеси из муки, йогурта, красного перца, лука и помидоров', '150.00', '150', 'uploads/dishes/68f8bcb8ca0ee.png', 1, '2025-10-22 10:38:29'),
(2, 2, 'Адана-кебаб', 'Блюдо из мяса, основным его ингредиентом выступает мясной фарш, который прожаривают на мангале и подают вместе с рисом, овощами, зеленью и толстым лавашем.', '150.00', '150', 'uploads/dishes/68f8bcd5809de.png', 1, '2025-10-22 10:39:31'),
(3, 3, 'Балык-экмек', 'Блюдо из хлеба с рыбой, для приготовления используют обжаренное филе морского окуня либо дорадо, которое выкладывают на багет вместе с салатом, луком, помидорами и поливают лимоном', '150.00', '150', 'uploads/dishes/68f8bcf318dea.png', 1, '2025-10-22 10:40:30'),
(4, 4, 'Имам баялды', 'Овощное блюдо, представляющее собой баклажаны с овощной начинкой. Заправка для баклажанов готовится из лука, зеленого перца, томатов, чеснока и зелени, обильно заправленных специями и томатной пастой. Все это запекается в духовке и подается с хлебом и йогуртом.', '150.00', '150', 'uploads/dishes/68f8bcfe3fbdf.png', 1, '2025-10-22 10:41:23'),
(5, 5, 'Гёзлеме', 'Лепёшка из тончайшего теста, в которое заворачивают разные наполнители в виде фарша, картофеля, твердовато сыра и сыра лор (аналог творога).', '150.00', '150', 'uploads/dishes/68f8bd11b94ca.png', 1, '2025-10-22 10:42:43'),
(6, 6, 'Хайдари', 'Густой соус на основе йогурта и белого сыра, в смесь которых добавляют чеснок, оливковое масло, мяту и грецкий орех.', '150.00', '150', 'uploads/dishes/68f8bd2815c8f.png', 1, '2025-10-22 10:43:06'),
(7, 7, 'Пахлава', 'Сладость, которую готовят из слоеного теста, пропитывают медовым сиропом и дополняют разнообразными орехами. ', '150.00', '150', 'uploads/dishes/68f8bd3879963.png', 1, '2025-10-22 10:44:13'),
(8, 8, 'Айран', 'Кисломолочный продукт, изготавливается на основе йогурта с добавлением воды и соли и не подвергается процессу газификации', '150.00', '150', 'uploads/dishes/68f8bd3ed1d7e.png', 1, '2025-10-22 10:46:23'),
(9, 9, 'Ракы', 'Напиток на основе аниса, имеет специфический травянистый вкус и может отличаться разным содержанием алкоголя (от 40 до 50 % чистого спирта).', '150.00', '150', 'uploads/dishes/68f8bd43c66ea.png', 1, '2025-10-22 10:47:01'),
(11, 9, 'Новое название блюда', 'Новое описание', '1111.00', '1111', 'uploads/dishes/6900dd22a3151.png', 1, '2025-10-28 15:11:30');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_address` text,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','preparing','delivering','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `customer_phone`, `customer_address`, `total_amount`, `status`, `created_at`) VALUES
(1, 'ilaida', '89129822890', 'Пермь, ул.Кирова 20', '650.00', 'pending', '2025-10-23 06:53:37'),
(2, 'ilaida', '89129822890', 'Пермь, ул.Кирова 20', '650.00', 'delivering', '2025-10-23 06:53:40'),
(3, 'ilaida', '89129822890', 'Пермь, ул.Кирова 20', '650.00', 'completed', '2025-10-24 07:31:22'),
(4, 'ilaida', '89129822890', 'Пермь, ул.Кирова 20', '650.00', 'pending', '2025-10-24 15:24:24'),
(5, 'ilaida', '89129822890', 'Пермь, ул.Кирова 20', '650.00', 'completed', '2025-10-24 15:34:52'),
(6, 'ilaida', '89129822890', 'Пермь, ул.Кирова 20', '1461.00', 'completed', '2025-10-28 15:17:18');

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `dish_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `dish_id`, `quantity`, `price`) VALUES
(1, 1, 8, 1, '150.00'),
(2, 1, 2, 1, '150.00'),
(3, 1, 7, 1, '150.00'),
(4, 2, 8, 1, '150.00'),
(5, 2, 2, 1, '150.00'),
(6, 2, 7, 1, '150.00'),
(7, 3, 9, 1, '150.00'),
(8, 3, 8, 1, '150.00'),
(9, 3, 6, 1, '150.00'),
(10, 4, 9, 1, '150.00'),
(11, 4, 8, 1, '150.00'),
(12, 4, 6, 1, '150.00'),
(13, 5, 9, 1, '150.00'),
(14, 5, 8, 1, '150.00'),
(15, 5, 6, 1, '150.00'),
(16, 6, 11, 1, '1111.00'),
(17, 6, 9, 1, '150.00');

-- --------------------------------------------------------

--
-- Структура таблицы `reservations`
--

CREATE TABLE `reservations` (
  `id` int NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `reservation_date` date DEFAULT NULL,
  `reservation_time` time DEFAULT NULL,
  `guests` int DEFAULT NULL,
  `comment` text,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `reservations`
--

INSERT INTO `reservations` (`id`, `customer_name`, `customer_phone`, `customer_email`, `reservation_date`, `reservation_time`, `guests`, `comment`, `status`, `created_at`) VALUES
(1, 'Илайда Сезгин', '89129822890', 'ilaida.sezgin2006@gmail.com', '2025-10-23', '15:30:00', 7, 'Желательно столик возле окна и подальше от входа и туалетов', 'pending', '2025-10-23 06:19:49'),
(2, 'ilaida', '89129822890', 'ilaida.sezgin2006@gmail.com', '2025-10-25', '15:00:00', 9, 'аллергия на пыль', 'confirmed', '2025-10-24 15:35:30'),
(3, 'ilaida', '89129822890', 'ilaida.sezgin2006@gmail.com', '2025-10-28', '10:30:00', 3, 'ffff', 'pending', '2025-10-28 15:17:44');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'ilaida', 'ilaida.sezgin2006@gmail.com', '89129822890', '$2y$10$DAKFMKuCNGxa6dcWJCLiiez7g.8jLu0ShZgX9Nu2FbsEtTxinR.k6', 'user', '2025-10-22 13:15:09');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `dishes`
--
ALTER TABLE `dishes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `dish_id` (`dish_id`);

--
-- Индексы таблицы `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `dishes`
--
ALTER TABLE `dishes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `dishes`
--
ALTER TABLE `dishes`
  ADD CONSTRAINT `dishes_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`dish_id`) REFERENCES `dishes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
