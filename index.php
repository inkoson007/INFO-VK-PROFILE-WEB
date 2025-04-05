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
</head>

<body>
    <div class="container">
        <h1>VK Шпион</h1>
        <p>Введите ID на профиль ВКонтакте:</p>
        <input type="text" id="vk_id" placeholder="ID">
        <button onclick="searchProfile()">Поиск</button>
    </div>
   
    <div class="container">
    <h1>Обновление V0.6</h1>
    <p>Добавили страницу с кем возможно общался</p>
    <p>Изменили ссылку в списке друзей и подписчиков на страницу в профиле этого сайта</p>
    <p>Добавили ссылку на страницу в вк в профиле</p>
    <p>Добавили количество подарков в профиль</p>
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