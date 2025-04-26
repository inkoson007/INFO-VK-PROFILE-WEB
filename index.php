<?php
// index.php - Главная страница
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK Шпион</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>

<body>
    <div class="container">
        <h1>VK Шпион</h1>
        <p>Введите ID на профиль ВКонтакте:</p>
        <input type="text" id="vk_id" placeholder="ID">
        <button onclick="searchProfile()">Поиск</button>
        <button onclick="window.location.href='https://regvk.com/id/'">Узнать ID пользователя</button>
    </div>
   
    <div class="container">
    <h1>Обновление <span class="rainbow-text">V0.8</span></h1>
    <div class="update-block">
        <ul>
            <li>Добавлена иконка сайта</li>
            <li>Добавлена информация о поле пользователя</li>
            <li>Цвет в возможных людей с которыми общался пользователь</li>
            <li>Чёрный цвет выделения текста</li>
        </ul>
    </div>
</div>
    
    <script>
        function searchProfile() {
            let vkId = document.getElementById('vk_id').value.trim();
            if (vkId) {
                window.location.href = 'profile.php?id=' + encodeURIComponent(vkId);
            }
        }
    </script>
    <footer>Developer INK</footer>
</body>
</html>