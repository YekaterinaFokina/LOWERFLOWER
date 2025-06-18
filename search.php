<?php
session_start();
include 'config.php';

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
// Получаем поисковый запрос
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Поиск товаров (обновленный запрос для поиска по категориям и названию)
$products = [];
if (!empty($searchQuery)) {
    $query = "SELECT p.*, c.name as category_name FROM products p 
              JOIN categories c ON p.category_id = c.category_id
              WHERE p.name LIKE ? OR c.name LIKE ?
              ORDER BY p.product_id DESC";
    
    $searchParam = "%{$searchQuery}%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $searchParam, $searchParam);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
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
    <title>Поиск - LOVER FLOWER</title>
    <style>
        /* Основные стили из вашего примера */
        :root {
            --black: #040A0A;
            --blue: #43FFD2;
            --red: #7D2253;
            --pink: #D978AC;
            --white: #FFFFFF;
        }
        
        body {
            background-color: var(--black);
            font-family: 'Cormorant';
            color: var(--white);
            padding-top: 80px; /* Высота хедера */
        }
        
        .logo {
        width: 34px;
        height: 75px;
        top: -7px;
        left: 165px;
    }



    .fixed-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background-color: var(--black);
        z-index: 1000;
        color: var(--white);
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
        /* Стили для страницы поиска */
        .search-page {
            padding: 50px 0;
            min-height: 60vh;
            position: relative;
        }
        
        .search-header {
            margin-left: 165px;
            margin-bottom: 50px;
        }
        
        .search-title {
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 30px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            margin-bottom: 20px;
            color: var(--blue);
        }
        
        
        .search-results-count {
            font-family: 'Oswald';
            font-weight: 300;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        
        .no-results {
            padding: 50px 0;
        }
        
        .no-results p {
            font-family: 'Oswald';
            margin-bottom: 30px;
            font-weight: 400;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }
        
        .back-to-catalog {
            display: inline-block;
            padding: 15px 70px;
            background-color: var(--blue);
            color: var(--black);
            text-decoration: none;
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            transition: background-color 0.3s;
        }
        
        .back-to-catalog:hover {
            background-color: #3ae8c2;
        }
        
        /* Сетка товаров (как в каталоге) */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
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
            width: 100%;
            height: 435px;
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
            font-weight: 400;
            font-size: 20px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .product-card p {
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        
        .product-card p span {
            font-weight: 300;
            text-decoration: line-through;
        }
        
        .product-buttons {
            display: flex;
            gap: 10px;
        }
        
        .product-button {
            flex: 1;
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
        
        /* Декоративные элементы */
        .search-bg-1 {
            position: absolute;
            top: 100px;
            right: 0;
            width: 500px;
            height: 500px;
            background-image: url('img/Ellipse_52.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -1;
            opacity: 0.5;
        }
        
    .footer-section {
        background-color: var(--black);
        padding: 50px 40px;
        font-family: 'Oswald';
        color: var(--white);
        width: 95%;
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
            width: 60px;
            height: 60px;
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
/* Фиксированный хедер */
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
        transition: transform 0.3s ease;
    }

    .fixed-header.visible {
        transform: translateY(0);
    }
    /* Стили для поиска в хедере */
    .search-menu-item {
            position: relative;
            display: flex;
            align-items: center;
            margin-left: 20px; /* Добавляем отступ слева */
        }
        
        .search-form-container {
            display: block !important; /* Поиск всегда виден */
            position: static;
            width: 350px; /* Уменьшаем ширину */
            background-color: transparent;
            padding: 0;
            border: none;
            border-radius: 0;
        }
        
        .search-form {
            display: flex;
            align-items: center;
            width: 100%;
            position: relative;
        }
        
        .search-input {
            flex: 1;
            padding: 5px 0;
            background-color: transparent;
            border: none;
            border-bottom: 0.5px solid #555555;
            color: var(--white);
            font-family: 'Oswald';
            font-size: 15px;
            outline: none;
            width: 100%;
        }
        
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .search-clear {
            position: absolute;
            right: 0;
            background: none;
            border: none;
            color: var(--white);
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .search-clear:hover {
            opacity: 1;
        }
        
        /* Скрываем текст "ПОИСК" и иконку, оставляем только поле ввода */
        .search-toggle {
            display: none;
        }
/* Фоновые элементы */
.product-bg-left {
    position: fixed; /* Изменено с absolute на fixed */
    top: 100px;
    right: 0;
    width: 1073px;
    height: 1073px;
    background-image: url('img/Ellipse\ 40.png');
    background-size: contain;
    background-repeat: no-repeat;
    z-index: -1;
    opacity: 0.7;
    transform: rotate(180deg);
    pointer-events: none; /* Чтобы не мешали взаимодействию с контентом */
}

.product-bg-right {
    position: fixed; /* Изменено с absolute на fixed */
    top: 0;
    left: 0;
    width: 1073px;
    height: 717px;
    background-image: url('img/Ellipse\ 39.png');
    background-size: contain;
    background-repeat: no-repeat;
    z-index: -1;
    opacity: 0.7;
    pointer-events: none;
}

.product-ellipse-top-left {
    position: fixed; /* Изменено с absolute на fixed */
    top: -100px;
    right: 0;
    width: 700px;
    height: 700px;
    background-image: url('img/Ellipse 52.png');
    background-size: contain;
    background-repeat: no-repeat;
    z-index: -2;
    pointer-events: none;
}

.search-bg-1 {
    position: fixed; /* Изменено с absolute на fixed */
    top: 100px;
    right: 0;
    width: 500px;
    height: 500px;
    background-image: url('img/Ellipse_52.png');
    background-size: contain;
    background-repeat: no-repeat;
    z-index: -1;
    opacity: 0.5;
    pointer-events: none;
}


    </style>
</head>
<body>
    <!-- Фиксированный header -->
<header class="fixed-header visible">
        <div class="logo"><img src="img/logo.png" alt=""></div>
        <nav>
            <ul>
                <li><a href="catalog.php">КАТАЛОГ</a></li>
                <li><a href="order_pay.php">ДОСТАВКА И ОПЛАТА</a></li>
                <li><a href="about.php">О НАС</a></li>
                <li><a href="contact.php">КОНТАКТЫ</a></li>
                <li><a href="FAQ.php">FAQ</a></li>
                <li class="search-menu-item">
                    <a href="#" class="search-toggle"><img src="img/ph_magnifying-glass-thin.png">ПОИСК</a>
                    <div class="search-form-container">
                        <form action="search.php" method="GET" class="search-form">
                            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                   placeholder="Поиск по товарам и категориям..." class="search-input" autocomplete="off">
                            <?php if (!empty($searchQuery)): ?>
                                <button type="button" class="search-clear">&times;</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </li>
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
    <!-- Основное содержимое страницы поиска -->
    <section class="search-page">
        <div class="search-bg-1"></div>
        
        <div class="search-header">
            <h1 class="search-title">Результаты поиска: <span class="search-query"><?php echo htmlspecialchars($searchQuery); ?></span></h1>
            
            <?php if (!empty($searchQuery)): ?>
                <?php if (!empty($products)): ?>
                    
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <img src="<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                                <?php if (isset($product['is_new']) && $product['is_new']): ?>
                                    <div class="new-badge1">NEW</div>
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
                <?php else: ?>
                    <div class="no-results">
                        <p>По запросу "<?php echo htmlspecialchars($searchQuery); ?>" ничего не найдено.</p>
                        <p>Попробуйте изменить запрос или перейдите в каталог</p>
                        <a href="catalog.php" class="back-to-catalog">В каталог</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>Введите поисковый запрос в поле выше</p>
                    <a href="catalog.php" class="back-to-catalog">В каталог</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
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
        document.addEventListener('DOMContentLoaded', function() {
            // Обработчик для кнопки очистки поиска
            const searchClear = document.querySelector('.search-clear');
            const searchInput = document.querySelector('.search-input');
            
            if (searchClear) {
                searchClear.addEventListener('click', function() {
                    searchInput.value = '';
                    searchInput.focus();
                    this.style.display = 'none';
                    // Можно добавить автоматическую отправку формы при очистке
                    if (searchInput.closest('form')) {
                        searchInput.closest('form').submit();
                    }
                });
            }
            
            // Показываем/скрываем крестик при наличии текста
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    if (searchClear) {
                        searchClear.style.display = this.value.trim() ? 'block' : 'none';
                    }
                });
                
                // Инициализация состояния крестика
                if (searchClear) {
                    searchClear.style.display = searchInput.value.trim() ? 'block' : 'none';
                }
            }
            
            // Обработка нажатия Enter
            if (searchInput) {
                searchInput.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter' && this.value.trim() !== '') {
                        this.closest('form').submit();
                    }
                });
            }
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация корзины
            function initCart() {
                // Обработчики для открытия/закрытия корзины
                document.querySelectorAll('.cart-icon-container, .cart-icon').forEach(el => {
                    el.addEventListener('click', openCart);
                });
                
                document.querySelector('.close-cart').addEventListener('click', closeCart);
                document.querySelector('.cart-overlay').addEventListener('click', closeCart);
                
                // Обработчики для кнопок "В корзину"
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
                fetch('search.php', {
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
                                        <button class="quantity-btn minus" data-id="${item.product_id}">-</button>
                                        <span class="quantity-input">${item.quantity}</span>
                                        <button class="quantity-btn plus" data-id="${item.product_id}">+</button>
                                        <span class="remove-item" data-id="${item.product_id}">Удалить</span>
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
                fetch('search.php', {
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
                fetch('search.php', {
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
        });
        document.addEventListener('DOMContentLoaded', function() {
        });
});
</script>
<div class="product-bg-left"></div>
<div class="product-bg-right"></div>
<div class="product-ellipse-top-left"></div>

</body>
</html>