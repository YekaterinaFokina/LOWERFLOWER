<?php
session_start();
include 'config.php';

// Получаем ID заказа из URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Количество товаров в корзине
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;

// Обработка формы заказа звонка
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['callback_submit'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Пожалуйста, введите ваше имя';
    }
    
    if (empty($phone)) {
        $errors[] = 'Пожалуйста, введите ваш телефон';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO callbacks (name, phone, created_at, status) VALUES (?, ?, NOW(), 'new')");
            $stmt->bind_param("ss", $name, $phone);
            $stmt->execute();
            
            // Сохраняем сообщение в сессии
            $_SESSION['success_message'] = 'Ваша заявка принята! Мы скоро с вами свяжемся.';
            
            // Перенаправляем на эту же страницу методом GET
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Показываем сообщение об успехе, если оно есть в сессии
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Удаляем сообщение, чтобы оно не показывалось снова
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ успешно оформлен - Lover Flower</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Oswald:wght@200..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--black);
            font-family: 'Cormorant';
            font-size: 17px;
            color: var(--white);
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
        
        nav a img {
            width: 18px;
            height: 18px;
            padding-right: 12px;
        }
        
        .contact-info2 {
            font-family: 'Oswald';
            font-weight: 400;
            line-height: 1;
            letter-spacing: 10%;
            text-transform: uppercase;
            font-size: 16px;
            color: var(--blue);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .fixed-header .contact-info2 p {
            margin: 0;
            font-size: 14px;
            white-space: nowrap;
        }

        .contact-info2 span {
            padding: 7px; 
            color: var(--white); 
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px); 
            display: flex; 
            align-items: center; 
        }

        .cart-icon-container {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
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
        
        /* Стили для основного контента */
        .success-container {
            margin: 150px 165px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 70vh;
        }
        
        .success-title {
            font-family: 'Cormorant';
            font-weight: 400;
            font-size: 80px;
            color: var(--white);
            margin-bottom: 50px;
            text-transform: uppercase;
        }
        .success-title span {
            margin-left: 500px;
        }
        
        .order-number {
            position: relative;
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 16px;
            color: var(--white);
            margin-bottom: 30px;
            padding-top: 20px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
        }
        
        .order-number:before {
            content: '';
            position: absolute;
            top: -100px;
            left: 0;
            width: 400px;
            height: 1px;
            background-color: var(--pink);
        }
        
        .thank-you {
            font-family: 'Cormorant';
            font-weight: 400;
            font-size: 40px;
            color: var(--white);
            margin-bottom: 20px;
            position: relative;
        }
        
        .notification {
            font-family: 'Oswald';
            font-size: 16px;
            color: var(--white);
            margin-bottom: 250px;
            font-weight: 400;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
        }
        
        .home-link {
            font-family: 'Oswald';
            color: var(--blue);
            text-decoration: none;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 50px;
            font-weight: 700;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
        }
        
        .home-link:hover {
            text-decoration: underline;
        }
        
        /* Стили для изображений */
        .success-image {
            position: absolute;
            left: 745px;
            bottom: -100px;
            width: 460px;
            height: 600px;
            background-image: url('img/image 149.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -1;
        }
        
        .lover-flower-img {
            position: absolute;
            right: 600px;
            top: 90%;
            transform: translateY(-50%);
            width: 250px;
            height: 250px;
            background: url('img/lover flower1.png') no-repeat center/contain;
            z-index: -1;
        }
        
        /* Фоновые элементы */
        .product-bg-left {
            position: absolute;
            top: 700px;
            right: 0px;
            width: 500px;
            height: 500px;
            background-image: url('img/Ellipse\ 40.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -2;
            opacity: 0.7;
            transform: rotate(180deg);
        }

        .product-bg-right {
            position: absolute;
            top: -100px;
            left: 0;
            width: 473px;
            height: 417px;
            background-image: url('img/Ellipse\ 42.png');
            background-size: contain;
            background-repeat: no-repeat;
            transform: rotate(180deg);
            z-index: -2;
            opacity: 0.7;
        }

        .product-ellipse-top-left {
            position: absolute;
            top: 100px;
            right: 0;
            width: 600px;
            height: 600px;
            background-image: url('img/Ellipse 52.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -2;
        }

        .product-ellipse-center {
            position: absolute;
            top: 500px;
            left: 10%;
            width: 400px;
            height: 400px;
            background-image: url('img/Ellipse 52.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -2;
        }

        .product-bg-3 {
            position: absolute;
            top: 300px;
            right: 140px;
            width: 700px;
            height: 700px;
            background-image: url('img/Ellipse 52.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -3;
            transform: rotate(45deg);
        }

        .product-bg-4 {
            position: absolute;
            top: 800px;
            left: 0%;
            width: 350px;
            height: 350px;
            background-image: url('img/Ellipse 32(2).png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -1;
        }
        
        /* Footer styles from 404.php */
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

        /* Popup styles from 404.php */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background-color:rgb(10, 20, 20);
            padding: 30px;
            border: var(--blue);
            width: 460px;
            position: relative;
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
        }

        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .popup-header h3 {
            font-family: 'Cormorant';
            color: var(--white);
            margin: 0;
            font-weight: 400;
            font-size: 40px;
            line-height: 100%;
            letter-spacing: 2%;
            text-transform: uppercase;
        }

        .close-popup {
            font-size: 50px;
            color: var(--blue);
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-popup:hover {
            color: var(--blue);
        }

        .popup-line {
            width: 100px;
            height: 1px;
            background-color: var(--blue);
            margin-bottom: 20px;
        }

        .popup-text {
            color: var(--white);
            margin-bottom: 20px;
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group input {
            width: 93%;
            padding: 13px;
            background-color: transparent;
            border: solid 1px #43FFD2;
            color: var(--white);
            font-family: 'Oswald';
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .submit-btn {
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
        }

        .popup-agreement {
            font-family: 'Oswald';
            color: var(--white);
            margin-top: 15px;
            font-weight: 400;
            font-size: 10px;
            line-height: 100%;
            letter-spacing: 2%;
        }
        .popup-agreement span {
            color: var(--pink);
            text-decoration: underline;
        }

        .success-message {
            position: fixed; 
            top: 20px; 
            right: 20px; 
            background: green; 
            color: white; 
            padding: 10px; 
            z-index: 10001;
        }
    </style>
</head>
<body>
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
    <div class="contact-info2">
        <p class="clickable-phone">+375 (29) 113-69-69</p>
        <div class="cart-icon-container">
            <img src="img/ph_handbag-thin.png" class="cart-icon" style="height: 50px; width: 50px;" alt="Корзина">
            <span class="cart-count"><?php echo $cartCount; ?></span>
        </div>
    </div>
</header>

<div class="success-container">
    <h1 class="success-title">Оплата прошла <span>успешно!</span></h1>
    
    <div class="order-number">Ваш номер заказа – <?php echo $order_id; ?></div>
    
    <div class="lover-flower-img"></div>
    
    <div class="notification">Спасибо за заказ! <br>Вы получите уведомление о статусе вашего заказа</div>
    
    <a href="index.php" class="home-link">На главную</a>
    
    <div class="success-image"></div>
</div>

<!-- Фоновые элементы -->
<div class="product-bg-left"></div>
<div class="product-bg-right"></div>
<div class="product-ellipse-top-left"></div>
<div class="product-ellipse-center"></div>
<div class="product-bg-3"></div>
<div class="product-bg-4"></div>

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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация корзины
        function initCart() {
            // Обработчики для открытия/закрытия корзины
            document.querySelectorAll('.cart-icon-container, .cart-icon').forEach(el => {
                el.addEventListener('click', function() {
                    // Здесь можно добавить логику открытия корзины
                    alert('Ваша корзина обнулена после оформления заказа.');
                });
            });
        }

        // Инициализация всех компонентов
        initCart();

        // Обработчик для телефона в шапке
        const phoneNumber = document.querySelector('.contact-info2 p');
        if (phoneNumber) {
            phoneNumber.style.cursor = 'pointer';
            phoneNumber.addEventListener('click', function() {
                document.getElementById('callback-popup').style.display = 'flex';
                // Сброс сообщений при открытии попапа
                const successMessage = document.querySelector('.success-message');
                if (successMessage) {
                    setTimeout(() => {
                        successMessage.remove();
                        location.reload(); // Перезагрузка страницы после успешной отправки
                    }, 3000);
                }
            });
        }

        // Закрытие попапа
        document.querySelector('.close-popup').addEventListener('click', function() {
            document.getElementById('callback-popup').style.display = 'none';
            // Если есть сообщение об успехе, перезагружаем страницу
            if (document.querySelector('.success-message')) {
                location.reload();
            }
        });

        // В обработчике отправки формы
        document.getElementById('callback-form').addEventListener('submit', function(e) {
            // Не нужно preventDefault(), так как хотим обычную отправку формы
            // После отправки попап закроется благодаря перенаправлению
        });

        // Закрытие попапа при клике вне его
        document.querySelector('.popup-overlay').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                // Если есть сообщение об успехе, перезагружаем страницу
                if (document.querySelector('.success-message')) {
                    location.reload();
                }
            }
        });

        // Маска для телефона
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,2})(\d{0,3})(\d{0,2})(\d{0,2})/);
                e.target.value = !x[2] ? x[1] : '+375 (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
            });
        }
    });
