<?php
include 'config.php';
session_start();

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
    <title>Доставка и оплата | LOVER FLOWER</title>
    <style>
        /* Все стили из предыдущего кода остаются без изменений */
         
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
        /* Добавляем новые стили для этой страницы */
        
        .delivery-page {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
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
        
        .page-title {
            font-family: 'Cormorant';
            font-weight: 400;
            font-size: 100px;
            line-height: 100%;
            letter-spacing: 2%;
            text-transform: uppercase;
            margin: 50px 165px;
        }
        .page-title span{
            margin-left: 150px;
        }
        .notice-block {
            display: flex;
            align-items: center;
            margin: 0 165px 50px;
            margin-left: 20%;
            border-radius: 20px;
            padding: 30px;
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 16px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            color: var(--white);

        }
        .notice-block strong {
            color: var(--pink);

        }
        
        .notice-icon {
            width: 25px;
            height: 150px;
            margin-right: 30px;
        }
        
        .notice-text {
            font-family: 'Oswald';
            font-weight: 300;
            line-height: 2.5;
        }
        .notice-text span {
            color: var(--blue);
        }
        
        .section-title {
            color: var(--pink);
            margin: 50px 165px 90px;
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 20px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }
        .section-title1 {
            color: var(--pink);
            margin: 50px 305px 30px;
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 20px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }
        .section-title2 {
            color: var(--pink);
            margin: 150px 165px 30px;
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 20px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }
        .payment-methods {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin: 0 165px 50px;
        }
        
        .payment-method {
            flex: 1;
            background-color: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px 30px 10px;
            text-align: center;
            position: relative;
        }
        
        .payment-icon {
            width: 10px;
            height: 10px;
            background-color: #6B535F;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20% auto 0px;
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .payment-text {
            font-family: 'Oswald';
            line-height: 1.5;
            font-family: 'Oswald';
            font-weight: 300;
            font-size: 20px;
            line-height: 26px;
            letter-spacing: 2%;
            text-align: center;
            text-transform: uppercase;

        }
        
        .delivery-list {
            margin: 0 305px 50px;
        }
        
        .delivery-list li {
            font-family: 'Oswald';
            margin-bottom: 15px;
            padding-left: 20px;
            position: relative;
            list-style: none;
            font-family: 'Oswald';
            font-weight: 300;
            font-size: 20px;
            line-height: 26px;
            letter-spacing: 2%;
            text-transform: uppercase;
            

        }
        .delivery-list li span {
            color: var(--blue);
        }
        .delivery-list li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--white);
        }

        .delivery-list1 {
            margin: 0 165px 50px;
        }
        
        .delivery-list1 li {
            font-family: 'Oswald';
            margin-bottom: 15px;
            padding-left: 20px;
            position: relative;
            list-style: none;
            font-family: 'Oswald';
            font-weight: 300;
            font-size: 20px;
            line-height: 26px;
            letter-spacing: 2%;
            text-transform: uppercase;
            

        }
        .delivery-list1 li span {
            color: var(--blue);
        }

        
        .delivery-list1 li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--white);
        }
        


        
        .delivery-list2 li {
            padding-left: 20px;
            position: relative;
            list-style: none;
            font-family: 'Oswald';
            font-weight: 300;
            font-size: 20px;
            line-height: 26px;
            letter-spacing: 2%;
            

        }
        .delivery-list2 li span {
            color: var(--blue);
        }

        
        .delivery-list2 li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--white);
        }

        .additional-info {
            background-color: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            margin: 150px 165px;
        }
        
        .info-title {
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 20px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;
            color: var(--pink);
            margin-bottom: 20px;

        }

        .background-image {
        position: absolute;
        z-index: -1;
        pointer-events: none;
    }
    
    .bg-image-156 {
        top: 200px;
        right: 0;
        height: auto;
        opacity: 0.7;
    }
    
    .bg-image-157 {
        bottom: -200%;
        left: 0;
        height: auto;
        opacity: 0.7;
    }
    
    .bg-image-158 {
        bottom: -280%;
        right: 0;
        
        height: auto;
        opacity: 0.5;
    }
    .bg-image-159 {
        bottom: -260%;
        right: 82%;
        height: auto;
        z-index: 2;
    }
    .bg-ellipse {
        position: absolute;
        z-index: -1;
        
    }
    
    /* Позиции для Ellipse 45 */
    .ellipse-1 {
        top: -20%;
        left: -10%;
        width: 400px;
        height: 400px;
    }
    
    .ellipse-2 {
        top: -30%;
        right: 0%;
        width: 500px;
        height: 500px;
    }
    
    .ellipse-3 {
        bottom: -200%;
        left: 20%;
        width: 600px;
        height: 700px;
    }
    
    .ellipse-4 {
        bottom: -100%;
        right: 83%;
        width: 500px;
        height: 700px;
    }
    .ellipse-5 {
        bottom: -300%;
        right: 80%;
        width: 700px;
        height: 700px;
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

<!-- Основное содержимое страницы -->
<div class="delivery-page">
    <!-- Хлебные крошки -->
    <div class="breadcrumbs">
        <a href="/">Главная</a> / Доставка и оплата
    </div>
    
    <!-- Заголовок -->
    <h1 class="page-title">Доставка <br> <span>и оплата</span></h1>
    
    <!-- Блок с уведомлением -->
    <div class="notice-block">
        <img src="img/!.png" class="notice-icon" alt="Восклицательный знак">
        <p class="notice-text">
            <strong>Дорогие клиенты</strong><br>
            Во время пандемии (COVID-19) компания Lover Flower призывает всех меньше<br> контактировать с другими людьми для защиты себя и своих близких. Именно<br> поэтому мы организовали <span>БЕСКОНТАКТНУЮ ДОСТАВКУ</span>
        </p>
    </div>
    
    <!-- Способы оплаты -->
    <h2 class="section-title">Способы оплаты:</h2>
    <div class="payment-methods">
        <div class="payment-method">
            <div class="payment-icon"></div>
            <p class="payment-text">БАНКОВСКОЙ КАРТОЙ ПРИ ОФОРМЛЕНИИ ЗАКАЗА ЧЕРЕЗ САЙТ или по ссылке</p>
        </div>
        
        <div class="payment-method">
            <div class="payment-icon"></div>
            <p class="payment-text">НАЛИЧНЫМИ, БАНКОВСКОЙ КАРТОЙ ПРИ САМОВЫВОЗЕ или с расчетного счета организации</p>
        </div>
        
        <div class="payment-method">
            <div class="payment-icon"></div>
            <p class="payment-text">НАЛИЧНЫМИ ПРИ ДОСТАВКЕ КУРЬЕРОМ</p>
        </div>
        
        <div class="payment-method">
            <div class="payment-icon"></div>
            <p class="payment-text">КРИПТОВАЛЮТОЙ</p>
        </div>
    </div>
    
    <!-- Стоимость доставки -->
    <h2 class="section-title1">Стоимость доставки</h2>
    <ul class="delivery-list">
        <li><b>Бесплатно</b> – при заказе на сумму <span>от 90 рублей</span></li>
        <li><b>10 рублей</b> – при заказе на сумму <span>менее 90 рублей</span></li>
        <li>Так же вы можете забрать ваш заказ самостоятельно по адресу:<br>
        <span>г. Минск, ул. Тимирязева д. 67, комн. 112 ежедневно с 10.00 до 21.00</span></li>
    </ul>
    
    
    <!-- Условия доставки -->
    <h2 class="section-title2">Условия доставки</h2>
    <ul class="delivery-list1">
        <li>Доставка осуществляется по городу Минску в пределах МКАД <span>в любой день</span></li>
        <li>Возможность, сроки и стоимость доставки за пределы МКАД, доставки в ночное время,<br> праздники <span>оговариваются с менеджером</span></li>
    </ul>
    
    <!-- Дополнительная информация -->
    <div class="additional-info">
        <h3 class="info-title">Дополнительно:</h3>
        <p style="font-family: 'Oswald'; font-weight: 300; margin-bottom: 30px;font-size: 20px;line-height: 100%;letter-spacing: 4%;color: var(--white);">
            Доставка иному лицу возможна только в случае оплаты заказа заказчиком. Доставка осуществляется не ранее<br> чем через 2 часа после оплаты заказа, но может быть ранее, если букет есть в наличии либо по договорённости<br> с менеджером.<br><br>
            Время ожидания курьера составляет 15 минут.<br><br>
            В случае если на момент доставки цветов получателя нет либо нет возможности по иным причинам произвести<br> доставку (указан неточный адрес, закрытая входная дверь, контрольно-пропускная система и др.), мы оставляем<br> за собой право по собственному выбору:
        </p>
        
        <ul class="delivery-list2" style="margin-left: -3%;">
            <li>оставить цветы тому, кто открыл дверь;</li>
            <li>с заказчиком согласовать повторную доставку, которая дополнительно оплачивается;</li>
            <li>отказаться от передачи цветов без возврата денежных средств.</li>
        </ul>
        
        <p style="font-family: 'Oswald'; font-weight: 300; margin-bottom: 30px;font-size: 20px;line-height: 100%;letter-spacing: 4%;color: var(--white);">
            Если вы либо иной получатель не получили заказ, вам необходимо сообщить об этом менеджеру по телефону <span style="color: var(--blue);">+375 29 113 69 69</span>.
        </p>
        
        <h3 class="info-title">Возврат денег</h3>
        <p style="font-family: 'Oswald'; font-weight: 300; margin-bottom: 30px;font-size: 20px;line-height: 100%;letter-spacing: 4%;color: var(--white);">
            При отказе заказчика от заказа в течение двух часов, если заказ ещё не начал готовиться, средства<br> возвращаются в полном объёме. Если же флорист начал подготовку, то заказчик имеет право на возврат 50%<br> стоимости, либо, если ещё не был оплачен, то обязан оплатить 50%.<br><br>
            Цветы надлежащего качества возврату и обмену не подлежат, а если имеются какие-либо недостатки в цветах – <br>возврат производится лишь если эти недостатки не являются природными и естественными изъянами растения.<br>
            Возврат денежных средств производится незамедлительно на тот счёт, с которого произошла оплата, их же<br> поступление на счёт зависит от платёжной системы.
        </p>
    </div>
</div>
<!-- ФОНОВЫЕ ИЗОБРАЖЕНИЯ-->
<img src="img/image 156.png" class="background-image bg-image-156" alt="">
<img src="img/image 157.png" class="background-image bg-image-157" alt="">
<img src="img/image 158.png" class="background-image bg-image-158" alt="">
<img src="img/lover flower3.png" class="background-image bg-image-159" alt="">

<img src="img/Ellipse 45.png" class="bg-ellipse ellipse-1" alt="">
<img src="img/Ellipse 45.png" class="bg-ellipse ellipse-2" alt="">
<img src="img/Ellipse 45.png" class="bg-ellipse ellipse-3" alt="">
<img src="img/Ellipse 45.png" class="bg-ellipse ellipse-4" alt="">
<img src="img/Ellipse 45.png" class="bg-ellipse ellipse-5" alt="">
<!-- Footer-->
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