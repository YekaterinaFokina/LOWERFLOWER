<?php
    $servername = "MySQL-8.2"; // или IP-адрес Вашего сервера
    $username = "root"; // Ваше имя пользователя
    $password = ""; // Ваш пароль
    $dbname = "shop_db"; // имя Вашей базы данных

    // Создаем соединение
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Проверяем соединение
    if ($conn->connect_error) {
        die("Ошибка подключения: " . $conn->connect_error);
    }

    
    
?>
