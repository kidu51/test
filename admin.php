<?php
require_once __DIR__ . '/storage.php';

$data = read_storage();
$messages = [];

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect_with_event(string $eventId): void {
    header('Location: ' . url_for('admin.php', ['event' => $eventId]));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'create_event':
            $title = trim($_POST['title'] ?? '');
            $subtitle = trim($_POST['subtitle'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $id = create_event($data, $title, $subtitle, $date);
            write_storage($data);
            redirect_with_event($id);
            break;
        case 'switch_event':
            $eventId = $_POST['event_id'] ?? '';
            set_active_event($data, $eventId);
            write_storage($data);
            redirect_with_event($eventId);
            break;
        case 'delete_event':
            $eventId = $_POST['event_id'] ?? '';
            delete_event($data, $eventId);
            write_storage($data);
            redirect_with_event($data['active']);
            break;
        case 'update_meta':
            $eventId = $_POST['event_id'] ?? '';
            $title = trim($_POST['title'] ?? '');
            $subtitle = trim($_POST['subtitle'] ?? '');
            $date = trim($_POST['date'] ?? '');
            update_event_meta($data, $eventId, $title, $subtitle, $date);
            write_storage($data);
            redirect_with_event($eventId ?: $data['active']);
            break;
        case 'save_lists':
            $eventId = $_POST['event_id'] ?? '';
            $people = $_POST['people'] ?? '';
            $nominations = $_POST['nominations'] ?? '';
            update_lists($data, $eventId, $people, $nominations);
            write_storage($data);
            redirect_with_event($eventId);
            break;
        case 'clear_votes':
            $eventId = $_POST['event_id'] ?? '';
            clear_votes($data, $eventId);
            write_storage($data);
            redirect_with_event($eventId);
            break;
    }
}

$eventId = $_GET['event'] ?? $data['active'];
if (!isset($data['events'][$eventId])) {
    $eventId = $data['active'];
}
$current = $data['events'][$eventId] ?? reset($data['events']);
$results = $current ? tally_results($current) : [];
$voteUrl = url_for('vote.php', ['event' => $eventId]);
$presentationUrl = url_for('presentation.php', ['event' => $eventId]);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($voteUrl);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка — Корпоративные номинации</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --accent: #7c3aed;
            --accent-2: #22d3ee;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --border: #1f2937;
            --green: #10b981;
            --red: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(124, 58, 237, 0.16), transparent 30%),
                        radial-gradient(circle at 80% 0%, rgba(34, 211, 238, 0.1), transparent 26%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        header {
            padding: 28px 26px 10px;
        }
        .container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 20px 60px;
        }
        .title {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .title span.badge {
            background: linear-gradient(120deg, var(--accent), var(--accent-2));
            color: #0b1021;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .subtitle { color: var(--muted); margin: 0; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
            margin-top: 18px;
        }
        .stack { display: flex; flex-direction: column; gap: 10px; }
        .card {
            background: rgba(17, 24, 39, 0.72);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(10px);
        }
        h2 { margin-top: 0; font-size: 18px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, textarea, select, button {
            width: 100%;
            background: #0b1021;
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 14px;
            transition: border 0.2s ease, transform 0.1s ease;
        }
        input:focus, textarea:focus, select:focus { border-color: var(--accent); outline: none; }
        textarea { min-height: 160px; resize: vertical; }
        button {
            cursor: pointer;
            background: linear-gradient(120deg, var(--accent), var(--accent-2));
            color: #0b1021;
            font-weight: 700;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        button.secondary { background: transparent; border: 1px solid var(--border); color: var(--text); }
        button.danger { background: linear-gradient(120deg, var(--red), #f97316); color: #0b1021; }
        button:hover { transform: translateY(-1px); }
        .row { display: flex; gap: 10px; }
        .row > * { flex: 1; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
        .pill { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.04); border-radius: 999px; padding: 6px 10px; color: var(--muted); }
        .qr { text-align: center; }
        .link { word-break: break-all; color: var(--accent-2); }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { text-align: left; padding: 10px; border-bottom: 1px solid var(--border); }
        .table th { color: var(--muted); font-weight: 600; }
        .chip { display: inline-flex; padding: 6px 10px; border-radius: 12px; background: rgba(124, 58, 237, 0.15); border: 1px solid rgba(124, 58, 237, 0.5); }
        .muted { color: var(--muted); font-size: 13px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 12px; }
        .inline-form { display: inline; }
    </style>
</head>
<body>
    <header class="container">
        <div class="title">
            <span class="badge">admin</span>
            <span>Корпоративные номинации</span>
        </div>
        <p class="subtitle">Создавайте отдельные проекты, загружайте списки и управляйте QR-ссылками.</p>
    </header>
    <div class="container">
        <div class="grid">
            <div class="card">
                <h2>Создать корпоратив</h2>
                <form method="post" class="stack">
                    <input type="hidden" name="action" value="create_event">
                    <label>Название</label>
                    <input name="title" placeholder="Например, Winter Party 2024" required>
                    <label>Подзаголовок</label>
                    <input name="subtitle" placeholder="Тема, дресс-код или город">
                    <label>Дата</label>
                    <input name="date" type="date" value="<?php echo date('Y-m-d'); ?>">
                    <button type="submit">Создать и переключиться</button>
                </form>
            </div>
            <div class="card">
                <h2>Текущий корпоратив</h2>
                <form method="post" class="stack">
                    <input type="hidden" name="action" value="switch_event">
                    <label>Выбрать</label>
                    <select name="event_id" onchange="this.form.submit()">
                        <?php foreach ($data['events'] as $id => $event): ?>
                            <option value="<?php echo h($id); ?>" <?php echo $id === $eventId ? 'selected' : ''; ?>><?php echo h($event['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if ($current): ?>
                    <div class="stats" style="margin-top:12px;">
                        <div class="pill">Номинаций: <strong><?php echo count($current['nominations']); ?></strong></div>
                        <div class="pill">Сотрудников: <strong><?php echo count($current['people']); ?></strong></div>
                        <div class="pill">Голосов: <strong><?php echo count($current['votes']); ?></strong></div>
                    </div>
                    <div class="header-actions">
                        <form method="post" class="inline-form" onsubmit="return confirm('Удалить корпоратив и его голоса?');">
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="event_id" value="<?php echo h($eventId); ?>">
                            <button type="submit" class="danger">Удалить корпоратив</button>
                        </form>
                        <form method="post" class="inline-form" onsubmit="return confirm('Очистить все голоса?');">
                            <input type="hidden" name="action" value="clear_votes">
                            <input type="hidden" name="event_id" value="<?php echo h($eventId); ?>">
                            <button type="submit" class="secondary">Сбросить голоса</button>
                        </form>
                        <a class="chip" href="<?php echo h($voteUrl); ?>" target="_blank">Открыть голосование</a>
                        <a class="chip" href="<?php echo h($presentationUrl); ?>" target="_blank">Открыть презентацию</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($current): ?>
        <div class="grid">
            <div class="card">
                <h2>Основная информация</h2>
                <form method="post">
                    <input type="hidden" name="action" value="update_meta">
                    <input type="hidden" name="event_id" value="<?php echo h($eventId); ?>">
                    <label>Название корпоратива</label>
                    <input name="title" value="<?php echo h($current['title']); ?>" required>
                    <label>Подзаголовок</label>
                    <input name="subtitle" value="<?php echo h($current['subtitle'] ?? ''); ?>" placeholder="Тема, офис, город">
                    <label>Дата</label>
                    <input name="date" type="date" value="<?php echo h($current['date'] ?? date('Y-m-d')); ?>">
                    <button type="submit">Сохранить описание</button>
                </form>
            </div>

            <div class="card qr">
                <h2>QR на голосование</h2>
                <img src="<?php echo h($qrUrl); ?>" alt="QR to vote" style="max-width: 240px; width: 100%; border-radius: 12px; border: 1px solid var(--border);">
                <div class="muted">Ссылка: <span class="link"><?php echo h($voteUrl); ?></span></div>
                <div class="muted" style="margin-top:8px;">Презентация: <span class="link"><?php echo h($presentationUrl); ?></span></div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Сотрудники</h2>
                <form method="post">
                    <input type="hidden" name="action" value="save_lists">
                    <input type="hidden" name="event_id" value="<?php echo h($eventId); ?>">
                    <label>Список (по строке на человека)</label>
                    <textarea name="people" placeholder="Иван Иванов\nМария Смирнова\n..."><?php echo h(implode("\n", $current['people'])); ?></textarea>
                    <button type="submit">Сохранить сотрудников</button>
                </form>
            </div>
            <div class="card">
                <h2>Номинации</h2>
                <form method="post">
                    <input type="hidden" name="action" value="save_lists">
                    <input type="hidden" name="event_id" value="<?php echo h($eventId); ?>">
                    <label>Список (по строке на номинацию)</label>
                    <textarea name="nominations" placeholder="Лучший тимлид\nКороль мемов\n..."><?php echo h(implode("\n", $current['nominations'])); ?></textarea>
                    <button type="submit">Сохранить номинации</button>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top:18px;">
            <h2>Результаты (live)</h2>
            <table class="table">
                <thead>
                    <tr><th>Номинация</th><th>Лидер</th><th>Подсчёт</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo h($row['nomination']); ?></td>
                            <td><span class="chip"><?php echo h($row['winner']); ?></span></td>
                            <td>
                                <div class="muted">
                                <?php if (empty($row['counts'])): ?>
                                    пока нет голосов
                                <?php else: ?>
                                    <?php foreach ($row['counts'] as $person => $count): ?>
                                        <div><?php echo h($person); ?> — <?php echo $count; ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
