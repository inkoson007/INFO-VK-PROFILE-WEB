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
        <p>Введите ID или ссылку на профиль ВКонтакте:</p>
        <input type="text" id="vk_id" placeholder="ID или ссылка">
        <button onclick="searchProfile()">Поиск</button>
    </div>
   
    <div class="container">
    <h1>Обновление V0.3</h1>
    <p>Добавили просмотр онлайна друзей и подписчиков</p>
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