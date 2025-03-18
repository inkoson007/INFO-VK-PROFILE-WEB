<?php
// followers.php - Список подписчиков
if (!isset($_GET['id'])) {
    die("Не указан ID пользователя.");
}
$vk_id = htmlspecialchars($_GET['id']);
$token = ''; // Вставьте ваш токен ВК API

// Получаем список подписчиков с дополнительной информацией (имена, фамилии, аватарки)
$api_url = "https://api.vk.com/method/users.getFollowers?user_id=$vk_id&fields=first_name,last_name,photo_50&access_token=$token&v=5.131";
$response = json_decode(file_get_contents($api_url), true);

if (!isset($response['response'])) {
    die("Не удалось получить список подписчиков.");
}

$followers = $response['response']['items'];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Подписчики</title>
</head>
<body>
    <div class="container">
        <h1>Список подписчиков</h1>
        <ul>
            <?php foreach ($followers as $follower): ?>
                <li>
                    <img src="<?php echo $follower['photo_50']; ?>" alt="Аватарка" width="50" height="50">
                    <a href="https://vk.com/id<?php echo $follower['id']; ?>" target="_blank">
                        <?php echo $follower['first_name'] . ' ' . $follower['last_name']; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <footer>Developer INK</footer>
</body>
</html>
