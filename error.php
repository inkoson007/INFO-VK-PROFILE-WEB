<?php
$error_message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : "Неизвестная ошибка";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css?v=1.1.6">
    <link rel="icon" href="img/logo.png" type="image/png">
    <title>Ошибка</title>
</head>
<body>
    <div class="container">
        <h2>❌ Ошибка!</h2>
        <p><?php echo $error_message; ?></p>
        <button onclick="history.back()">Вернуться назад</button>
    </div>
    <footer>Developer INK</footer>
</body>
</html>
