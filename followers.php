<?php
// followers.php - Список подписчиков с онлайном и последним визитом
if (!isset($_GET['id'])) {
    die("Не указан ID пользователя.");
}

// Подключаем конфиг
$config = include('config.php');

$vk_id = htmlspecialchars($_GET['id']);
$token = $config['token'];

// Получаем список подписчиков с доп. информацией
$api_url = "https://api.vk.com/method/users.getFollowers?user_id=$vk_id&fields=first_name,last_name,photo_50,last_seen,online&access_token=$token&v=5.131";
$response = json_decode(file_get_contents($api_url), true);

if (!isset($response['response'])) {
    die("Не удалось получить список подписчиков.");
}

$followers = $response['response']['items'];

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
    <link rel="stylesheet" href="styles.css?v=1.1.6">
    <link rel="icon" href="img/logo.png" type="image/png">
    <title>Подписчики</title>
</head>
<body>
    <div class="container">
        <h1>Список подписчиков</h1>
        <ul>
            <?php foreach ($followers as $follower): ?>
                <li>
                <img src="<?php echo $follower['photo_50']; ?>" 
                     alt="Аватарка" 
                      width="50" 
                     height="50"
                     class="<?php echo $follower['online'] ? 'online-avatar' : 'offline-avatar'; ?>">
                    <a href="profile.php?id=<?php echo $follower['id']; ?>">
                        <?php echo $follower['first_name'] . ' ' . $follower['last_name']; ?>
                    </a> [<?php echo $follower['id']; ?>]
                    <span>
                        <?php echo $follower['online'] ? '🟢 В сети' : '⚫ Был(а): ' . formatLastSeen($follower['last_seen']); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <footer>Developer INK</footer>
</body>
</html>
