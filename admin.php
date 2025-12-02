<?php
require __DIR__ . '/storage.php';

$data = load_data();
$message = '';
$error = '';

$requestedProjectId = $_GET['project'] ?? null;
$currentProject = find_project($data, $requestedProjectId) ?? find_project($data, $data['activeProjectId']);

if (!$currentProject && !empty($data['projects'])) {
    $currentProject = $data['projects'][0];
    $data['activeProjectId'] = $currentProject['id'];
    save_data($data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_project') {
        $projectName = trim($_POST['project_name'] ?? '');
        $newProject = [
            'id' => uniqid('proj_', true),
            'name' => $projectName !== '' ? $projectName : 'Новый корпоратив',
            'nominations' => [],
            'employees' => [],
            'votes' => [],
        ];
        save_project($data, $newProject);
        $data['activeProjectId'] = $newProject['id'];
        save_data($data);
        $currentProject = $newProject;
        $message = 'Создан новый корпоратив.';
    } elseif ($action === 'switch_project') {
        $targetId = $_POST['project_id'] ?? '';
        $target = find_project($data, $targetId);
        if ($target) {
            $data['activeProjectId'] = $targetId;
            save_data($data);
            header('Location: admin.php?project=' . urlencode($targetId));
            exit;
        }
        $error = 'Не удалось переключить корпоратив.';
    } elseif ($action === 'delete_project') {
        $targetId = $_POST['project_id'] ?? '';
        if ($targetId) {
            delete_project($data, $targetId);
            save_data($data);
            $currentProject = find_project($data, $data['activeProjectId']) ?? ($data['projects'][0] ?? null);
            $message = 'Корпоратив удалён.';
        }
    } elseif ($action === 'save_lists') {
        if ($currentProject) {
            $nominations = normalize_list($_POST['nominations'] ?? '');
            $employees = normalize_list($_POST['employees'] ?? '');

            $currentProject['nominations'] = $nominations;
            $currentProject['employees'] = $employees;
            $currentProject['votes'] = [];

            save_project($data, $currentProject);
            save_data($data);
            $message = 'Списки обновлены для выбранного корпоратива.';
        } else {
            $error = 'Сначала создайте корпоратив.';
        }
    }
}

if (!$currentProject && empty($data['projects'])) {
    $data['activeProjectId'] = null;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['REQUEST_URI'] ?? ''), '/');
$voteUrl = $scheme . '://' . $host . $basePath . '/vote.php';
$presentationUrl = $scheme . '://' . $host . $basePath . '/presentation.php';

