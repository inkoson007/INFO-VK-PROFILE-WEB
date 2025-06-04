<?php
// friends_statistics.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['id'])) {
    die("Не указан ID пользователя.");
}

// Загружаем конфиг
$config = include('config.php');
if (!$config) {
    die("Ошибка загрузки конфигурации");
}

$vk_id = (int)$_GET['id'];
$token = $config['token'];
$db_config = $config['db'];
$table_prefix = isset($db_config['prefix']) ? $db_config['prefix'] : '';

// Подключение к БД с автоматическим созданием структуры
try {
    // Подключаемся к серверу MySQL без выбора БД
    $dsn = "mysql:host={$db_config['host']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаем БД если не существует
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db_config['dbname']}`");
    
    // Создаем таблицу пользователей
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$table_prefix}users` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `vk_id` INT NOT NULL,
        `first_name` VARCHAR(255) NOT NULL,
        `last_name` VARCHAR(255) NOT NULL,
        `photo_url` VARCHAR(512),
        `last_checked` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `vk_id` (`vk_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Создаем таблицу друзей
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$table_prefix}friends` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `friend_vk_id` INT NOT NULL,
        `friend_first_name` VARCHAR(255) NOT NULL,
        `friend_last_name` VARCHAR(255) NOT NULL,
        `friend_photo_url` VARCHAR(512),
        `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `removed_at` TIMESTAMP NULL DEFAULT NULL,
        `is_current` TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_friend` (`user_id`, `friend_vk_id`, `is_current`),
        KEY `user_id` (`user_id`),
        KEY `friend_vk_id` (`friend_vk_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Получаем данные пользователя из VK API
$user_url = "https://api.vk.com/method/users.get?user_ids=$vk_id&fields=first_name,last_name,photo_50&access_token=$token&v=5.131";
$user_response = json_decode(file_get_contents($user_url), true);

if (!isset($user_response['response'][0])) {
    die("Ошибка при получении данных пользователя из VK API");
}

$user = $user_response['response'][0];
$current_time = date('Y-m-d H:i:s');

// Сохраняем/обновляем пользователя в БД
try {
    $stmt = $pdo->prepare("INSERT INTO {$table_prefix}users 
        (vk_id, first_name, last_name, photo_url, last_checked) 
        VALUES (:vk_id, :first_name, :last_name, :photo_url, :last_checked)
        ON DUPLICATE KEY UPDATE 
        first_name = VALUES(first_name), 
        last_name = VALUES(last_name), 
        photo_url = VALUES(photo_url),
        last_checked = VALUES(last_checked)");

    $stmt->execute([
        ':vk_id' => $vk_id,
        ':first_name' => $user['first_name'],
        ':last_name' => $user['last_name'],
        ':photo_url' => $user['photo_50'],
        ':last_checked' => $current_time
    ]);
} catch (PDOException $e) {
    die("Ошибка при сохранении пользователя: " . $e->getMessage());
}

// Получаем текущих друзей из VK API
$friends_url = "https://api.vk.com/method/friends.get?user_id=$vk_id&fields=first_name,last_name,photo_50&access_token=$token&v=5.131";
$friends_response = json_decode(file_get_contents($friends_url), true);
$current_friends = isset($friends_response['response']['items']) ? $friends_response['response']['items'] : [];

// Получаем текущих друзей из БД
try {
    $db_friends = $pdo->prepare("SELECT friend_vk_id FROM {$table_prefix}friends WHERE user_id = ? AND is_current = 1");
    $db_friends->execute([$vk_id]);
    $existing_friends = $db_friends->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Ошибка при получении друзей из БД: " . $e->getMessage());
}

// Определяем изменения
$current_friends_ids = array_column($current_friends, 'id');
$new_friends = array_diff($current_friends_ids, $existing_friends);
$removed_friends = array_diff($existing_friends, $current_friends_ids);

// Добавляем новых друзей
if (!empty($new_friends)) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO {$table_prefix}friends 
            (user_id, friend_vk_id, friend_first_name, friend_last_name, friend_photo_url, added_at, is_current) 
            VALUES (?, ?, ?, ?, ?, ?, 1)");
        
        foreach ($current_friends as $friend) {
            if (in_array($friend['id'], $new_friends)) {
                $stmt->execute([
                    $vk_id,
                    $friend['id'],
                    $friend['first_name'],
                    $friend['last_name'],
                    $friend['photo_50'],
                    $current_time
                ]);
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Ошибка при добавлении новых друзей: " . $e->getMessage());
    }
}

// Помечаем удаленных друзей
if (!empty($removed_friends)) {
    try {
        $placeholders = implode(',', array_fill(0, count($removed_friends), '?'));
        $stmt = $pdo->prepare("UPDATE {$table_prefix}friends SET is_current = 0, removed_at = ? 
            WHERE user_id = ? AND friend_vk_id IN ($placeholders) AND is_current = 1");
        $params = array_merge([$current_time, $vk_id], $removed_friends);
        $stmt->execute($params);
    } catch (PDOException $e) {
        die("Ошибка при обновлении удаленных друзей: " . $e->getMessage());
    }
}

// Получаем историю друзей для отображения
try {
    $history_stmt = $pdo->prepare("
        SELECT 
            friend_vk_id as id, 
            friend_first_name as first_name, 
            friend_last_name as last_name, 
            friend_photo_url as photo_50, 
            added_at, 
            removed_at, 
            is_current
        FROM {$table_prefix}friends 
        WHERE user_id = ? 
        ORDER BY COALESCE(removed_at, added_at) DESC, added_at DESC
        LIMIT 1000
    ");
    $history_stmt->execute([$vk_id]);
    $friends_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при получении истории друзей: " . $e->getMessage());
}

// Получаем статистику
try {
    $stats_stmt = $pdo->prepare("
        SELECT 
            SUM(is_current = 1) as current_count,
            SUM(is_current = 0) as removed_count,
            COUNT(CASE WHEN added_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as added_24h,
            COUNT(CASE WHEN removed_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as removed_24h
        FROM {$table_prefix}friends 
        WHERE user_id = ?
    ");
    $stats_stmt->execute([$vk_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при получении статистики: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика друзей</title>
    <link rel="stylesheet" href="styles.css?v=1.1.6">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Статистика друзей</h1>
        <p>Пользователь: <?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?> [<?php echo $vk_id; ?>]</p>
        
       <div class="stats">
       <div class="stats-card stat-current">
        <div class="stats-value"><?php echo $stats['current_count']; ?></div>
       <div class="stats-label">Текущих друзей</div>
     </div>
       <div class="stats-card stat-removed">
       <div class="stats-value"><?php echo $stats['removed_count']; ?></div>
       <div class="stats-label">Удаленных друзей</div>
    </div>
       <div class="stats-card stat-added">
       <div class="stats-value"><?php echo $stats['added_24h']; ?></div>
       <div class="stats-label">Добавлено за 24ч</div>
    </div>
      <div class="stats-card stat-deleted">
      <div class="stats-value"><?php echo $stats['removed_24h']; ?></div>
      <div class="stats-label">Удалено за 24ч</div>
    </div>
</div>

        <p>Последнее обновление: <?php echo $current_time; ?></p>

        <h2>История изменений</h2>
        <?php if (empty($friends_history)): ?>
            <p>Нет данных о истории друзей.</p>
        <?php else: ?>
      <div class="friends-list">
    <?php foreach ($friends_history as $friend): ?>
        <div class="friend-item fade-in">
            <img src="<?php echo htmlspecialchars($friend['photo_50']); ?>" 
                 alt="Аватар" 
                 class="friend-avatar <?php echo $friend['is_current'] ? 'online-avatar' : 'offline-avatar'; ?>">
            
            <div class="friend-info">
                <a href="profile.php?id=<?php echo $friend['id']; ?>" class="friend-link">
                    <span class="friend-name <?php echo $friend['is_current'] ? 'current' : 'removed'; ?>">
                        <?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?>
                    </span>
                </a>
                
                <div class="friend-meta">
                    <span class="friend-status <?php echo $friend['is_current'] ? 'status-current' : 'status-removed'; ?>">
                        <?php echo $friend['is_current'] ? 'В друзьях' : 'Удален'; ?>
                    </span>
                    <span class="friend-date">
                        <?php echo $friend['is_current'] 
                            ? 'Добавлен: ' . date('d.m.Y H:i', strtotime($friend['added_at']))
                            : 'Удален: ' . date('d.m.Y H:i', strtotime($friend['removed_at'])); ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
        <?php endif; ?>
    </div>
    <footer>Developer INK</footer>
</body>
</html>