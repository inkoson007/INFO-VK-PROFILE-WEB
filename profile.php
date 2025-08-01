<?php
// Включение вывода всех ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Функция для склонения существительных
function getNounPluralForm($number, $one, $two, $five) {
    $number = abs($number);
    $number %= 100;
    if ($number >= 5 && $number <= 20) {
        return $five;
    }
    $number %= 10;
    if ($number == 1) {
        return $one;
    }
    if ($number >= 2 && $number <= 4) {
        return $two;
    }
    return $five;
}

// profile.php - Страница профиля с улучшенной обработкой ошибок
if (!isset($_GET['id'])) {
    header("Location: error.php?message=" . urlencode("Не указан ID пользователя"));
    exit();
}

try {
    // Подключаем конфиг
    $config = @include('config.php');
    if (!$config || !isset($config['token'])) {
        throw new Exception("Неверная конфигурация: токен не найден");
    }

    $vk_id = htmlspecialchars($_GET['id']);
    $token = $config['token'];

    // Проверка валидности ID
    if (!is_numeric($vk_id)) {
        throw new Exception("ID пользователя должен содержать только цифры");
    }

    // Формируем URL для API запроса
    $api_url = "https://api.vk.com/method/users.get?user_ids=$vk_id&fields=photo_200,city,bdate,counters,last_seen,online,status,sex&access_token=$token&v=5.131";

    // Используем cURL вместо file_get_contents для лучшей надежности
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception("Ошибка cURL: " . curl_error($ch));
    }
    
    curl_close($ch);

    $data = json_decode($response, true);
    
    // Обработка ошибок API VK
    if (isset($data['error'])) {
        $error_msg = $data['error']['error_msg'];
        $error_code = $data['error']['error_code'];
        throw new Exception("Ошибка VK API (#$error_code): $error_msg");
    }

    if (!isset($data['response'][0])) {
        throw new Exception("Пользователь не найден или данные недоступны");
    }

    $user = $data['response'][0];

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

    // Форматирование данных
    $online_text = $is_online ? '<span class="status online">🟢 В сети</span>' : '<span class="status offline">⚫ Офлайн</span>';
    $time_ago = $last_seen_time ? time() - $last_seen_time : null;
    $last_seen_formatted = $last_seen_time ? date("d.m.Y H:i", $last_seen_time) : "Неизвестно";
    
    // Добавляем расчет дней с последнего онлайна
    $time_ago_text = "";
    if ($time_ago) {
        $years = floor($time_ago / (60 * 60 * 24 * 365));
        $months = floor(($time_ago % (60 * 60 * 24 * 365)) / (60 * 60 * 24 * 30));
        $weeks = floor(($time_ago % (60 * 60 * 24 * 30)) / (60 * 60 * 24 * 7));
        $days = floor(($time_ago % (60 * 60 * 24 * 7)) / (60 * 60 * 24));
        $hours = floor(($time_ago % (60 * 60 * 24)) / (60 * 60));
        $minutes = floor(($time_ago % (60 * 60)) / 60);
        
        if ($years > 0) {
            $time_ago_text .= $years . " " . getNounPluralForm($years, 'год', 'года', 'лет') . " ";
        }
        if ($months > 0 && $years < 2) {
            $time_ago_text .= floor($months) . " " . getNounPluralForm($months, 'месяц', 'месяца', 'месяцев') . " ";
        }
        if ($weeks > 0 && $years == 0 && $months < 2) {
            $time_ago_text .= $weeks . " " . getNounPluralForm($weeks, 'неделю', 'недели', 'недель') . " ";
        }
        if ($days > 0 && $years == 0 && $months == 0) {
            $time_ago_text .= $days . " " . getNounPluralForm($days, 'день', 'дня', 'дней') . " ";
        }
        if ($hours > 0 && $years == 0 && $months == 0 && $days < 2) {
            $time_ago_text .= $hours . " " . getNounPluralForm($hours, 'час', 'часа', 'часов') . " ";
        }
        if ($minutes > 0 && $years == 0 && $months == 0 && $days == 0 && $hours < 2) {
            $time_ago_text .= $minutes . " " . getNounPluralForm($minutes, 'минуту', 'минуты', 'минут');
        }
        
        $time_ago_text .= " назад";
    } else {
        $time_ago_text = "Неизвестно";
    }

    $sex_map = [0 => 'Не указан', 1 => 'Женский', 2 => 'Мужской'];
    $user_sex = $sex_map[$user['sex']] ?? 'Неизвестно';

    // Получение счетчиков
    $friends_count = $user['counters']['friends'] ?? 0;
    $followers_count = $user['counters']['followers'] ?? 0;
    $photos_count = $user['counters']['photos'] ?? 0;
    $videos_count = $user['counters']['videos'] ?? 0;
    $audios_count = $user['counters']['audios'] ?? 0;

    // Получение количества подарков с обработкой ошибок
    $gifts_count = 0;
    try {
        $gifts_url = "https://api.vk.com/method/gifts.get?user_id=$vk_id&access_token=$token&v=5.131";
        $gifts_response = json_decode(file_get_contents($gifts_url), true);
        $gifts_count = $gifts_response['response']['count'] ?? 0;
    } catch (Exception $e) {
        // В случае ошибки просто продолжаем с нулевым значением
    }

    // Обработка даты рождения
    $bdate = $user['bdate'] ?? null;
    $birth_with_age = "Не указана";

   if ($bdate) {
    try {
        // Массив названий месяцев
        $months = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 
            4 => 'апреля', 5 => 'мая', 6 => 'июня',
            7 => 'июля', 8 => 'августа', 9 => 'сентября',
            10 => 'октября', 11 => 'ноября', 12 => 'декабря'
        ];
        
        // Проверяем разные форматы даты
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $bdate, $matches)) {
            // Формат с годом: дд.мм.гггг
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = (int)$matches[2];
            $year = $matches[3];
            
            $month_name = $months[$month] ?? 'неизвестного месяца';
            $formatted_date = "$day $month_name $year год";
            
            // Рассчитываем возраст
            $birthDate = DateTime::createFromFormat('d.m.Y', $bdate);
            $today = new DateTime('today');
            $age = $birthDate->diff($today)->y;
            
            $birth_with_age = $formatted_date . " <span class='age-badge'>[{$age} лет]</span>";
        } elseif (preg_match('/^(\d{1,2})\.(\d{1,2})$/', $bdate, $matches)) {
            // Формат без года: дд.мм
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = (int)$matches[2];
            
            $month_name = $months[$month] ?? 'неизвестного месяца';
            $birth_with_age = "$day $month_name";
        } else {
            // Неизвестный формат - выводим как есть
            $birth_with_age = $bdate . " <span class='age-badge'>[Год не указан]</span>";
        }
    } catch (Exception $e) {
        $birth_with_age = $bdate . " [Ошибка формата]";
    }
}

} catch (Exception $e) {
    // Перенаправляем на страницу ошибки с сообщением
    header("Location: error.php?message=" . urlencode($e->getMessage()));
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> | VK Шпион</title>
    <link rel="stylesheet" href="styles.css?v=1.1.6">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container fade-in">
        <div class="user-info">
            <img src="<?php echo $user['photo_200']; ?>" alt="Аватар">
            <div class="user-details">
                <h1><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h1>
                <p><?php echo $online_text; ?></p>
                
                <div class="stats-grid">
                    <div class="stats-card">
                        <div class="stats-icon">👥</div>
                        <div>
                            <div class="stats-value"><?php echo $friends_count; ?></div>
                            <div class="stats-label">Друзей</div>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon">❤️</div>
                        <div>
                            <div class="stats-value"><?php echo $followers_count; ?></div>
                            <div class="stats-label">Подписчиков</div>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon">🎁</div>
                        <div>
                            <div class="stats-value"><?php echo $gifts_count; ?></div>
                            <div class="stats-label">Подарков</div>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon">📷</div>
                        <div>
                            <div class="stats-value"><?php echo $photos_count; ?></div>
                            <div class="stats-label">Фото</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h2>📌 Основная информация</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Пол:</span>
                    <span class="info-value"><?php echo $user_sex; ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Дата рождения:</span>
                    <span class="info-value"><?php echo $birth_with_age; ?></span>
                </div>
                
                <?php if (isset($user['city']['title'])): ?>
                <div class="info-item">
                    <span class="info-label">Город:</span>
                    <span class="info-value"><?php echo $user['city']['title']; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($user['status'])): ?>
                <div class="info-item">
                    <span class="info-label">Статус:</span>
                    <span class="info-value"><?php echo $user['status']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$is_online): ?>
        <div class="profile-section">
            <h2>⏱ Последний онлайн</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Дата и время:</span>
                    <span class="info-value"><?php echo $last_seen_formatted; ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Прошло времени:</span>
                    <span class="info-value"><?php echo $time_ago_text; ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Устройство:</span>
                    <span class="info-value"><?php echo $platform_text; ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="button-group">
            <button onclick="window.location.href='friends.php?id=<?php echo $vk_id; ?>'">
                👥 Друзья
            </button>
            <button onclick="window.location.href='followers.php?id=<?php echo $vk_id; ?>'">
                ❤️ Подписчики
            </button>
            <button onclick="window.location.href='likes_from_users.php?id=<?php echo $vk_id; ?>'">
                👍 Лайки
            </button>
            <button onclick="window.location.href='possibly_chatted.php?id=<?php echo $vk_id; ?>'">
                💬 Возможное общение
            </button>
             <button onclick="window.location.href='friends_statistics.php?id=<?php echo $vk_id; ?>'">
                📊 Статистика друзей
            </button>
            <button onclick="window.location.href='https://vk.com/id<?php echo $vk_id; ?>'" class="vk-button">
                🔗 Профиль VK
            </button>
        </div>
    </div>
    
    <footer>Developer INK</footer>
</body>
</html>