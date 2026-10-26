<?php

function clever_youtube_channels_path(): string
{
    return dirname(__DIR__) . '/youtube-dashboard/channels.json';
}

function clever_youtube_channels(): array
{
    $json = @file_get_contents(clever_youtube_channels_path());
    if ($json === false) throw new RuntimeException('Unable to read YouTube channel settings.');
    $channels = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($channels)) throw new RuntimeException('Invalid YouTube channel settings.');
    return $channels;
}

function clever_save_youtube_channels($text): void
{
    if (!is_string($text)) throw new RuntimeException('Enter one channel per line.');
    $channels = [];
    foreach (preg_split('/\R/', $text) as $line) {
        if (trim($line) === '') continue;
        $parts = explode('|', $line);
        if (count($parts) !== 2) throw new RuntimeException('Use Channel name | Channel ID on each line.');
        [$name, $id] = array_map('trim', $parts);
        if ($name === '' || strlen($name) > 200 || !preg_match('/^UC[A-Za-z0-9_-]{22}$/D', $id)) {
            throw new RuntimeException('Each channel needs a name (up to 200 bytes) and a 24-character channel ID starting with UC.');
        }
        if (isset($channels[$name]) || in_array($id, $channels, true)) throw new RuntimeException('Channel names and IDs must be unique.');
        $channels[$name] = $id;
    }
    $json = json_encode((object)$channels, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    $path = clever_youtube_channels_path();
    $temporary = tempnam(dirname($path), '.channels-');
    if ($temporary === false) throw new RuntimeException('The YouTube settings directory must be writable by PHP.');
    try {
        if (file_put_contents($temporary, $json, LOCK_EX) === false || !chmod($temporary, 0644) || !rename($temporary, $path)) {
            throw new RuntimeException('Unable to save YouTube channels. Check directory permissions.');
        }
    } finally {
        if (is_file($temporary)) unlink($temporary);
    }
}

/** API keys are supplied only through the server environment. */
function clever_youtube_fetch_settings(): array
{
    $path = dirname(__DIR__) . '/youtube-dashboard/fetch-settings.json';
    if (!is_file($path)) return ['start_date' => gmdate('Y-m-d', strtotime('-30 days')), 'end_date' => gmdate('Y-m-d')];
    $settings = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    return clever_validate_youtube_fetch_settings($settings);
}

function clever_validate_youtube_fetch_settings($settings): array
{
    foreach (['start_date', 'end_date'] as $key) {
        $value = $settings[$key] ?? null;
        if (!is_string($value)) throw new RuntimeException('Select a start and end date.');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d') !== $value) throw new RuntimeException('Use valid dates in YYYY-MM-DD format.');
    }
    if ($settings['start_date'] > $settings['end_date']) throw new RuntimeException('Start date must be on or before end date.');
    return ['start_date' => $settings['start_date'], 'end_date' => $settings['end_date']];
}

function clever_save_youtube_fetch_settings(array $input): void
{
    $settings = clever_validate_youtube_fetch_settings($input);
    $path = dirname(__DIR__) . '/youtube-dashboard/fetch-settings.json';
    $temporary = tempnam(dirname($path), '.fetch-settings-');
    if ($temporary === false) throw new RuntimeException('The YouTube settings directory must be writable by PHP.');
    try {
        if (file_put_contents($temporary, json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) === false
            || !chmod($temporary, 0644) || !rename($temporary, $path)) {
            throw new RuntimeException('Unable to save YouTube fetch settings.');
        }
    } finally {
        if (is_file($temporary)) unlink($temporary);
    }
}
