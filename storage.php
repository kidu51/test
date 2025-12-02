<?php
const DATA_FILE = __DIR__ . '/data/data.json';

function default_data(): array
{
    return [
        'projects' => [],
        'activeProjectId' => null,
    ];
}

function load_data(): array
{
    if (!file_exists(DATA_FILE)) {
        return default_data();
    }

    $raw = file_get_contents(DATA_FILE);
    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        return default_data();
    }

    // Backward compatibility for the previous single-project format
    if (!isset($decoded['projects'])) {
        $converted = default_data();
        $converted['projects'][] = [
            'id' => uniqid('proj_', true),
            'name' => 'Корпоратив',
            'nominations' => $decoded['nominations'] ?? [],
            'employees' => $decoded['employees'] ?? [],
            'votes' => $decoded['votes'] ?? [],
        ];
        $converted['activeProjectId'] = $converted['projects'][0]['id'];
        save_data($converted);
        return $converted;
    }

    $decoded['projects'] = array_map(static function ($project) {
        return [
            'id' => $project['id'] ?? uniqid('proj_', true),
            'name' => $project['name'] ?? 'Без названия',
            'nominations' => $project['nominations'] ?? [],
            'employees' => $project['employees'] ?? [],
            'votes' => $project['votes'] ?? [],
        ];
    }, $decoded['projects']);

    if (empty($decoded['activeProjectId']) && !empty($decoded['projects'])) {
        $decoded['activeProjectId'] = $decoded['projects'][0]['id'];
    }

    return $decoded + default_data();
}

function save_data(array $data): void
{
    if (!is_dir(dirname(DATA_FILE))) {
        mkdir(dirname(DATA_FILE), 0777, true);
    }

    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function normalize_list(string $input): array
{
    $items = array_filter(array_map('trim', explode("\n", $input)), static function ($item) {
        return $item !== '';
    });

    return array_values(array_unique($items));
}

function find_project(array $data, ?string $projectId): ?array
{
    if ($projectId === null) {
        return null;
    }

    foreach ($data['projects'] as $project) {
        if ($project['id'] === $projectId) {
            return $project;
        }
    }

    return null;
}

function save_project(array &$data, array $project): void
{
    $found = false;
    foreach ($data['projects'] as &$existing) {
        if ($existing['id'] === $project['id']) {
            $existing = $project;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $data['projects'][] = $project;
    }

    if (empty($data['activeProjectId'])) {
        $data['activeProjectId'] = $project['id'];
    }
}

function delete_project(array &$data, string $projectId): void
{
    $data['projects'] = array_values(array_filter(
        $data['projects'],
        static fn($project) => ($project['id'] ?? '') !== $projectId
    ));

    if ($data['activeProjectId'] === $projectId) {
        $data['activeProjectId'] = $data['projects'][0]['id'] ?? null;
    }
}
