<?php
// profile.php - Страница профиля
if (!isset($_GET['id'])) {
    die("Не указан ID пользователя.");
}

// Подключаем конфиг
$config = include('config.php');

$vk_id = htmlspecialchars($_GET['id']);
$token = $config['token'];
$api_url = "https://api.vk.com/method/users.get?user_ids=$vk_id&fields=photo_200,city,bdate,counters,last_seen,online,status&access_token=$token&v=5.131";
$response = json_decode(file_get_contents($api_url), true);

if (!isset($response['response'][0])) {
    die("Пользователь не найден.");
}
$user = $response['response'][0];

// Проверка статуса онлайна
$is_online = $user['online'] ?? 0;
$last_seen_time = $user['last_seen']['time'] ?? null;
$platform_id = $user['last_seen']['platform'] ?? null;
$platforms = [
    1 => "Мобильная версия",
    2 => "iPhone",
    3 => "iPad",
    4 => "Android",
    5 => "Windows Phone",
    6 => "Windows 10",
    7 => "Полная версия сайта"
];
$platform_text = $platforms[$platform_id] ?? "Неизвестное устройство";

$online_text = $is_online ? "🟢 В сети" : "⚫ Офлайн";
$time_ago = $last_seen_time ? time() - $last_seen_time : null;
$last_seen_formatted = $last_seen_time ? date("d.m.Y H:i", $last_seen_time) : "Неизвестно";
$time_ago_text = $time_ago ? gmdate("H часов i минут", $time_ago) . " назад" : "Неизвестно";

// Количество друзей и подписчиков
$friends_count = $user['counters']['friends'] ?? 0;
$followers_count = $user['counters']['followers'] ?? 0;

// Получение количества подарков
$gifts_url = "https://api.vk.com/method/gifts.get?user_id=$vk_id&access_token=$token&v=5.131";
$gifts_response = json_decode(file_get_contents($gifts_url), true);
$gifts_count = isset($gifts_response['response']['count']) ? $gifts_response['response']['count'] : 0;

// Пользовательский статус
$user_status = !empty($user['status']) ? $user['status'] : "Нет статуса";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль <?php echo $user['first_name'] . ' ' . $user['last_name']; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <img src="<?php echo $user['photo_200']; ?>" alt="Аватар">
        <h2><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h2>
        <p>Город: <?php echo $user['city']['title'] ?? 'Не указан'; ?></p>
        <p>Дата рождения: <?php echo $user['bdate'] ?? 'Не указана'; ?></p>
        <p>Статус: <?php echo $online_text; ?></p>
        <?php if (!$is_online): ?>
            <p>Последний онлайн: <?php echo $last_seen_formatted; ?></p>
            <p>Прошло времени с последнего онлайна: <?php echo $time_ago_text; ?></p>
            <p>Устройство входа: <?php echo $platform_text; ?></p>
        <?php endif; ?>
        <p>Количество друзей: <?php echo $friends_count; ?></p>
        <p>Количество подписчиков: <?php echo $followers_count; ?></p>
        <p>Количество подарков: <?php echo $gifts_count; ?></p>
        <p>Пользовательский статус: <?php echo $user_status; ?></p>
        
      <!-- Кнопки для просмотра друзей и подписчиков -->
    <button onclick="window.location.href='friends.php?id=<?php echo $vk_id; ?>'">Просмотр друзей</button>
    <button onclick="window.location.href='followers.php?id=<?php echo $vk_id; ?>'">Просмотр подписчиков</button>
    <button onclick="window.location.href='likes_from_users.php?id=<?php echo $vk_id; ?>'">Статистика лайков</button>
    <button onclick="window.location.href='possibly_chatted.php?id=<?php echo $vk_id; ?>'">Возможно общался(ется)</button>
    <button onclick="window.location.href='https://vk.com/id<?php echo $vk_id; ?>'">Профиль VK</button>
</div>
    <footer>Developer INK</footer>
</body>
</html>