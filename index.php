<?php
include 'config.php';

// Начинаем сессию в самом начале
session_start();
// Обработка AJAX запросов для корзины
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
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

// Получаем текущую категорию из URL
$currentCategoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Получаем список всех категорий
$categoriesStmt = $conn->query("SELECT * FROM categories");
$categories = $categoriesStmt->fetch_all(MYSQLI_ASSOC);

// Получаем список цветов и форматов для фильтров
$colors = $conn->query("SELECT * FROM colors")->fetch_all(MYSQLI_ASSOC);
$formats = $conn->query("SELECT * FROM formats")->fetch_all(MYSQLI_ASSOC);

// Базовый запрос для получения товаров
$productsQuery = "SELECT p.*, c.name as category_name FROM products p 
                  JOIN categories c ON p.category_id = c.category_id";
$productsParams = [];
$types = "";

if ($currentCategoryId > 0) {
    $productsQuery .= " WHERE p.category_id = ?";
    $productsParams[] = $currentCategoryId;
    $types = "i";
}

$productsQuery .= " ORDER BY p.product_id DESC";

$productsStmt = $conn->prepare($productsQuery);
if (!empty($productsParams)) {
    $productsStmt->bind_param($types, ...$productsParams);
}
$productsStmt->execute();
$products = $productsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;

//questions-section
// Обработка формы
// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['phone']) && isset($_POST['comment'])) {
    // Проверка заполнения полей
    if (empty($_POST['name'])) {
        $message = "Пожалуйста, укажите Ваше имя";
    } elseif (empty($_POST['phone'])) {
        $message = "Пожалуйста, укажите Ваш телефон";
    } elseif (empty($_POST['comment'])) {
        $message = "Пожалуйста, оставьте свой комментарий";
    } else {
        // Подготовленный запрос для безопасности
        $stmt = $conn->prepare("INSERT INTO feedback (name, phone, comment, submission_date) VALUES (?, ?, ?, NOW())");
        
        // Привязка параметров
        $stmt->bind_param("sss", $_POST['name'], $_POST['phone'], $_POST['comment']);
        
        // Выполнение запроса
        if ($stmt->execute()) {
            // Редирект после успешной отправки
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        } else {
            $message = "Ошибка при отправке формы: " . $stmt->error;
        }
        
        // Закрытие запроса
        $stmt->close();
    }
}

// Показ сообщения об успехе после редиректа
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "";
}
// Подключение попапа заказа звонка
include 'callback_popup.php';
?>




