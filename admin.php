<?php
require __DIR__ . '/storage.php';

$data = load_data();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominations = normalize_list($_POST['nominations'] ?? '');
    $employees = normalize_list($_POST['employees'] ?? '');

    $data['nominations'] = $nominations;
    $data['employees'] = $employees;
    $data['votes'] = [];

    save_data($data);
    $message = 'Списки обновлены, можно делиться QR-кодом для голосования.';
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['REQUEST_URI'] ?? ''), '/');
$voteUrl = $scheme . '://' . $host . $basePath . '/vote.php';

$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($voteUrl);

function winner_for_nomination(string $nomination, array $votes): array
{
    $nominationVotes = $votes[$nomination] ?? [];
    if (!$nominationVotes) {
        return ['winner' => null, 'count' => 0];
    }

    arsort($nominationVotes);
    $topEmployee = key($nominationVotes);
    $topVotes = current($nominationVotes);

    return ['winner' => $topEmployee, 'count' => $topVotes];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка голосования</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f6fa; }
        h1, h2 { margin-top: 0; }
        .panel { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        textarea { width: 100%; min-height: 120px; padding: 12px; border-radius: 8px; border: 1px solid #ccc; }
        button { padding: 10px 18px; border: none; background: #3f51b5; color: #fff; border-radius: 8px; cursor: pointer; }
        button:hover { background: #324293; }
        .message { color: #2e7d32; margin-bottom: 12px; font-weight: bold; }
        .qr { display: flex; align-items: center; gap: 20px; }
        .votes-table { width: 100%; border-collapse: collapse; }
        .votes-table th, .votes-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .votes-table th { background: #f0f0f0; }
        .slider { display: flex; align-items: center; gap: 16px; margin-top: 12px; }
        .slide { min-height: 120px; padding: 16px; background: #e8eaf6; border-radius: 10px; flex: 1; }
        .muted { color: #777; }
        .small { font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Голосование за шуточные номинации — Админка</h1>

    <div class="panel">
        <h2>1. Загрузка списков</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="nominations">Номинации (каждая с новой строки):</label>
            <textarea id="nominations" name="nominations" placeholder="Самый громкий смех\nЛучший тост"><?= htmlspecialchars(implode("\n", $data['nominations']), ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="employees">Сотрудники (каждый с новой строки):</label>
            <textarea id="employees" name="employees" placeholder="Иванов Иван\nПетров Пётр"><?= htmlspecialchars(implode("\n", $data['employees']), ENT_QUOTES, 'UTF-8') ?></textarea>

            <button type="submit">Сохранить списки</button>
        </form>
    </div>

    <div class="panel">
        <h2>2. QR-код для голосования</h2>
        <div class="qr">
            <img src="<?= $qrUrl ?>" alt="QR код для голосования">
            <div>
                <div class="small muted">Ссылка для коллег:</div>
                <div><a href="<?= htmlspecialchars($voteUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($voteUrl, ENT_QUOTES, 'UTF-8') ?></a></div>
                <div class="small muted">Отсканируйте QR, чтобы заполнить форму.</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>3. Результаты</h2>
        <?php if (!$data['nominations']): ?>
            <p class="muted">Сначала добавьте номинации и сотрудников.</p>
        <?php else: ?>
            <table class="votes-table">
                <thead>
                    <tr>
                        <th>Номинация</th>
                        <th>Лидер</th>
                        <th>Голосов</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['nominations'] as $nomination): ?>
                        <?php $winner = winner_for_nomination($nomination, $data['votes']); ?>
                        <tr>
                            <td><?= htmlspecialchars($nomination, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $winner['winner'] ? htmlspecialchars($winner['winner'], ENT_QUOTES, 'UTF-8') : '<span class="muted">Пока нет голосов</span>' ?></td>
                            <td><?= $winner['count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="slider">
                <button type="button" id="prev">Назад</button>
                <div class="slide" id="slide"></div>
                <button type="button" id="next">Далее</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const nominations = <?= json_encode($data['nominations'], JSON_UNESCAPED_UNICODE) ?>;
        const votes = <?= json_encode($data['votes'], JSON_UNESCAPED_UNICODE) ?>;

        const slide = document.getElementById('slide');
        if (slide) {
            function calculateWinner(nomination) {
                const nominationVotes = votes[nomination] || {};
                const entries = Object.entries(nominationVotes);
                if (entries.length === 0) {
                    return { name: 'Голосов пока нет', count: 0 };
                }
                entries.sort((a, b) => b[1] - a[1]);
                return { name: entries[0][0], count: entries[0][1] };
            }

            function renderSlide(index) {
                if (!nominations.length) {
                    slide.textContent = 'Добавьте номинации в админке';
                    return;
                }
                const nomination = nominations[index];
                const winner = calculateWinner(nomination);
                slide.innerHTML = `<div class="small muted">Номинация</div><div style="font-size: 1.2em; font-weight: bold;">${nomination}</div><div class="small muted">Победитель</div><div style="font-size: 1.5em;">${winner.name}</div><div class="small muted">Голосов: ${winner.count}</div>`;
            }

            let current = 0;
            renderSlide(current);

            document.getElementById('next').addEventListener('click', () => {
                current = (current + 1) % Math.max(nominations.length, 1);
                renderSlide(current);
            });
            document.getElementById('prev').addEventListener('click', () => {
                current = (current - 1 + Math.max(nominations.length, 1)) % Math.max(nominations.length, 1);
                renderSlide(current);
            });
        }
    </script>
</body>
</html>
