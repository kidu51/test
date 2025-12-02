<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Голосование за номинации</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); text-align: center; }
        a { display: inline-block; margin: 8px; padding: 10px 16px; border-radius: 8px; text-decoration: none; color: #fff; }
        .admin { background: #3f51b5; }
        .vote { background: #009688; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Корпоративные номинации</h1>
        <p>Выберите раздел:</p>
        <div>
            <a class="admin" href="admin.php">Админка</a>
            <a class="vote" href="vote.php">Голосование</a>
        </div>
    </div>
</body>
</html>