<!DOCTYPE html>
<html lang="ru">
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
    <title>Цветочный Магазин</title>
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
        width: 1440;
        height: 6731;
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
        z-index: 1;
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
        top: 750px; 
        right: 17%;
        display: flex; 
        flex-direction: column; /* Вертикальное расположение */
        align-items: center;
        z-index: 1;
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
        margin: 0 10px;
    }

    .remove-item {
        margin-left: auto;
        color: var(--white);
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
    .contact-info2 span {
        padding: 7px; 
        color: var(--white); 
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px); 
        display: flex; 
        align-items: center; 
        flex-direction: column; /* Вертикальное расположение текста */
    }

    

    .contact-info3 img {
        
        margin-left: 100rem;
        margin-top: 5px; /* Немного отступаем от текста */
        display: block; /* Чтобы был блочным элементом и переносился на новую строку */
    }

    nav a:hover{
        color: var(--blue);
        text-decoration: underline;
    }

    .hero {
        position: relative;
        text-align: center;
        padding: 5px 10px;
        width: 103%;
        height: 850px;
        background-image: linear-gradient(to top, rgba(4, 10, 10, 1), rgba(4, 10, 10, 0)), url(img/bacr-1.png);
        background-size: cover;
        background-position: center;
    }
    .hero h1 {
        font-family: 'Cormorant', sans-serif;
        font-weight: 300;
        font-size: 170px;
        line-height: 0.7;
        letter-spacing: 45px;
        text-transform: uppercase;
    }

    .hero p {
        font-size: 18px;
        padding-bottom: 30px;
    }
    .btn {
        padding: 10px 30px;
        background-color: var(--blue);
        color: #000000;
        text-decoration: none;
    }


    
    
    .about {
        position: relative;
        margin: 50px 0;
        padding: 50px 20px;
        width: 102%;
        margin-bottom: 300px;
        background: url(img/Ellipse\ 31.png), url(img/Ellipse\ 32.png), url(img/букеты.png), url(img/Цветы.png), url(img/дополнительно.png);
        background-size: 50%, 50%, 50%, 50%, 50%;
        background-repeat: no-repeat;
        background-position: left bottom, right bottom, right top, left center, right bottom;
        height: auto; /* Убираем фиксированную высоту */
        display: flex;
        flex-direction: column; /* Вертикальное расположение блоков */
        align-items: flex-start; /* Выравнивание по левому краю */
    }

    
    .info-block1 {
        width: 100%; /* Устанавливаем ширину блока */
        max-width: 445px; /* Максимальная ширина */
        border-radius: 20px; /* Закругленные углы */
        backdrop-filter: blur(20px); /* Эффект размытия фона */
        margin-top: 20px; /* Отступ сверху */
        padding: 20px;
        height: 264px;
        margin-top: -130px;
        margin-left: 205px;
        padding-left: 40px;
        background-color: rgba(0, 0, 0, 0.1); /* Полупрозрачный фон */
    }
     .info-block {
        width: 100%; /* Устанавливаем ширину блока */
        max-width: 445px; /* Максимальная ширина */
        height: 264px;
        border-radius: 20px; /* Закругленные углы */
        backdrop-filter: blur(20px); /* Эффект размытия фона */
        margin-top: -170px; /* Отступ сверху */
        margin-left: 785px;
        padding: 20px;
        padding-left: 50px;
        background-color: rgba(0, 0, 0, 0.1); /* Полупрозрачный фон */
    }
    .info-block2 {
        width: 100%; /* Устанавливаем ширину блока */
        max-width: 445px; /* Максимальная ширина */
        border-radius: 20px; /* Закругленные углы */
        backdrop-filter: blur(20px); /* Эффект размытия фона */
        height: 264px;
        margin-top: -90px; /* Отступ сверху */
        margin-left: 735px;
        padding: 20px;
        padding-left: 40px;
        background-color: rgba(0, 0, 0, 0.1); /* Полупрозрачный фон */
    }

    .info-block h1,
    .info-block1 h1,
    .info-block2 h1 {
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 30px;
        color: var(--blue);
        line-height: 100%;
        margin-left: 10px;
        letter-spacing: 4%;
        text-transform: uppercase;
    }

    .catalog-link,
    .catalog-link1,
    .catalog-link2 {
        position: relative; /* Позволяет позиционировать элемент */
        bottom: 0; /* Отступ от нижнего края блока */
        top: 50px;
        left: 270px; /* Отступ от правого края блока */
        color: var(--pink); /* Цвет текста */
        text-decoration: underline; /* Подчеркнутый текст */
        font-family: Oswald; /* Шрифт, если необходимо */
        font-weight: 700; /* Жирный текст */
        font-size: 15px; /* Размер шрифта */
    }
    .about h2{
        width: 463;
        height: 121;
        margin-left: 165px;
        font-family: 'Cormorant';
        font-weight: 400;
        font-size: 100px;
        line-height: 100%;
        letter-spacing: 2%;
        text-transform: uppercase;
        margin-bottom: 20px;
        
    }
    .about p{
        width: 540;
        height: 78;
        margin-left: 260px;
        
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 17px;
        line-height: 26px;
        letter-spacing: 4%;
        
    }
   
    .catalog-container {
        display: flex; /* Размещаем info-block1 и info-block-wrapper рядом */
        justify-content: space-between; /* Распределяем пространство между ними */
        margin-top: 20px; /* Отступ сверху */
    }

    .info-block-wrapper {
        display: flex;
        flex-direction: column; /* Размещаем info-block и info-block2 друг под другом */
    }

    
    .info-block li,
    .info-block1 li,
    .info-block2 li {
        color: var(--white);
        font-family: 'Oswald';
        font-weight: 400;
        font-size: 18px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        margin-top: 5px;
        margin-left: -10px;
        margin-bottom: 5px;
    }


    .products {
        position: relative;
        background-image: url(img/image\ 115.png);
        background-position: right top;
        background-repeat: no-repeat;
        background-size: 974px 748px;
        padding: 50px 0 150px;
        overflow: hidden;
    }
    .product-container {
        display: flex;
        justify-content: center; /* Центрируем карточки */
        flex-wrap: wrap; /* Позволяем карточкам переноситься на новую строку */
    }
    .products::before {
        content: "";
        position: absolute;
        left: 0;
        margin-bottom: 150px; /* Позиция первого эллипса */
        background-image: url(img/Ellipse\ 31.png);
        background-size: 500px; /* Размер первого эллипса */
        width: 571.2769254167857px;
        height: 396.429388509183px; /* Высота первого эллипса */
        background-repeat: no-repeat; /* Не повторять изображение */
        }
    
    .products h2{
        width: 700px;
        height: 121px;
        
        margin-left: 165px;

        font-family: 'Cormorant';
        font-weight: 400;
        font-size: 100px;
        line-height: 100%;
        letter-spacing: 2%;
        text-transform: uppercase;

    }
    .products h2 span{
        margin-left: 505px;
    }
    .products p {

        font-family: 'Oswald';
        font-weight: 300;
        font-size: 20px;
        line-height: 100%;
        letter-spacing: 2%;
        width: 338px;
        height: 30px;
        margin-left: 676px;


    }

    .product-card {
        position: relative; /* Убедитесь, что карточка имеет относительное позиционирование */
        display: inline-block;
        width: 350px; /* Занимает всю ширину карточки */
        height: 450px;
        margin: 10px;
        padding: 10px;
        border-radius: 5px;
        text-align: center; /* Центрируем текст */
    }

    .product-card img {
        width: 350px; /* Занимает всю ширину карточки */
        height: 450px;
        margin-bottom: 10px; /* Небольшой отступ от изображения */
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

    .product-button {
        background-color: transparent; /* Прозрачный фон */
        border: 0.5px solid var(--white); /* Белая обводка */
        color: var(--white); /* Белый текст */
        padding: 9px 34px;
        cursor: pointer;
        font-family: 'Oswald';
        font-size: 17px;
        text-decoration: none;
        font-weight: 700;
        text-transform: uppercase;
        text-align: center; /* Выравнивание текста по левому краю */
        display: block; /* Делаем кнопку блочным элементом */
        
    }
        
    .product-button:hover {
        background-color: rgba(255, 255, 255, 0.1); /* При наведении делаем легкий белый оттенок */
    }

    
    .slider-container {
        overflow: hidden;
        width: 100%;
    }
    .slider-wrapper {
        position: relative;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 80px;
    }

    .product-container {
        display: flex;
        transition: transform 0.5s ease;
        gap: 15px;
        padding: 20px 0;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none; /* Hide scrollbar for Firefox */
        -ms-overflow-style: none; /* Hide scrollbar for IE/Edge */
    }

    .product-container::-webkit-scrollbar {
        display: none; /* Hide scrollbar for Chrome/Safari */
    }

    .product-card {
        flex: 0 0 150px;
        height: 650px;
        
        margin: 10px;
    }
    .slider-track {
        display: flex;
        transition: transform 0.5s ease;
        gap: 7px;
    }/* !!!! */

    .slider-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        z-index: 10;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .left-arrow {
        left: 0;
    }

    .right-arrow {
        right: 0;
    }

    .slider-arrow img {
        width: 80px;
        
    }
    

    .new-badge1 {
        position: absolute; /* Абсолютное позиционирование для кружка */
        top: 10px; /* Отступ сверху */
        right: 10px; /* Отступ справа */
        background-color: var(--pink); /* Цвет фона */
        color: var(--black); /* Цвет текста */
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex; /* Используем flexbox */
        justify-content: center; /* Центрируем по горизонтали */
        align-items: center; /* Центрируем по вертикали */
        font-weight: 300;
        font-family: 'Oswald';
        font-size: 20px; 
        text-align: center;
    }
    .new-badge2 {
        position: absolute; /* Абсолютное позиционирование для кружка */
        top: 10px; /* Отступ сверху */
        right: 10px; /* Отступ справа */
        background-color: #31985A; /* Цвет фона */
        color: var(--black); /* Цвет текста */
        width: 60px;
        height: 60px;
        border-radius: 50%; /* Круглая форма */
        display: flex; /* Используем flexbox */
        justify-content: center; /* Центрируем по горизонтали */
        align-items: center; /* Центрируем по вертикали */
        font-weight: 300;
        font-family: 'Oswald';
        font-size: 20px; /* Размер шрифта */
        text-align: center; /* Центрирование текста */
    }
    .catalog-link-container {
        display: flex;
        align-items: center; /* Центрируем по вертикали */
        margin-top: 20px; /* Отступ сверху */
    }
    
    .link-container {
        width: 80px;
        margin-top: 100px;
        margin-left: 290px; 
    }
    .order-section {
        display: flex;
       
        background-image: url('img/image 122.png');
        background-size: 462px 845px; /* Задайте фиксированные размеры */
        background-repeat: no-repeat;
        background-position: left; /* Позиционирование изображения */
        width: 100%; /* Ширина блока */
        height: 845px; /* Высота блока */
    }
    
    .order-left h2 {
        font-family: 'Cormorant';
        font-weight: 400;
        font-size: 100px;
        line-height: 100%;
        letter-spacing: 2%;
        text-transform: uppercase;
        width: 684;
        height: 121;
        margin-right: 300px;
        margin-top: 10px;
        margin-left: 165px;

    }
    .order-left h2 span {
        width: 315;
        height: 121;
        
        margin-left: 0px;

    }
    .order-left {
        flex: 0 100px 100px; /* Фиксированная ширина для левой части */
        padding-right: 20px;
        margin-left: 275px; /* Добавлено для соответствия макету */
    }

    .order-left h2 {
        font-family: 'Cormorant';
        font-weight: 400;
        font-size: 100px;
        line-height: 100%;
        letter-spacing: 2%;
        text-transform: uppercase;
        width: 100%; /* Исправлено для правильного отображения ширины */
        margin-right: 0; /* Удалено для правильного отображения */
        margin-top: 10px;
        margin-left: 0; /* Удалено, так как отступ теперь в .order-left */
    }

    .order-right {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Два столбца */
        grid-template-rows: repeat(3, 150px); /* Три строки с авто-высотой */
        margin-top: 300px;
        margin-left: -400px;
    }
    .rectangle-decoration {
        position: absolute;
        margin-top: -50px;
        margin-left: 3.2%;
        transform: translateX(-50%);
        width: 100px;
        z-index: 1;
    }

    .line-decoration {
        position: absolute;
        margin-left: 650px;
        margin-top: 13%;
        transform: translateY(-50%);
        height: 80%;
        z-index: 1;
    }

    .flower-decoration {
        position: absolute;
        margin-top: 30%;
        margin-left: 450px;
        width: 300px;
        z-index: 1;
    }
    
    .info {
        display: flex;
        flex-direction: column;
        margin: 0px;
    }

    .info h3 {
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 30px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        color: var(--pink);
        
        margin: 10px 0;
    }

    .info p {
        font-family: 'Oswald';
        font-weight: 400;
        font-size: 14px;
        
        line-height: 100%;
        letter-spacing: 4%;
        word-spacing: 3px;
        text-transform: uppercase;
        margin: -5px 0;

    }

    /* Индивидуальные позиции для каждого info блока */
    .step1 {
        grid-column: 1;
        grid-row: 1;
    }

    .step2 {
        grid-column: 1;
        grid-row: 2;
    }

    .step3 {
        grid-column: 1;
        grid-row: 3;
    }

    .step4 {
        grid-column: 2;
        grid-row: 1;
    }

    .step5 {
        grid-column: 2; /* Занимает два столбца */
        grid-row: 2;

    }
    .step4,
    .step5 {
        margin-top: 0px;
        margin-left: -100px;
    }
    
    .special-occasion {
        position: relative;
        padding-top: 300px;
        width: 104.9%;
        background-color: var(--black);
        overflow: hidden;
        
    }

    .special-occasion::before {
        content: "";
        position: absolute;
        right: -260px;
        top: 80%;
        transform: translateY(-50%);
        background-image: url('img/Ellipse 39.png');
        background-size: contain;
        background-repeat: no-repeat;
        width: 500px;
        height: 500px;
        z-index: 1;
    }

    .special-container {
        display: flex;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        position: relative;
        z-index: 2;
    }

    .special-left {
        flex: 1;
        
    }

    .special-left h2 {
        font-family: 'Cormorant';
        font-weight: 400;
        font-size: 100px;
        line-height: 100%;
        letter-spacing: 2%;
        text-transform: uppercase;
        color: var(--white);
    }
    .special-left h2 span {
        margin-left: 350px;
    }

    .special-left .main-text {
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        margin-bottom: 50px;
        margin-left: 70px;

    }

    .features-list {
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;
        margin-left: 50px;

    }

    .features-list li {
        position: relative;
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 16px;
        color: var(--white);
        padding-left: 10px;
        margin-bottom: 5px;
        line-height: 1.5;
    }


    .custom-bouquet-btn {
        display: inline-block;
        padding: 20px 60px;
        background-color: var(--blue);
        color: var(--black);
        font-family: 'Oswald';
        font-weight: 700;
        text-decoration: none;
        font-size: 18px;
        line-height: 100%;
        letter-spacing: 10%;
        margin-left: 50px;
        text-transform: uppercase;
        transition: background-color 0.3s;
    }

    .custom-bouquet-btn:hover {
        background-color: #3ae8c2;
    }

    .special-right {
        flex: 1;
        position: relative;
    }

    .special-right .top-image {

        display: block;
        width: 255px;
        height: 223px;
        border-radius: 20px;
        

    }
    .top-image{
        margin-top: 15%;
        margin-left: 24%;
    }

    .image-row {
        display: flex;
        gap: 15px;
        margin-top: 10%;
        margin-left: -37%;
    }
    
    .image-row img {
        object-fit: cover;
        width: 255px;
        height: 292px;
        border-radius: 20px;

    }
    .image-row .image-row1 {
        object-fit: cover;
        width: 160px;
        height: 292px;
        border-radius: 20px;
    } 
    .features-list img {
        margin-left: -15%;
        margin-top: -30px;
    }
    .special-left .line12 {
        margin-top: -1450px; /* Поднимаем линию на 50px вверх */
        margin-bottom: 145px; /* Добавляем отступ снизу, чтобы компенсировать подъем */
    }


    .questions-section {
        margin-top: 210px;
        width: 104.9%;
        height: 866px;
        background: #0F2222;
        display: flex;
        justify-content: center; /* Центрирование по горизонтали */
        align-items: center; /* Центрирование по вертикали */
    }

    .questions-container {
        display: flex;
        width: 80%; /* Занимаем 80% от ширины секции */
        max-width: 1200px; /* Ограничиваем максимальную ширину */
        padding: 50px;
        box-sizing: border-box; /* Учитываем padding и border в общей ширине */
    }
    .questions-section {
        position: relative; /* Для позиционирования абсолютных элементов */
        overflow: hidden; /* Чтобы скрыть части элементов, выходящие за границы */
    }

    .questions-ellipse40 {
        position: absolute;
        top: 100px;
        left: 20px;
        width: 800px;
        z-index: 1;
    }



    .questions-flower {
        position: absolute;
        left: 0px;
        bottom: -300px;
        transform: rotate(0deg);
        z-index: 2;
        width: 691.8848605300913;
        height: 1037.827229759983;
    }

    .questions-decor {
        position: absolute;
        left: 500px;
        bottom: 250px;
        z-index: 3;
        width: 300px;
    }

    .questions-line {
        position: absolute;
        top: -70px;
        right: 25%;
        transform: translateX(50%);
        z-index: 2;
        width: 295px;
        margin-bottom: 20px;
    }


    .questions-left {
        flex: 1;
    }

    .questions-left h2 {
        font-family: Cormorant;
        font-weight: 400;
        font-size: 100px;
        line-height: 100%;
        letter-spacing: 2%;
        text-transform: uppercase;
        color: var(--white);
        margin-left: -50px;
        margin-top: 10px;
    }
    .questions-left h2 span {
        margin-left: 90px;
    }

    .questions-right {
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        margin-top: 130px;
        margin-left: 40px; /* Добавляем отступ слева */
    }

    .questions-right p {
        font-family: 'Oswald';
        letter-spacing: 0.04em;
        color: var(--white);
        margin-bottom: 40px;
        margin-left: 240px;
        text-align: left;
        font-weight: 400;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;

    }

    .contact-form {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .contact-form input,
    .contact-form textarea {
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid #43FFD2;
        background-color: transparent;
        color: var(--white);
        font-family: 'Oswald';
        outline: none;
        font-weight: 400;
        font-size: 14px;
        line-height: 100%;
        letter-spacing: 4%;
        text-transform: uppercase;

        
    }

    .contact-form textarea {
        resize: vertical;
        height: 150px;
    }

    .contact-form button {
        background-color: #43FFD2;
        color: #000;
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 18px;
        line-height: 1;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 20px 100px;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
        align-self: flex-start; /* Прижимаем к левой стороне */
    }

    .contact-form button:hover {
        background-color: #3ae8c2;
    }

    .contact-form .policy-text {
        font-family: 'Oswald';
        font-weight: 300;
        font-size: 12px;
        line-height: 1.3;
        letter-spacing: 0.04em;
        color: var(--white);
        margin-top: 10px;
        text-align: left;
        margin-left: 5px;
    }

    .contact-form .policy-text a {
        color: var(--pink);
        text-decoration: underline;
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
        width: 104.9%;
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

    

    .social-title {
        display: block;
        width: 300px;
        margin-top: -400px;
        margin-left: 1000px;
    }
    
    .social-icons {
        text-align: right;
        margin-right: 100px;
    }
    .social-icons a {
        display: inline-block;
        transition: transform 0.3s; /* Плавный переход для увеличения */
        margin-right: 10px; /* Регулируйте отступ сверху как необходимо */
        z-index: 2;
        
    }

    .social-icons a:hover {
        transform: scale(1.1); /* Увеличение иконок на 10% при наведении */
    }

    .social-icons a img {
        width: 50px;
        height: 50px;
        
    }

    .social-icons a:hover img {
        transform: scale(1.1);
    }
    


    .footer-section {
        background-color: var(--black);
        padding: 50px 40px;
        font-family: 'Oswald';
        color: var(--white);
        width: 98%;
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
        
        .social-iconss {
            margin-top: 20px;
            text-align: right;
        }
        
        .social-iconss a {
            display: inline-block;
            transition: transform 0.3s;
            margin-left: 10px;
            z-index: 2;
        }
        
        .social-iconss a:hover {
            transform: scale(1.1);
        }
        
        .social-iconss a img {
            width: 50px;
            height: 50px;
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
.contact-info2p p.callback-trigger:hover {
    cursor: pointer;
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

</style>
<body>
    <header>
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
            <p class="callback-trigger" style="cursor: pointer;">+375 (29) 113-69-69<br><span>ЗАКАЗАТЬ ЗВОНОК</span></p>
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
                    <li><a href="#FAQ">FAQ</a></li>
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


   
        <section class="hero">
            <h1>LOVER <br> FLOWER</h1>
            <p>Создаём для тех, кто ценит свежесть и изящество цветка</p>
            <a href="catalog.php" class="btn">СМОТРЕТЬ КАТАЛОГ</a>
        </section>

        <section id="about" class="about">
            <h2>КАТАЛОГ</h2>
            <p>У нас самый большой выбор цветов, букетов, открыток и подарков.<br>
            Мы всегда поможем Вам подобрать букет для Вашего события, наш <br>менеджер Вас проконсультирует и поможет определиться с выбором</p>
            <p>Ознакомьтесь с нашими разделами каталога</p>


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


            <div class="info-block">
                <h1>Готовые букеты из сухоцветов</h1>
                <ul>
                    <li>Букеты</li>
                    <li>Для интерьера</li>
                    <li>Композиции</li>
                </ul>
                <a href="catalog.php" class="catalog-link">СМОТРЕТЬ КАТАЛОГ</a>
            </div>

            <div class="info-block1">
                <h1>Цветы</h1>
                <ul>
                    <li>Сборные букеты</li>
                    <li>Монобукеты</li>
                    <li>Розы</li>
                    <li>Свадебные</li>
                </ul>
                <a href="catalog.php" class="catalog-link1">СМОТРЕТЬ КАТАЛОГ</a>
            </div>

            <div class="info-block2">
                <h1>Дополнительно</h1>
                <ul>
                    <li>Шары</li>
                    <li>Игрушки</li>
                    <li>Открытки</li>
                    <li>Упаковка</li>
                </ul>
                <a href="catalog.php" class="catalog-link2">СМОТРЕТЬ КАТАЛОГ</a>
            </div>
        </section>

        <section id="products" class="products">
            <h2>Популярные <br> <span>букеты</span></h2>
            <p>Самые любимые композиции наших клиентов</p>
            <div class="slider-wrapper">
                <button class="slider-arrow left-arrow">
                    <img src="img/Arrow 5.png" alt="Предыдущий">
                </button>
                <div class="slider-container">
                    <div class="slider-track">
                        <div class="product-card">
                            <img src="img/image 114.png" alt="Розы">
                            <div class="new-badge1">NEW</div>
                            <h3>лучший день</h3>
                            <p>167.000 руб.</p>
                            <a href="catalog.php" class="product-button">В корзину</a>
                        </div>
                        <div class="product-card">
                            <img src="img/image 1142.png" alt="Тюльпаны">
                            <h3>лучший день</h3>
                            <p>167.000 руб.</p>
                            <a href="catalog.php" class="product-button">В корзину</a>
                        </div>
                        <div class="product-card">
                            <img src="img/image 1143.png" alt="Смешанные букеты">
                            <div class="new-badge2">SALE</div>
                            <h3>лучший день</h3>
                            <p>167.000 руб. <span>187.000 ₽</span></p>
                            <a href="catalog.php" class="product-button">В корзину</a>
                        </div>
                        <div class="product-card">
                            <img src="img/image 1143.png" alt="Лилии">
                            <h3>лучший день</h3>
                            <p>200.000 руб.</p>
                            <a href="catalog.php" class="product-button">В корзину</a>
                        </div>
                        <div class="product-card">
                            <img src="img/image 1142.png" alt="Герберы">
                            <h3>лучший день</h3>
                            <p>150.000 руб.</p>
                            <a href="catalog.php" class="product-button">В корзину</a>
                        </div>
                        <div class="product-card">
                            <img src="img/image 1143.png" alt="Орхидеи">
                            <h3>лучший день</h3>
                            <p>250.000 руб.</p>
                            <a href="catalog.php" class="product-button">В корзину</a>
                        </div>
                        
                    </div>
                </div>
                <button class="slider-arrow right-arrow">
                    <img src="img/Arrow 6.png" alt="Следующий">
                </button>
            </div>
            <div class="catalog-link-container">
                <a href="catalog.php" class="catalog-link">Смотреть весь каталог</a>
                <img src="img/Arrow 7.png" class="link-container" alt="Стрелка" > <!-- Добавьте ссылку на изображение стрелки -->
            </div>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
            const track = document.querySelector('.slider-track');
            const leftArrow = document.querySelector('.left-arrow');
            const rightArrow = document.querySelector('.right-arrow');
            const cards = document.querySelectorAll('.product-card');
            const cardWidth = cards[0].offsetWidth + 20; // ширина карточки + gap
            let currentPosition = 0;
            const cardsToScroll = 3;
            const maxPosition = -(cards.length - 3) * cardWidth;

            // Инициализация - показываем первые 3 карточки
            updateArrows();

            leftArrow.addEventListener('click', function() {
                currentPosition = Math.min(currentPosition + (cardWidth * cardsToScroll), 0);
                track.style.transform = `translateX(${currentPosition}px)`;
                updateArrows();
            });

            rightArrow.addEventListener('click', function() {
                currentPosition = Math.max(currentPosition - (cardWidth * cardsToScroll), maxPosition);
                track.style.transform = `translateX(${currentPosition}px)`;
                updateArrows();
            });

            function updateArrows() {
                leftArrow.style.display = currentPosition === 0 ? 'none' : 'flex';
                rightArrow.style.display = currentPosition <= maxPosition ? 'none' : 'flex';
            }

            
            });
        </script>

        <section class="order-section">
            <div class="order-left">
                <h2>КАК СДЕЛАТЬ <br><span>ЗАКАЗ</span></h2>
            </div>
            <div class="order-right">
                <!-- Добавлен Rectangle 34.png перед первым шагом -->
                <img src="img/Rectangle 34.png" class="rectangle-decoration" alt="">
                
                <div class="info step1">
                    <h3>1 шаг</h3>
                    <p>Выберите какие цветы или подарки<br> вы хотите купить</p>
                </div>
                <div class="info step2">
                    <h3>2 шаг</h3>
                    <p>Оформите заказ, и мы отправим вам<br> подтверждение на электронную почту,<br> а так же менеджер свяжется с вами по<br> телефону или в WhatsApp</p>
                </div>
                <div class="info step3">
                    <h3>3 шаг</h3>
                    <p>Наши флористы бережно подойдут к<br> созданию букета цветов в самом начале<br> дня или накануне</p>
                </div>
                
                <!-- Добавлена Line 11.png справа от order-right -->
                <img src="img/Line 11.png" class="line-decoration" alt="">
                
                <div class="info step4">
                    <h3>4 шаг</h3>
                    <p>Один из наших курьеров или<br> партнёров доставит ваш заказ по<br> указанному адресу. Мы отправим<br> вам сообщение в Whats App как<br> только заказ будет доставлен</p>
                </div>
                <div class="info step5">
                    <h3>5 шаг</h3>
                    <p>Наслаждайтесь цветами, если<br> вы заказали их для дома или<br> любовью, которой поделились,<br> если вы заказали для друга</p>
                </div>
                
                <!-- Добавлен lover flower.png после последнего шага -->
                <img src="img/lover flower.png" class="flower-decoration" alt="">
            </div>
        </section>


        <section class="special-occasion">
            <div class="special-container">
                <!-- Левая часть с текстом -->
                <div class="special-left">
                    <h2>ОСОБЕННЫЙ <span>ПОВОД?</span> </h2>
                    <img src="img/Line 12.png" class="line12" alt="Декоративная линия">
                    <p class="main-text">Мы готовы прийти на помощь и<br> собрать уникальный букет, на любой<br> вкус, бюджет и для любого события<br> по вашему индивидуальному заказу.</p>
                    
                    <ul class="features-list">
                        <li>учтем даже самые изысканные<br> пожелания</li>
                        <li>подберем свежайшие цветы и сделаем<br> уникальный букет или композицию</li>
                        <li>оплатить можно при получении или<br> онлайн на сайте</li>
                        <img src="img/Arrow 8.png" alt="Декоративная стрелка">
                    </ul>
                    
                    <a href="catalog.php" class="custom-bouquet-btn">собрать индивидуальный букет</a>
                </div>
                
                <!-- Правая часть с изображениями -->
                <div class="special-right">
                    <img src="img/Rectangle 36.png" class="top-image" alt="">

                    <div class="image-row">
                        <img src="img/Rectangle 35.png" alt="">
                        <img src="img/Rectangle 38.png" class="image-row1" alt="">
                        <img src="img/Rectangle 37.png" alt="">
                    </div>
                </div>
            </div>
        </section>

        <section class="questions-section">
            <div class="questions-container">
                <!-- Добавленные декоративные элементы -->
                <img src="img/Ellipse 40.png" class="questions-ellipse40" alt="">
                <img src="img/image 123.png" class="questions-flower" alt="">
                <img src="img/have any questions_.png" class="questions-decor" alt="">
                <img src="img/image 123.png" class="questions-flower" alt="">
                <div class="questions-left">
                    <h2>ОСТАЛИСЬ <br> <span>ВОПРОСЫ?</span></h2>
                </div>
                <div class="questions-right">
                    <img src="img/Line 13.png" class="questions-line" alt="">
                    <p>Отправьте Ваш вопрос, заказ,<br> предложение или жалобу через форму<br> обратной связи, и наш специалист<br> свяжется с Вами в течение 15 минут.</p>
                    <form class="contact-form" method="POST" action="">
                        <input type="text" name="name" placeholder="Ваше имя" required>
                        <input type="tel" name="phone" placeholder="+7 (977) 777-77-77" required>
                        <textarea name="comment" placeholder="Ваш комментарий" required></textarea>
                        <button type="submit">Отправить</button>
                        <p class="policy-text">Нажимая на кнопку «Отправить», я даю свое согласие на обработку<br> персональных данных, в соответствии с <a href="#">Политикой конфиденциальности</a></p>
                    </form>
                    <?php if (!empty($message)): ?>
                    <p><?php echo $message; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>


        <section class="social-section">
            <!-- Background elements -->
            <img src="img/Ellipse 41.png" class="social-ellipse41" alt="">
            <img src="img/Ellipse 42.png" class="social-ellipse42" alt="">
            
            
            <!-- Main content -->
            <div class="social-container">
                
                <!-- Image gallery -->
                <div class="social-gallery">
                    <img src="img/Rectangle 44.png" alt="">
                    <img src="img/Rectangle 45.png" alt="">
                    <img src="img/Rectangle 46.png" alt="">
                    <img src="img/Rectangle 47.png" alt="">
                </div>
                <img src="img/instagram1.png" class="social-instagram1" alt="">

                <!-- Line and title -->
                <img src="img/Line 14.png" class="social-line">
                <img src="img/our social networks.png" class="social-title" alt="Our Social Networks">
                
                <!-- Social icons -->
                <div class="social-icons">
                <a href="contact.php">
                    <img src="img/insagram.png" alt="Instagram">
                </a>
                <a href="contact.php">
                    <img src="img/whatsapp.png" alt="WhatsApp">
                </a>
                <a href="contact.php">
                    <img src="img/phone.png" alt="Телефон">
                </a>
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
                            <div class="social-iconss">
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