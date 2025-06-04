<?php
// likes_from_users.php - Статистика людей, которые лайкали посты и фото пользователя
if (!isset($_GET['id'])) {
    die("Не указан ID пользователя.");
}

// Подключаем конфиг
$config = include('config.php');

$vk_id = htmlspecialchars($_GET['id']);
$token = $config['token'];

// Получаем информацию о пользователе
$user_url = "https://api.vk.com/method/users.get?user_ids=$vk_id&fields=first_name,last_name,photo_200,online&access_token=$token&v=5.131";
$user_response = json_decode(file_get_contents($user_url), true);

if (!isset($user_response['response'][0])) {
    die("Ошибка: не удалось получить данные пользователя.");
}

$user = $user_response['response'][0];

// Функция для получения лайков на объекте (постах или фото)
function getLikes($type, $items, $token) {
    $likes = [];
    foreach ($items as $item) {
        $object_id = $item['id'];
        $likes_url = "https://api.vk.com/method/likes.getList?type=$type&owner_id={$item['owner_id']}&item_id=$object_id&filter=likes&access_token=$token&v=5.131";
        $likes_response = json_decode(file_get_contents($likes_url), true);
        
        if (isset($likes_response['response']['items'])) {
            foreach ($likes_response['response']['items'] as $user_id) {
                if (!isset($likes[$user_id])) {
                    $likes[$user_id] = 0;
                }
                $likes[$user_id]++;
            }
        }
    }
    return $likes;
}

// Получаем посты пользователя
$wall_url = "https://api.vk.com/method/wall.get?owner_id=$vk_id&count=10&access_token=$token&v=5.131";
$wall_response = json_decode(file_get_contents($wall_url), true);
$wall_items = $wall_response['response']['items'] ?? [];
$wall_likes = getLikes('post', $wall_items, $token);
$total_wall_likes = array_sum($wall_likes);

// Получаем фото пользователя
$photos_url = "https://api.vk.com/method/photos.getAll?owner_id=$vk_id&count=10&access_token=$token&v=5.131";
$photos_response = json_decode(file_get_contents($photos_url), true);
$photos_items = $photos_response['response']['items'] ?? [];
$photo_likes = getLikes('photo', $photos_items, $token);
$total_photo_likes = array_sum($photo_likes);

// Объединяем лайки с постов и фото
$total_likes = [];
foreach ([$wall_likes, $photo_likes] as $likes_list) {
    foreach ($likes_list as $user_id => $count) {
        if (!isset($total_likes[$user_id])) {
            $total_likes[$user_id] = 0;
        }
        $total_likes[$user_id] += $count;
    }
}

// Получаем информацию о людях, которые ставили лайки
if (!empty($total_likes)) {
    $user_ids = implode(',', array_keys($total_likes));
    $likers_url = "https://api.vk.com/method/users.get?user_ids=$user_ids&fields=first_name,last_name,photo_50,online&access_token=$token&v=5.131";
    $likers_response = json_decode(file_get_contents($likers_url), true);
    $likers_data = $likers_response['response'] ?? [];
} else {
    $likers_data = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css?v=1.1.6">
    <link rel="icon" href="img/logo.png" type="image/png">
    <title>Статистика лайков</title>
</head>
<body>
    <div class="container">
        <h1>📊 Статистика лайков</h1>
        <div class="user-info">
            <img src="<?php echo $user['photo_200']; ?>" alt="Аватар <?php echo $user['first_name']; ?>" width="100" class="profile-avatar">
            <h2><?php echo $user['first_name'] . ' ' . $user['last_name']; ?> (<?php echo $user['id']; ?>)</h2>
        </div>

        <h3>Люди, которые ставили лайки:</h3>
        <?php if (!empty($likers_data)): ?>
            <ul>
                <?php foreach ($likers_data as $liker): ?>
                    <li>
                        <img src="<?php echo $liker['photo_50']; ?>" 
                             alt="Аватар <?php echo $liker['first_name']; ?>"
                             width="50" 
                             height="50"
                             class="<?php echo $liker['online'] ? 'online-avatar' : 'offline-avatar'; ?>"
                             onerror="this.src='img/default_avatar.png'">
                        <a href="profile.php?id=<?php echo $liker['id']; ?>">
                            <?php echo $liker['first_name'] . ' ' . $liker['last_name']; ?>
                        </a> (<?php echo $liker['id']; ?>)
                        — <strong>❤️ <?php echo $total_likes[$liker['id']] ?? 0; ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Нет данных о лайках</p>
        <?php endif; ?>

        <h3>📌 Общая статистика:</h3>
        <p>❤️ Лайков на постах: <strong><?php echo $total_wall_likes; ?></strong></p>
        <p>❤️ Лайков на фото: <strong><?php echo $total_photo_likes; ?></strong></p>
    </div>
    <footer>Developer INK</footer>
</body>
</html>