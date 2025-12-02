<?php
require __DIR__ . '/storage.php';

$data = load_data();
$message = '';
$error = '';

$projectId = $_GET['project'] ?? ($data['activeProjectId'] ?? null);
$currentProject = find_project($data, $projectId) ?? ($data['projects'][0] ?? null);
if ($currentProject) {
    $projectId = $currentProject['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = $_POST['project_id'] ?? $projectId;
    $currentProject = find_project($data, $projectId) ?? $currentProject;

    if (!$currentProject) {
        $error = 'Администратор ещё не создал корпоратив.';
    } elseif (!$currentProject['nominations'] || !$currentProject['employees']) {
        $error = 'Голосование ещё не настроено.';
    } else {
        foreach ($currentProject['nominations'] as $nomination) {
            $field = 'nomination_' . md5($nomination);
            $choice = trim($_POST[$field] ?? '');
            if ($choice === '' || !in_array($choice, $currentProject['employees'], true)) {
                $error = 'Пожалуйста, выберите сотрудников из выпадающих списков.';
                break;
            }
            if (!isset($currentProject['votes'][$nomination])) {
                $currentProject['votes'][$nomination] = [];
            }
            if (!isset($currentProject['votes'][$nomination][$choice])) {
                $currentProject['votes'][$nomination][$choice] = 0;
            }
            $currentProject['votes'][$nomination][$choice]++;
        }

        if ($error === '') {
            save_project($data, $currentProject);
            save_data($data);
            $message = 'Спасибо! Ваш голос учтён.';
        }
    }
}
?>
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
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; background: radial-gradient(circle at 10% 20%, rgba(124,93,250,0.18), transparent 25%), radial-gradient(circle at 90% 0%, rgba(45,212,191,0.14), transparent 25%), var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .panel { width: 100%; max-width: 900px; background: var(--card); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); padding: 24px; }
        h1 { margin: 0 0 6px; }
        p { margin: 4px 0 14px; color: var(--muted); }
        label { display: block; margin: 14px 0 6px; font-weight: 700; color: var(--text); }
        input[list] { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.04); color: var(--text); }
        .muted { color: var(--muted); }
        .message { padding: 12px; border-radius: 12px; font-weight: 700; margin-bottom: 12px; }
        .success { background: rgba(45,212,191,0.12); color: #7ef0df; border: 1px solid rgba(45,212,191,0.3); }
        .error { background: rgba(255,107,107,0.14); color: #ffc7c7; border: 1px solid rgba(255,107,107,0.4); }
        .button { margin-top: 18px; padding: 12px 18px; border-radius: 12px; border: none; background: linear-gradient(135deg, var(--accent), #5c6aff); color: #fff; font-weight: 800; cursor: pointer; letter-spacing: 0.4px; box-shadow: 0 10px 28px rgba(0,0,0,0.25); }
        .badge { display: inline-block; padding: 6px 10px; background: rgba(255,255,255,0.08); border-radius: 999px; font-size: 0.85rem; color: var(--muted); }
    </style>
</head>
<body>
    <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 10px; flex-wrap: wrap;">
            <div>
                <div class="badge">Корпоративное голосование</div>
                <h1>Выбор победителей</h1>
                <?php if ($currentProject): ?>
                    <p>Текущее мероприятие: <strong><?= htmlspecialchars($currentProject['name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                <?php else: ?>
                    <p class="muted">Администратор ещё не создал корпоратив.</p>
                <?php endif; ?>
            </div>
            <a href="admin.php" style="color: var(--accent-2); text-decoration: none; font-weight: 700;">← В админку</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!$currentProject): ?>
            <p class="muted">Нет доступного корпоратива. Попросите администратора создать его.</p>
        <?php elseif (!$currentProject['nominations'] || !$currentProject['employees']): ?>
            <p class="muted">Голосование будет доступно, когда администратор добавит номинации и список сотрудников.</p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="project_id" value="<?= htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($currentProject['nominations'] as $nomination): ?>
                    <?php $fieldName = 'nomination_' . md5($nomination); ?>
                    <label for="<?= $fieldName ?>"><?= htmlspecialchars($nomination, ENT_QUOTES, 'UTF-8') ?></label>
                    <input list="employees" id="<?= $fieldName ?>" name="<?= $fieldName ?>" placeholder="Начните вводить фамилию" required>
                <?php endforeach; ?>
                <datalist id="employees">
                    <?php foreach ($currentProject['employees'] as $employee): ?>
                        <option value="<?= htmlspecialchars($employee, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                </datalist>
                <button class="button" type="submit">Отправить голос</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
