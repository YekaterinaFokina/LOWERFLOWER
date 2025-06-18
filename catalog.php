<?php
session_start();
include 'config.php';

// Обработка AJAX запросов для корзины (оставляем без изменений)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_to_cart':
            $productId = (int)$_POST['product_id'];
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] += $quantity;
            } else {
                $stmt = $conn->prepare("SELECT product_id, name, price, image_path FROM products WHERE product_id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $productInfo = $stmt->get_result()->fetch_assoc();
                
                if ($productInfo) {
                    $_SESSION['cart'][$productId] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'name' => $productInfo['name'],
                        'price' => $productInfo['price'],
                        'image' => $productInfo['image_path']
                    ];
                }
            }
            
            $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
            echo json_encode(['success' => true, 'cart_count' => $cartCount]);
            exit;
            
        case 'get_cart':
            $items = isset($_SESSION['cart']) ? array_values($_SESSION['cart']) : [];
            $total = 0;
            
            foreach ($items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            echo json_encode([
                'items' => $items,
                'total' => $total,
                'cart_count' => array_sum(array_column($items, 'quantity'))
            ]);
            exit;
            
        case 'update_cart':
            $productId = (int)$_POST['product_id'];
            $action = $_POST['action_type'];
            
            if (isset($_SESSION['cart'][$productId])) {
                switch ($action) {
                    case 'increase':
                        $_SESSION['cart'][$productId]['quantity']++;
                        break;
                    case 'decrease':
                        if ($_SESSION['cart'][$productId]['quantity'] > 1) {
                            $_SESSION['cart'][$productId]['quantity']--;
                        } else {
                            unset($_SESSION['cart'][$productId]);
                        }
                        break;
                    case 'remove':
                        unset($_SESSION['cart'][$productId]);
                        break;
                }
            }
            
            $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
            echo json_encode(['success' => true, 'cart_count' => $cartCount]);
            exit;
    }

}

// 1. Получаем параметры фильтрации из URL
$currentCategoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$selectedColors = isset($_GET['colors']) ? array_map('intval', (array)$_GET['colors']) : [];
$selectedFormats = isset($_GET['formats']) ? array_map('intval', (array)$_GET['formats']) : [];
$minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000; // Максимальная цена по умолчанию
$sortType = isset($_GET['sort']) ? $_GET['sort'] : 'popular';
$lightType = isset($_GET['light_type']) ? $_GET['light_type'] : null;

// 2. Получаем данные для фильтров
$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);
$colors = $conn->query("SELECT * FROM colors")->fetch_all(MYSQLI_ASSOC);
$formats = $conn->query("SELECT * FROM formats")->fetch_all(MYSQLI_ASSOC);

// 3. Формируем SQL запрос с учетом фильтров
$query = "SELECT p.*, c.name as category_name FROM products p 
          JOIN categories c ON p.category_id = c.category_id";

$where = [];
$params = [];
$types = "";

// Фильтр по категории
if ($currentCategoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $currentCategoryId;
    $types .= "i";
}

// Фильтр по цветам (исправлено - было неправильное получение параметров)
if (!empty($selectedColors)) {
    $placeholders = implode(',', array_fill(0, count($selectedColors), '?'));
    $where[] = "p.product_id IN (SELECT product_id FROM product_colors WHERE color_id IN ($placeholders))";
    $params = array_merge($params, $selectedColors);
    $types .= str_repeat('i', count($selectedColors));
}

// Фильтр по форматам (исправлено - аналогично цветам)
if (!empty($selectedFormats)) {
    $placeholders = implode(',', array_fill(0, count($selectedFormats), '?'));
    $where[] = "p.product_id IN (SELECT product_id FROM product_formats WHERE format_id IN ($placeholders))";
    $params = array_merge($params, $selectedFormats);
    $types .= str_repeat('i', count($selectedFormats));
}

// Фильтр по цене (исправлены границы)
if ($minPrice > 0 || $maxPrice < 40000) {
    $where[] = "p.price BETWEEN ? AND ?";
    $params[] = $minPrice;
    $params[] = $maxPrice;
    $types .= "ii";
}

// Фильтр по типу света (добавлен новый фильтр)
if ($lightType) {
    $where[] = "p.light_type = ?";
    $params[] = $lightType;
    $types .= "s";
}

// Собираем WHERE часть
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Сортировка (исправлено - добавлены все варианты)
switch ($sortType) {
    case 'price-asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price-desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'new':
        $query .= " ORDER BY p.is_new DESC, p.created_at DESC";
        break;
    case 'popular':
        $query .= " ORDER BY p.is_popular DESC, p.views_count DESC";
        break;
    default:
        $query .= " ORDER BY p.product_id DESC";
}

