-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.2
-- Время создания: Май 05 2025 г., 13:45
-- Версия сервера: 8.2.0
-- Версия PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `shop_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `bouquet_requests`
--

CREATE TABLE `bouquet_requests` (
  `request_id` int NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `postal_address` text NOT NULL,
  `contact_person_name` varchar(100) NOT NULL,
  `contact_phone` varchar(15) NOT NULL,
  `bouquet_cost` decimal(10,2) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `unp` varchar(50) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `bank_code` varchar(20) DEFAULT NULL,
  `estimated_requests_per_month` int DEFAULT NULL,
  `submission_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `callbacks`
--

CREATE TABLE `callbacks` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','processed','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cart`
--

CREATE TABLE `cart` (
  `cart_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `category_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`) VALUES
(1, 'Букеты из гипсофил', 'Красивые букеты из гипсофил'),
(2, 'Букеты из ромашек', 'Свежие букеты из ромашек'),
(3, 'Букеты из хризантем', 'Яркие букеты из хризантем'),
(4, 'Комнатные цветы в горшках', 'Комнатные растения в горшках'),
(5, 'Монобукеты', 'Букеты из одного вида цветов'),
(6, 'Сборные букеты', 'Букеты из разных видов цветов'),
(7, 'Букет на праздник', 'Праздничные букеты'),
(8, 'Композиции из цветов', 'Цветочные композиции'),
(9, 'Конверты', 'Конверты для денег и открыток'),
(10, 'Открытки', 'Поздравительные открытки'),
(11, 'Подарки', 'Дополнительные подарки'),
(12, 'Букеты из сухоцветов', 'Букеты из сухих цветов'),
(13, 'Шары', 'Воздушные шары'),
(14, 'Популярное', 'Популярные товары'),
(15, 'Букеты роз', 'Букеты из роз'),
(16, 'Цветы на похороны', 'Траурные букеты'),
(17, 'упаковка подарков', 'подарочная упаковка для ваших цветов');

-- --------------------------------------------------------

--
-- Структура таблицы `colors`
--

