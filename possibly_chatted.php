<?php
// possibly_chatted.php - Анализ возможных переписок пользователя с друзьями

if (!isset($_GET['id'])) {
    die("Не указан ID пользователя.");
}

$config = include('config.php');
$vk_id = htmlspecialchars($_GET['id']);
$token = $config['token'];

// Получаем данные о самом пользователе
$user_url = "https://api.vk.com/method/users.get?user_ids=$vk_id&fields=last_seen,online,first_name,last_name&access_token=$token&v=5.131";
$user_response = json_decode(file_get_contents($user_url), true);
if (!isset($user_response['response'][0])) {
    die("Ошибка при получении пользователя.");
}

$user = $user_response['response'][0];
$user_online = $user['online'];
$user_last_seen = $user_online ? time() : $user['last_seen']['time'];

// Получаем друзей
$friends_url = "https://api.vk.com/method/friends.get?user_id=$vk_id&fields=photo_50,online,last_seen,first_name,last_name&access_token=$token&v=5.131";
$friends_response = json_decode(file_get_contents($friends_url), true);
if (!isset($friends_response['response']['items'])) {
    die("Ошибка при получении списка друзей.");
}
$friends = $friends_response['response']['items'];

function formatLastSeen($timestamp) {
    return date('d.m.Y H:i', $timestamp);
}

$possibly_chatted = [];

foreach ($friends as $friend) {
    if ($user_online) {
        // Пользователь онлайн — показываем только тех, кто тоже онлайн
        if ($friend['online']) {
            $possibly_chatted[] = $friend;
        }
    } else {
        // Пользователь оффлайн — ищем друзей, которые были онлайн в пределах +5 минут после его выхода
        if (!$friend['online'] && isset($friend['last_seen']['time'])) {
            $friend_last_seen = $friend['last_seen']['time'];
            if ($friend_last_seen >= $user_last_seen && $friend_last_seen <= $user_last_seen + 300) {
                $possibly_chatted[] = $friend;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Возможные собеседники</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Возможно общался(ется)</h1>
        <p>Пользователь: <?php echo $user['first_name'] . ' ' . $user['last_name']; ?> [<?php echo $vk_id; ?>]</p>
        <p>Сейчас: <?php echo $user_online ? '🟢 В сети' : '⚫ Был(а): ' . formatLastSeen($user_last_seen); ?></p>

        <h2>Возможные собеседники:</h2>
        <?php if (count($possibly_chatted) === 0): ?>
            <p>Никто из друзей не совпал по времени.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($possibly_chatted as $friend): ?>
                    <li>
                        <img src="<?php echo $friend['photo_50']; ?>" alt="Аватар" width="50" height="50">
                        <a href="profile.php?id=<?php echo $friend['id']; ?>">
                            <?php echo $friend['first_name'] . ' ' . $friend['last_name']; ?>
                        </a> [<?php echo $friend['id']; ?>]
                        <span>
                            <?php
                                if ($friend['online']) {
                                    echo '🟢 Сейчас в сети';
                                } else {
                                    echo '⚫ Был(а): ' . formatLastSeen($friend['last_seen']['time']);
                                }
                            ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <footer>Developer INK</footer>
</body>
</html>
