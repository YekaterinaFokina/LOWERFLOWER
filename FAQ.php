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

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
// Подключение попапа заказа звонка
include 'callback_popup.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.cdnfonts.com/css/cormorant-2" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Cormorant+Garamond" rel="stylesheet">
            
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,100..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Oswald:wght@200..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="style/style-faq.css">
</head>
<style>
    
:root {
    --black: #040A0A;
    --blue: #43FFD2;
    --red: #7D2253;
    --pink: #D978AC;
    --white: #FFFFFF;
    --border: .2rem solid var(--black);
    --box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
}

body {
    position: relative;
    
    display: flex;
    flex-direction: column;
    
    margin: 0;
    font-family: 'Cormorant';
    font-size: 17px;
    color: var(--white);
    overflow-x: hidden;
    max-height:1000px;
    
}
.left-img-1{
    position: absolute;
    z-index: 1200;
    

}
.right-img-1{
    position: absolute;
    right: 0;
    top: 20px;
    z-index: 1111;
}
.right-img-2{
    position: absolute;
    right: 0;
    bottom: 170px;
    z-index: 555;
}
.light{
    position: absolute;
    z-index: 500;
}
.light-1{
    top: -300px;
    left: -550px;

}
.light-2{
    top: -200px;
    right: -400px;

}
.light-3{
    bottom: -300px;
    right: -600px;
  

}
.light-4{
    left: -600px;
    bottom: -290px;
}





/* header */
/* header-section нужен для того что бы сделать фон
на всю ширину черным */
.header-section{
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background-color: var(--black);
    z-index: 1300;
    
    
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}
/* здесь ограничиние по ширине 80% сайта */
.fixed-header {
    max-width: 80%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0 auto; 

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
        nav li{
            margin-left: 35px;
        }
        nav a {
            color: #f0f0f0;
            font-size: 14px;
            text-decoration: none;
            font-family: 'Oswald';
        }
        
        nav a img {
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
            display: flex;
            align-items: center;
        }
        .contact-info span{
            font-weight: 400;
            font-size: 14px;
            color: var(--blue);
        }

        .contact-info img{
            margin-right: 5px;
        }
        .callback-trigger{
            display: flex;
            align-items: center;
        }




/* section back - container */

.section-back{
    width: 100%;
    background-color: rgb(32, 32, 32);
    min-height: 1000PX;
    position: relative;
}

.section-container{
    max-width: 75%;
    margin: 0  auto;
    margin-top: 100px;
    font-family: 'Oswald';
    z-index: 600;
    overflow: hidden;
}

.container-header{
    text-transform: uppercase;
    color: var(--white);
    padding-top: 30px;
}

.container-header .head-2 h1{
    margin: 40px auto;
    
    font-family: 'Cormorant ', serif;
    font-size: 120px;
    font-weight: 100;
    letter-spacing: 2%;
    display: flex;
    align-items: center;
}

.container-header .head-2 h1 img{
    margin-right: 25px;
    width: 85px;
}


/* текст вываливается */

/* Общие стили */
.faq-item {
    border: 1px solid var(--blue); /* Рамка */
    margin-bottom: 5px; /* Отступ между элементами */
    
    color: var(--blue);
    padding: 10px;
    border-radius: 5px;
    max-width: 825px;
    font-size: 20px;
    margin-top: 10px;
    position: relative;
    z-index: 1500;
}

.faq-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    padding: 5px;
    
   
}

.faq-content {
    font-family: 'Oswald';
    font-weight: 100;
    padding: 0 5px;
    overflow: hidden;
    color: var(--white);
    transition: max-height 0.3s ease;
    max-height: 0; /* Скрываем контент по умолчанию */
}

.faq-content.open {
    max-height: 500px; /* Примерная высота, можно корректировать */
}

.faq-toggle {
    font-size: 20px;
    font-weight: bold;
}



/* Footer */
.footer-section {
    
    background-color: var(--black);
    padding: 50px 40px;
    font-family: 'Oswald';
    color: var(--white);
    width: 100%;
    
    z-index: 1000;
}