CREATE TABLE `colors` (
  `color_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `colors`
--

INSERT INTO `colors` (`color_id`, `name`, `code`) VALUES
(1, 'белый', '#FFFFFF'),
(2, 'желтый', '#FFFF00'),
(3, 'зеленый', '#00FF00'),
(4, 'красный', '#FF0000'),
(5, 'оранжевый', '#FFA500'),
(6, 'розовый', '#FFC0CB'),
(7, 'синий', '#0000FF');

-- --------------------------------------------------------

--
-- Структура таблицы `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `comment` text NOT NULL,
  `submission_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `formats`
--

CREATE TABLE `formats` (
  `format_id` int NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `formats`
--

INSERT INTO `formats` (`format_id`, `name`) VALUES
(1, 'букет'),
(2, 'в вазе'),
(3, 'в конверте'),
(4, 'в корзине'),
(5, 'в шляпной коробке'),
(6, 'в ящике');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `recipient_phone` varchar(20) DEFAULT NULL,
  `comment` text,
  `delivery_type` enum('pickup','courier') NOT NULL DEFAULT 'pickup',
  `city` varchar(50) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `building` varchar(20) DEFAULT NULL,
  `apartment` varchar(20) DEFAULT NULL,
  `delivery_time` varchar(50) DEFAULT NULL,
  `payment_method` enum('card','cash','apple_pay','google_pay','crypto','invoice') NOT NULL DEFAULT 'card',
  `promo_code` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('new','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `bouquet_composition` text,
  `description` text,
  `category_id` int DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_new` tinyint(1) DEFAULT '0',
  `is_popular` tinyint(1) DEFAULT '0',
  `views_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`product_id`, `name`, `price`, `bouquet_composition`, `description`, `category_id`, `tags`, `image_path`, `is_new`, `is_popular`, `views_count`, `created_at`) VALUES
(1, 'Лучший день', 167000.00, 'Розы, Гипсофила', 'Красивый букет из роз и гипсофилы', 1, 'букет, розы', 'img/image 131.png', 1, 0, 500, '2025-04-29 21:48:52'),
(2, 'Лучший день', 145000.00, 'Тюльпаны', 'Сочный букет из тюльпанов', 2, 'букет, тюльпаны', 'img/image 114(1).png', 0, 0, 0, '2025-04-29 21:48:52'),
(3, 'Лучший день', 167000.00, 'Хризантемы', 'Яркий букет из хризантем', 3, 'букет, хризантемы', 'img/image 130.png', 1, 1, 150, '2025-04-29 21:48:52'),
(4, 'Лучший день', 210000.00, 'Розы', 'Элегантный букет из роз', 1, 'букет, розы', 'img/image 131.png', 0, 1, 150, '2025-04-29 21:48:52'),
(5, 'Лучший день', 175000.00, 'Тюльпаны', 'Свежий букет из тюльпанов', 2, 'букет, тюльпаны', 'img/image 132.png', 0, 0, 0, '2025-04-29 21:48:52'),
(6, 'Лучший день', 167000.00, 'Хризантемы', 'Яркий букет из хризантем', 3, 'букет, хризантемы', 'img/image 133.png', 0, 0, 0, '2025-04-29 21:48:52'),
(7, 'Лучший день', 167000.00, 'Розы', 'Красивый букет из роз', 1, 'букет, розы', 'img/image 134.png', 0, 0, 0, '2025-04-29 21:48:52'),
(8, 'Лучший день', 145000.00, 'Тюльпаны', 'Сочный букет из тюльпанов', 2, 'букет, тюльпаны', 'img/image 135.png', 0, 0, 0, '2025-04-29 21:48:52'),
(9, 'Лучший день', 167000.00, 'Хризантемы', 'Яркий букет из хризантем', 3, 'букет, хризантемы', 'img/image 136.png', 0, 0, 0, '2025-04-29 21:48:52'),
(10, 'Лучший день', 155000.00, 'Хризантемы', 'Нежный букет из хризантем', 3, 'букет, хризантемы', 'img/image 137.png', 0, 0, 0, '2025-04-29 21:48:52'),
(11, 'Лучший день', 189000.00, 'Хризантемы', 'Популярный букет из хризантем', 3, 'букет, хризантемы', 'img/image 138.png', 0, 0, 0, '2025-04-29 21:48:52'),
(12, 'Лучший день', 167000.00, 'Розы', 'Элегантный букет из роз', 1, 'букет, розы', 'img/image 139.png', 0, 0, 0, '2025-04-29 21:48:52'),
(13, 'Лучший день', 145000.00, 'Тюльпаны', 'Свежий букет из тюльпанов', 2, 'букет, тюльпаны', 'img/image 140.png', 0, 0, 0, '2025-04-29 21:48:52'),
(14, 'Упаковка подарков', 16500.00, 'Упаковка ', 'Красивая упаковка ', 17, 'Упаковка', 'img/image 1(3).png', 0, 0, 0, '2025-04-29 21:48:52'),
(16, 'рубиновые искры', 16700.00, 'Состав: Гвоздика (Диантус), Леукодендрон, Леукоспермум (Нутан), Лотос, Роза', 'Завораживающая глубина ваших чувств передана огненными красками этого букета', 7, 'Букет начальнику, Мужские букеты', 'img/image 141.png', 0, 0, 0, '2025-04-29 21:48:52'),
(30, 'Лучший день', 210000.00, 'Розы', 'Красивый букет из роз', 1, 'букет, розы', 'img/image 114(1).png', 0, 0, 0, '2025-04-29 21:48:52');

-- --------------------------------------------------------

--
-- Структура таблицы `product_attributes`
--

CREATE TABLE `product_attributes` (
  `attribute_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('light','flower_type','other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `product_attributes`
--

INSERT INTO `product_attributes` (`attribute_id`, `name`, `type`) VALUES
(1, 'Нежные', 'light'),
(2, 'Яркие', 'light'),
(3, 'Альстромерия', 'flower_type'),
(4, 'Антуриум', 'flower_type'),
(5, 'Аспарагус', 'flower_type'),
(6, 'Астильба', 'flower_type'),
(7, 'Астранция', 'flower_type');

-- --------------------------------------------------------

--
-- Структура таблицы `product_attribute_values`
--

CREATE TABLE `product_attribute_values` (
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `product_attribute_values`
--

INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `value`) VALUES
(1, 1, 'Да'),
(1, 3, 'Альстромерия'),
(2, 2, 'Да'),
(3, 1, 'Да'),
(4, 2, 'Да'),
(5, 1, 'Да');

-- --------------------------------------------------------

--
-- Структура таблицы `product_colors`
--

CREATE TABLE `product_colors` (
  `product_id` int NOT NULL,
  `color_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `product_colors`
--

INSERT INTO `product_colors` (`product_id`, `color_id`) VALUES
(1, 1),
(3, 1),
(5, 1),
(2, 2),
(4, 3),
(2, 4),
(5, 4),
(2, 5),
(1, 6),
(3, 6),
(5, 6),
(4, 7);

-- --------------------------------------------------------

--
-- Структура таблицы `product_formats`
--

CREATE TABLE `product_formats` (
  `product_id` int NOT NULL,
  `format_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `product_formats`
--

INSERT INTO `product_formats` (`product_id`, `format_id`) VALUES
(1, 1),
(2, 1),
(5, 1),
(4, 2),
(3, 5);

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int DEFAULT NULL,
  `comment` text,
  `submission_date` datetime DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`review_id`, `product_id`, `name`, `email`, `rating`, `comment`, `submission_date`) VALUES
(3, 16, 'Иван', 'Ivan@gmail.com', 5, 'Лучшая доставка цветов в городе! Быстро доставили, привезли к нужному времени, а сам букет превзошел все ожидания!', '2025-04-28 22:14:54');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `bouquet_requests`
--
ALTER TABLE `bouquet_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Индексы таблицы `callbacks`
--
ALTER TABLE `callbacks`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Индексы таблицы `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`color_id`);

--
-- Индексы таблицы `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`);

