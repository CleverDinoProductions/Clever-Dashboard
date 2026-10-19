<?php
/** Reuse the existing English fetcher's key without executing its import. */
function football_stats_sportsdb_api_key(?string $legacyKey = null): string
{
    $configured = getenv('THESPORTSDB_API_KEY');
    if (is_string($configured) && trim($configured) !== '') return trim($configured);
    if ($legacyKey !== null && $legacyKey !== '') return $legacyKey;

    // Compatibility with existing installations. Keep the credential in its
    // original file until operators migrate it to the environment variable.
    $source = file_get_contents(dirname(__DIR__).'/fetch-englishfootball.php');
    $pattern = <<<'REGEX'
~\$API_KEY\s*=\s*'([^']+)'\s*;~
REGEX;
    if ($source !== false && preg_match($pattern, $source, $match)) return $match[1];
    throw new RuntimeException('Configure THESPORTSDB_API_KEY before syncing football data.');
}
