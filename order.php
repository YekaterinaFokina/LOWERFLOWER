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


// Проверяем, есть ли товары в корзине
if (empty($_SESSION['cart'])) {
    header("Location: catalog.php");
    exit;
}

// Получаем товары из корзины
$cartItems = $_SESSION['cart'];
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Обработка формы заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $recipient_phone = trim($_POST['recipient_phone'] ?? '');
    $recipient_name = trim($_POST['recipient_name'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $delivery_type = $_POST['delivery_type'];
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $apartment = trim($_POST['apartment'] ?? '');
    $delivery_time = trim($_POST['delivery_time'] ?? '');
    $payment_method = $_POST['payment_method'];
    $promo_code = trim($_POST['promo_code'] ?? '');
    
    // Валидация данных
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Пожалуйста, укажите ваше имя';
    }
    
    if (empty($phone)) {
        $errors[] = 'Пожалуйста, укажите ваш телефон';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Пожалуйста, укажите корректный email';
    }
    
    if ($delivery_type === 'courier') {
        if (empty($city)) {
            $errors[] = 'Пожалуйста, укажите город';
        }
        
        if (empty($street)) {
            $errors[] = 'Пожалуйста, укажите улицу';
        }
        
        if (empty($building)) {
            $errors[] = 'Пожалуйста, укажите номер дома';
        }
    }
    
    if (empty($errors)) {
        // Сохраняем заказ в базу данных
        $stmt = $conn->prepare("INSERT INTO orders (
            customer_name, customer_phone, customer_email,
            recipient_name, recipient_phone, comment,
            delivery_type, city, street, building, apartment, delivery_time,
            payment_method, promo_code, total_amount, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())");
        
        $stmt->bind_param(
            "ssssssssssssssd",
            $name, $phone, $email,
            $recipient_name, $recipient_phone, $comment,
            $delivery_type, $city, $street, $building, $apartment, $delivery_time,
            $payment_method, $promo_code, $total
        );
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
            // Сохраняем товары заказа
            foreach ($cartItems as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (
                    order_id, product_id, product_name, quantity, price
                ) VALUES (?, ?, ?, ?, ?)");
                
                $stmt->bind_param(
                    "iisid",
                    $order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']
                );
                
                $stmt->execute();
            }
            
            // Очищаем корзину
            unset($_SESSION['cart']);
            
            // Перенаправляем на страницу подтверждения
            header("Location: order_success.php?id=$order_id");
            exit;
        } else {
            header("Location: order_not_success.php?id=$order_id");
        }
    }
    
    // Если есть ошибки, сохраняем их в сессии
    if (!empty($errors)) {
        $_SESSION['order_errors'] = $errors;
        $_SESSION['order_form_data'] = $_POST;
    }
}

