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
// Обработка формы заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orgName'])) {
    $organizationName = $_POST['orgName'];
    $unp = $_POST['inn'];
    $postalAddress = $_POST['postAddress'];
    $accountNumber = $_POST['accountNumber'];
    $contactPerson = $_POST['contactPerson'];
    $bankCode = $_POST['bankCode'];
    $contactPhone = $_POST['phoneNumber'];
    
    // Очистка и преобразование стоимости букета
    $bouquetCost = preg_replace('/[^\d.]/', '', $_POST['bouquetPrice']);
    $bouquetCost = floatval($bouquetCost);

    $email = $_POST['email'];
    $estimatedRequestsPerMonth = $_POST['requestsPerMonth'];
    $submissionDate = date('Y-m-d H:i:s');

    // Подготовка SQL запроса
    $stmt = $conn->prepare("INSERT INTO bouquet_requests (organization_name, postal_address, contact_person_name, contact_phone, bouquet_cost, email, unp, account_number, bank_code, estimated_requests_per_month, submission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $organizationName, $postalAddress, $contactPerson, $contactPhone, $bouquetCost, $email, $unp, $accountNumber, $bankCode, $estimatedRequestsPerMonth, $submissionDate);

    if ($stmt->execute()) {
        // Успешно добавлено - перенаправляем на ту же страницу с параметром успеха
        header("Location: client.php?success=1");
        exit(); // Завершаем выполнение скрипта
    } else {
        // Ошибка при добавлении
        $response = ['success' => false, 'message' => 'Ошибка при отправке заявки.'];
        echo json_encode($response);
        exit();
    }
    
    $stmt->close();
}

// Проверка на успешную отправку формы
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>alert('Заявка успешно отправлена!');</script>";
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
    <title>Корпоративные клиенты</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.cdnfonts.com/css/cormorant-2" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Cormorant+Garamond" rel="stylesheet">
            
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,100..700;1,300..700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Oswald:wght@200..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="style/style-client.css">
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
    --input-bg: rgba(0, 0, 0, 0.5);
    --text-color: #fff;
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
    max-height: 3400PX;
    
}
.left-img-1{
    position: absolute;
    z-index: 1200;

}
.right-img-1{
    position: absolute;
    right: 0;
    top: 0;
    z-index: 1;
}
.right-img-2{
    position: absolute;
    left: 0;
    top: 1630px;
    z-index: 555;
    width: 1111px;
}
.right-img-2 img{
    
    width: 110%;
}
.right-img-3{
    position: absolute;
    left: 0;
    top: 1860px;
    z-index: 500;
    
}
.right-img-4{
    position: absolute;
    right: 450px;
    bottom: 300px;
    z-index: 500;
    
    
}
.light{
    position: absolute;
    z-index: 500;
}
.light-1{
    top: -300px;
    left: -650px;

}
.light-2{
    top: -200px;
    right: -350px;
    z-index: 1;

}
.light-3{
    top: 1300px;
    left: 400px;

}

/* Эти привязаны к section-back */
.light-4{
    bottom: -500px;
    left: -550px;
    transform: rotate(90deg);
    z-index: 1;

}
.light-5{
    bottom: -400px;
    right: -650px;
    z-index: 1;

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
    z-index: 1000;
    
    
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}
/* здесь ограничиние по ширине 80% сайта */
.fixed-header {
    max-width: 80%;display: flex;
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
    min-height: 5000px;
    position: relative;
    overflow: hidden;
}

.section-container{
    max-width: 75%;
    margin: 0  auto;
    margin-top: 100px;
    font-family: 'Oswald';
    z-index: 600;
}

.container-header{
    text-transform: uppercase;
    color: var(--white);
    padding-top: 30px;
}

.container-header .head-2 h1{
    margin: 60px auto;
    max-width: 90%;
    font-family: 'Cormorant ', serif;
    font-size: 120px;
    font-weight: 100;
    letter-spacing: 2%;
}
.container-header .head-2 h2{
    margin: 30px auto;
    max-width: 90%;
   color: var(--white);
    font-family: 'Cormorant ', serif;
    font-size: 50px;
    font-weight: 100;
    letter-spacing: 2%;
}
.container-header .head-2 h2 span{
    margin-left: 12%;
}
.container-header .head-2 h2 span img{
    margin-left: 25px;
}



/* Букеты */
.container {
    max-width: 75%;
    margin: 80px auto;
    font-family: 'Oswald';
    
}

