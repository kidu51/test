<?php
require __DIR__ . '/storage.php';

$data = load_data();
$projectId = $_GET['project'] ?? ($data['activeProjectId'] ?? null);
$currentProject = find_project($data, $projectId) ?? ($data['projects'][0] ?? null);
if ($currentProject) {
    $projectId = $currentProject['id'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Показ результатов</title>
    <style>
        :root {
            --bg: #050910;
            --accent: #7c5dfa;
            --accent-2: #2dd4bf;
            --text: #f7fafc;
            --muted: #8a94a7;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; background: radial-gradient(circle at 20% 20%, rgba(124,93,250,0.2), transparent 30%), radial-gradient(circle at 80% 0%, rgba(45,212,191,0.2), transparent 28%), var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .frame { width: 100%; max-width: 1200px; min-height: 70vh; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 22px; padding: 30px; box-shadow: 0 28px 80px rgba(0,0,0,0.45); position: relative; overflow: hidden; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 16px; color: var(--muted); }
        .topbar strong { color: var(--text); }
        .controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        button { padding: 10px 14px; border-radius: 12px; border: none; cursor: pointer; font-weight: 800; }
        .ghost { background: rgba(255,255,255,0.08); color: var(--text); }
        .primary { background: linear-gradient(135deg, var(--accent), #5c6aff); color: white; }
        .slide { display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 12px; height: calc(70vh - 40px); padding: 20px; }
        .tag { text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; color: var(--muted); }
        .nomination { font-size: clamp(2rem, 3vw + 1rem, 3rem); font-weight: 800; }
        .winner { font-size: clamp(2.6rem, 4vw + 1rem, 4rem); font-weight: 800; color: #b2f5ea; }
        .sub { color: var(--muted); }
        .empty { color: var(--muted); text-align: center; }
        .progress { position: absolute; bottom: 0; left: 0; height: 6px; background: linear-gradient(90deg, var(--accent), var(--accent-2)); transition: width 0.3s ease; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="topbar">
            <div>
                <div class="tag">Показ результатов</div>
                <?php if ($currentProject): ?>
                    <strong><?= htmlspecialchars($currentProject['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php else: ?>
                    <strong>Нет активного корпоратива</strong>
                <?php endif; ?>
            </div>
            <div class="controls">
                <button class="ghost" id="prev">Назад</button>
                <button class="ghost" id="next">Далее</button>
                <button class="primary" id="fullscreen">На весь экран</button>
                <a class="ghost" style="text-decoration:none; padding:10px 12px; display:inline-block;" href="admin.php">Админка</a>
            </div>
        </div>
        <?php if (!$currentProject || !$currentProject['nominations']): ?>
            <div class="empty">Добавьте номинации, чтобы начать слайды.</div>
        <?php else: ?>
            <div id="slide" class="slide"></div>
            <div id="progress" class="progress" style="width:0;"></div>
        <?php endif; ?>
    </div>

    <script>
        const project = <?= json_encode($currentProject, JSON_UNESCAPED_UNICODE) ?>;
        const slideEl = document.getElementById('slide');
        const progress = document.getElementById('progress');

        function computeWinner(nomination) {
            const votes = project?.votes?.[nomination] || {};
            const entries = Object.entries(votes);
            if (!entries.length) {
                return { name: 'Голосов пока нет', count: 0 };
            }
            entries.sort((a, b) => b[1] - a[1]);
            return { name: entries[0][0], count: entries[0][1] };
        }

        const slides = [];
        if (project?.nominations) {
            project.nominations.forEach((nomination) => {
                slides.push({ type: 'nomination', nomination });
                const winner = computeWinner(nomination);
                slides.push({ type: 'winner', nomination, winner });
            });
        }

        let index = 0;

        function render() {
            if (!slideEl || !slides.length) return;
            const item = slides[index];
            if (item.type === 'nomination') {
                slideEl.innerHTML = `
                    <div class="tag">Номинация</div>
                    <div class="nomination">${item.nomination}</div>
                    <div class="sub">Нажмите "Далее" чтобы показать победителя</div>
                `;
            } else {
                slideEl.innerHTML = `
                    <div class="tag">Победитель</div>
                    <div class="nomination">${item.nomination}</div>
                    <div class="winner">${item.winner.name}</div>
                    <div class="sub">Голосов: ${item.winner.count}</div>
                `;
            }
            if (progress) {
                progress.style.width = `${((index + 1) / slides.length) * 100}%`;
            }
        }

        function next() {
            if (!slides.length) return;
            index = (index + 1) % slides.length;
            render();
        }

        function prev() {
            if (!slides.length) return;
            index = (index - 1 + slides.length) % slides.length;
            render();
        }

        document.getElementById('next')?.addEventListener('click', next);
        document.getElementById('prev')?.addEventListener('click', prev);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight' || event.key === ' ') {
                next();
            }
            if (event.key === 'ArrowLeft') {
                prev();
            }
        });
        document.getElementById('fullscreen')?.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen?.();
            } else {
                document.exitFullscreen?.();
            }
        });

        render();
    </script>
</body>
</html>