.footer-container {
    margin: 0 auto;
    max-width: 80%;
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
       
        font-family: 'Oswald';
        font-weight: 400;
        line-height: 1;
        letter-spacing: 10%;
        text-transform: uppercase;
        font-size: 14px;
        
    }
    
    .contact-info12 p {
        margin: 5px 0;
    }
    
    .social-icons {
        margin-top: 20px;
        
    }
    .contact-text,

    .contact-title {
        text-transform: uppercase;
    }
    .contact-text{
        padding-bottom: 15px;
    }
    
    .social-icons a {
        display: inline-block;
        transition: transform 0.3s;
        margin-right: 20px;
    }
    
    .social-icons a:hover {
        transform: scale(1.1);
    }
    
    .social-icons a img {
        width: 32px;
        height: 32px;
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
        .breadcrumbs {
        position: absolute;
            margin: 20px 65px;
            font-family: 'Oswald';
            font-size: 14px;
            color: var(--white);
            font-weight: 400;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            z-index: 1000;
            margin-bottom: 100px;

        }
        
        .breadcrumbs a {
            color: var(--white);
            text-decoration: none;
        }
        
        .breadcrumbs a:hover {
            text-decoration: underline;
            color: var(--blue);
        }
</style>
<body>
    <!-- лампочки -->
    <div class="light light-1"><img src="img/свечение5.png" alt=""></div>
    <div class="light light-2"><img src="img/свечение5.png" alt=""></div>
    <div class="light light-3"><img src="img/свечение5.png" alt=""></div>
    <div class="light light-4"><img src="img/свечение5.png" alt=""></div>
    <!-- правая картинка верхняя-->
    <div class="right-img-1"><img src="img/image 559.png" alt=""></div>
    <!-- правая картинка нижняя-->
    
 
    <div class="header-section">
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
</div>


    <!-- тело сайта -->
     <div class="section-back">
        <div class="left-img-1"><img src="img/image 557.png" alt=""></div>
        <div class="section-container">
            <div class="container-header">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a> / FAQ
            </div>  
                <div class="head-2">
                    <h1><img src="img/Line 16.png" alt=""><span>FAQ</span></h1>
                </div>
               
            </div>
            <div class="faq-item">
                <div class="faq-title">
                    БУДЕТ ЛИ ЗАКАЗАННЫЙ БУКЕТ В ТОЧНОСТИ СООТВЕТСТВОВАТЬ ЕГО ИЗОБРАЖЕНИЮ НА САЙТЕ?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-content">
                    Букет собирается по индивидуальной матрице букета. Однако возможны случаи, когда нет необходимых цветов либо имеющиеся цветы отличаются от представленных на фото, и с согласования заказчика они могут быть заменены на аналогичные. Либо если флорист считает, что данные изменения не повлекут сильного изменения в образе букета, то самостоятельно может заменить некоторые цветы. И перед отправкой направляется фото заказчику, который утверждает получившийся букет. Каждый цветок отличается от другого, как и каждый букет будет индивидуальным, но в этом и есть его прелесть… в индивидуальности.
                </div>
            </div>
        
            <div class="faq-item">
                <div class="faq-title">
                    КАК ДОЛГО СТОЯТ БУКЕТЫ ИЗ СУХОЦВЕТОВ?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-content">
                    Букеты из сухоцветов могут сохранять свою красоту и форму на протяжении нескольких лет, если за ними правильно ухаживать. Они не требуют воды и могут быть размещены в сухом, прохладном месте, защищенном от прямых солнечных лучей. Однако со временем цвет может немного поблекнуть.
                </div>
            </div>
        
            <div class="faq-item">
                <div class="faq-title">
                    ДЕЛАЕТЕ ЛИ ВЫ ФОТО ГОТОВОГО БУКЕТА ПЕРЕД ОТПРАВКОЙ?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-content">
                    Да, мы всегда делаем фотографии готовых букетов перед отправкой. Это позволяет заказчику увидеть, как выглядит его заказ, и подтвердить его перед доставкой. Мы стремимся к полной прозрачности и удовлетворенности наших клиентов.
                </div>
            </div>
        
            <div class="faq-item">
                <div class="faq-title">
                    А СКОЛЬКО ДОЛЖНЫ ПРОСТОЯТЬ ЦВЕТЫ В БУКЕТЕ?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-content">
                    Свежие цветы в букете могут стоять от 5 до 14 дней в зависимости от вида цветов и условий хранения. Чтобы продлить их жизнь, рекомендуется менять воду в вазе каждые два дня, обрезать стебли и избегать прямых солнечных лучей.
                </div>
            </div>
        
            <div class="faq-item">
                <div class="faq-title">
                    ЕСТЬ ЛИ ДОСТАВКА ЗА ПРЕДЕЛЫ МКАД?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-content">
                    Да, мы осуществляем доставку за пределы МКАД. Условия и стоимость доставки зависят от расстояния и конкретного адреса. Пожалуйста, свяжитесь с нашей службой поддержки для получения более подробной информации.
                </div>
            </div>
        
            <div class="faq-item">
                <div class="faq-title">
                    КАК ОПРЕДЕЛИТЬ СВЕЖИЕ ЛИ ЦВЕТЫ?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-content">
                    Свежие цветы должны иметь яркие и насыщенные цвета, упругие листья и стебли без повреждений. Также стоит обратить внимание на запах: свежие цветы обычно имеют приятный аромат. Если цветы выглядят вялыми или имеют потемневшие листья, это может быть признаком их несвежести.
                </div>
            </div>
        
            <div class="faq-item">
                <div class="faq-title">
                    ЧЕМ ОТЛИЧАЕТСЯ БЕЛОРУССКАЯ РОЗА ОТ ИМПОРТНОЙ?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-content">
                    Белорусские розы отличаются своей устойчивостью к климатическим условиям и имеют более насыщенный цвет. Импортные розы, как правило, имеют более крупные бутоны и разнообразие сортов. Однако, выбор между ними зависит от предпочтений клиента и целей использования букета.
                </div>
            </div>



        </div>
    </div>
    




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
                <p class="contact-text">Доставка 24/7 по договоренности с оператором</p>
                <h3 class="contact-title">Ул. Тимирязева 67</h3>
                <p class="contact-text">10:00 до 21:00<br>без выходных</p>
                <h3 class="contact-title">+375 (29) 113-69-69</h3>
                <p class="contact-text">Прием звонков круглосуточно</p>
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
    // Инициализация корзины
    function initCart() {
        document.querySelectorAll('.cart-icon-container, .cart-icon').forEach(el => {
            el.addEventListener('click', openCart);
        });
        
        document.querySelector('.close-cart').addEventListener('click', closeCart);
        document.querySelector('.cart-overlay').addEventListener('click', closeCart);
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

    // Инициализация всех компонентов
    initCart();
    
    // Фиксированный хедер
    const header = document.querySelector('header:not(.fixed-header)');
    const fixedHeader = document.querySelector('.fixed-header');
    const headerHeight = header.offsetHeight;
    
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
    updateFixedHeader();
    
    // Кнопка "Наверх"
    const backToTopButton = document.createElement('div');
    backToTopButton.className = 'back-to-top';
    backToTopButton.id = 'backToTop';
    backToTopButton.innerHTML = '&uarr;';
    document.body.appendChild(backToTopButton);
    
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    });
});


    const faqTitles = document.querySelectorAll('.faq-title');

    faqTitles.forEach(title => {
        title.addEventListener('click', () => {
            const faqItem = title.closest('.faq-item');
            const faqContent = faqItem.querySelector('.faq-content');
            const faqToggle = title.querySelector('.faq-toggle');

            faqContent.classList.toggle('open');

            if (faqContent.classList.contains('open')) {
                faqToggle.textContent = '-';
            } else {
                faqToggle.textContent = '+';
            }
        });
    });
</script>
</body>
</html>