--
-- Индексы таблицы `formats`
--
ALTER TABLE `formats`
  ADD PRIMARY KEY (`format_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Индексы таблицы `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`attribute_id`);

--
-- Индексы таблицы `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`product_id`,`attribute_id`),
  ADD KEY `attribute_id` (`attribute_id`);

--
-- Индексы таблицы `product_colors`
--
ALTER TABLE `product_colors`
  ADD PRIMARY KEY (`product_id`,`color_id`),
  ADD KEY `color_id` (`color_id`);

--
-- Индексы таблицы `product_formats`
--
ALTER TABLE `product_formats`
  ADD PRIMARY KEY (`product_id`,`format_id`),
  ADD KEY `format_id` (`format_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `bouquet_requests`
--
ALTER TABLE `bouquet_requests`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `callbacks`
--
ALTER TABLE `callbacks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `colors`
--
ALTER TABLE `colors`
  MODIFY `color_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT для таблицы `formats`
--
ALTER TABLE `formats`
  MODIFY `format_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT для таблицы `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `attribute_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Ограничения внешнего ключа таблицы `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD CONSTRAINT `product_attribute_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `product_attribute_values_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`attribute_id`);

--
-- Ограничения внешнего ключа таблицы `product_colors`
--
ALTER TABLE `product_colors`
  ADD CONSTRAINT `product_colors_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `product_colors_ibfk_2` FOREIGN KEY (`color_id`) REFERENCES `colors` (`color_id`);

--
-- Ограничения внешнего ключа таблицы `product_formats`
--
ALTER TABLE `product_formats`
  ADD CONSTRAINT `product_formats_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `product_formats_ibfk_2` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`);

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
