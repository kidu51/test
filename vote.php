<?php
require __DIR__ . '/storage.php';

$data = load_data();
$submitted = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$data['nominations'] || !$data['employees']) {
        $error = 'Голосование ещё не настроено.';
    } else {
        foreach ($data['nominations'] as $nomination) {
            $field = 'nomination_' . md5($nomination);
            $choice = trim($_POST[$field] ?? '');
            if ($choice === '' || !in_array($choice, $data['employees'], true)) {
                $error = 'Пожалуйста, выберите сотрудников из выпадающих списков.';
                break;
            }
            if (!isset($data['votes'][$nomination])) {
                $data['votes'][$nomination] = [];
            }
            if (!isset($data['votes'][$nomination][$choice])) {
                $data['votes'][$nomination][$choice] = 0;
            }
            $data['votes'][$nomination][$choice]++;
        }

        if ($error === '') {
            save_data($data);
            $submitted = true;
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
        body { font-family: Arial, sans-serif; margin: 20px; background: #fdfdfd; }
        h1 { margin-top: 0; }
        .panel { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 820px; margin: 0 auto; }
        label { display: block; margin: 12px 0 6px; font-weight: bold; }
        input[list] { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; }
        button { margin-top: 16px; padding: 10px 18px; border: none; background: #009688; color: #fff; border-radius: 8px; cursor: pointer; }
        button:hover { background: #00796b; }
        .success { color: #2e7d32; font-weight: bold; margin-bottom: 10px; }
        .error { color: #c62828; font-weight: bold; margin-bottom: 10px; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Выберите победителей в шуточных номинациях</h1>
        <?php if ($submitted): ?>
            <div class="success">Спасибо! Ваш голос учтён.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (!$data['nominations'] || !$data['employees']): ?>
            <p class="muted">Голосование будет доступно, когда администратор добавит списки номинаций и сотрудников.</p>
        <?php else: ?>
            <form method="post">
                <?php foreach ($data['nominations'] as $nomination): ?>
                    <?php $fieldName = 'nomination_' . md5($nomination); ?>
                    <label for="<?= $fieldName ?>"><?= htmlspecialchars($nomination, ENT_QUOTES, 'UTF-8') ?></label>
                    <input list="employees" id="<?= $fieldName ?>" name="<?= $fieldName ?>" placeholder="Начните вводить фамилию" required>
                <?php endforeach; ?>
                <datalist id="employees">
                    <?php foreach ($data['employees'] as $employee): ?>
                        <option value="<?= htmlspecialchars($employee, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                </datalist>
                <button type="submit">Отправить голос</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