h2 {
    color: --white; /* Светло-зеленый цвет для заголовка */
    font-size: 29px;
    margin-bottom: 80px;
    max-width: 70%;
    font-family: 'Oswald';
    font-weight: 300;
}

.container .p {
    margin-left: 7%;
    max-width: 70%;
    margin-bottom: 15px;
    font-size: 26px;
    font-family: 'Oswald';
    font-weight: 100;
}

.highlighted {
    color: var(--blue); /* Светло-зеленый цвет для выделенного текста */
    
}
.container-header-1{
    text-transform: uppercase;
    color: var(--white);
    padding-top: 30px;
    
}

.container-header-1 {
    margin: 0 auto;
    margin-bottom: 0;
    max-width:95%;
    margin-left: 230px;
    color: var(--white);
   
    
    
}
.container-header-1 .head-2 h2{
    max-width:85%;
    font-family: 'Cormorant ', serif;font-size: 50px;
    letter-spacing: 2%;
    font-weight: 100;
    margin: 0;
}
.container-header-1 .head-2-p{
    margin-left: 0;
    font-size: 26px;
    font-family: 'Oswald';
    font-weight: 100;
    color: var(--blue);
}



/* Количество заявок в месяц */
.container {
    width: 80%;
    margin: 20px auto;
  }

  .grid-container {
    display: grid;
    grid-template-columns: 1fr 1fr; /* Два столбца */
    grid-template-rows: repeat(5, auto); /* 5 строк */
    gap: 10px 30px ;
   
  }

  .grid-item {
    background-color: rgba(0, 0, 0, 0.5);
    padding: 20px;
    border-radius: 5px;
    text-align: justify;
    z-index: 600;
  }
  .gd{
    
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .grid-item h3 {
    font-family: 'Oswald';
    font-weight: 400;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--blue); /* Светло-зеленый цвет для заголовков */
    padding-top: 4PX;
    
  }

  .grid-item p {
    margin: 0;
  }

  .grid-item:nth-child(2) {
    justify-self: stretch; /* Растягивание второго элемента по ширине */
  }

  .grid-item:nth-child(1) {
    align-self: center; /* Центрирование первого элемента по вертикали */
  }

  .stages {
    margin-top: 60px;
    margin-left: 54%;
  }

  .stages h2 {
    color:var(--blue);
    font-size: 1.5em;
    border-bottom: 1px solid ;
    padding-bottom: 25px;
    margin-bottom: 10px;
    font-family: 'Cormorant ', serif;
    font-weight: 100;
    font-size: 50px;

  }

  .stages ol {
    list-style-type: decimal;
    padding-left: 20px;
  }

  .stages li {
    margin-bottom: 10px;
    font-family: 'Oswald';
    font-weight: 300;
    letter-spacing: 2px;

  }