// Количество товаров в корзине
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
// Подключение попапа заказа звонка
include 'callback_popup.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа - Lover Flower</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Oswald:wght@200..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        /* Стили из product.php */
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
        
        /* Остальные стили из product.php... */
        
        /* Стили для страницы оформления заказа */
        .order-container {
            margin: 150px 165px 50px;
            display: flex;
            gap: 50px;
        }
        
        .order-form-container {
            flex: 2;
        }
        
        .order-summary {
            flex: 1;
            padding: 30px;
            border-radius: 10px;
            margin-top: 433px;
            height: fit-content;
        }
        
        .breadcrumbs {
            margin-bottom: 30px;
            font-family: 'Oswald';
            color: var(--white);
            font-weight: 400;
            font-size: 12px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }
        
        .breadcrumbs a {
            color: var(--white);
            text-decoration: none;
        }
        
        .breadcrumbs a:hover {
            color: var(--blue);
            text-decoration: underline;
        }
        
        .page-title {
            font-family: 'Cormorant';
            font-weight: 400;
            font-size: 100px;
            color: var(--white);
            margin-bottom: 150px;
            text-transform: uppercase;
        }
        .page-title span {
            margin-left: 160px;
        }
        
        .section-title{
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 14px;
            color: var(--blue);
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .section-title1 {
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 30px;
            line-height: 100%;
            letter-spacing: 4%;
            color: var(--blue);
            text-transform: uppercase;
            margin-bottom: 70px;
        } 
        
        .form-group {
            margin-bottom: 20px;
        }
        .address-row {
            display: flex;
            gap: 32px; /* Расстояние между элементами */
            margin-bottom: 20px;
        }

        .address-row .form-group {
            min-width: 0; /* Для корректного отображения в flex-контейнере */
        }

        .address-row input {
            width: 160px;
            flex: none; /* Отключаем flex-grow */
            height: 60px;
            background: var(--black);
            border: 1px solid #555;
            color: var(--white);
            font-family: 'Oswald';
            font-size: 14px;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .address-row label {
            display: block;
            margin-bottom: 8px;
            font-family: 'Oswald';
            color: var(--white);
            font-weight: 400;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 6%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-family: 'Oswald';
            color: var(--white);
            font-weight: 400;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 6%;

        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 515px;
            height: 60px;
            background: var(--black);
            border: 1px solid #555;
            color: var(--white);
            padding: 0 15px;
            font-family: 'Oswald';
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 60px;
            width: 515px;
            color: var(--white);
            padding: 15px 15px;
            resize: vertical;
        }
        
        .required-field::after {
            content: '*';
            color: var(--pink);
            margin-left: 3px;
        }
        
        .radio-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .radio-option {
            position: relative;
            padding-left: 40px;
            cursor: pointer;
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 14px;
            color: var(--white);
            line-height: 24px;
            display: flex;
            align-items: center;
            text-transform: uppercase;

        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: transparent;
            border: 1px solid #555;
            border-radius: 50%;
            transition: all 0.3s;
        }
        .radio-option:hover .checkmark {
            border-color: var(--blue);
        }
        
        .radio-option input:checked ~ .checkmark {
            border-color: var(--white);
            background-color: rgba(67, 255, 210, 0.1);
        }
        .radio-option input[type="radio"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            position: absolute;
            opacity: 0;
        }
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .radio-option input:checked ~ .checkmark:after {
            display: block;
        }
        
        .radio-option .checkmark:after {
            top: 4px;
            left: 4.3px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: var(--white);
        }
        .delivery-form {
            display: none;
            margin-bottom: 30px;
        }
        
        .delivery-form.active {
            display: block;
        }
        
        .address-fields {
            display: flex;
            gap: 32px;
        }
        
        .address-fields .form-group {
            flex: 1;
        }
        
        .payment-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .payment-option {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            text-align: center;
            border: 1px solid #555;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 12px;
        }
        
        .payment-option.active {
            border-color: var(--blue);
            background: rgba(67, 255, 210, 0.1);
        }
        
        .promo-code-container {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            height: 60px;
            width: 191px;
        }
        
        .promo-code-container input {
            flex: 1;
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 14px;
            color: var(--white);
            line-height: 100%;
            letter-spacing: 4%;
            background: transparent;
            border: 1px solid #555555;
            padding: 0 15px;
            

        }
        
        .promo-code-container button {
            flex: 1;
            padding: 15px 50px;
            background: transparent;
            border: 1px solid var(--blue);
            color: var(--blue);
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            text-transform: uppercase;
        }
        
        .order-total {
            margin-bottom: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 4%;
            text-transform: uppercase;

        }
        
        .total-row.total {
            font-weight: 700;
            font-size: 20px;
            color: var(--blue);
            margin-top: 20px;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: transparent;
            border: 1px solid var(--white);
            color: var(--white);
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        
        .privacy-policy {
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 12px;
            line-height: 100%;
            color: var(--white);
            letter-spacing: 2%;
    
        }
        
        .privacy-policy a {
            color: var(--blue);
            text-decoration: underline;
        }
        
        .order-summary-title {
            font-family: 'Oswald';
            color: var(--blue);
            margin-bottom: 30px;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 14px;
            line-height: 100%;
            letter-spacing: 6%;
            text-transform: uppercase;

        }
        
        .order-item {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .order-item img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .order-item-info {
            flex: 1;
        }
        
        .order-item-title {
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .order-item-price {
            font-family: 'Oswald';
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .order-item-quantity {
            font-family: 'Oswald';
            font-weight: 300;
            font-size: 12px;
            color: #777;
        }
        
        
        
        .order-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-family: 'Oswald';
            font-weight: 400;
            font-size: 14px;
        }
        
        .order-summary-row.total {
            font-weight: 700;
            font-size: 16px;
            color: var(--blue);
        }
        
        .error-message {
            color: var(--pink);
            font-family: 'Oswald';
            font-weight: 300;
            font-size: 12px;
            margin-top: 5px;
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
        .contact-info2p p.callback-trigger:hover {
            cursor: pointer;
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
    .checkout-btn {
        width: 305px;
        height: 50px;
        color: var(--black);
        background: var(--blue);
        border: none;
        font-family: 'Oswald';
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        text-transform: uppercase;
    }

        
        nav a:hover{
            color: var(--blue);
            text-decoration: underline;
        }
        /* Стили для кнопки "Наверх" */
        .back-to-top {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 60px;
                height: 60px;
                background-color: rgba(0, 0, 0, 0.2);
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
        /* Стили для уведомлений */
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

        /* Стили для декоративных элементов */
        /* Фоновые элементы */
        .product-bg-left {
            position: absolute;
            top: 1700px;
            right: 0px;
            width: 500px;
            height: 500px;
            background-image: url('img/Ellipse\ 40.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -1;
            opacity: 0.7;
            transform: rotate(180deg);
        }

        .product-bg-right {
            position: absolute;
            top: 0;
            left: 0;
            width: 1073px;
            height: 717px;
            background-image: url('img/Ellipse\ 39.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -1;
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
            top: 700px;
            right: 140px;
            width: 700px;
            height: 700px;
            background-image: url('img/Ellipse 52.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -1;
            transform: rotate(45deg);
        }

        .product-bg-4 {
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

        .lover-flower-img {
            position: absolute;
            top: 320px;
            left: 800px;
            width: 250px;
            height: 250px;
            background: url('img/lover flower1.png') no-repeat center/contain;
        }
        .payment-img {
            position: absolute;
            right: -400px;
            top: 170%;
            transform: translateY(-50%);
            width: 250px;
            height: 250px;
            background: url('img/payment.png') no-repeat center/contain;
        }
        /* Стили для декоративных изображений */
        .top-right-decoration {
            position: absolute;
            top: -200px;
            right: 0;
            width: 700px;
            height: 700px;
            background-image: url('img/image 1471.png');
            background-size: contain;
            background-repeat: no-repeat;
            transform: rotate(180deg);
            z-index: -1;
            opacity: 0.7;
        }

        .top-left-decoration {
            position: absolute;
            top: 100px;
            left: 0;
            width: 700px; /* Настройте по необходимости */
            height: 700px; /* Настройте по необходимости */
            background-image: url('img/image 1471.png');
            background-size: contain;
            background-repeat: no-repeat;
            z-index: -1;
            opacity: 0.7;
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
    <div class="contact-info2p">
            <p class="callback-trigger" style="cursor: pointer;">+375 (29) 113-69-69</p>
            <div class="cart-icon-container">
            <img src="img/ph_handbag-thin.png" class="cart-icon" style="height: 50px; width: 50px;" alt="Корзина">
            <span class="cart-count"><?php echo $cartCount; ?></span>
        </div>
    </div>
</header>
<!-- Декоративные элементы -->
<div class="top-left-decoration"></div>
<div class="top-right-decoration"></div>

<div class="order-container">
    <div class="order-form-container">
        <div class="breadcrumbs">
            <a href="index.php">Главная</a> / <span>Оформление заказа</span>
        </div>
        
        <h1 class="page-title">Оформление <span>заказа</span></h1>
        
        <form id="order-form" method="POST">
            <h1 class="section-title1">Оформление заказа</h1>
            <h2 class="section-title">Контактные данные</h2>
            
            <div class="form-group">
                <label for="name" class="required-field">Ваше имя</label>
                <input type="text" id="name" name="name" placeholder="Введите ваше имя" required>
            </div>
            
            <div class="form-group">
                <label for="phone" class="required-field">Ваш телефон</label>
                <input type="tel" id="phone" name="phone" placeholder="+7 (977) 777-77-77" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="required-field">Ваш e-mail</label>
                <input type="email" id="email" name="email" placeholder="Введите вашу почту" required>
            </div>
            
            <div class="form-group">
                <label for="recipient_phone">Телефон получателя (необязательно)</label>
                <input type="tel" id="recipient_phone" placeholder="+7 (977) 777-77-77" name="recipient_phone">
            </div>
            
            <div class="form-group">
                <label for="recipient_name">Имя получателя (необязательно)</label>
                <input type="text" id="recipient_name" placeholder="Введите имя получателя" name="recipient_name">
            </div>
            
            <div class="form-group">
                <label for="comment">Комментарий к заказу</label>
                <textarea id="comment" name="comment" placeholder="Примечания к вашеу заказу,  особые пожелания отделу доставки"></textarea>
            </div>
            
            <h2 class="section-title">Доставка</h2>
            
            <div class="radio-options">
                <label class="radio-option">
                    <input type="radio" name="delivery_type" value="pickup" 
                        <?php echo (!isset($_POST['delivery_type']) || $_POST['delivery_type'] === 'pickup') ? 'checked' : ''; ?>>
                    Самовывоз
                    <span class="checkmark"></span>
                </label>
                
                <label class="radio-option">
                    <input type="radio" name="delivery_type" value="courier" 
                        <?php echo (isset($_POST['delivery_type']) && $_POST['delivery_type'] === 'courier') ? 'checked' : ''; ?>>
                    Доставка курьером
                    <span class="checkmark"></span>
                </label>
            </div>
            
            <!-- Форма доставки курьером -->
            <div id="courier-form" class="delivery-form <?php echo (isset($_POST['delivery_type']) && $_POST['delivery_type'] === 'courier' ? 'active' : ''); ?>">
                <div class="form-group">
                    <label for="city" class="required-field">Город</label>
                    <input type="text" id="city" name="city" placeholder="Введите город" value="<?php echo $_POST['city'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="street" class="required-field">Улица</label>
                    <input type="text" id="street" name="street" placeholder="Введите улицу" value="<?php echo $_POST['street'] ?? ''; ?>">
                </div>
                <div class="address-row">
                    <div class="form-group">
                            <label for="apartmen">Корп/стр</label>
                            <input type="text" style="display: flex; border: 1px solid #555555; height: 60px; width: 160px;" id="apartment" name="apartment" placeholder="Корп/стр" value="">
                    </div>
                    
                    <div class="address-fields">
                        <div class="form-group">
                            <label for="building" class="required-field">Дом</label>
                            <input type="text" style="display: flex; border: 1px solid #555555; height: 60px; width: 160px;" id="building" name="building" placeholder="Дом" value="<?php echo $_POST['building'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="apartment">Кв/офис</label>
                            <input type="text" style="display: flex; border: 1px solid #555555; height: 60px; width: 160px;" id="apartment" name="apartment" placeholder="Кв/офис" value="<?php echo $_POST['apartment'] ?? ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="delivery_time">Время доставки</label>
                    <select id="delivery_time" name="delivery_time">
                        <option value="anytime">В любое время</option>
                        <option value="morning">Утро (9:00 - 12:00)</option>
                        <option value="afternoon">День (12:00 - 18:00)</option>
                        <option value="evening">Вечер (18:00 - 22:00)</option>
                    </select>
                </div>
                
                
                <p style="font-family: 'Oswald';font-weight: 400;font-size: 14px;line-height: 100%;letter-spacing: 10%;text-transform: uppercase;color: #555555;">Стоимость доставки: 0 ₽</p>
            </div>
            
            <!-- Блок оплаты с радио-кнопками -->
            <h2 class="section-title">Оплата</h2>
            
            <div class="radio-options">
                <label class="radio-option">
                    <input type="radio" name="payment_method" value="card" 
                        <?php echo (!isset($_POST['payment_method']) || $_POST['payment_method'] === 'card') ? 'checked' : ''; ?>>
                    Банковская карта
                    <span class="checkmark"></span>
                </label>
                
                <label class="radio-option">
                    <input type="radio" name="payment_method" value="cash" 
                        <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash') ? 'checked' : ''; ?>>
                    Наличными
                    <span class="checkmark"></span>
                </label>
                
                <label class="radio-option">
                    <input type="radio" name="payment_method" value="apple_pay" 
                        <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'apple_pay') ? 'checked' : ''; ?>>
                    Apple Pay
                    <span class="checkmark"></span>
                </label>
                
                <label class="radio-option">
                    <input type="radio" name="payment_method" value="google_pay" 
                        <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'google_pay') ? 'checked' : ''; ?>>
                    Google Pay
                    <span class="checkmark"></span>
                </label>
                
                <label class="radio-option">
                    <input type="radio" name="payment_method" value="crypto" 
                        <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'crypto') ? 'checked' : ''; ?>>
                    Криптовалюта
                    <span class="checkmark"></span>
                </label>
                
                <label class="radio-option">
                    <input type="radio" name="payment_method" value="invoice" 
                        <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'invoice') ? 'checked' : ''; ?>>
                    С расчетного счета
                    <span class="checkmark"></span>
                </label>
            </div>
            
            <div class="promo-code-container">
                <input type="text" id="promo_code" name="promo_code" placeholder="Промокод">
                <button type="button" id="apply-promo">Применить</button>
            </div>
            
            <div class="order-total">
                <div class="total-row">
                    <span style="font-family: Oswald;margin-bottom: 20px;font-weight: 700;font-size: 30px;line-height: 100%;letter-spacing: 4%;text-transform: uppercase; color: var(--blue);">Общая сумма заказа:</span>
                    <span style="font-family: Oswald;font-weight: 700;font-size: 30px;line-height: 100%;letter-spacing: 4%;text-transform: uppercase; color: var(--blue);"><?php echo number_format($total, 0, '', '.'); ?> ₽</span>
                </div>
                <div class="total-row">
                    <span>Скидка:</span>
                    <span>0 ₽</span>
                </div>
                <div class="total-row">
                    <span>Доставка:</span>
                    <span>0 ₽</span>
                </div>
               
            </div>
            
            <div style="position: relative; display: inline-block;">
                <button type="submit" class="checkout-btn">К оплате</button>
                <div class="payment-img"></div>
            </div>
            
            <p class="privacy-policy">
                Нажимая на кнопку «К Оплате», я даю свое согласие на обработку<br> персональных данных, 
                в соответствии с <a href="#">Политикой конфиденциальности</a>,<br> а так же ознакомлен 
                с условиями оплаты и доставки
            </p>
        </form>
    </div>
    
    <div class="order-summary">
        <div class="lover-flower-img"></div>
        <h3 class="order-summary-title">Ваш заказ:</h3>
        
        <?php foreach ($cartItems as $item): ?>
            <div class="order-item">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                <div class="order-item-info">
                    <div class="order-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="order-item-price"><?php echo number_format($item['price'], 0, '', '.'); ?> ₽</div>
                    <div class="add-to-cart-container" style="display: flex; align-items: center; justify-content: space-between;">
                    <div class="quantity-controls" style="border: 0.5px solid #555555; display: flex; align-items: center; justify-content: center; height: 30px; width: 130px;">
                        <button class="quantity-btn minus" data-id="<?php echo $item['product_id']; ?>" style="background: none; border: none; color: white; font-family: 'Oswald'; font-size: 24px; cursor: pointer; padding: 0 10px;">-</button>
                        <input type="number" class="quantity-input" data-id="<?php echo $item['product_id']; ?>" style="font-family: 'Oswald'; text-align: center; font-size: 14px; width: 30px; margin: 0 5px; background: none; border: none; color: white;" value="<?php echo $item['quantity']; ?>" min="1">
                        <button class="quantity-btn plus" data-id="<?php echo $item['product_id']; ?>" style="background: none; border: none; color: white; font-family: 'Oswald'; font-size: 24px; cursor: pointer; padding: 0 10px;">+</button>
                    </div>
                    <span class="remove-item" data-id="<?php echo $item['product_id']; ?>" style="font-family: 'Oswald'; font-weight: 400; font-size: 10px; line-height: 100%; letter-spacing: 4%; text-transform: uppercase; text-decoration: underline; cursor: pointer;">Удалить</span>
                </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="order-summary-total">

            <div class="order-summary-row total">
                <span>Предварительный итог:</span>
                <span><?php echo number_format($total, 0, '', '.'); ?> ₽</span>
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

<!-- Кнопка "Наверх" -->
<div class="back-to-top" id="backToTop"></div>

<!-- Фоновые элементы -->
<div class="product-bg-left"></div>
<div class="product-bg-right"></div>
<div class="product-ellipse-top-left"></div>
<div class="product-ellipse-center"></div>
<div class="product-bg-3"></div>
<div class="product-bg-4"></div>
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
    // Обработчики для кнопок изменения количества
document.addEventListener('click', function(e) {
    // Уменьшение количества
    if (e.target.classList.contains('minus')) {
        const productId = e.target.dataset.id;
        const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
        let quantity = parseInt(input.value);
        
        if (quantity > 1) {
            quantity--;
            input.value = quantity;
            updateCartItem(productId, 'decrease');
        } else {
            // Если количество 1, то при нажатии "-" удаляем товар
            updateCartItem(productId, 'remove');
        }
    }
    
    // Увеличение количества
    if (e.target.classList.contains('plus')) {
        const productId = e.target.dataset.id;
        const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
        let quantity = parseInt(input.value);
        
        quantity++;
        input.value = quantity;
        updateCartItem(productId, 'increase');
    }
    
    // Удаление товара
    if (e.target.classList.contains('remove-item')) {
        const productId = e.target.dataset.id;
        updateCartItem(productId, 'remove');
    }
});

// Обработчик изменения значения в инпуте
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('quantity-input')) {
        const productId = e.target.dataset.id;
        const newQuantity = parseInt(e.target.value);
        
        if (newQuantity < 1) {
            e.target.value = 1;
            return;
        }
        
        // Получаем текущее количество из корзины
        const currentQuantity = <?php echo json_encode(array_column($_SESSION['cart'] ?? [], 'quantity', 'product_id')); ?>;
        
        if (currentQuantity[productId] !== newQuantity) {
            // Вычисляем разницу
            const difference = newQuantity - currentQuantity[productId];
            
            if (difference > 0) {
                // Увеличиваем количество
                for (let i = 0; i < difference; i++) {
                    updateCartItem(productId, 'increase');
                }
            } else {
                // Уменьшаем количество
                for (let i = 0; i < Math.abs(difference); i++) {
                    updateCartItem(productId, 'decrease');
                }
            }
        }
    }
});

// Функция обновления корзины через AJAX
function updateCartItem(productId, action) {
    fetch('order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_cart&product_id=${productId}&action_type=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем счетчик корзины
            updateCartCount(data.cart_count);
            
            // Если товар удален, скрываем его
            if (action === 'remove') {
                const itemElement = document.querySelector(`.order-item [data-id="${productId}"]`).closest('.order-item');
                if (itemElement) {
                    itemElement.remove();
                }
                
                // Если корзина пуста, перезагружаем страницу
                if (data.cart_count === 0) {
                    location.reload();
                }
            }
            
            // Обновляем итоговую сумму
            updateOrderTotal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Ошибка при обновлении корзины', true);
    });
}

// Функция обновления итоговой суммы
function updateOrderTotal() {
    fetch('order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_cart'
    })
    .then(response => response.json())
    .then(data => {
        // Обновляем итоговую сумму в блоке заказа
        document.querySelectorAll('.order-summary-row.total span:last-child').forEach(el => {
            el.textContent = formatPrice(data.total) + ' ₽';
        });
        
        // Обновляем итоговую сумму в форме
        document.querySelectorAll('.total-row.total span:last-child').forEach(el => {
            el.textContent = formatPrice(data.total) + ' ₽';
        });
    });
}

// Функция форматирования цены
function formatPrice(price) {
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
document.addEventListener('DOMContentLoaded', function() {
    // Обработчики для радио-кнопок доставки
    document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Скрываем все формы доставки
            document.querySelectorAll('.delivery-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Показываем нужную форму
            if (this.value === 'courier') {
                document.getElementById('courier-form').classList.add('active');
            }
        });
    });

    // Обработчики для радио-кнопок оплаты
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Скрываем все формы оплаты
            document.querySelectorAll('.payment-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Показываем нужную форму
            document.getElementById(this.value + '-form')?.classList.add('active');
        });
    });

    // Инициализация при загрузке
    const selectedDelivery = document.querySelector('input[name="delivery_type"]:checked');
    if (selectedDelivery && selectedDelivery.value === 'courier') {
        document.getElementById('courier-form').classList.add('active');
    }
    
    const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
    if (selectedPayment) {
        document.getElementById(selectedPayment.value + '-form')?.classList.add('active');
    }

    
    // Применение промокода
    document.getElementById('apply-promo').addEventListener('click', function() {
        const promoCode = document.getElementById('promo_code').value;
        
        if (promoCode.trim() === '') {
            showToast('Введите промокод', true);
            return;
        }
        
        // Здесь можно добавить AJAX запрос для проверки промокода
        showToast('Промокод применен');
    });
    
    // Обработка ошибок формы
    <?php if (isset($_SESSION['order_errors'])): ?>
        const errors = <?php echo json_encode($_SESSION['order_errors']); ?>;
        errors.forEach(error => {
            showToast(error, true);
        });
        
        <?php unset($_SESSION['order_errors']); ?>
    <?php endif; ?>
    
    // Функция для показа уведомлений
    function showToast(message, isError = false) {
        const toast = document.createElement('div');
        toast.className = `toast ${isError ? 'error' : ''}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    // Инициализация кнопки "Наверх"
    function initBackToTop() {
        const backToTopButton = document.getElementById('backToTop');
        
        window.addEventListener('scroll', function() {
            backToTopButton.classList.toggle('visible', window.pageYOffset > 300);
        });
        
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
</script>
</body>
</html>