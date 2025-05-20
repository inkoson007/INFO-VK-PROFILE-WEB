<?php
// index.php - Главная страница
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK Шпион</title>
    <link rel="stylesheet" href="styles.css?v=1.1.1">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container fade-in">
        <div class="logo-container">
            <img src="img/logo.png" alt="Логотип VK Шпион" width="80" class="logo">
            <h1>VK Шпион</h1>
        </div>
        
        <p class="subtitle">Получите подробную информацию о профиле ВКонтакте</p>
        
        <div class="search-box">
            <input type="text" id="vk_id" placeholder="Введите ID или короткое имя" autocomplete="off">
            <button onclick="searchProfile()" class="primary-button">
                <span class="button-icon">🔍</span> Поиск
            </button>
            <button onclick="window.location.href='https://regvk.com/id/'" class="secondary-button">
                <span class="button-icon">ℹ️</span> Узнать ID
            </button>
        </div>
    </div>
   
    <div class="container fade-in delay-1">
        <h2>Обновление <span class="rainbow-text">V1.2</span></h2>
        <div class="update-block">
            <ul>
                <li>Добавили подсчет дней с последнего онлайна</li>
            </ul>
        </div>
    </div>
    
    <script>
        function searchProfile() {
            let vkId = document.getElementById('vk_id').value.trim();
            if (vkId) {
                window.location.href = 'profile.php?id=' + encodeURIComponent(vkId);
            } else {
                alert('Пожалуйста, введите ID или имя пользователя');
            }
        }
        
        // Поиск при нажатии Enter
        document.getElementById('vk_id').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProfile();
            }
        });
    </script>
    <footer>Developer INK</footer>
</body>
</html>