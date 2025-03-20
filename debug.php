<?php
// Подключаем конфиг
$config = include('config.php');
$token = $config['token'];

// Проверяем токен: получаем информацию о владельце токена
$api_url = "https://api.vk.com/method/users.get?access_token=$token&v=5.131";
$response = json_decode(file_get_contents($api_url), true);

if (!isset($response['response'])) {
    die("<h2>Ошибка: Недействительный токен!</h2>");
}

$owner = $response['response'][0];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Отладка токена</title>
</head>
<body>
    <div class="container">
        <h1>Информация о токене</h1>
        <div class="user-info">
            <p><strong>Имя:</strong> <?php echo $owner['first_name'] . ' ' . $owner['last_name']; ?></p>
            <p><strong>ID:</strong> <?php echo $owner['id']; ?></p>
            <p><strong>Статус:</strong> <?php echo $owner['online'] ? '🟢 В сети' : '⚫ Не в сети'; ?></p>
            <p><strong>Токен:</strong> <span style="color:green;">Действителен ✅</span></p>
        </div>
    </div>
    <footer>Developer INK</footer>
</body>
</html>
