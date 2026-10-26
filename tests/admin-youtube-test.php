<?php
require_once dirname(__DIR__) . '/lib/youtube-settings.php';
function clever_csrf_token(): string { return 'test-csrf'; }
$isAccountAdmin = true;
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$_SERVER['REQUEST_METHOD'] = 'GET';
$originalKey = getenv('YOUTUBE_API_KEY');
try {
    foreach (['', 'secret-test-key-do-not-render'] as $apiKey) {
        putenv('YOUTUBE_API_KEY=' . $apiKey);
        ob_start();
        require dirname(__DIR__) . '/admin/youtube.php';
        $html = ob_get_clean();
        foreach (['name="start_date"', 'name="end_date"', 'value="save_youtube_fetch_settings"', 'value="run_youtube_fetch"', 'value="test-csrf"'] as $expected) {
            if (!str_contains($html, $expected)) throw new RuntimeException('Missing fetch control: ' . $expected);
        }
        if (str_contains($html, 'secret-test-key-do-not-render')) throw new RuntimeException('API key leaked into HTML');
        if (($apiKey === '') !== (bool)preg_match('/value="run_youtube_fetch"\s+disabled/', $html)) throw new RuntimeException('Run button must require API configuration');
    }
} finally {
    putenv($originalKey === false ? 'YOUTUBE_API_KEY' : 'YOUTUBE_API_KEY=' . $originalKey);
}
echo "Admin YouTube controls tests passed.\n";
