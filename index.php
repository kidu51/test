<?php
require_once __DIR__ . '/storage.php';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$data = read_storage();
$activeId = $data['active'];
$current = $data['events'][$activeId] ?? null;
$voteUrl = url_for('vote.php', ['event' => $activeId]);
$presentationUrl = url_for('presentation.php', ['event' => $activeId]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корпоративные номинации</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #050815;
            --card: rgba(255,255,255,0.04);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #22d3ee;
            --accent-2: #a855f7;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(34, 211, 238, 0.1), transparent 30%),
                        radial-gradient(circle at 80% 0%, rgba(168, 85, 247, 0.12), transparent 26%),
                        var(--bg);
            color: var(--text);
        }
        .container {
            max-width: 1024px;
            margin: 0 auto;
            padding: 48px 20px 64px;
            text-align: center;
        }
        h1 { font-size: 32px; margin: 0 0 10px; }
        p { color: var(--muted); }
        .card {
            background: var(--card);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
            margin-top: 26px;
        }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-top: 16px; }
        a.btn {
            background: linear-gradient(120deg, var(--accent), var(--accent-2));
            color: #0b1224;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 30px rgba(34,211,238,0.25);
        }
        a.btn.secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid rgba(255,255,255,0.12);
            box-shadow: none;
        }
        .pill { display: inline-block; margin: 6px 6px 0 0; padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-top: 20px; }
        .list { text-align: left; }
        .list h3 { margin-bottom: 6px; }
        .link { color: var(--accent); word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Голосование за шуточные номинации</h1>
        <p>Управляйте корпоративами, собирайте голоса и показывайте результаты как стильное шоу.</p>
        <div class="actions">
            <a class="btn" href="admin.php">Перейти в админку</a>
            <a class="btn secondary" href="<?php echo h($voteUrl); ?>">Голосование</a>
            <a class="btn secondary" href="<?php echo h($presentationUrl); ?>">Презентация</a>
        </div>

        <?php if ($current): ?>
        <div class="card">
            <h2><?php echo htmlspecialchars($current['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars($current['subtitle'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
            <p class="pill">Дата: <?php echo htmlspecialchars($current['date'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
            <p class="pill">Номинаций: <?php echo count($current['nominations']); ?></p>
            <p class="pill">Сотрудников: <?php echo count($current['people']); ?></p>
            <p class="pill">Голосов: <?php echo count($current['votes']); ?></p>
            <div class="grid">
                <div class="list">
                    <h3>Номинации</h3>
                    <p class="muted"><?php echo implode(' · ', array_map('htmlspecialchars', $current['nominations'])); ?></p>
                </div>
                <div class="list">
                    <h3>Сотрудники</h3>
                    <p class="muted"><?php echo implode(' · ', array_map('htmlspecialchars', $current['people'])); ?></p>
                </div>
            </div>
            <p class="muted" style="margin-top:16px;">Ссылка для голосования: <span class="link"><?php echo h($voteUrl); ?></span></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
