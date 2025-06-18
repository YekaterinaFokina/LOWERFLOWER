<?php

// callback_popup.php
if (!isset($conn)) {
    include 'config.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['callback_submit'])) {
    // Обработка формы
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    $errors = [];
    
    if (empty($name)) $errors[] = 'Пожалуйста, введите ваше имя';
    if (empty($phone)) $errors[] = 'Пожалуйста, введите ваш телефон';
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO callbacks (name, phone, created_at, status) VALUES (?, ?, NOW(), 'new')");
            $stmt->bind_param("ss", $name, $phone);
            $stmt->execute();
            
            $_SESSION['success_message'] = 'Ваша заявка принята! Мы скоро с вами свяжемся.';
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

?>

<!-- Попап для заказа звонка -->
<div id="callback-popup" class="callback-popup-wrapper">
    <div class="popup-content">
        <div class="popup-header">
            <h3>Заказать звонок</h3>
            <button class="close-popup">&times;</button>
        </div>
        <div class="popup-line"></div>
        <p class="popup-text">Впишите свои данные, и мы свяжемся с Вами.<br> Ваши данные ни при каких обстоятельствах<br> не будут переданы третьим лицам.</p>
        <form class="callback-popup-form" method="POST">
            <input type="hidden" name="callback_submit" value="1">
            <div class="form-group">
                <input type="text" name="name" placeholder="Ваше имя" required>
            </div>
            <div class="form-group">
                <input type="tel" name="phone" placeholder="+375 (__) ___-__-__" required>
            </div>
            <button type="submit" class="submit-btn">Отправить</button>
        </form>
        <p class="popup-agreement">Нажимая на кнопку «Отправить», я даю согласие<br> на обработку персональных данных</p>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчики для открытия попапа заказа звонка
    const callbackTriggers = document.querySelectorAll('.callback-trigger, .contact-info2 span');
    
    callbackTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            document.querySelector('.callback-popup-wrapper').style.display = 'flex';
        });
    });

    // Закрытие попапа при клике на крестик или вне попапа
    document.querySelector('.close-popup').addEventListener('click', function() {
        document.querySelector('.callback-popup-wrapper').style.display = 'none';
    });

    document.querySelector('.callback-popup-wrapper').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
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