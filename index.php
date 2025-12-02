<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Голосование за номинации</title>
    <style>
        :root {
            --bg: #0f1624;
            --card: rgba(255,255,255,0.08);
            --accent: #7c5dfa;
            --accent-2: #2dd4bf;
            --text: #f5f7fb;
            --muted: #9aa5b1;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { width: 100%; max-width: 640px; background: linear-gradient(135deg, rgba(124,93,250,0.22), rgba(45,212,191,0.18)); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 60px rgba(0,0,0,0.35); border-radius: 20px; padding: 26px; text-align: center; }
        h1 { margin-top: 0; margin-bottom: 8px; font-size: 1.9rem; }
        p { margin: 8px 0 20px; color: var(--muted); }
        a { display: inline-block; margin: 8px; padding: 12px 18px; border-radius: 12px; text-decoration: none; color: #fff; font-weight: 700; letter-spacing: 0.3px; box-shadow: 0 12px 30px rgba(0,0,0,0.25); transition: transform 0.15s ease, box-shadow 0.15s ease; }
        a:hover { transform: translateY(-2px); box-shadow: 0 18px 40px rgba(0,0,0,0.3); }
        .admin { background: linear-gradient(135deg, var(--accent), #5c6aff); }
        .vote { background: linear-gradient(135deg, #00bfa6, var(--accent-2)); }
    </style>
</head>
<body>
    <div class="card">
        <div style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; color: var(--muted);">Корпоративные вечера</div>
        <h1>Панель голосования</h1>
        <p>Создавайте отдельные проекты для разных мероприятий, запускайте голосование по QR-коду и выводите победителей в большом экране режиме.</p>
        <div>
            <a class="admin" href="admin.php">Админка</a>
            <a class="vote" href="vote.php">Голосование</a>
        </div>
    </div>
</body>
</html>
