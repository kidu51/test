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
$results = $event ? tally_results($event) : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Презентация — <?php echo $event ? h($event['title']) : 'Номинации'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #030712;
            --accent: #a855f7;
            --accent-2: #22d3ee;
            --text: #e2e8f0;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, 'Segoe UI', sans-serif;
            background: radial-gradient(circle at 15% 15%, rgba(168, 85, 247, 0.14), transparent 32%),
                        radial-gradient(circle at 85% 0%, rgba(34, 211, 238, 0.12), transparent 26%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow: hidden;
        }
        .controls {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        button {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(0,0,0,0.25);
            color: var(--text);
            cursor: pointer;
            font-weight: 700;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }
        button.primary { background: linear-gradient(120deg, var(--accent), var(--accent-2)); color: #050815; border: none; }
        .slide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            opacity: 0;
            transform: translateX(40px);
            transition: all 0.6s ease;
        }
        .slide.active { opacity: 1; transform: translateX(0); }
        .nomination { font-size: clamp(28px, 6vw, 64px); font-weight: 800; line-height: 1.1; }
        .winner { font-size: clamp(30px, 7vw, 70px); font-weight: 900; margin-top: 12px; color: #0f172a; padding: 12px 20px; background: linear-gradient(120deg, var(--accent), var(--accent-2)); border-radius: 18px; box-shadow: 0 20px 80px rgba(0,0,0,0.45); }
        .muted { color: var(--muted); margin-top: 8px; font-size: 16px; }
        .overlay { position: absolute; inset: 0; background: radial-gradient(circle at 50% 60%, rgba(255,255,255,0.04), transparent 50%); pointer-events: none; }
        .footer { position: fixed; bottom: 18px; width: 100%; text-align: center; color: var(--muted); font-size: 14px; }
    </style>
</head>
<body>
    <?php if (!$event): ?>
        <div class="slide active">
            <div>
                <div class="nomination">Корпоратив не выбран</div>
                <div class="muted">Создайте событие в админке.</div>
            </div>
        </div>
    <?php else: ?>
        <div class="controls">
            <button id="prev">← Назад</button>
            <button id="play" class="primary">▶ Авто</button>
            <button id="next">Вперёд →</button>
        </div>
        <div id="slides"></div>
        <div class="overlay"></div>
        <div class="footer">Корпоратив: <?php echo h($event['title']); ?> · <?php echo h($event['date'] ?? ''); ?></div>
        <script>
            const results = <?php echo json_encode($results, JSON_UNESCAPED_UNICODE); ?>;
            const slides = [];
            results.forEach(row => {
                slides.push({ type: 'nomination', title: row.nomination });
                slides.push({ type: 'winner', title: row.nomination, winner: row.winner });
            });
            if (slides.length === 0) {
                slides.push({ type: 'nomination', title: 'Голосов пока нет', winner: '' });
            }

            const slidesRoot = document.getElementById('slides');
            slides.forEach((slide, index) => {
                const el = document.createElement('div');
                el.className = 'slide' + (index === 0 ? ' active' : '');
                el.dataset.index = index;
                el.innerHTML = `
                    <div>
                        <div class="nomination">${slide.type === 'winner' ? 'Победитель: ' + slide.title : slide.title}</div>
                        ${slide.type === 'winner' ? `<div class="winner">${slide.winner || '—'}</div>` : '<div class="muted">ожидание победителя</div>'}
                    </div>
                `;
                slidesRoot.appendChild(el);
            });

            let current = 0;
            let timer = null;
            const move = (delta) => {
                const next = (current + delta + slides.length) % slides.length;
                show(next);
            };
            const show = (index) => {
                const prevEl = slidesRoot.querySelector('.slide.active');
                if (prevEl) prevEl.classList.remove('active');
                const el = slidesRoot.querySelector(`.slide[data-index="${index}"]`);
                if (el) el.classList.add('active');
                current = index;
            };
            const togglePlay = () => {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                    playBtn.textContent = '▶ Авто';
                } else {
                    timer = setInterval(() => move(1), 3500);
                    playBtn.textContent = '⏸ Стоп';
                }
            };

            const prevBtn = document.getElementById('prev');
            const nextBtn = document.getElementById('next');
            const playBtn = document.getElementById('play');
            prevBtn.onclick = () => move(-1);
            nextBtn.onclick = () => move(1);
            playBtn.onclick = togglePlay;
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight') move(1);
                if (e.key === 'ArrowLeft') move(-1);
                if (e.key === ' ') { e.preventDefault(); togglePlay(); }
            });
        </script>
    <?php endif; ?>
</body>
</html>
