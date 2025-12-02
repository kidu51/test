<?php
const DATA_FILE = __DIR__ . '/data/data.json';

function load_data(): array
{
    if (!file_exists(DATA_FILE)) {
        return ['nominations' => [], 'employees' => [], 'votes' => []];
    }

    $raw = file_get_contents(DATA_FILE);
    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        return ['nominations' => [], 'employees' => [], 'votes' => []];
    }

    $decoded['nominations'] = $decoded['nominations'] ?? [];
    $decoded['employees'] = $decoded['employees'] ?? [];
    $decoded['votes'] = $decoded['votes'] ?? [];

    return $decoded;
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
