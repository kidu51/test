<?php
// Core storage helpers for the corporate awards voting app.
// The storage format keeps multiple events ("corporate parties") in one JSON file.

const STORAGE_FILE = __DIR__ . '/data/data.json';
const STORAGE_VERSION = 1;

function ensure_data_dir(): void {
    $dir = dirname(STORAGE_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function default_event(): array {
    $now = date('Y-m-d');
    return [
        'id' => 'demo-party',
        'title' => 'Demo Corporate Party',
        'subtitle' => 'Sample data you can replace',
        'date' => $now,
        'nominations' => [
            'Самый громкий смех',
            'Лучший тимлид',
            'Главный оптимист',
        ],
        'people' => [
            'Анна Петрова',
            'Борис Иванов',
            'Владимир Смирнов',
            'Галина Орлова',
            'Дмитрий Соколов',
        ],
        'votes' => [],
        'created_at' => date(DATE_ATOM),
    ];
}

function default_data(): array {
    $event = default_event();
    return [
        'version' => STORAGE_VERSION,
        'active' => $event['id'],
        'events' => [ $event['id'] => $event ],
    ];
}

function read_storage(): array {
    ensure_data_dir();
    if (!file_exists(STORAGE_FILE) || filesize(STORAGE_FILE) === 0) {
        $data = default_data();
        write_storage($data);
        return $data;
    }

    $json = file_get_contents(STORAGE_FILE);
    $data = json_decode($json, true);

    if (!is_array($data) || !isset($data['events'])) {
        $data = default_data();
    }

    // backward compatibility: ensure keys
    $data['version'] = $data['version'] ?? STORAGE_VERSION;
    $data['events'] = $data['events'] ?? [];
    if (empty($data['events'])) {
        $default = default_event();
        $data['events'][$default['id']] = $default;
        $data['active'] = $default['id'];
    }

    $active = $data['active'] ?? array_key_first($data['events']);
    if (!isset($data['events'][$active])) {
        $data['active'] = array_key_first($data['events']);
    }

    return $data;
}

function write_storage(array $data): void {
    ensure_data_dir();
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $fp = fopen(STORAGE_FILE, 'c+');
    if (!$fp) {
        throw new RuntimeException('Cannot open storage file');
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function slugify(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9а-яё-]+/u', '-', $value);
    $value = preg_replace('/-+/', '-', $value);
    return trim($value, '-') ?: 'event-' . substr(md5($value), 0, 6);
}

function update_event_meta(array &$data, string $id, string $title, string $subtitle, string $date): void {
    if (!isset($data['events'][$id])) {
        return;
    }
    $data['events'][$id]['title'] = $title ?: $data['events'][$id]['title'];
    $data['events'][$id]['subtitle'] = $subtitle;
    $data['events'][$id]['date'] = $date ?: $data['events'][$id]['date'];
}

function create_event(array &$data, string $title, string $subtitle = '', string $date = ''): string {
    $id = slugify($title ?: 'event');
    $suffix = 1;
    $base = $id;
    while (isset($data['events'][$id])) {
        $id = $base . '-' . $suffix;
        $suffix++;
    }

    $data['events'][$id] = [
        'id' => $id,
        'title' => $title ?: 'Новый корпоратив',
        'subtitle' => $subtitle,
        'date' => $date ?: date('Y-m-d'),
        'nominations' => [],
        'people' => [],
        'votes' => [],
        'created_at' => date(DATE_ATOM),
    ];
    $data['active'] = $id;
    return $id;
}

function delete_event(array &$data, string $id): void {
    unset($data['events'][$id]);
    if (empty($data['events'])) {
        $default = default_event();
        $data['events'][$default['id']] = $default;
        $data['active'] = $default['id'];
        return;
    }
    if (($data['active'] ?? '') === $id) {
        $data['active'] = array_key_first($data['events']);
    }
}

function set_active_event(array &$data, string $id): void {
    if (isset($data['events'][$id])) {
        $data['active'] = $id;
    }
}

function parse_multiline_list(string $text): array {
    $items = array_filter(array_map('trim', preg_split('/\r?\n/', $text)));
    $unique = [];
    foreach ($items as $item) {
        $unique[$item] = true;
    }
    return array_keys($unique);
}

function update_lists(array &$data, string $eventId, string $peopleText, string $nominationsText): void {
    if (!isset($data['events'][$eventId])) {
        return;
    }
    $data['events'][$eventId]['people'] = parse_multiline_list($peopleText);
    $data['events'][$eventId]['nominations'] = parse_multiline_list($nominationsText);
}

function clear_votes(array &$data, string $eventId): void {
    if (isset($data['events'][$eventId])) {
        $data['events'][$eventId]['votes'] = [];
    }
}

function record_votes(array &$data, string $eventId, array $votes): void {
    if (!isset($data['events'][$eventId])) {
        return;
    }
    $time = time();
    foreach ($votes as $nomination => $person) {
        if ($person === '' || $person === null) {
            continue;
        }
        $data['events'][$eventId]['votes'][] = [
            'nomination' => $nomination,
            'person' => $person,
            'timestamp' => $time,
        ];
    }
}

function tally_results(array $event): array {
    $results = [];
    foreach ($event['nominations'] as $nomination) {
        $results[$nomination] = [];
    }
    foreach ($event['votes'] as $vote) {
        $nomination = $vote['nomination'];
        $person = $vote['person'];
        if (!isset($results[$nomination])) {
            $results[$nomination] = [];
        }
        $results[$nomination][$person] = ($results[$nomination][$person] ?? 0) + 1;
    }

    $ranked = [];
    foreach ($results as $nomination => $counts) {
        arsort($counts);
        $winner = key($counts) ?: '—';
        $ranked[] = [
            'nomination' => $nomination,
            'winner' => $winner,
            'counts' => $counts,
        ];
    }
    return $ranked;
}

function url_for(string $path, array $params = []): string {
    $query = http_build_query($params);
    return $path . ($query ? ('?' . $query) : '');
}