/* анкета */
.container {
    width: 80%;
    margin: 20px auto;
  }
  .head-3{
    margin-top: 350px;
    
  }
  .head-3 h2{
    max-width:100%;
    font-family: 'Cormorant ', serif;
    font-size: 50px;
    font-weight: 100;
    letter-spacing: 2%;
    text-transform: uppercase;
}
  .form-block {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    position: relative;
    z-index: 5000;
    padding: 30px;
    border-radius: 10px;
  }

  .form-block h2 {
    color: var(--blue);
    grid-column: 1 / span 2;
    text-align: left;
    margin-top: 0;
    font-size: 30px;
    font-weight: 500;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .form-group label {
    display: block;
    margin-bottom: 5px;
    
  }

  .form-group input {
    width:93%;
    padding: 20px;
    background-color: var(--input-bg);
    border: 1px solid #555555;
    border-radius: 5px;
    color: var(--text-color);
  }

  .form-group input:focus {
    outline: none;
    border: 1px solid var(--blue);
  }
  /* кнопка */
  .submit-button {
    font-family: 'Oswald';
    font-weight: 200;
    background-color: #43FFD2; /* Бирюзовый цвет */
    color: #000; /* Черный цвет текста */
    font-size: 16px; /* Размер шрифта */
    font-weight: bold; /* Жирный шрифт */
    padding: 20px 120px; /* Отступы внутри кнопки */
    border: none; /* Убираем рамку */
    margin-left: 30px;
    cursor: pointer; /* Изменяем курсор при наведении */
    transition: background-color 0.3s ease; /* Плавный переход цвета фона */
    text-transform: uppercase; /* Текст в верхнем регистре */
  }
  
  .submit-button:hover {
    background-color: #32CD99; /* Более темный бирюзовый цвет при наведении */
  }
  
  .submit-button:focus {
    outline: none; /* Убираем обводку при фокусе */
    box-shadow: 0 0 5px rgba(67, 255, 210, 0.5); /* Подсветка при фокусе */
  }
  .submit-button-text{
    margin-left: 30px;
    max-width: 44%;
    letter-spacing: 2px
  }
  .submit-button-text a{
    color: #7D2253;
  }
  

/* Footer */
.footer-section {
    position: relative;
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
            z-index: 5000;
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
    <div class="light light-1"><img src="img/image/свечение.png" alt=""></div>
    <div class="light light-2"><img src="img/image/свечение.png" alt=""></div>
    <div class="light light-3"><img src="img/image/свечение.png" alt=""></div>
   
    <!-- правая картинка верхняя-->
    <div class="right-img-1"><img src="img/image/image 158.png" alt=""></div>
    <!-- правая картинка нижняя-->
    <div class="right-img-2"><img src="img/image/image 159.png" alt=""></div>
    <div class="right-img-3"><img src="img/image/image 170.png" alt=""></div>
    <div class="right-img-4"><img src="img/image/lover flower.png" alt=""></div>
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
        <div class="left-img-1"><img src="img/image/image 157.png" alt=""></div>
        <div class="section-container">
            <div class="container-header">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a> / Корпоротивным клиентам
            </div>  
                <div class="head-2">
                    <h1>Букеты</h1>
                    
                </div>
                <div class="head-2">
                    <h2>поздравления для <br> <span>Ваших работников</span></h2>
                </div>
            </div>
           
        </div>
        
        <div class="container">
            <h2>ЕСЛИ У ВАС БОЛЬШОЕ КОЛИЧЕСТВО СОТРУДНИКОВ (ИЛИ НЕ ОЧЕНЬ) И ВЫ УСТАЛИ ПОМНИТЬ О КАЖДОМ ИХ ДНЕ РОЖДЕНИИ И КАК ЧЕМ ПОЗДРАВИТЬ, ТО <span class="highlighted">МЫ МОЖЕМ ДЕЛАТЬ ЭТО ЗА ВАС.</span></h2>
    
            <p class="p">Одним вариантом сотрудничества является предоставление нам перечня сотрудников с Днями их рождений и мы уже самостоятельно связываемся с каждым, изготавливаем букет либо композицию и доставляем в удобное время сотруднику либо по месту работы либо по месту жительства, так как в настоящее время многие сотрудники продолжают выполнять работу удалённо либо находятся на больничном или в отпуске.</p>
    
            <p class="p">Другим вариантом сотрудничества является заказ букетов самостоятельно представителем организации за день до нужной даты.</p>
    
            <p class="p">Так Вы <span class="highlighted">экономите время</span> остальных сотрудников на сбор денег на поиск подарка и доставку его получателю, а также оберегаете себя от риска пропустить кого-либо из важных организации людей, а именно так Вы проявляете заботу и внимание к сотрудникам для того, чтобы и к делам Вашей организации они относились со всей внимательностью и также не пропускали важные дни.</p>
        </div>
        <div class="container-header-1">
          
            <div class="head-2">
                <h2>Букеты могут изготавливаться в фирменном цвете организации и есть возможность делать поздравления на Вашей фирменной открытке, которую мы можем изготовить сами.</h2>
                <p class="head-2-p">Стоимость одного букета не менее 60 рублей.</p>
            </div>
        </div>
        <!-- заявки таблица -->
        <div class="container">
        <div class="grid-container">
            <div class="grid-item">
              <h3>КОЛИЧЕСТВО ЗАЯВОК В МЕСЯЦ</h3>
          
            </div>
            <div class="grid-item">
              <h3>ПРИЯТНЫЕ БОНУСЫ</h3>
             
            </div>
            <div class="grid-item gd">
                <p>1-2</p>
              </div>
            
            <div class="grid-item ">
                
                <p>Стоимость одной доставки по Минску – 10 рублей, за пределы МКАД – от 20 рублей</p>
              </div>
              <div class="grid-item gd">
              <p>3-10</p>
            </div>
            <div class="grid-item ">
              <p>Доставка бесплатно</p>
              <p>Букет-подарок руководителю в его День рождения</p>
            </div>
            <div class="grid-item gd">
              <p>Более 10</p>
            </div>
            <div class="grid-item ">
              <p>Доставка бесплатно</p>
              <p>Букет-подарок руководителю в его День рождения</p>
              <p>Праздничная ель перед Новым годом</p>
            </div>
          </div>
      
          <div class="stages">
            <h2>ЭТАПЫ РАБОТЫ:</h2>
            <ol>
              <li>ЗАПОЛНЕНИЕ ЗАЯВКИ</li>
              <li>ПОДПИСАНИЕ ДОГОВОРА</li>
              <li>ВРУЧЕНИЕ ЦВЕТОВ</li>
              <li>ОТЧЕТ О ДОСТАВЛЕННЫХ ЗАКАЗАХ</li>
              <li>ОПЛАТА</li>
            </ol>
          </div>
        
            <div class="stages">
              <h2>ЭТАПЫ РАБОТЫ:</h2>
              <ol>
                <li>ЗАПОЛНЕНИЕ ЗАЯВКИ</li>
                <li>ПОДПИСАНИЕ ДОГОВОРА</li>
                <li>ВРУЧЕНИЕ ЦВЕТОВ</li>
                <li>ОТЧЕТ О ДОСТАВЛЕННЫХ ЗАКАЗАХ</li>
                <li>ОПЛАТА</li>
              </ol>
            </div>
          </div>
    
    <!-- Анкета -->
    <div class="container">
        <div class="head-3">
                <h2>Если у Вас единичный заказ, то можете выбрать букет в каталоге либо заказать индивидуальный букет и указать его стоимость, а при оформлении заказа в корзине указать, что оплата будет производиться с расчётного счёта организации.</h2>
              
            </div>
        <form action="" method="post">

        
            <div class="form-block">
                <h2>ЗАПОЛНИТЕ ЗАЯВКУ:</h2>
            
                <div class="form-group">
                    <label for="orgName">Наименование организации:</label>
                    <input type="text" id="orgName" name="orgName" placeholder="Введите наименование Вашей организации">
                </div>
            
                <div class="form-group">
                    <label for="inn">УНП:</label>
                    <input type="text" id="inn" name="inn" placeholder="УНП">
                </div>
            
                <div class="form-group">
                    <label for="postAddress">Почтовый адрес:</label>
                    <input type="text" id="postAddress" name="postAddress" placeholder="Введите почтовый адрес">
                </div>
            
                <div class="form-group">
                    <label for="accountNumber">Расчетный счет:</label>
                    <input type="text" id="accountNumber" name="accountNumber" placeholder="Введите номер расчетного счета">
                </div>
            
                <div class="form-group">
                    <label for="contactPerson">Контактное лицо:</label>
                    <input type="text" id="contactPerson" name="contactPerson" placeholder="Введите имя контактного лица">
                </div>
            
                <div class="form-group">
                    <label for="bankCode">Код банка:</label>
                    <input type="text" id="bankCode" name="bankCode" placeholder="Код банка">
                </div>
            
                <div class="form-group">
                    <label for="phoneNumber">Контактный номер телефона:</label>
                    <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="+7 (977) 777-77-77">
                </div>
            
                <div class="form-group">
                    <label for="requestsPerMonth">Предполагаемое количество заявок в месяц:</label>
                    <input type="number" id="requestsPerMonth" name="requestsPerMonth" placeholder="Введите предполагаемое количество заявок в месяц">
                </div>
            
                <div class="form-group">
                    <label for="bouquetPrice">Стоимость букета сотруднику (если разная – указать):</label>
                    <input type="text" id="bouquetPrice" name="bouquetPrice" placeholder="Укажите стоимость букета сотруднику">
                </div>
                <br>
                <div class="form-group">
                    <label for="email">Адрес электронной почты:</label>
                    <input type="email" id="email" name="email" placeholder="Укажите Ваш адрес электронной почты">
                </div>
                
            </div>

            <button class="submit-button" type="submit">
                ОТПРАВИТЬ
            </button>
            <p class="submit-button-text">Нажимая  на кнопку «Отправить», я даю свое согласие на обработку персональных данных, в соответствии с <a href="">Политикой конфиденциальности</a></p>
        </form>   
        <div class="light light-4"><img src="img/image/свечение.png" alt=""></div>
        <div class="light light-5"><img src="img/image/свечение.png" alt=""></div>
    </div>



    </div>
    
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
</script>



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
</body>
</html>