if ($currentProject) {
    $voteUrl .= '?project=' . urlencode($currentProject['id']);
    $presentationUrl .= '?project=' . urlencode($currentProject['id']);
}

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
        :root {
            --bg: #0f1624;
            --card: rgba(255,255,255,0.06);
            --accent: #7c5dfa;
            --accent-2: #2dd4bf;
            --text: #f5f7fb;
            --muted: #9aa5b1;
            --danger: #ff6b6b;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; background: radial-gradient(circle at 20% 20%, rgba(124,93,250,0.2), transparent 25%), radial-gradient(circle at 80% 0%, rgba(45,212,191,0.18), transparent 25%), var(--bg); color: var(--text); min-height: 100vh; }
        h1 { margin: 0 0 8px; }
        h2 { margin: 0 0 12px; }
        .page { max-width: 1200px; margin: 0 auto; padding: 32px 20px 48px; }
        .hero { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .hero-text { max-width: 720px; }
        .card { background: var(--card); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 18px; box-shadow: 0 18px 60px rgba(0,0,0,0.3); backdrop-filter: blur(6px); margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        label { display: block; margin: 12px 0 6px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; }
        textarea { width: 100%; min-height: 140px; padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: var(--text); resize: vertical; }
        input[type="text"] { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: var(--text); }
        button { padding: 10px 16px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease; }
        button:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,0,0,0.25); }
        .primary { background: linear-gradient(135deg, var(--accent), #5c6aff); color: white; }
        .ghost { background: rgba(255,255,255,0.08); color: var(--text); }
        .danger { background: var(--danger); color: white; }
        .message { padding: 10px 12px; border-radius: 12px; margin-bottom: 10px; font-weight: 600; }
        .success { background: rgba(45,212,191,0.12); color: #7ef0df; border: 1px solid rgba(45,212,191,0.3); }
        .error { background: rgba(255,107,107,0.14); color: #ffc7c7; border: 1px solid rgba(255,107,107,0.4); }
        .flex { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .project-list { display: flex; gap: 8px; flex-wrap: wrap; }
        .pill { padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 8px; }
        .pill strong { color: white; }
        .muted { color: var(--muted); }
        .qr { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
        .qr img { border-radius: 12px; box-shadow: 0 10px 32px rgba(0,0,0,0.32); }
        .card h3 { margin: 0 0 8px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .table th { color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; }
        .table tbody tr:hover { background: rgba(255,255,255,0.03); }
        .slider { display: flex; align-items: center; gap: 10px; }
        .slide { flex: 1; padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(124,93,250,0.18), rgba(45,212,191,0.18)); min-height: 120px; }
        .actions { display: flex; gap: 8px; }
        .inline-form { display: inline; margin: 0; }
        .presentation-link { text-decoration: none; }
    </style>
</head>
<body>
    <div class="page">
        <div class="hero">
            <div class="hero-text">
                <div class="muted">Корпоративные вечера</div>
                <h1>Админ-панель голосования</h1>
                <p class="muted">Создавайте отдельные проекты под разные мероприятия, делитесь QR-кодом для голосования и выводите победителей на большой экран.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Корпоративы</h2>
            <div class="project-list">
                <?php foreach ($data['projects'] as $project): ?>
                    <div class="pill">
                        <strong><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($currentProject && $project['id'] === $currentProject['id']): ?>
                            <span class="muted">активен</span>
                        <?php endif; ?>
                        <form class="inline-form" method="post" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <button class="ghost" name="action" value="switch_project" type="submit">Открыть</button>
                            <button class="ghost danger" name="action" value="delete_project" type="submit" onclick="return confirm('Удалить корпоратив и все голоса?');">✕</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            <form class="flex" method="post" style="margin-top: 12px;">
                <input type="text" name="project_name" placeholder="Название нового корпоратива" aria-label="Название корпоратива">
                <button class="primary" type="submit" name="action" value="create_project">Создать корпоратив</button>
            </form>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Списки для выбранного корпоратива</h2>
                <?php if (!$currentProject): ?>
                    <p class="muted">Создайте корпоратив, чтобы добавить номинации и сотрудников.</p>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="save_lists">
                        <label for="nominations">Номинации (каждая с новой строки)</label>
                        <textarea id="nominations" name="nominations" placeholder="Самый громкий смех&#10;Лучший тост"><?= htmlspecialchars(implode("\n", $currentProject['nominations']), ENT_QUOTES, 'UTF-8') ?></textarea>

                        <label for="employees">Сотрудники (каждый с новой строки)</label>
                        <textarea id="employees" name="employees" placeholder="Иванов Иван&#10;Петров Пётр"><?= htmlspecialchars(implode("\n", $currentProject['employees']), ENT_QUOTES, 'UTF-8') ?></textarea>

                        <div class="flex" style="margin-top: 12px;">
                            <button class="primary" type="submit">Сохранить списки</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card">
                <h2>QR и ссылки</h2>
                <?php if (!$currentProject): ?>
                    <p class="muted">Нет активного корпоратива.</p>
                <?php else: ?>
                    <div class="qr">
                        <img src="<?= $qrUrl ?>" alt="QR код для голосования" width="220" height="220">
                        <div>
                            <div class="muted">Ссылка для голосования</div>
                            <div><a class="presentation-link" style="color: var(--accent-2);" href="<?= htmlspecialchars($voteUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($voteUrl, ENT_QUOTES, 'UTF-8') ?></a></div>
                            <div class="muted" style="margin-top:8px;">Демонстрация для экрана</div>
                            <div><a class="presentation-link" style="color: var(--accent);" href="<?= htmlspecialchars($presentationUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank">Открыть показ результатов →</a></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2>Результаты</h2>
            <?php if (!$currentProject || !$currentProject['nominations']): ?>
                <p class="muted">Добавьте номинации, чтобы увидеть таблицу лидеров.</p>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Номинация</th>
                                <th>Лидер</th>
                                <th>Голосов</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentProject['nominations'] as $nomination): ?>
                                <?php $winner = winner_for_nomination($nomination, $currentProject['votes']); ?>
                                <tr>
                                    <td><?= htmlspecialchars($nomination, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $winner['winner'] ? htmlspecialchars($winner['winner'], ENT_QUOTES, 'UTF-8') : '<span class="muted">Пока нет голосов</span>' ?></td>
                                    <td><?= $winner['count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="slider" style="margin-top: 12px;">
                    <button class="ghost" type="button" id="prev">Назад</button>
                    <div class="slide" id="slide"></div>
                    <button class="ghost" type="button" id="next">Далее</button>
                    <a class="presentation-link primary" style="padding: 12px 16px; display: inline-block; text-decoration: none;" href="<?= htmlspecialchars($presentationUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank">Полноэкранный показ</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const project = <?= json_encode($currentProject, JSON_UNESCAPED_UNICODE) ?>;
        const slide = document.getElementById('slide');
        if (project && slide) {
            function calculateWinner(nomination) {
                const nominationVotes = project.votes?.[nomination] || {};
                const entries = Object.entries(nominationVotes);
                if (entries.length === 0) {
                    return { name: 'Голосов пока нет', count: 0 };
                }
                entries.sort((a, b) => b[1] - a[1]);
                return { name: entries[0][0], count: entries[0][1] };
            }

            function renderSlide(index) {
                if (!project.nominations || project.nominations.length === 0) {
                    slide.textContent = 'Добавьте номинации в админке';
                    return;
                }
                const nomination = project.nominations[index];
                const winner = calculateWinner(nomination);
                slide.innerHTML = `
                    <div class="muted" style="font-size:12px; letter-spacing:0.8px; text-transform:uppercase;">Номинация</div>
                    <div style="font-size: 1.4em; font-weight: 800; margin-bottom: 8px;">${nomination}</div>
                    <div class="muted" style="font-size:12px; letter-spacing:0.8px; text-transform:uppercase;">Победитель</div>
                    <div style="font-size: 1.8em; font-weight: 700;">${winner.name}</div>
                    <div class="muted">Голосов: ${winner.count}</div>
                `;
            }

            let current = 0;
            renderSlide(current);

            document.getElementById('next').addEventListener('click', () => {
                current = (current + 1) % Math.max(project.nominations.length, 1);
                renderSlide(current);
            });
            document.getElementById('prev').addEventListener('click', () => {
                current = (current - 1 + Math.max(project.nominations.length, 1)) % Math.max(project.nominations.length, 1);
                renderSlide(current);
            });
        }
    </script>
</body>
</html>