// Выполняем запрос
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
// Подключение попапа заказа звонка
include 'callback_popup.php';
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Oswald:wght@200..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>КАТАЛОГ</title>
</head>
    <style>
        :root{
        --black: #040A0A;
        --blue: #43FFD2;
        --red: #7D2253;
        --pink: #D978AC;
        --white: #FFFFFF;
        --border:.2rem solid var(--black);
        --box-shadow:0 .5rem 1rem rgba(0,0,0,.1);
        }
    body {
        position: relative;
        min-height: 100vh;
        
        background-color: var(--black);
        font-family: 'Cormorant';
        font-size: 17px;
        color: var(--white);
    }
    .logo {
        width: 34px;
        height: 75px;
        top: -7px;
        left: 165px;
    }
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
    }

    .fixed-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background-color: var(--black);
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        transform: translateY(-100%);
        transition: transform 0.3s ease;
    }

    .fixed-header.visible {
        transform: translateY(0);
    }

    .fixed-header .logo img {
        width: 30px;
        height: 65px;
    }

    .fixed-header nav ul {
        gap: 15px;
    }

    .fixed-header .contact-info2 {
        position: static;
        display: flex;
        gap: 15px;
    }

    .fixed-header .contact-info2 p {
        margin: 0;
        font-size: 14px;
    }

    .fixed-header .contact-info2 img {
        width: 25px;
        height: 25px;
    }


    
    .header-bg-left {
        position: absolute;
        top: 100px;
        right: 0;
        width: 1073px;
        height: 1073px;
        background-image: url('img/image 128.png');
        background-size: contain;
        background-repeat: no-repeat;
        z-index: -1;
        opacity: 0.7;
        transform: rotate(180deg);
    }

    .header-bg-right {
        position: absolute;
        top: 0;
        left: 0;
        width: 1073px;
        height: 717px;
        background-image: url('img/image 128.png');
        background-size: contain;
        background-repeat: no-repeat;
        z-index: -1;
        opacity: 0.7;
    }

    .header-ellipse-top-left {
        position: absolute;
        top: -100px;
        right: 0;
        width: 700px;
        height: 700px;
        background-image: url('img/Ellipse 52.png');
        background-size: contain;
        background-repeat: no-repeat;
        z-index: -2;
    }


    .header-ellipse-center {
        position: absolute;
        top: 800px;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 600px;
        height: 600px;
        background-image: url('img/Ellipse 52.png');
        background-size: contain;
        background-repeat: no-repeat;
        z-index: -2;
        opacity: 0.5;
    }
    .logo {
        margin-left: 5rem;
        font-size: 24px;
        font-weight: bold;
    }

    nav ul {
        list-style: none;
        display: flex;
        gap: 17px;
        margin: 0;
    }
    nav a {
        color: #f0f0f0;
        font-size: 14px;
        text-decoration: none;
        font-family: 'Oswald';
    }
    nav a img{
        width: 18px;
        height: 18px;
        padding-right: 12px;
    }
    .contact-info {
        font-family: 'Oswald';
        color: #f0f0f0;
        text-align: right;
        font-weight: 300;
        font-size: 10px;
        line-height: 1.5;
        letter-spacing: 10%;
        text-transform: uppercase;
    }
    .contact-info span{
        font-weight: 400;
        font-size: 14px;
        color: var(--blue);
    }

    .contact-info1 {
        font-family: 'Oswald';
        font-weight: 400;
        line-height: 1; 
        letter-spacing: 10%;
        text-transform: uppercase;
        font-size: 14px;
        color: var(--blue);
        position: absolute;
        top: 350px; 
        right: 5%; 
        padding: 20px; 
        border-radius: 15px; 
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px); 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        gap: 20px; 
    }

    .icon {
        width: 40px; 
        height: 40px; 
        transition: transform 0.3s; 
    }

    .icon:hover {
        transform: scale(1.1);
    }

    .contact-info2 {
        font-family: 'Oswald';
        font-weight: 400;
        line-height: 2; 
        letter-spacing: 10%;
        text-transform: uppercase;
        font-size: 16px;
        color: var(--blue);
        position: absolute;
        top: 550px; 
        right: 10%;
        display: flex; 
        flex-direction: column; /* Вертикальное расположение */
        align-items: center; 
    }

    .contact-info2 img {
        width: 20px; 
        height: 20px; 
        margin-right: 5px; 
    }
    .contact-info2 span {
        padding: 7px; 
        color: var(--white); 
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px); 
        display: flex; 
        cursor: pointer;
        align-items: center; 
        flex-direction: column; /* Вертикальное расположение текста */
    }

    .contact-info2p {
            font-family: 'Oswald';
            font-weight: 400;
            line-height: 1; /* Изменено с 2 на 1 */
            letter-spacing: 10%;
            text-transform: uppercase;
            font-size: 16px;
            color: var(--blue);
            display: flex;
            gap: 15px; /* Промежуток между элементами */
            align-items: center; /* Выравнивание по центру по вертикали */
            /* Убрано flex-direction: column */
        }


        .contact-info2p img {
            width: 20px; 
            height: 20px; 
            margin-right: 5px; 
        }
        .contact-info2p span {
            padding: 7px; 
            color: var(--white); 
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px); 
            display: flex; 
            align-items: center; 
            flex-direction: column; /* Вертикальное расположение текста */
        }
    nav a:hover{
        color: var(--blue);
        text-decoration: underline;
    }
    /* Styles for catalog block */
    .catalog-block {
        width: 950px;
        height: 534px;
        margin-left: 99px;
        border-radius: 20px;
        color: var(--white);
        backdrop-filter: blur(20px);
        background: #0000004D;
        text-align: center;
        padding: 20px;
    }
    .subtitle {
        margin-left: 165px;
        font-family: 'Oswald';
        font-weight: 400;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
    }
    .subtitle a {
        color: var(--white);
        text-decoration: none;
    }
    .subtitle a:hover{
        color: var(--blue);
        text-decoration: underline;
    }
    
    .catalog-block h2 {
        font-family: 'Cormorant';
        font-weight: 400;
        font-size: 100px;
        color: var(--white);
        margin-top: 20px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        margin-bottom: 20px;
        margin-left: -500px;
    }
    .catalog-block h2 span {
        margin-left: 500px;
    }
    .catalog-block p {
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 14px;
        
        color: var(--white);
        letter-spacing: 0.04em;
        line-height: 1.5;
        margin-bottom: 30px;
        text-transform: uppercase;
    }
    .categories {
        display: flex;
        flex-wrap: wrap;
        justify-content: left;
    }
    .categories a {
        display: inline-block;
        padding: 10px 20px;
        margin: 5px;
        border-radius: 40px;
        color: var(--white);
        text-decoration: none;
        font-family: 'Oswald';
        font-weight: 400;
        border: 0.5px solid #FFFFFF;
        font-size: 12px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;

        
    }
     /* Новые стили для блока фильтров и товаров */
     .catalog-container {
            display: flex;
            margin: 50px 16px;
            gap: 30px;
        }

        .filters {
            width: 300px;
            height: 1050px;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin-top: 70px;
        }

        .filter-section {
            margin-bottom: 30px;
        }

        .filter-section h3 {
            font-family: 'Oswald';
            color: var(--blue);
            margin-bottom: 15px;
            font-weight: 700;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-options label {
            display: flex;
            align-items: center;
            font-family: 'Oswald';
            cursor: pointer;
            font-weight: 300;
            font-size: 12px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }

    .filter-options input[type="checkbox"] {
            margin-right: 10px;
            width: 16px;
            height: 16px;
            accent-color: var(--blue);
    }

    .price-slider {
        margin-top: 15px;
    }

    .slider-container {
        color: var(--blue);
        position: relative;
        height: 40px;
        display: flex;
        align-items: center;
    }

    .slider {
        -webkit-appearance: none;
        width: 100%;
        height: 4px;
        background: #43FFD2;
        outline: none;
        position: absolute;
    }

    .slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #43FFD2;
        cursor: pointer;
        z-index: 2;
    }

    .slider::-moz-range-thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #43FFD2;
        cursor: pointer;
        z-index: 2;
    }

    .price-values {
        margin-top: 10px;
        font-family: 'Oswald';
        font-size: 14px;
        color: var(--white);
        display: flex;
        justify-content: space-between;
    }

        .products-section {
            flex: 1;
        }

        .sort-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .sort-options select {
            margin-left: 700px;
            padding: 8px 15px;
            background: rgb(0 0 0);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            color: var(--white);
            font-family: 'Oswald';
            font-size: 14px;
            font-weight: 300;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }
        

        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Фиксированное количество колонок - 3 */
            gap: 20px;
        }

        /* Стили для карточек товаров (аналогичные предыдущим) */
        .product-card {
            position: relative;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            border-radius: 10px;
            padding: 15px;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-card img {
            width: 255px; /* Занимает всю ширину карточки */
            height: 335px;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .new-badge1 {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: var(--pink);
            color: var(--black);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Oswald';
            font-size: 14px;
        }


    .product-card h3 {
        font-family: 'Oswald';
        color: var(--white);
        font-weight: 400;
        font-size: 20px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        text-align: left;
        
    }

    .product-card p {
        font-family: 'Oswald'; /* Укажите желаемый шрифт */
        color: var(--white);
        margin: 10px 0; /* Отступ сверху и снизу */
        margin-bottom: 50px;
        font-weight: 700;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        text-align: left; /* Центрируем текст */
    }
    .product-card p span {
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        text-decoration: line-through;

    }

    .product-buttons {
        display: flex;
        gap: 10px; /* Расстояние между кнопками */
        margin-top: 10px;
    }

    .product-button {
        flex: 1; /* Равномерное распределение ширины */
        display: inline-block;
        padding: 10px 0;
        text-align: center;
        background-color: transparent;
        border: 1px solid var(--white);
        color: var(--white);
        text-decoration: none;
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 14px;
        text-transform: uppercase;
        transition: all 0.3s;
        cursor: pointer;
    }

    .product-button:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Стиль для кнопки "В корзину" при наведении */
    .add-to-cart:hover {
        background-color: rgba(255, 255, 255, 0.1); /* Легкий голубой оттенок */
    }

    .product-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
    }
    .quantity-btn {
                background: none; /* Убираем фон */
                border: none; /* Убираем рамку */
                padding: 0; /* Убираем отступы */
                color: var(--white); /* Цвет текста */
                font-size: 'Oswald';
                margin-left: 15px;
                margin-right: 10px;
                font-size: 24px; /* Установите нужный размер текста */
                cursor: pointer; /* Указатель при наведении */
            }
            .quantity-input {
                padding: 20px 0;
                background: none; /* Убираем фон */
                border: none; /* Убираем рамку */
                margin-left: 15px;
                color: var(--white); /* Цвет текста */
                font-size: 'Oswald';
                text-align: center;
                font-size: 14px; /* Установите нужный размер текста */
            }
    


    .catalog-bg-3 {
        position: absolute;
        top: 1000px;
        right: 200px;
        width: 250px;
        height: 250px;
        background-image: url('img/Ellipse 52.png');
        background-size: contain;
        background-repeat: no-repeat;
        z-index: -1;
        
        transform: rotate(45deg);
    }

    .catalog-bg-4 {
        position: absolute;
        top: 2000px;
        left: 0%;
        width: 350px;
        height: 350px;
        background-image: url('img/Ellipse 32(2).png');
        background-size: contain;
        background-repeat: no-repeat;
        z-index: -1;
        
        
    }

    .catalog-bg-5 {
        position: absolute;
        top: 1500px;
        left: 10%;
        width: 500px;
        height: 500px;
        background-image: url('img/Ellipse 52.png');
        background-size: contain;
        background-repeat: no-repeat;
        z-index: -1;
        
        transform: rotate(-15deg);
    }


    

    /* Social Networks Section Styles */

    .social-ellipse41 {
        position: absolute;
        bottom: 100px;
        right: -50px;
        z-index: 1;
        width: 200px;
    }

    .social-ellipse42 {
        position: absolute;
        bottom: -90px;
        left: -100px;
        z-index: 1;
        transform: rotate(45deg);
        width: 400px;
    }

    .social-section {
        position: relative;
        width: 100%;
        height: 766px; /* Установите нужную высоту */
        overflow: hidden; /* Чтобы скрыть части изображений, выходящие за пределы */
    }

    .social-gallery {
        display: flex;
        justify-content: flex-start; /* Слева */
        gap: 20px; /* Расстояние между изображениями */
        margin-top: 300px; /* Регулируйте отступ сверху как необходимо */
        margin-left: 200px; /* Сдвинуть на 100px вправо */
    }

    .social-gallery-image {
        width: 160px; /* Установите ширину изображений */
    }

    .social-instagram1 {
        display: block;
        margin: 0px; /* Центрируем изображение по горизонтали */
    }

    
    


    .footer-section {
        background-color: var(--black);
        padding: 50px 40px;
        font-family: 'Oswald';
        color: var(--white);
        
        margin-top: auto;
        z-index: 10;
    }

    .footer-container {
        display: flex;
        justify-content: space-between;
    }
    .footer-container h3{
        color: var(--blue);
        font-size: 14px;
    }
    .footer-container p {
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 12px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;

    }
    
    .footer-left,
    .footer-center,
    .footer-right {
        flex: 1;
        padding: 0 20px;
    }


    /* Left Section */
    .footer-left {
        display: flex;
        flex-direction: column;
    }

    .footer-logo {
        width: 34px;
        height: 75px;

    }

    .requisites {
        margin-top: 20px;
    }
    .requisites h3 {
        font-family: 'Oswald';
        font-weight: 700;
        color: var(--blue);
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;

    }
    .requisites p {
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 12px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
    }

     /* Center Section */
     .footer-center {
        display: flex;
        justify-content: space-around;
    }

    .catalog,
    .bouquet {
        flex: 1;
    }

    .catalog-title,
    .bouquet-title,
    .delivery-title,
    .contact-title {
        text-transform: uppercase;
    }

    .catalog ul,
    .bouquet ul,
    .delivery ul {
        list-style: none;
        padding: 0;
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 12px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        

    }
    .catalog li,
    .bouquet li,
    .delivery li {
        margin-bottom: 10px;
        
    }




    /* Right Section */
    .footer-right {
            display: flex;
            justify-content: space-between;
        }
        
        .delivery {
            z-index: 10;
        }
        
        .delivery a {
            margin-top: 20px;
            display: flex;
            padding: 0;
            margin-bottom: 10px;
            text-decoration: none;
            font-family: 'Oswald';
            color: var(--blue);
            font-weight: 700;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
        }
        
        .delivery a:hover {
            color: var(--blue);
            text-decoration: underline;
        }
        
        .contact-info12 {
            text-align: right;
            font-family: 'Oswald';
            font-weight: 400;
            line-height: 1;
            letter-spacing: 10%;
            text-transform: uppercase;
            font-size: 14px;
            color: var(--blue);
        }
        
        .contact-info12 p {
            margin: 5px 0;
        }
        
        .social-icons {
            margin-top: 20px;
            text-align: right;
        }
        
        .social-icons a {
            display: inline-block;
            transition: transform 0.3s;
            margin-left: 10px;
        }
        
        .social-icons a:hover {
            transform: scale(1.1);
        }
        
        .social-icons a img {
            width: 50px;
            height: 50px;
        }


    .remove-item {
        margin-left: auto;
        color: var(--white);
        cursor: pointer;
    }
    /* Кнопка "Наверх" */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background-color: rgba(0, 0, 0, 0.2); /* #00000033 - черный с 20% прозрачностью */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        border: 1px solid #43FFD2;
    }

    .back-to-top.visible {
        opacity: 1;
        visibility: visible;
    }

    .back-to-top::after {
        content: "";
        width: 20px;
        height: 20px;
        border-top: 2px solid #43FFD2;
        border-right: 2px solid #43FFD2;
        transform: rotate(-45deg);
        margin-bottom: 5px;
    }
     /* Стили для корзины */
     .cart-icon-container {
        position: relative;
        cursor: pointer;
    }

    .cart-count {
        position: absolute;
        top: -10px;
        right: -10px;
        background-color: var(--pink);
        color: var(--black);
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
    }

    .cart-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9998;
        display: none;
    }

    .cart-popup {
        position: fixed;
        top: 0;
        right: -400px;
        width: 400px;
        height: 100%;
        background-color: var(--black);
        z-index: 9999;
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
        border-left: 1px solid var(--blue);
        overflow-y: auto;
    }

    .cart-popup.active {
        right: 0;
    }

    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 20px;
        border-bottom: 1px solid var(--blue);
    }
    .cart-header {
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 20px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        color: var(--blue);
    }
    


    .close-cart {
        background: none;
        border: none;
        color: var(--white);
        font-size: 24px;
        cursor: pointer;
    }

    .cart-items {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }

    .cart-item {
        display: flex;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .cart-item img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        margin-right: 15px;
    }

    .cart-item-info {
        flex: 1;
    }

    .cart-item-title {
        font-family: 'Oswald';
        font-weight: 400;
        font-size: 16px;
        margin-bottom: 5px;
    }

    .cart-item-price {
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .cart-item-actions {
        display: flex;
        align-items: center;
    }

    .cart-item-quantity {
        margin: 0 5px;
        font-size: 22px;
    }

    .cart-action {
        margin-left: 20px;
        color: var(--pink);
        cursor: pointer;
    }

    .cart-footer {
        padding: 20px;
        border-top: 1px solid var(--blue);
    }

    .cart-total {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 2%;
        color: var(--blue);
        text-transform: uppercase;

    }

    .checkout-btn {
        width: 100%;
        padding: 13px 115px;
        background-color: transparent;
        color: var(--white);
        border: none;
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        border: 1px solid #fff;
        text-transform: uppercase;
    }

    .header-bg-left, .header-bg-right, 
    .header-ellipse-top-left, .header-ellipse-bottom-right,
     .catalog-bg-3,
    .catalog-bg-4, .catalog-bg-5 {
        z-index: -1;
    }

    .selected-category {
        font-family: 'Oswald';
        font-size: 16px;
        color: var(--blue);
        padding: 10px 15px;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        text-transform: uppercase;
    }

    .close-category {
        font-size: 50px;
        color: var(--white);
        transition: color 0.3s;
    }

    .close-category:hover {
        color: var(--pink);
    }

    .loading, .no-products {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px;
        font-family: 'Oswald';
        color: var(--white);
    }

    .toast {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 24px;
        background-color: var(--blue);
        color: var(--black);
        border-radius: 4px;
        font-family: 'Oswald';
        z-index: 10000;
        animation: fadeIn 0.3s;
    }

    .toast.error {
        background-color: var(--pink);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateX(-50%) translateY(20px); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
            /* Стили только для попапа заказа звонка */
.callback-popup-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    display: none;
    justify-content: center;
    align-items: center;
}

.callback-popup-wrapper .popup-content {
    background-color: rgb(10, 20, 20);
    padding: 30px;
    border: 1px solid var(--blue);
    width: 460px;
    position: relative;
}

.callback-popup-wrapper .popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.callback-popup-wrapper .popup-header h3 {
    font-family: 'Cormorant';
    color: var(--white);
    margin: 0;
    font-weight: 400;
    font-size: 40px;
    line-height: 100%;
    letter-spacing: 2%;
    text-transform: uppercase;
}

.callback-popup-wrapper .close-popup {
    font-size: 50px;
    color: var(--blue);
    cursor: pointer;
    transition: color 0.3s;
    line-height: 0.5;
    background: none;
    border: none;
    padding: 0;
}

.callback-popup-wrapper .close-popup:hover {
    color: var(--pink);
}

.callback-popup-wrapper .popup-line {
    width: 100px;
    height: 1px;
    background-color: var(--blue);
    margin-bottom: 20px;
}

.callback-popup-wrapper .popup-text {
    color: var(--white);
    margin-bottom: 20px;
    font-family: 'Oswald';
    font-weight: 400;
    font-size: 14px;
    line-height: 140%;
    letter-spacing: 4%;
    text-transform: uppercase;
}

.callback-popup-wrapper .callback-popup-form .form-group {
    margin-bottom: 15px;
}

.callback-popup-wrapper .callback-popup-form .form-group input {
    width: 93%;
    padding: 13px;
    background-color: transparent;
    border: 1px solid var(--blue);
    color: var(--white);
    font-family: 'Oswald';
    font-size: 14px;
    text-transform: uppercase;
}

.callback-popup-wrapper .callback-popup-form .form-group input::placeholder {
    color: rgba(255, 255, 255, 0.5);
    opacity: 1;
}

.callback-popup-wrapper .callback-popup-form .submit-btn {
    width: 255px;
    padding: 12px;
    background-color: var(--blue);
    color: var(--black);
    border: none;
    font-family: 'Oswald';
    font-weight: bold;
    cursor: pointer;
    text-transform: uppercase;
    transition: background-color 0.3s;
    font-size: 14px;
    margin-top: 10px;
}

.callback-popup-wrapper .callback-popup-form .submit-btn:hover {
    background-color: var(--pink);
    color: var(--white);
}

.callback-popup-wrapper .popup-agreement {
    font-family: 'Oswald';
    color: var(--white);
    margin-top: 15px;
    font-weight: 400;
    font-size: 10px;
    line-height: 140%;
    letter-spacing: 2%;
}

.callback-popup-wrapper .popup-agreement span {
    color: var(--pink);
    text-decoration: underline;
    cursor: pointer;
}

/* Адаптивность */
@media (max-width: 768px) {
    .callback-popup-wrapper .popup-content {
        width: 90%;
        padding: 20px;
    }
    
    .callback-popup-wrapper .popup-header h3 {
        font-size: 30px;
    }
    
    .callback-popup-wrapper .callback-popup-form .submit-btn {
        width: 100%;
    }
}
.contact-info2p p.callback-trigger:hover {
    cursor: pointer;
}
    </style>
<body>
<header>
        <div class="header-bg-left"></div>
        <div class="header-bg-right"></div>
        <div class="header-ellipse-top-left"></div>
        <div class="header-ellipse-bottom-right"></div>
        <div class="header-ellipse-center"></div>
        <div class="logo"><img src="img/logo.png" alt=""></div>
        <nav>
            <ul>
                <li><a href="catalog.php">КАТАЛОГ</a></li>
                <li><a href="order_pay.php">ДОСТАВКА И ОПЛАТА</a></li>
                <li><a href="about.php">О НАС</a></li>
                <li><a href="contact.php">КОНТАКТЫ</a></li>
                <li><a href="FAQ.php">FAQ</a></li>
                <li><a href="search.php"><img src="img/ph_magnifying-glass-thin.png">ПОИСК</a></li>
            </ul>
        </nav>
        <div class="contact-info">
            <p><span>zakaz@loverflower.by</span><br> Доставка 24/7 по договоренности с оператором <br><br><span>ул. Тимирязева 67</span><br> 10:00 до 21:00 без выходных</p>
        </div>
        <div class="contact-info1">
            <a href="contact.php">
                <img src="img/insagram.png" alt="Instagram" class="icon">
            </a>
            <a href="contact.php">
                <img src="img/whatsapp.png" alt="WhatsApp" class="icon">
            </a>
            <a href="contact.php">
                <img src="img/phone.png" alt="Телефон" class="icon">
            </a>
        </div>
        <div class="contact-info2">
            <p>+375 (29) 113-69-69<br><span>ЗАКАЗАТЬ ЗВОНОК</span></p>
            <div class="cart-icon-container">
                <img src="img/ph_handbag-thin.png" class="cart-icon" style="height: 60px; width: 60px;" alt="Корзина">
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </div>
        </div>
        </header>
        <header class="fixed-header">
            <div class="logo"><img src="img/logo.png" alt=""></div>
            <nav>
                <ul>
                    <li><a href="catalog.php">КАТАЛОГ</a></li>
                    <li><a href="order_pay.php">ДОСТАВКА И ОПЛАТА</a></li>
                    <li><a href="about.php">О НАС</a></li>
                    <li><a href="contact.php">КОНТАКТЫ</a></li>
                    <li><a href="FAQ.php">FAQ</a></li>
                    <li><a href="search.php"><img src="img/ph_magnifying-glass-thin.png">ПОИСК</a></li>
                </ul>
            </nav>
            <div class="contact-info2p">
                <p class="callback-trigger" style="cursor: pointer;">+375 (29) 113-69-69</p>
                <div class="cart-icon-container">
                    <img src="img/ph_handbag-thin.png" class="cart-icon" style="height: 50px; width: 50px;" alt="Корзина">
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </div>
            </div>
        </header>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const header = document.querySelector('header:not(.fixed-header)');
                const fixedHeader = document.querySelector('.fixed-header');
                const headerHeight = header.offsetHeight;
                const fixedHeaderHeight = fixedHeader.offsetHeight;
                
                function updateFixedHeader() {
                    if (window.pageYOffset > headerHeight) {
                        fixedHeader.classList.add('visible');
                        document.body.classList.add('fixed-header-visible');
                    } else {
                        fixedHeader.classList.remove('visible');
                        document.body.classList.remove('fixed-header-visible');
                    }
                }
                
                window.addEventListener('scroll', updateFixedHeader);
                updateFixedHeader(); // Инициализация при загрузке
            });
        </script>

    <section class="catalog">
        <p class="subtitle"><a href="index.php">Главная</a> / Каталог</p>
        <div class="catalog-block">
            <h2>Каталог<br><span>Букетов</span></h2>
            <p>В нашем магазине самый большой выбор букетов для любых<br> событий:</p>
            <div class="categories">
                <?php foreach ($categories as $category): ?>
                    <a href="catalog.php?category=<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
      <!-- Новый блок с фильтрами и товарами -->
      <div class="catalog-container">
        
        
        <div class="catalog-bg-3"></div>
        <div class="catalog-bg-4"></div>
        <div class="catalog-bg-5"></div>
            <!-- Блок фильтров -->
            <div class="filters">
                <div class="filter-section">
                    <h3>По свету</h3>
                    <div class="filter-options">
                        <label><input type="checkbox"> Нежные</label>
                        <label><input type="checkbox"> Яркие</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>по Цвету</h3>
                    <div class="filter-options">
                        <label><input type="checkbox" name="color" value="1">белый</label>
                        <label><input type="checkbox" name="color" value="2">желтый</label>
                        <label><input type="checkbox" name="color" value="3">зеленый</label>
                        <label><input type="checkbox" name="color" value="4">красный</label>
                        <label><input type="checkbox" name="color" value="5">оранжевый</label>
                        <label><input type="checkbox" name="color" value="6">розовый</label>
                        <label><input type="checkbox" name="color" value="7">синий</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>по формату</h3>
                    <div class="filter-options">
                        <label><input type="checkbox" name="format" value="1">букет</label>
                        <label><input type="checkbox" name="format" value="2">в вазе</label>
                        <label><input type="checkbox" name="format" value="3">в конверте</label>
                        <label><input type="checkbox" name="format" value="4">в корзине</label>
                        <label><input type="checkbox" name="format" value="5">в шляпной коробке</label>
                        <label><input type="checkbox" name="format" value="6">в ящике</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Стоимость</h3>
                    <div class="price-slider">
                    <div class="slider-container">
                        <input type="range" min="0" max="1000" value="500" class="slider" id="price-min">
                        <input type="range" min="0" max="1000000" value="1000000" class="slider" id="price-max">
                    </div>
                    <div class="price-values">
                        <span id="min-price-value">500</span> руб. - 
                        <span id="max-price-value">1000000</span> руб.
                        </div>
                    </div>
                </div>
                    

                <div class="filter-section">
                    <h3>По цветку</h3>
                    <div class="filter-options">
                        <label><input type="checkbox">альстромерия (2)</label>
                        <label><input type="checkbox">Антуриум (1)</label>
                        <label><input type="checkbox">Аспарагус (1)</label>
                        <label><input type="checkbox">астильба (7)</label>
                        <label><input type="checkbox">астранция (1)</label>
                    </div>
                </div>

                <button id="reset-filters" style="text-align: center; cursor: pointer; width: 255px; height: 40px; background-color: transparent; border: 1px solid var(--white); color: var(--white); text-decoration: none; font-family: 'Oswald'; font-weight: 700; font-size: 14px; text-transform: uppercase;">Сбросить фильтр</button>
                
            </div>

            <!-- Блок товаров -->
            <div class="products-section">
                <div class="sort-options">
                    
                    <select>
                        <option value="popular">По популярности</option>
                        <option value="price-asc">По возрастанию цены</option>
                        <option value="price-desc">По убыванию цены</option>
                        <option value="new">Новинки</option>
                    </select>
                </div>
                
                <div class="back-to-top" id="backToTop"></div>
            <script>
                // Кнопка "Наверх"
                const backToTopButton = document.getElementById('backToTop');
                
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        backToTopButton.classList.add('visible');
                    } else {
                        backToTopButton.classList.remove('visible');
                    }
                });
                
                backToTopButton.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            </script>
                <div class="selected-category" id="selectedCategory" style="display: none;">
                    <span id="selectedCategoryName"></span>
                    <span class="close-category" style="margin-left: 10px; cursor: pointer;">&times;</span>
                </div>
                <script>
                // Обновленный код для обработки выбора категории
                document.addEventListener('DOMContentLoaded', function() {
                    // Проверяем, есть ли выбранная категория в URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const categoryId = urlParams.get('category');
                    
                    if (categoryId) {
                        // Находим категорию в массиве PHP-переменной $categories
                        const category = <?php echo json_encode($categories); ?>.find(c => c.category_id == categoryId);
                        
                        if (category) {
                            showSelectedCategory(category.name);
                        }
                    }
                    
                    // Обработчик клика на крестик
                    document.querySelector('.close-category').addEventListener('click', function() {
                        // Удаляем параметр категории из URL
                        const url = new URL(window.location.href);
                        url.searchParams.delete('category');
                        window.location.href = url.toString();
                    });
                });

                function showSelectedCategory(categoryName) {
                    const selectedCategoryDiv = document.getElementById('selectedCategory');
                    const selectedCategoryName = document.getElementById('selectedCategoryName');
                    
                    selectedCategoryName.textContent = categoryName;
                    selectedCategoryDiv.style.display = 'inline-flex';
                }
                </script>

                <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                    <?php if (isset($product['is_new']) && $product['is_new']): ?>
                        <div class="new-badge1">NEW</div>
                    <?php elseif (isset($product['is_hit']) && $product['is_hit']): ?>
                        <div class="new-badge1">HIT</div>
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($product['name'] ?? ''); ?></h3>
                    <p>
                        <?php echo number_format($product['price'] ?? 0, 0, '', '.'); ?> руб.
                        <?php if (isset($product['old_price']) && $product['old_price'] > 0): ?>
                            <span><?php echo number_format($product['old_price'], 0, '', '.'); ?> ₽</span>
                        <?php endif; ?>
                    </p>
                    <div class="product-buttons">
                    <a href="product.php?id=<?php echo $product['product_id'] ?? ''; ?>" class="product-button">Подробнее</a>
                    <button class="product-button add-to-cart" data-product-id="<?php echo $product['product_id'] ?? ''; ?>">В корзину</button>
                </div>
                </div>
            <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

   <!-- Footer Section -->
