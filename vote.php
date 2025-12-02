<?php
require_once __DIR__ . '/storage.php';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$data = read_storage();
$eventId = $_GET['event'] ?? $data['active'];
if (!isset($data['events'][$eventId])) {
    $eventId = $data['active'];
}
$event = $data['events'][$eventId] ?? null;
$submitted = false;

if ($event && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $votes = [];
    foreach ($event['nominations'] as $nomination) {
        $value = trim($_POST['vote'][$nomination] ?? '');
        $votes[$nomination] = $value;
    }
    record_votes($data, $eventId, $votes);
    write_storage($data);
    $submitted = true;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Голосование — <?php echo $event ? h($event['title']) : 'Номинации'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #050815;
            --card: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.08);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #22d3ee;
            --accent-2: #a855f7;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, rgba(34,211,238,0.15), rgba(168,85,247,0.18)), #050815;
            color: var(--text);
        }
        .container { max-width: 960px; margin: 0 auto; padding: 40px 20px 80px; }
        h1 { margin: 0 0 10px; font-size: 30px; }
        p { color: var(--muted); }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 18px; margin-top: 16px; box-shadow: 0 16px 60px rgba(0,0,0,0.35); }
        label { display: block; font-weight: 600; margin-bottom: 8px; }
        input[type="text"], input[list] {
            width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); background: #0b1224; color: var(--text);
        }
        input:focus { outline: 1px solid var(--accent); border-color: var(--accent); }
        button { width: 100%; margin-top: 14px; padding: 13px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; background: linear-gradient(120deg, var(--accent), var(--accent-2)); color: #050815; box-shadow: 0 14px 40px rgba(34, 211, 238, 0.25); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; }
        .badge { display: inline-flex; padding: 6px 10px; border-radius: 12px; background: rgba(255,255,255,0.08); color: var(--muted); font-size: 13px; }
        .success { border: 1px solid rgba(16, 185, 129, 0.4); background: rgba(16,185,129,0.08); color: #bbf7d0; padding: 10px 12px; border-radius: 12px; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$event): ?>
            <h1>Корпоратив не выбран</h1>
            <p>Перейдите в <a href="admin.php" style="color: var(--accent);">админку</a> и создайте проект.</p>
        <?php else: ?>
            <div class="badge">Корпоратив: <?php echo h($event['title']); ?></div>
            <h1>Выберите победителей</h1>
            <p>Введите или выберите фамилии — ввод с поиском работает прямо в поле.</p>

            <?php if ($submitted): ?>
                <div class="success">Голоса записаны! Спасибо за участие 🎉</div>
            <?php endif; ?>

            <form method="post">
                <div class="grid">
                    <?php foreach ($event['nominations'] as $nomination): ?>
                        <div class="card">
                            <label><?php echo h($nomination); ?></label>
                            <input list="people" name="vote[<?php echo h($nomination); ?>]" placeholder="Начните вводить фамилию...">
                        </div>
                    <?php endforeach; ?>
                </div>
                <datalist id="people">
                    <?php foreach ($event['people'] as $person): ?>
                        <option value="<?php echo h($person); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <button type="submit">Отправить</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