</script>

<?php if (!empty($success_message)): ?>
    <div class="success-message">
        <?php echo $success_message; ?>
    </div>
    <script>
        // Автоматическое скрытие сообщения через 3 секунды
        setTimeout(function() {
            document.querySelector('.success-message').remove();
        }, 3000);
    </script>
<?php endif; ?>

<!-- Попап для заказа звонка -->
<div id="callback-popup" class="popup-overlay" style="display: none;">
    <div class="popup-content">
        <div class="popup-header">
            <h3>Заказать звонок</h3>
            <span class="close-popup">&times;</span>
        </div>
        <div class="popup-line"></div>
        <p class="popup-text">Впишите свои данные, и мы свяжемся с Вами.<br> Ваши данные ни при каких обстоятельствах<br> не будут переданы третьим лицам.</p>
        <form id="callback-form" method="POST">
            <input type="hidden" name="callback_submit" value="1">
            <div class="form-group">
                <input type="text" name="name" placeholder="Ваше имя" required>
            </div>
            <div class="form-group">
                <input type="tel" name="phone" placeholder="+375 (__) ___-__-__" required>
            </div>
            <button type="submit" class="submit-btn">Отправить</button>
        </form>
        <p class="popup-agreement">Нажимая на кнопку «Отправить», я даю свое согласие на обработку<br> персональных данных, в соответствии с <span>Политикой конфиденциальности</span> </p>
    </div>
</div>
</body>
</html>