<section class="footer-section">
                <div class="footer-container">
                    <div class="footer-left">
                        <img src="img/logo.png" alt="Логотип" class="footer-logo">
                        <div class="requisites">
                            <h3>реквизиты</h3>
                            <p>ООО «ЛОВЕФЛОВЕ» 220035, Республика<br> Беларусь, г. Минск, ул. Тимирязева Д. 67<br> комн. 112 (ПОМ.11) УНП 193263781, Р/С<br> BY55MTBK30120001093300096372 ЗАО<br> «МТБанк», БИК MTBKBY22 220007, г. Минск,<br> улица Толстого</p>
                        </div>
                    </div>
        
                    <div class="footer-center">
                        <div class="catalog">
                            <h3 class="catalog-title">Каталог</h3>
                            <ul>
                                <li>Популярное</li>
                                <li>Сухоцветы</li>
                                <li>Букеты роз</li>
                                <li>Композиции из цветов</li>
                                <li>Индивидуальный букет</li>
                                <li>Букет на праздник</li>
                                <li>Упаковка подарков</li>
                                <li>Шары</li>
                                <li>Открытки</li>
                                <li>Конверты</li>
                            </ul>
                        </div>
                        <div class="bouquet">
                            <h3 class="bouquet-title">Букет</h3>
                            <ul>
                                <li>Для девушки</li>
                                <li>Для мужчины</li>
                                <li>Для жены</li>
                                <li>Для мамы</li>
                                <li>Для коллеги</li>
                                <li>Для начальника</li>
                                <li>Для дочки</li>
                                <li>Для детей</li>
                                <li>Для женщины</li>
                            </ul>
                        </div>
                    </div>
                    <div class="footer-right">
                        <div class="delivery">
                            <a href="order_pay.php">Доставка и оплата</a>
                            <a href="about.php">О нас</a>
                            <a href="FAQ.php">FAQ</a>
                            <a href="contact.php">Контакты</a>
                            <a href="client.php">Для корпоративных клиентов</a>
                        </div>
                        <div class="contact-info12">
                            <h3 class="contact-title">zakaz@loverflower.by</h3>
                            <p class="delivery-info">Доставка 24/7 по договоренности с оператором</p>
                            <h3 class="address">Ул. Тимирязева 67</h3>
                            <p class="work-time">10:00 до 21:00<br>без выходных</p>
                            <h3 class="phone">+375 (29) 113-69-69</h3>
                            <p class="reception">Прием звонков круглосуточно</p>
                            <div class="social-icons">
                                <a href="contact.php">
                                    <img src="img/insagram.png" alt="Instagram">
                                </a>
                                <a href="contact.php">
                                    <img src="img/whatsapp.png" alt="WhatsApp">
                                </a>
                                <a href="contact.php">
                                    <img src="img/phone.png" alt="Phone">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
    </section>


    <!-- Модальное окно корзины -->
    <div class="cart-overlay"></div>
    <div class="cart-popup">
        <div class="cart-header">
            <h3>Ваша корзина</h3>
            <button class="close-cart">&times;</button>
        </div>
        <div class="cart-items">
            <!-- Товары будут загружаться здесь через JavaScript -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Предварительный итог: </span>
                <span class="total-price">0 руб.</span>
            </div>
            <a href="order.php" class="checkout-btn">Оформить заказ</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // 1. Инициализация слайдера цены (исправлено - добавлен обработчик изменения)
    const minSlider = document.getElementById('price-min');
    const maxSlider = document.getElementById('price-max');
    const minPriceValue = document.getElementById('min-price-value');
    const maxPriceValue = document.getElementById('max-price-value');
    
    function updatePriceValues() {
        minPriceValue.textContent = minSlider.value;
        maxPriceValue.textContent = maxSlider.value;
        applyFilters(); // Применяем фильтры при изменении
    }
    
    minSlider.addEventListener('input', updatePriceValues);
    maxSlider.addEventListener('input', updatePriceValues);
    
    // 2. Обработчики для фильтров (исправлено - один обработчик для всех)
    document.querySelectorAll('.filter-options input, select').forEach(element => {
        element.addEventListener('change', applyFilters);
    });
    
    // 3. Кнопка сброса (исправлено - полный сброс)
    document.getElementById('reset-filters').addEventListener('click', function() {
        // Сброс ползунков
        minSlider.value = 0;
        maxSlider.value = 1000000; // Обновляем максимальное значение
        maxPriceValue.textContent = '1000000'; // Обновляем отображаемое значение
        updatePriceValues();
        
        // Сброс чекбоксов
        document.querySelectorAll('.filter-options input').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Сброс сортировки
        document.querySelector('select').value = 'popular';
        
        applyFilters();
    });
    
    // 4. Основная функция фильтрации (полностью переписана)
    function applyFilters() {
        const params = new URLSearchParams();
        
        // Категория
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get('category');
        if (categoryId) params.set('category', categoryId);
        
        // Цвета
        document.querySelectorAll('input[name="color"]:checked').forEach(checkbox => {
            params.append('colors[]', checkbox.value);
        });
        
        // Форматы
        document.querySelectorAll('input[name="format"]:checked').forEach(checkbox => {
            params.append('formats[]', checkbox.value);
        });
        
        // Цена
        params.set('min_price', minSlider.value);
        params.set('max_price', maxSlider.value);
        
        // Свет
        const lightChecked = document.querySelector('input[name="light"]:checked');
        if (lightChecked) params.set('light_type', lightChecked.value);
        
        // Сортировка
        params.set('sort', document.querySelector('select').value);
        
        // Загрузка товаров с фильтрами
        fetch('catalog.php?' + params.toString())
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newProducts = doc.querySelector('.products-grid').innerHTML;
                document.querySelector('.products-grid').innerHTML = newProducts;
                
                // Обновляем обработчики кнопок "В корзину"
                document.querySelectorAll('.add-to-cart').forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = this.dataset.productId;
                        addToCart(productId, 1);
                    });
                });
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при загрузке товаров', true);
            });
    }
});
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация корзины
        function initCart() {
            // Обработчики для открытия/закрытия корзины
            document.querySelectorAll('.cart-icon-container, .cart-icon').forEach(el => {
                el.addEventListener('click', openCart);
            });
            
            document.querySelector('.close-cart').addEventListener('click', closeCart);
            document.querySelector('.cart-overlay').addEventListener('click', closeCart);
            
            // Обработчики для кнопок "В корзину" на карточках товаров
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    addToCart(productId, 1);
                });
            });
        }

        function openCart() {
            document.querySelector('.cart-overlay').style.display = 'block';
            document.querySelector('.cart-popup').classList.add('active');
            loadCartItems();
        }

        function closeCart() {
            document.querySelector('.cart-overlay').style.display = 'none';
            document.querySelector('.cart-popup').classList.remove('active');
        }

        function loadCartItems() {
            fetch('catalog.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_cart'
            })
            .then(response => response.json())
            .then(data => {
                const cartItemsContainer = document.querySelector('.cart-items');
                cartItemsContainer.innerHTML = '';
                
                if (data.items.length === 0) {
                    cartItemsContainer.innerHTML = '<p>Ваша корзина пуста</p>';
                    document.querySelector('.total-price').textContent = '0 руб.';
                    return;
                }
                
                data.items.forEach(item => {
                    const itemHTML = `
                        <div class="cart-item">
                            <img src="${item.image}" alt="${item.name}">
                            <div class="cart-item-info">
                                <div class="cart-item-title">${item.name}</div>
                                <div class="cart-item-price">${formatPrice(item.price)} руб.</div>
                                <div class="cart-item-actions">
                                    <div class="quantity-controlss" style="border: 0.5px solid #555555; padding: 5px 0;">
                                        <button class="quantity-btn minus" data-id="${item.product_id}">-</button>
                                        <span class="quantity-input" style="font-family: 'Oswald'; margin-right: 15px;">${item.quantity}</span>
                                        <button class="quantity-btn plus" data-id="${item.product_id}">+</button>
                                    </div>
                                    <span class="remove-item" data-id="${item.product_id}" style="margin-left: auto; font-family: 'Oswald'; font-weight: 400; font-size: 10px; line-height: 100%; letter-spacing: 4%; text-transform: uppercase; text-decoration: underline; text-decoration-style: solid; text-decoration-offset: 0%; text-decoration-thickness: 0%; cursor: pointer;">Удалить</span>
                                </div>
                            </div>
                        </div>
                    `;
                    cartItemsContainer.insertAdjacentHTML('beforeend', itemHTML);
                });
                
                document.querySelector('.total-price').textContent = `${formatPrice(data.total)} руб.`;
                
                // Добавляем обработчики для кнопок в корзине
                document.querySelectorAll('.minus').forEach(btn => {
                    btn.addEventListener('click', function() {
                        updateCartItem(this.dataset.id, 'decrease');
                    });
                });
                
                document.querySelectorAll('.plus').forEach(btn => {
                    btn.addEventListener('click', function() {
                        updateCartItem(this.dataset.id, 'increase');
                    });
                });
                
                document.querySelectorAll('.remove-item').forEach(btn => {
                    btn.addEventListener('click', function() {
                        updateCartItem(this.dataset.id, 'remove');
                    });
                });
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при загрузке корзины', true);
            });
        }

        function updateCartItem(productId, action) {
            fetch('catalog.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_cart&product_id=${productId}&action_type=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCartItems();
                    updateCartCount(data.cart_count);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при обновлении корзины', true);
            });
        }

        function addToCart(productId, quantity) {
            fetch('catalog.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_to_cart&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCount(data.cart_count);
                    showToast('Товар добавлен в корзину');
                    if (document.querySelector('.cart-popup').classList.contains('active')) {
                        loadCartItems();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при добавлении в корзину', true);
            });
        }

        function updateCartCount(count) {
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = count;
            });
        }

        function formatPrice(price) {
            return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function showToast(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = `toast ${isError ? 'error' : ''}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Инициализация всех компонентов
        initCart();
        initBackToTop();
        initFixedHeader();

        // Остальные функции инициализации...
    });
    </script>
</body>
</html>