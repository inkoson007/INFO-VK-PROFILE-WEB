<?php
// friends.php - Список друзей с онлайном и последним визитом
if (!isset($_GET['id'])) {
    die("Не указан ID пользователя.");
}

// Подключаем конфиг
$config = include('config.php');

$vk_id = htmlspecialchars($_GET['id']);
$token = $config['token'];

// Получаем список друзей с дополнительной информацией
$api_url_friends = "https://api.vk.com/method/friends.get?user_id=$vk_id&fields=photo_50,last_seen,online&access_token=$token&v=5.131";
$response_friends = json_decode(file_get_contents($api_url_friends), true);

if (!isset($response_friends['response'])) {
    die("Не удалось получить список друзей.");
}

$friends_data = $response_friends['response']['items'];

function formatLastSeen($last_seen) {
    if (!isset($last_seen['time'])) return 'Нет данных';
    return date('d.m.Y H:i', $last_seen['time']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Друзья</title>
</head>
<body>
    <div class="container">
        <h1>Список друзей</h1>
        <ul>
            <?php foreach ($friends_data as $friend): ?>
                <li>
                    <img src="<?php echo $friend['photo_50']; ?>" alt="Аватарка" width="50" height="50">
                    <a href="https://vk.com/id<?php echo $friend['id']; ?>" target="_blank">
                        <?php echo $friend['first_name'] . ' ' . $friend['last_name']; ?>
                    </a> [<?php echo $friend['id']; ?>]
                    <span>
                        <?php echo $friend['online'] ? '🟢 В сети' : '⚫ Был(а): ' . formatLastSeen($friend['last_seen']); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <footer>Developer INK</footer>
</body>
</html>
