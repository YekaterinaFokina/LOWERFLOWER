<?php
include 'config.php';
session_start();

// Обработка формы (как в основном коде)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['phone']) && isset($_POST['comment'])) {
    if (empty($_POST['name'])) {
        $message = "Пожалуйста, укажите Ваше имя";
    } elseif (empty($_POST['phone'])) {
        $message = "Пожалуйста, укажите Ваш телефон";
    } elseif (empty($_POST['comment'])) {
        $message = "Пожалуйста, оставьте свой комментарий";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (name, phone, comment, submission_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $_POST['name'], $_POST['phone'], $_POST['comment']);
        
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        } else {
            $message = "Ошибка при отправке формы: " . $stmt->error;
        }
        $stmt->close();
    }
}

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "";
}


// Получаем количество товаров в корзине
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
// Подключение попапа заказа звонка
include 'callback_popup.php';

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты | LOVER FLOWER</title>
    
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
            font-family: 'Cormorant';
            background-color: var(--black);
            color: var(--white);
            padding-top: 100px;
        }
        
        /* Стили для хлебных крошек */
        .breadcrumbs {
            margin: 20px 165px;
            font-family: 'Oswald';
            font-size: 14px;
            color: var(--white);
            font-weight: 400;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }
        
        .breadcrumbs a {
            color: var(--white);
            text-decoration: none;
        }
        
        .breadcrumbs a:hover {
            text-decoration: underline;
            color: var(--blue);
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
        /* Стили для основного контента */
        .contact-page {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .contact-title1 {
            font-family: 'Cormorant';
            font-weight: 400;
            font-size: 100px;
            line-height: 100%;
            letter-spacing: 2%;
            text-transform: uppercase;
            margin: 50px 165px;
            margin-left: 301px;
        }
        
        /* Стили для блоков контактов */
        .contact-blocks {
            display: flex;
            justify-content: space-between;
            margin: 0 165px 50px;
            gap: 30px;
        }
        
        .contact-block {
            flex: 1;
            background-color: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            height: 255px;
            width: 255px;
        }
        
        .contact-block h3 {
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 20px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 20px;
            text-align: center;
            padding-top: 60px;
        }
        
        .contact-block p {
            font-family: 'Oswald';
            font-weight: 300;
            font-size: 20px;
            line-height: 26px;
            letter-spacing: 2%;
            text-align: center;

        }

    .questions-section {
        height: 866px;
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
        margin-left: 300px;
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
        border: 1px solid #555555;
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
        /* Стили для карты */
        .map-section {
            margin: 100px 165px;
        }
        
        .map-section h3 {
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 20px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 50px;
        }
        
        .map-container {
            width: 1100px;
            height: 100%;
            
            overflow: hidden;
        }
        
        /* Адаптивность */
        @media (max-width: 1200px) {
            .contact-blocks {
                flex-wrap: wrap;
            }
            
            .contact-block {
                flex: 0 0 calc(50% - 10px);
                margin-bottom: 20px;
            }
            
            .contact-title1, .map-section h3 {
                font-size: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .contact-block {
                flex: 0 0 100%;
            }
            
            .contact-title1, .map-section h3 {
                font-size: 50px;
                margin-left: 20px;
                margin-right: 20px;
            }
            
            .contact-blocks, .map-section {
                margin-left: 20px;
                margin-right: 20px;
            }
        }
        .cart-footer {
        padding: 20px;
        border-top: 1px solid var(--blue);
    }

    .footer-section {
        background-color: var(--black);
        padding: 50px 40px;
        font-family: 'Oswald';
        color: var(--white);
        position: absolute;
        left: 0px;
        right: 0px;
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


/* Стили для фоновых изображений */
.background-image {
    position: absolute;
    z-index: -1;
    pointer-events: none;
}

/* Изображение справа от заголовка */
.contact-title-image {
    right: 0;
    top: 10px;
    width: 700px;
    height: auto;
}

/* Изображение слева от блока questions-left */
.questions-left-image {
    left: 0;
    top: 1000px;
    transform: translateY(-50%);
    width: 900px;
    height: auto;
}

/* Изображение слева от блока questions-right */
.questions-right-image {
    left: 30%;
    top: 1000px;
    width: 300px;
    height: auto;
}

/* Декоративные эллипсы */
.decor-ellipse {
    position: absolute;
    z-index: -1;
    opacity: 0.5;
}

.ellipse-1 {
    top: 0px;
    left: -10%;
    width: 500px;
    height: 500px;
}

.ellipse-2 {
    bottom: -1400px;
    right: 15%;
    width: 700px;
    height: 700px;
}

.ellipse-3 {
    top: 100px;
    left: 20%;
    width: 880px;
    height: 880px;
}
  </style>
    <!-- Подключаем те же шрифты -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Oswald:wght@200..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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
    <div class="contact-info2p">
            <p class="callback-trigger" style="cursor: pointer;">+375 (29) 113-69-69</p>
            <div class="cart-icon-container">
            <img src="img/ph_handbag-thin.png" class="cart-icon" style="height: 50px; width: 50px;" alt="Корзина">
            <span class="cart-count"><?php echo $cartCount; ?></span>
        </div>
    </div>
</header>
<img src="img/image 161.png" class="background-image contact-title-image" alt="">
<img src="img/image 160.png" class="background-image questions-left-image" alt="">
<img src="img/have any questions_(1).png" class="background-image questions-right-image" alt="">

    <!-- Основное содержимое страницы -->
    <div class="contact-page">
        <!-- Хлебные крошки -->
        <div class="breadcrumbs">
            <a href="index.php">Главная</a> / Контакты
        </div>
        
        <!-- Заголовок -->
        <h1 class="contact-title1">Контакты</h1>
        
        <!-- Блоки с контактной информацией -->
        <div class="contact-blocks">
            <div class="contact-block">
                <h3>Время работы</h3>
                <p>с 10:00 до 21:00 без выходных</p>
            </div>
            
            <div class="contact-block">
                <h3>Адрес</h3>
                <p>г. Минск, ул. Тимирязева 67, комн. 112</p>
            </div>
            
            <div class="contact-block">
                <h3>Телефон</h3>
                <p>+375 (29) 113-69-69</p>
            </div>
            
            <div class="contact-block">
                <h3>E-MAIL</h3>
                <p>zakaz@loverflower.by</p>
            </div>
        </div>
        
        <!-- Форма обратной связи (как в основном коде) -->
        <section class="questions-section">
            <div class="questions-container">
                
                <div class="questions-left">
                    <h2>НАПИШИТЕ <br> <span>НАМ</span></h2>
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
        
        <!-- Карта -->
        <div class="map-section">
            <h3>Мы на карте</h3>
            <div class="map-container">
                <!-- Вставка карты Яндекс -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2349.3700343157147!2d27.506293212099962!3d53.925169572348025!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46dbc56c6b46c34f%3A0xb4592a0d073badb1!2z0YPQuy4g0KLQuNC80LjRgNGP0LfQtdCy0LAgNjcsINCc0LjQvdGB0LosINCc0LjQvdGB0LrQsNGPINC-0LHQu9Cw0YHRgtGMIDIyMDAzNSwg0JHQtdC70LDRgNGD0YHRjA!5e0!3m2!1sru!2sru!4v1746364953388!5m2!1sru!2sru" width="1100" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>


    <img src="img/Ellipse 47.png" class="decor-ellipse ellipse-1" alt="">
<img src="img/Ellipse 47.png" class="decor-ellipse ellipse-2" alt="">
<img src="img/Ellipse 47.png" class="decor-ellipse ellipse-3" alt="">

    <!-- Footer (как в основном коде) -->
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
    
    <!-- Модальное окно корзины (как в основном коде) -->
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
    
    <!-- Скрипты (как в основном коде) -->
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
    </script>
</body>
</html>