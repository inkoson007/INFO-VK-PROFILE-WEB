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

// Системная информация
$os_info = php_uname(); // ОС
$php_version = phpversion();
$server_time = date("d.m.Y H:i:s");

// IP-адрес сервера
$server_ip = getHostByName(getHostName());

// Аптайм (для Unix-подобных ОС)
$uptime = shell_exec("uptime -p") ?? 'Недоступно';

// Использование памяти
$meminfo = @file_get_contents("/proc/meminfo");
$memory_usage = 'Недоступно';
if ($meminfo) {
    preg_match("/MemTotal:\s+(\d+)/", $meminfo, $total);
    preg_match("/MemAvailable:\s+(\d+)/", $meminfo, $available);
    if (isset($total[1], $available[1])) {
        $used = $total[1] - $available[1];
        $memory_usage = round(($used / $total[1]) * 100, 2) . '%';
    }
}

// Использование CPU
$load = sys_getloadavg();
$cpu_usage = $load[0] . ' (за 1 мин)';

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

        <h2>Системная информация</h2>
        <div class="system-info">
            <p><strong>ОС сервера:</strong> <?php echo $os_info; ?></p>
            <p><strong>Версия PHP:</strong> <?php echo $php_version; ?></p>
            <p><strong>Текущее серверное время:</strong> <?php echo $server_time; ?></p>
            <p><strong>IP-адрес сервера:</strong> <?php echo $server_ip; ?></p>
            <p><strong>Аптайм:</strong> <?php echo htmlspecialchars($uptime); ?></p>
            <p><strong>Использование памяти:</strong> <?php echo $memory_usage; ?></p>
            <p><strong>Загрузка CPU:</strong> <?php echo $cpu_usage; ?></p>
        </div>
    </div>
    <footer>Developer INK</footer>
</body>
</html>
