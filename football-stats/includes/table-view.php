<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

require_once __DIR__ . '/table-view-date-helper.php';

/**
 * Universal URL builder that strictly preserves active route state across toggles
 */
if (!function_exists('football_stats_build_table_view_url')) {
    function football_stats_build_table_view_url($tab = null, $league = null, $subtab = null, array $extraParams = [])
    {
        // 1. Fallback to active $_GET variables if not explicitly provided
        $tab = $tab ?? $_GET['tab'] ?? null;
        $league = $league ?? $_GET['league'] ?? null;
        $subtab = $subtab ?? $_GET['subtab'] ?? null;

        $params = [];

        if ($tab !== null && $tab !== '') {
            $params['tab'] = $tab;
        }

        if ($league !== null && $league !== '') {
            $params['league'] = $league;
        }

        if ($subtab !== null && $subtab !== '') {
            $params['subtab'] = $subtab;
        }

        // 2. Preserve active persistent query parameters
        $persistentKeys = [
            'calc_mode', 
            'match_filter_mode', 
            'snapshot_season', 
            'matchweek', 
            'snapshot_date', 
            'match_id', 
            'table_filter',
            'tracker_team',
            'table_view'
        ];
        
        foreach ($persistentKeys as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '' && !array_key_exists($key, $extraParams)) {
                $params[$key] = $_GET[$key];
            }
        }

        // 3. Apply extra or overriding parameters
        foreach ($extraParams as $key => $value) {
            if ($value === null || $value === '') {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return '?' . http_build_query($params);
    }
}

/**
 * Check if a subtab supports historical snapshots
 */
if (!function_exists('football_stats_tab_supports_table_view')) {
    function football_stats_tab_supports_table_view($subtab)
    {
        return in_array(
            (string)$subtab,
            [
                'table', 'table-2', 'blocks-overview', 'blocks-dynamic',
                'blocks-1', 'blocks-2', 'blocks-3', 'blocks-4', 'blocks-5',
                'relegation', 'relegation-2', 'leeds', 'leeds-2', 'leeds-3',
                'team-tracker', 'team-tracker-2', 'team-tracker-3',
                'simulation',
            ],
            true
        );
    }
}

/**
 * Helper to get current URL params safely
 */
if (!function_exists('football_stats_get_current_table_view_params')) {
    function football_stats_get_current_table_view_params()
    {
        $params = [];
        if (isset($_GET['table_view']) && in_array($_GET['table_view'], ['live', 'snapshot'], true)) {
            $params['table_view'] = $_GET['table_view'];
        }
        if (($params['table_view'] ?? null) === 'snapshot' && isset($_GET['matchweek'])) {
            $params['matchweek'] = (int) $_GET['matchweek'];
        }
        if (isset($_GET['snapshot_season'])) {
            $snapshotSeason = preg_replace('/[^0-9\-]/', '', (string) $_GET['snapshot_season']);
            if ($snapshotSeason !== '') {
                $params['snapshot_season'] = $snapshotSeason;
            }
        }
        if (isset($_GET['calc_mode'])) {
            $params['calc_mode'] = $_GET['calc_mode'];
        }
        if (isset($_GET['match_id'])) {
            $params['match_id'] = (int)$_GET['match_id'];
        }
        if (isset($_GET['table_filter'])) {
            $params['table_filter'] = $_GET['table_filter'];
        }
        return $params;
    }
}

/**
 * Fetch standing data (By Matchweek Snapshot or Live)
 */
if (!function_exists('football_stats_get_table_view')) {
    function football_stats_get_table_view(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        $metadataStmt = $db->prepare('SELECT season_label, matchweek, updated_at FROM live_table_metadata WHERE competition_code = ?');
        $metadataStmt->execute([$competitionCode]);
        $metadata = $metadataStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $liveSeasonLabel = $metadata['season_label'] ?? $fallbackSeasonLabel;
        $liveMatchweek = isset($metadata['matchweek']) ? (int)$metadata['matchweek'] : null;

        $requestedSeasonLabel = isset($_GET['snapshot_season'])
            ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_season'])
            : $liveSeasonLabel;

        if ($requestedSeasonLabel === '') {
            $requestedSeasonLabel = $liveSeasonLabel;
        }

        $snapshotSeasonsStmt = $db->prepare('SELECT DISTINCT season_label FROM league_table_snapshots WHERE competition_code = ? ORDER BY season_label DESC');
        $snapshotSeasonsStmt->execute([$competitionCode]);
        $availableSeasons = $snapshotSeasonsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($availableSeasons) && !in_array($requestedSeasonLabel, $availableSeasons, true)) {
            $requestedSeasonLabel = $liveSeasonLabel;
        }

        $playoffLeagues = ['ELC', 'L1', 'L2', 'NL'];
        if (in_array($competitionCode, $playoffLeagues, true)) {
            $snapshotWeeksStmt = $db->prepare('SELECT DISTINCT matchweek FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND matchweek <= 46 ORDER BY matchweek DESC');
        } else {
            $snapshotWeeksStmt = $db->prepare('SELECT DISTINCT matchweek FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? ORDER BY matchweek DESC');
        }
        $snapshotWeeksStmt->execute([$competitionCode, $requestedSeasonLabel]);
        $availableMatchweeks = array_map('intval', $snapshotWeeksStmt->fetchAll(PDO::FETCH_COLUMN));

        $requestedView = (isset($_GET['table_view']) && $_GET['table_view'] === 'snapshot') ? 'snapshot' : 'live';
        $requestedMatchweek = isset($_GET['matchweek']) ? (int)$_GET['matchweek'] : null;

        if ($requestedView === 'snapshot' && $requestedMatchweek === null && !empty($availableMatchweeks)) {
            $requestedMatchweek = $availableMatchweeks[0];
        }

        $isSnapshotView = $requestedView === 'snapshot'
            && $requestedMatchweek !== null
            && in_array($requestedMatchweek, $availableMatchweeks, true);

        if ($isSnapshotView) {
            $standingsStmt = $db->prepare('SELECT * FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND matchweek = ? ORDER BY position ASC');
            $standingsStmt->execute([$competitionCode, $requestedSeasonLabel, $requestedMatchweek]);
            $standings = $standingsStmt->fetchAll(PDO::FETCH_ASSOC);

            $lastUpdateStmt = $db->prepare('SELECT MAX(archived_at) AS archived_ts, MAX(source_updated_at) AS source_ts FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND matchweek = ?');
            $lastUpdateStmt->execute([$competitionCode, $requestedSeasonLabel, $requestedMatchweek]);
            $lastUpdateRow = $lastUpdateStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $lastUpdateTs = $lastUpdateRow['archived_ts'] ?? $lastUpdateRow['source_ts'] ?? null;
            
            $activeSeasonLabel = $requestedSeasonLabel;
            $activeMatchweek = $requestedMatchweek;
            $updatedLabel = 'Snapshot captured';
        } else {
            $standingsStmt = $db->query("SELECT * FROM $liveTableName ORDER BY position ASC");
            $standings = $standingsStmt->fetchAll(PDO::FETCH_ASSOC);

            $lastUpdateStmt = $db->query("SELECT MAX(updated_at) AS ts FROM $liveTableName");
            $lastUpdateRow = $lastUpdateStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $lastUpdateTs = $lastUpdateRow['ts'] ?? null;

            if ($liveMatchweek === null && !empty($standings)) {
                $liveMatchweek = max(array_map('intval', array_column($standings, 'played')));
            }

            $activeSeasonLabel = $liveSeasonLabel;
            $activeMatchweek = $liveMatchweek;
            $updatedLabel = 'Last updated';
        }

        return [
            'standings' => $standings,
            'last_update' => ['ts' => $lastUpdateTs],
            'updated_label' => $updatedLabel,
            'is_snapshot_view' => $isSnapshotView,
            'requested_view' => $requestedView,
            'requested_season_label' => $requestedSeasonLabel,
            'available_seasons' => $availableSeasons,
            'available_matchweeks' => $availableMatchweeks,
            'active_season_label' => $activeSeasonLabel,
            'active_matchweek' => $activeMatchweek,
            'live_season_label' => $liveSeasonLabel,
            'live_matchweek' => $liveMatchweek,
            'snapshot_count' => count($availableMatchweeks),
        ];
    }
}

/**
 * Fetch standings calculated in order of match date
 */
if (!function_exists('football_stats_get_table_view_by_date')) {
    function football_stats_get_table_view_by_date(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        $metadataStmt = $db->prepare('SELECT season_label, matchweek, updated_at FROM live_table_metadata WHERE competition_code = ?');
        $metadataStmt->execute([$competitionCode]);
        $metadata = $metadataStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $liveSeasonLabel = $metadata['season_label'] ?? $fallbackSeasonLabel;

        $requestedSeasonLabel = isset($_GET['snapshot_season'])
            ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_season'])
            : $liveSeasonLabel;
        if ($requestedSeasonLabel === '') $requestedSeasonLabel = $liveSeasonLabel;

        $availableSeasonsStmt = $db->prepare('SELECT DISTINCT season_label FROM league_table_snapshots_by_date WHERE competition_code = ? ORDER BY season_label DESC');
        $availableSeasonsStmt->execute([$competitionCode]);
        $availableSeasons = $availableSeasonsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($availableSeasons) && !in_array($requestedSeasonLabel, $availableSeasons, true)) {
            $requestedSeasonLabel = in_array($liveSeasonLabel, $availableSeasons, true)
                ? $liveSeasonLabel
                : ($availableSeasons[0] ?? $liveSeasonLabel);
        }

        $availableDatesStmt = $db->prepare('SELECT DISTINCT snapshot_date FROM league_table_snapshots_by_date WHERE competition_code = ? AND season_label = ? ORDER BY snapshot_date DESC');
        $availableDatesStmt->execute([$competitionCode, $requestedSeasonLabel]);
        $availableDates = $availableDatesStmt->fetchAll(PDO::FETCH_COLUMN);

        $requestedDate = isset($_GET['snapshot_date'])
            ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_date'])
            : null;

        $isSnapshotView = $requestedDate !== null && in_array($requestedDate, $availableDates, true);
        $activeDate = $isSnapshotView ? $requestedDate : ($availableDates[0] ?? null);

        if ($isSnapshotView) {
            $stmt = $db->prepare('SELECT * FROM league_table_snapshots_by_date WHERE competition_code = ? AND season_label = ? AND snapshot_date = ? ORDER BY position ASC');
            $stmt->execute([$competitionCode, $requestedSeasonLabel, $requestedDate]);
            $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $tsStmt = $db->prepare('SELECT MAX(archived_at) AS ts FROM league_table_snapshots_by_date WHERE competition_code = ? AND season_label = ? AND snapshot_date = ?');
            $tsStmt->execute([$competitionCode, $requestedSeasonLabel, $requestedDate]);
            $lastUpdateTs = ($tsStmt->fetch(PDO::FETCH_ASSOC) ?: [])['ts'] ?? null;
            $updatedLabel = 'Snapshot captured';
        } else {
            if ($activeDate !== null) {
                $stmt = $db->prepare('SELECT * FROM league_table_snapshots_by_date WHERE competition_code = ? AND season_label = ? AND snapshot_date = ? ORDER BY position ASC');
                $stmt->execute([$competitionCode, $requestedSeasonLabel, $activeDate]);
                $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $standings = [];
            }
            $tsStmt = $db->query("SELECT MAX(updated_at) AS ts FROM $liveTableName");
            $lastUpdateTs = ($tsStmt->fetch(PDO::FETCH_ASSOC) ?: [])['ts'] ?? null;
            $updatedLabel = 'Last updated';
        }

        return [
            'standings'              => $standings,
            'last_update'            => ['ts' => $lastUpdateTs],
            'updated_label'          => $updatedLabel,
            'is_snapshot_view'       => $isSnapshotView,
            'requested_season_label' => $requestedSeasonLabel,
            'available_seasons'      => $availableSeasons,
            'available_dates'        => $availableDates,
            'active_season_label'    => $requestedSeasonLabel,
            'active_date'            => $activeDate,
            'live_season_label'      => $liveSeasonLabel,
            'snapshot_count'         => count($availableDates),
        ];
    }
}



/**
 * Fetch standings calculated precisely after a specific match ID
 */
if (!function_exists('football_stats_get_table_view_by_match')) {
    function football_stats_get_table_view_by_match(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        $metadataStmt = $db->prepare('SELECT season_label FROM live_table_metadata WHERE competition_code = ?');
        $metadataStmt->execute([$competitionCode]);
        $metadata = $metadataStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $liveSeasonLabel = $metadata['season_label'] ?? $fallbackSeasonLabel;

        $requestedSeasonLabel = isset($_GET['snapshot_season'])
            ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_season'])
            : $liveSeasonLabel;
        if ($requestedSeasonLabel === '') $requestedSeasonLabel = $liveSeasonLabel;

        $seasonsStmt = $db->prepare('SELECT DISTINCT season_label FROM matches WHERE competition_code = ? ORDER BY season_label DESC');
        $seasonsStmt->execute([$competitionCode]);
        $availableSeasons = $seasonsStmt->fetchAll(PDO::FETCH_COLUMN);

        $selectedMatchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : null;

        $targetMatch = null;
        if ($selectedMatchId) {
            $mStmt = $db->prepare('SELECT * FROM matches WHERE id = ? AND competition_code = ?');
            $mStmt->execute([$selectedMatchId, $competitionCode]);
            $targetMatch = $mStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $standings = [];
        if ($targetMatch) {
            $mQuery = 'SELECT * FROM matches 
                       WHERE competition_code = ? AND season_label = ? 
                         AND home_goals IS NOT NULL AND away_goals IS NOT NULL
                         AND (
                           (match_date < ?) OR 
                           (match_date = ? AND id <= ?)
                         )
                       ORDER BY match_date ASC, id ASC';
            $mMatchesStmt = $db->prepare($mQuery);
            $mMatchesStmt->execute([
                $competitionCode,
                $requestedSeasonLabel,
                $targetMatch['match_date'],
                $targetMatch['match_date'],
                $targetMatch['id']
            ]);

            $playedMatches = $mMatchesStmt->fetchAll(PDO::FETCH_ASSOC);

            $crestMap = [];
            try {
                $cStmt = $db->query("SELECT team_name, team_crest FROM $liveTableName");
                foreach ($cStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $crestMap[$row['team_name']] = $row['team_crest'];
                }
            } catch (Exception $e) {}

            $stats = [];
            foreach ($playedMatches as $m) {
                $hg = (int)$m['home_goals'];
                $ag = (int)$m['away_goals'];
                $home = $m['home_team'];
                $away = $m['away_team'];

                if (!isset($stats[$home])) $stats[$home] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
                if (!isset($stats[$away])) $stats[$away] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];

                $stats[$home]['p']++; $stats[$away]['p']++;
                $stats[$home]['gf'] += $hg; $stats[$home]['ga'] += $ag;
                $stats[$away]['gf'] += $ag; $stats[$away]['ga'] += $hg;

                if ($hg > $ag) {
                    $stats[$home]['w']++; $stats[$home]['pts'] += 3;
                    $stats[$away]['l']++;
                } elseif ($hg < $ag) {
                    $stats[$away]['w']++; $stats[$away]['pts'] += 3;
                    $stats[$home]['l']++;
                } else {
                    $stats[$home]['d']++; $stats[$home]['pts']++;
                    $stats[$away]['d']++; $stats[$away]['pts']++;
                }
            }

            uasort($stats, function ($a, $b) {
                if ($a['pts'] !== $b['pts']) return $b['pts'] - $a['pts'];
                $gdA = $a['gf'] - $a['ga'];
                $gdB = $b['gf'] - $b['ga'];
                if ($gdA !== $gdB) return $gdB - $gdA;
                return $b['gf'] - $a['gf'];
            });

            $pos = 1;
            foreach ($stats as $teamName => $s) {
                $standings[] = [
                    'position'   => $pos++,
                    'team_name'  => $teamName,
                    'team_crest' => $crestMap[$teamName] ?? '',
                    'played'     => $s['p'],
                    'won'        => $s['w'],
                    'drawn'      => $s['d'],
                    'lost'       => $s['l'],
                    'gf'         => $s['gf'],
                    'ga'         => $s['ga'],
                    'gd'         => $s['gf'] - $s['ga'],
                    'points'     => $s['pts'],
                ];
            }
        }

        return [
            'standings'              => $standings,
            'last_update'            => ['ts' => null],
            'updated_label'          => 'Match-level Snapshot',
            'is_snapshot_view'       => ($targetMatch !== null),
            'requested_season_label' => $requestedSeasonLabel,
            'available_seasons'      => $availableSeasons,
            'active_season_label'    => $requestedSeasonLabel,
            'live_season_label'      => $liveSeasonLabel,
            'selected_match_id'      => $selectedMatchId,
            'target_match'           => $targetMatch,
        ];
    }
}

/** 
 * Fetch standings calculated precisely before a specific match ID 
 */
if (!function_exists('football_stats_get_table_view_by_match_before')) {
    function football_stats_get_table_view_by_match_before(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        $metadataStmt = $db->prepare('SELECT season_label FROM live_table_metadata WHERE competition_code = ?');
        $metadataStmt->execute([$competitionCode]);
        $metadata = $metadataStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $liveSeasonLabel = $metadata['season_label'] ?? $fallbackSeasonLabel;

        $requestedSeasonLabel = isset($_GET['snapshot_season'])
            ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_season'])
            : $liveSeasonLabel;
        if ($requestedSeasonLabel === '') $requestedSeasonLabel = $liveSeasonLabel;

        $seasonsStmt = $db->prepare('SELECT DISTINCT season_label FROM matches WHERE competition_code = ? ORDER BY season_label DESC');
        $seasonsStmt->execute([$competitionCode]);
        $availableSeasons = $seasonsStmt->fetchAll(PDO::FETCH_COLUMN);

        $selectedMatchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : null;

        $targetMatch = null;
        if ($selectedMatchId) {
            $mStmt = $db->prepare('SELECT * FROM matches WHERE id = ? AND competition_code = ?');
            $mStmt->execute([$selectedMatchId, $competitionCode]);
            $targetMatch = $mStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $standings = [];
        if ($targetMatch) {
            $mQuery = 'SELECT * FROM matches 
                       WHERE competition_code = ? AND season_label = ? 
                         AND home_goals IS NOT NULL AND away_goals IS NOT NULL
                         AND (
                           (match_date < ?) OR 
                           (match_date = ? AND id < ?)
                         )
                       ORDER BY match_date ASC, id ASC';
            $mMatchesStmt = $db->prepare($mQuery);
            $mMatchesStmt->execute([
                $competitionCode,
                $requestedSeasonLabel,
                $targetMatch['match_date'],
                $targetMatch['match_date'],
                $targetMatch['id']
            ]);

            $playedMatches = $mMatchesStmt->fetchAll(PDO::FETCH_ASSOC);

            $crestMap = [];
            try {
                $cStmt = $db->query("SELECT team_name, team_crest FROM $liveTableName");
                foreach ($cStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $crestMap[$row['team_name']] = $row['team_crest'];
                }
            } catch (Exception $e) {}

            $stats = [];
            foreach ($playedMatches as $m) {
                $hg = (int)$m['home_goals'];
                $ag = (int)$m['away_goals'];
                $home = $m['home_team'];
                $away = $m['away_team'];

                if (!isset($stats[$home])) $stats[$home] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
                if (!isset($stats[$away])) $stats[$away] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];

                $stats[$home]['p']++; $stats[$away]['p']++;
                $stats[$home]['gf'] += $hg; $stats[$home]['ga'] += $ag;
                $stats[$away]['gf'] += $ag; $stats[$away]['ga'] += $hg;

                if ($hg > $ag) {
                    $stats[$home]['w']++; $stats[$home]['pts'] += 3;
                    $stats[$away]['l']++;
                } elseif ($hg < $ag) {
                    $stats[$away]['w']++; $stats[$away]['pts'] += 3;
                    $stats[$home]['l']++;
                } else {
                    $stats[$home]['d']++; $stats[$home]['pts']++;
                    $stats[$away]['d']++; $stats[$away]['pts']++;
                }
            }

            uasort($stats, function ($a, $b) {
                if ($a['pts'] !== $b['pts']) return $b['pts'] - $a['pts'];
                $gdA = $a['gf'] - $a['ga'];
                $gdB = $b['gf'] - $b['ga'];
                if ($gdA !== $gdB) return $gdB - $gdA;
                return $b['gf'] - $a['gf'];
            });

            $pos = 1;
            foreach ($stats as $teamName => $s) {
                $standings[] = [
                    'position'   => $pos++,
                    'team_name'  => $teamName,
                    'team_crest' => $crestMap[$teamName] ?? '',
                    'played'     => $s['p'],
                    'won'        => $s['w'],
                    'drawn'      => $s['d'],
                    'lost'       => $s['l'],
                    'gf'         => $s['gf'],
                    'ga'         => $s['ga'],
                    'gd'         => $s['gf'] - $s['ga'],
                    'points'     => $s['pts'],
                ];
            }
        }

        return [
            'standings'              => $standings,
            'last_update'            => ['ts' => null],
            'updated_label'          => 'Match-level Snapshot',
            'is_snapshot_view'       => ($targetMatch !== null),
            'requested_season_label' => $requestedSeasonLabel,
            'available_seasons'      => $availableSeasons,
            'active_season_label'    => $requestedSeasonLabel,
            'live_season_label'      => $liveSeasonLabel,
            'selected_match_id'      => $selectedMatchId,
            'target_match'           => $targetMatch,
        ];
    }
}

/**
 * Fetch standings for any calculation mode based on $_GET['calc_mode']
 */
if (!function_exists('football_stats_get_table_view_combined')) {
    function football_stats_get_table_view_combined(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        $calcMode = $_GET['calc_mode'] ?? 'by_matchweek';
        
        if ($calcMode === 'by_match') {
            $tableView = football_stats_get_table_view_by_match($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        } elseif ($calcMode === 'by_match_before') {
            $tableView = football_stats_get_table_view_by_match_before($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        } elseif ($calcMode === 'by_date') {
            $tableView = football_stats_get_table_view_by_date($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        } else {
            $tableView = football_stats_get_table_view($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        }

        $tableView['calc_mode'] = $calcMode;
        return $tableView;
    }
}

/**
 * Render appropriate controls
 */
if (!function_exists('football_stats_render_combined_table_controls')) {
    function football_stats_render_combined_table_controls(array $tableView, $tab = null, $league = null, $subtab = null)
    {
        football_stats_render_table_view_controls($tableView, $tab, $league, $subtab);
    }
}

/**
 * Main Table Controls including Matchweek, Date, and Specific Match Selection
 */
if (!function_exists('football_stats_render_table_view_controls')) {
    function football_stats_render_table_view_controls(array $tableView, $tab = null, $league = null, $subtab = null)
    {
        $tab = $tab ?? $_GET['tab'] ?? null;
        $league = $league ?? $_GET['league'] ?? null;
        $subtab = $subtab ?? $_GET['subtab'] ?? null;

        $leagueMap = [
            'premier-league'  => 'PL',
            'championship'    => 'ELC',
            'league-one'      => 'L1',
            'league-two'      => 'L2',
            'national-league' => 'NL'
        ];
        $competitionCode = $leagueMap[$league] ?? strtoupper((string)$league);

        $calcMode = $_GET['calc_mode'] ?? $tableView['calc_mode'] ?? 'by_matchweek';
        $activeSeason = (string)($_GET['snapshot_season'] ?? $tableView['active_season_label'] ?? $tableView['requested_season_label'] ?? '');
        $controlId = 'table-view-' . preg_replace('/[^a-z0-9\-]/i', '-', (string)$subtab);

        $summaryDate = '';
        if ($calcMode !== 'by_date' && !empty($tableView['active_matchweek']) && isset($GLOBALS['db']) && function_exists('football_stats_get_first_date_for_matchweek')) {
            $summaryDate = football_stats_get_first_date_for_matchweek($GLOBALS['db'], $competitionCode, $activeSeason, $tableView['active_matchweek']);
        }

        $summaryMW = '';
        if ($calcMode === 'by_date' && !empty($tableView['active_date']) && isset($GLOBALS['db']) && function_exists('football_stats_get_matchweek_for_date')) {
            $summaryMW = football_stats_get_matchweek_for_date($GLOBALS['db'], $competitionCode, $activeSeason, $tableView['active_date']);
        }

        $matchFilterMode = $_GET['match_filter_mode'] ?? 'matchweek';
        $selectedMatchweek = isset($_GET['matchweek']) ? (int)$_GET['matchweek'] : null;
        $selectedDate = $_GET['snapshot_date'] ?? '';
        $selectedMatchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : null;
        $isSnapshot = isset($_GET['table_view']) && $_GET['table_view'] === 'snapshot';

        $availableMatchweeks = $tableView['available_matchweeks'] ?? [];
        $availableDates = $tableView['available_dates'] ?? [];
        $availableMatches = [];

        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            if (empty($availableDates)) {
                $dStmt = $GLOBALS['db']->prepare('SELECT DISTINCT match_date FROM matches WHERE competition_code = ? AND season_label = ? AND match_date IS NOT NULL AND match_date != "" ORDER BY match_date DESC');
                $dStmt->execute([$competitionCode, $activeSeason]);
                $availableDates = $dStmt->fetchAll(PDO::FETCH_COLUMN);
            }

            if (empty($availableMatchweeks)) {
                $mwStmt = $GLOBALS['db']->prepare('SELECT DISTINCT matchweek FROM matches WHERE competition_code = ? AND season_label = ? ORDER BY matchweek ASC');
                $mwStmt->execute([$competitionCode, $activeSeason]);
                $availableMatchweeks = array_map('intval', $mwStmt->fetchAll(PDO::FETCH_COLUMN));
            }

            $mQuery = 'SELECT id, matchweek, match_date, home_team, away_team, home_goals, away_goals FROM matches WHERE competition_code = ? AND season_label = ?';
            $params = [$competitionCode, $activeSeason];

            if ($matchFilterMode === 'matchweek' && $selectedMatchweek !== null) {
                $mQuery .= ' AND matchweek = ?';
                $params[] = $selectedMatchweek;
            } elseif ($matchFilterMode === 'date' && $selectedDate !== '') {
                $mQuery .= ' AND match_date = ?';
                $params[] = $selectedDate;
            }

            $mQuery .= ' ORDER BY match_date ASC, matchweek ASC, id ASC';
            $mStmt = $GLOBALS['db']->prepare($mQuery);
            $mStmt->execute($params);
            $availableMatches = $mStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        ?>
        <style>
            .table-view-switcher { margin: 14px 0 16px; padding: 14px; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; background: rgba(255, 255, 255, 0.03); }
            .table-view-summary { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; color: #dcddde; font-size: 13px; }
            .table-view-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: rgba(88, 101, 242, 0.15); border: 1px solid rgba(88, 101, 242, 0.35); color: #c7d2fe; font-weight: 600; }
            .table-view-actions { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
            .table-view-group { display: flex; flex-direction: column; gap: 4px; }
            .table-view-select { min-width: 180px; padding: 10px 12px; border-radius: 8px; background: #2f3136; border: 1px solid rgba(255, 255, 255, 0.08); color: #dcddde; font-size: 12px; font-weight: 600; cursor: pointer; }
            .table-view-label { color: #8e9297; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        </style>

        <div class="table-view-switcher">
            <div class="table-view-summary">
                <span class="table-view-pill">
                    <?php 
                        if ($calcMode === 'by_match') echo 'By Specific Match';
                        elseif ($calcMode === 'by_match_before') echo 'By Matchweek Before Specific Match';
                        elseif ($calcMode === 'by_date') echo 'By Date';
                        else echo 'By Matchweek';
                    ?>
                </span>
                <span>Season <?php echo htmlspecialchars($activeSeason); ?></span>
                <?php if ($calcMode === 'by_matchweek'): ?>
                    <span>Matchweek <?php echo (int)($tableView['active_matchweek'] ?? 0); ?><?php if ($summaryDate): ?> <strong style="color:#00ff88; font-size:12px;">[<?php echo htmlspecialchars($summaryDate); ?>]</strong><?php endif; ?></span>
                <?php endif; ?>
                <?php if ($calcMode === 'by_date'): ?>
                    <span><?php echo htmlspecialchars((string)($tableView['active_date'] ?? '')); ?><?php if ($summaryMW): ?> <strong style="color:#00ff88; font-size:12px;">[<?php echo htmlspecialchars($summaryMW); ?>]</strong><?php endif; ?></span>
                <?php endif; ?>
                <?php if ($calcMode === 'by_match' && !empty($tableView['target_match'])): ?>
                    <?php $tm = $tableView['target_match']; ?>
                    <span style="color:#00ff88; font-weight:bold;">
                        After: <?php echo htmlspecialchars("{$tm['home_team']} {$tm['home_goals']}-{$tm['away_goals']} {$tm['away_team']}"); ?> (<?php echo htmlspecialchars($tm['match_date']); ?>)
                    </span>
                <?php endif; ?>
                <?php if ($calcMode === 'by_match_before' && !empty($tableView['target_match'])): ?>
                    <?php $tm = $tableView['target_match']; ?>
                    <span style="color:#00ff88; font-weight:bold;">
                        Before: <?php echo htmlspecialchars("{$tm['home_team']} {$tm['home_goals']}-{$tm['away_goals']} {$tm['away_team']}"); ?> (<?php echo htmlspecialchars($tm['match_date']); ?>)
                    </span>
                <?php endif; ?>
            </div>

            <div class="table-view-actions">
                <!-- Dropdown 1: Calculation Mode -->
                <div class="table-view-group">
                    <label class="table-view-label">Calculation Mode</label>
                    <select class="table-view-select" onchange="window.location.href=this.value;">
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_matchweek'])); ?>" <?php echo ($calcMode === 'by_matchweek') ? 'selected="selected"' : ''; ?>>
                            By Matchweek (original)
                        </option>
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_date'])); ?>" <?php echo ($calcMode === 'by_date') ? 'selected="selected"' : ''; ?>>
                            By Date (postponed-aware)
                        </option>
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_match_before'])); ?>" <?php echo ($calcMode === 'by_match_before') ? 'selected="selected"' : ''; ?>>
                            By Specific Match (Before)
                        </option>
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_match'])); ?>" <?php echo ($calcMode === 'by_match') ? 'selected="selected"' : ''; ?>>
                            By Specific Match (After)
                        </option>
                        
                    </select>
                </div>

                <!-- Dropdown 2: Select Season -->
                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-season">Select Season</label>
                    <select id="<?php echo $controlId; ?>-season" class="table-view-select" onchange="window.location.href=this.value;">
                        <?php foreach ($tableView['available_seasons'] as $season): ?>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_season' => $season])); ?>" <?php echo ((string)$season === $activeSeason) ? 'selected="selected"' : ''; ?>>
                                Season <?php echo htmlspecialchars($season); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($calcMode === 'by_match'): ?>
                    <!-- Sub-Toggle Mode -->
                    <div class="table-view-group">
                        <label class="table-view-label">Match Filter Mode</label>
                        <select class="table-view-select" onchange="window.location.href=this.value;">
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_filter_mode' => 'matchweek', 'match_id' => $selectedMatchId])); ?>" <?php echo ($matchFilterMode === 'matchweek') ? 'selected="selected"' : ''; ?>>
                                Filter Matches by MW
                            </option>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_filter_mode' => 'date', 'match_id' => $selectedMatchId])); ?>" <?php echo ($matchFilterMode === 'date') ? 'selected="selected"' : ''; ?>>
                                Filter Matches by Date
                            </option>
                        </select>
                    </div>

                    <?php if ($matchFilterMode === 'date'): ?>
                        <!-- Filter Sub-Select: Date -->
                        <div class="table-view-group">
                            <label class="table-view-label">Filter Date</label>
                            <select class="table-view-select" onchange="window.location.href=this.value;">
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_date' => null, 'match_id' => $selectedMatchId])); ?>" <?php echo ($selectedDate === '') ? 'selected="selected"' : ''; ?>>
                                    All Dates
                                </option>
                                <?php foreach ($availableDates as $d): ?>
                                    <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_date' => $d, 'match_id' => $selectedMatchId])); ?>" <?php echo ($selectedDate === (string)$d) ? 'selected="selected"' : ''; ?>>
                                        <?php echo htmlspecialchars($d); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <!-- Filter Sub-Select: Matchweek -->
                        <div class="table-view-group">
                            <label class="table-view-label">Filter Matchweek</label>
                            <select class="table-view-select" onchange="window.location.href=this.value;">
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['matchweek' => null, 'match_id' => $selectedMatchId])); ?>" <?php echo ($selectedMatchweek === null) ? 'selected="selected"' : ''; ?>>
                                    All Matchweeks
                                </option>
                                <?php foreach ($availableMatchweeks as $mw): ?>
                                    <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['matchweek' => $mw, 'match_id' => $selectedMatchId])); ?>" <?php echo ($selectedMatchweek !== null && $selectedMatchweek === (int)$mw) ? 'selected="selected"' : ''; ?>>
                                        Matchweek <?php echo (int)$mw; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Target Specific Match Dropdown -->
                    <div class="table-view-group">
                        <label class="table-view-label">Select Game</label>
                        <select class="table-view-select" onchange="window.location.href=this.value;">
                            <?php foreach ($availableMatches as $m):
                                $mId = (int)$m['id'];
                                $score = ($m['home_goals'] !== null && $m['away_goals'] !== null) ? " ({$m['home_goals']}-{$m['away_goals']})" : ' (vs)';
                                $label = "MW{$m['matchweek']} [{$m['match_date']}]: {$m['home_team']}{$score}{$m['away_team']}";
                            ?>
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_id' => $mId])); ?>" <?php echo ($selectedMatchId === $mId) ? 'selected="selected"' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                <?php elseif ($calcMode === 'by_match_before'): ?>
                    <!-- Sub-Toggle Mode -->
                    <div class="table-view-group">
                        <label class="table-view-label">Match Filter Mode</label>
                        <select class="table-view-select" onchange="window.location.href=this.value;">
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_filter_mode' => 'matchweek', 'match_id' => $selectedMatchId])); ?>" <?php echo ($matchFilterMode === 'matchweek') ? 'selected="selected"' : ''; ?>>
                                Filter Matches by MW
                            </option>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_filter_mode' => 'date', 'match_id' => $selectedMatchId])); ?>" <?php echo ($matchFilterMode === 'date') ? 'selected="selected"' : ''; ?>>
                                Filter Matches by Date
                            </option>
                        </select>
                    </div>

                    <?php if ($matchFilterMode === 'date'): ?>
                        <!-- Filter Sub-Select: Date -->
                        <div class="table-view-group">
                            <label class="table-view-label">Filter Date</label>
                            <select class="table-view-select" onchange="window.location.href=this.value;">
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_date' => null, 'match_id' => $selectedMatchId])); ?>" <?php echo ($selectedDate === '') ? 'selected="selected"' : ''; ?>>
                                    All Dates
                                </option>
                                <?php foreach ($availableDates as $d): ?>
                                    <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_date' => $d, 'match_id' => $selectedMatchId])); ?>" <?php echo ($selectedDate === (string)$d) ? 'selected="selected"' : ''; ?>>
                                        <?php echo htmlspecialchars($d); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <!-- Filter Sub-Select: Matchweek -->
                        <div class="table-view-group">
                            <label class="table-view-label">Filter Matchweek</label>
                            <select class="table-view-select" onchange="window.location.href=this.value;">
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['matchweek' => null, 'match_id' => $selectedMatchId])); ?>" <?php echo ($selectedMatchweek === null) ? 'selected="selected"' : ''; ?>>
                                    All Matchweeks
                                </option>
                                <?php foreach ($availableMatchweeks as $mw): ?>
                                    <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['matchweek' => $mw, 'match_id' => $selectedMatchId])); ?>" <?php echo ($selectedMatchweek !== null && $selectedMatchweek === (int)$mw) ? 'selected="selected"' : ''; ?>>
                                        Matchweek <?php echo (int)$mw; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Target Specific Match Dropdown -->
                    <div class="table-view-group">
                        <label class="table-view-label">Select Game</label>
                        <select class="table-view-select" onchange="window.location.href=this.value;">
                            <?php foreach ($availableMatches as $m):
                                $mId = (int)$m['id'];
                                $score = ($m['home_goals'] !== null && $m['away_goals'] !== null) ? " ({$m['home_goals']}-{$m['away_goals']})" : ' (vs)';
                                $label = "MW{$m['matchweek']} [{$m['match_date']}]: {$m['home_team']}{$score}{$m['away_team']}";
                            ?>
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_id' => $mId])); ?>" <?php echo ($selectedMatchId === $mId) ? 'selected="selected"' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                
                <?php elseif ($calcMode === 'by_date'): ?>
                <!-- Dropdown 2 (By Date): Date Selection -->
                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-date">Select Date</label>
                    <select id="<?php echo $controlId; ?>-date" class="table-view-select" onchange="window.location.href=this.value;">
                        <?php foreach ($tableView['available_dates'] as $date):
                            $dateMW = '';
                            if (isset($GLOBALS['db']) && function_exists('football_stats_get_matchweek_for_date')) {
                                $dateMW = football_stats_get_matchweek_for_date($GLOBALS['db'], $competitionCode, $activeSeason, $date);
                            }
                            $activeDate = (string)($tableView['active_date'] ?? '');
                        ?>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_date', 'snapshot_season' => $activeSeason, 'snapshot_date' => $date])); ?>" <?php echo ($activeDate === (string)$date) ? 'selected="selected"' : ''; ?>>
                                <?php echo htmlspecialchars($date); ?><?php if ($dateMW) echo ' [' . htmlspecialchars($dateMW) . ']'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <!-- Dropdown 2 (By Matchweek): Matchweek Selection -->
                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-mw">Select Matchweek</label>
                    <select id="<?php echo $controlId; ?>-mw" class="table-view-select" onchange="window.location.href=this.value;">
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => 'live', 'matchweek' => null])); ?>" <?php echo !$isSnapshot ? 'selected="selected"' : ''; ?>>
                            Latest Live Table
                        </option>
                        <?php
                        $activeMW = (int)($tableView['active_matchweek'] ?? 0);
                        foreach ($tableView['available_matchweeks'] as $mw):
                            $mwUrl = football_stats_build_table_view_url($tab, $league, $subtab, [
                                'table_view' => 'snapshot',
                                'matchweek' => $mw,
                                'snapshot_season' => $activeSeason,
                            ]);

                            $mwDate = '';
                            if (isset($GLOBALS['db']) && function_exists('football_stats_get_first_date_for_matchweek')) {
                                $mwDate = football_stats_get_first_date_for_matchweek($GLOBALS['db'], $competitionCode, $activeSeason, $mw);
                            }
                        ?>
                            <option value="<?php echo htmlspecialchars($mwUrl); ?>" <?php echo ($isSnapshot && $activeMW === (int)$mw) ? 'selected="selected"' : ''; ?>>
                                <?php if ((int)$mw === 0): ?>Pre-season<?php else: ?>Matchweek <?php echo (int)$mw; ?><?php if ($mwDate) echo ' [' . htmlspecialchars($mwDate) . ']'; ?><?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Compute filtered standings from the matches table.
 * Supports filters: first_half, second_half, home, away.
 */
if (!function_exists('football_stats_compute_filtered_standings')) {
    function football_stats_compute_filtered_standings(PDO $db, $competitionCode, $seasonLabel, $filter, $halfwayMatchweek, $liveTableName, $maxRegularMW = null)
    {
        // Build crest map from live table, fallback to snapshots
        $crestMap = [];
        try {
            $stmt = $db->query("SELECT team_name, team_crest FROM $liveTableName");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $crestMap[$row['team_name']] = $row['team_crest'];
            }
        } catch (Exception $e) {}
        try {
            $stmt = $db->prepare("SELECT team_name, MAX(team_crest) AS team_crest FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND team_crest != '' GROUP BY team_name");
            $stmt->execute([$competitionCode, $seasonLabel]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (empty($crestMap[$row['team_name']])) {
                    $crestMap[$row['team_name']] = $row['team_crest'];
                }
            }
        } catch (Exception $e) {}

        // Fetch relevant matches (exclude playoff matches: mw=0 or mw>maxRegularMW)
        if ($filter === 'first_half') {
            $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek <= ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL");
            $stmt->execute([$competitionCode, $seasonLabel, $halfwayMatchweek]);
        } elseif ($filter === 'second_half') {
            if ($maxRegularMW !== null) {
                $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek > ? AND matchweek <= ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL");
                $stmt->execute([$competitionCode, $seasonLabel, $halfwayMatchweek, $maxRegularMW]);
            } else {
                $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek > ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL");
                $stmt->execute([$competitionCode, $seasonLabel, $halfwayMatchweek]);
            }
        } else {
            if ($maxRegularMW !== null) {
                $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek <= ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL");
                $stmt->execute([$competitionCode, $seasonLabel, $maxRegularMW]);
            } else {
                $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND home_goals IS NOT NULL AND away_goals IS NOT NULL");
                $stmt->execute([$competitionCode, $seasonLabel]);
            }
        }
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($matches)) {
            return [];
        }

        $stats = [];
        foreach ($matches as $m) {
            $hg   = (int)$m['home_goals'];
            $ag   = (int)$m['away_goals'];
            $home = $m['home_team'];
            $away = $m['away_team'];

            if (!isset($stats[$home])) $stats[$home] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
            if (!isset($stats[$away])) $stats[$away] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];

            if ($filter === 'home') {
                $stats[$home]['p']++;
                $stats[$home]['gf'] += $hg;
                $stats[$home]['ga'] += $ag;
                if ($hg > $ag)      { $stats[$home]['w']++; $stats[$home]['pts'] += 3; }
                elseif ($hg < $ag)  { $stats[$home]['l']++; }
                else                { $stats[$home]['d']++; $stats[$home]['pts']++; }
            } elseif ($filter === 'away') {
                $stats[$away]['p']++;
                $stats[$away]['gf'] += $ag;
                $stats[$away]['ga'] += $hg;
                if ($ag > $hg)      { $stats[$away]['w']++; $stats[$away]['pts'] += 3; }
                elseif ($ag < $hg)  { $stats[$away]['l']++; }
                else                { $stats[$away]['d']++; $stats[$away]['pts']++; }
            } else {
                // first_half or second_half — count both sides
                $stats[$home]['p']++; $stats[$away]['p']++;
                $stats[$home]['gf'] += $hg; $stats[$away]['ga'] += $ag;
                $stats[$away]['gf'] += $ag; $stats[$away]['ga'] += $hg;
                if ($hg > $ag)      { $stats[$home]['w']++; $stats[$home]['pts'] += 3; $stats[$away]['l']++; }
                elseif ($hg < $ag)  { $stats[$away]['w']++; $stats[$away]['pts'] += 3; $stats[$home]['l']++; }
                else                { $stats[$home]['d']++; $stats[$home]['pts']++; $stats[$away]['d']++; $stats[$away]['pts']++; }
            }
        }

        uasort($stats, function ($a, $b) {
            if ($a['pts'] !== $b['pts']) return $b['pts'] - $a['pts'];
            $gdA = $a['gf'] - $a['ga'];
            $gdB = $b['gf'] - $b['ga'];
            if ($gdA !== $gdB) return $gdB - $gdA;
            return $b['gf'] - $a['gf'];
        });

        $position = 1;
        $result   = [];
        foreach ($stats as $teamName => $s) {
            $result[] = [
                'team_name'  => $teamName,
                'team_crest' => $crestMap[$teamName] ?? '',
                'position'   => $position++,
                'played'     => $s['p'],
                'won'        => $s['w'],
                'drawn'      => $s['d'],
                'lost'       => $s['l'],
                'gf'         => $s['gf'],
                'ga'         => $s['ga'],
                'gd'         => $s['gf'] - $s['ga'],
                'points'     => $s['pts'],
            ];
        }
        return $result;
    }
}

/**
 * Render filter buttons for first half / second half / home / away views.
 */
if (!function_exists('football_stats_render_table_filter_buttons')) {
    function football_stats_render_table_filter_buttons($activeFilter, $tab, $league, $subtab)
    {
        $filters = [
            'all'         => 'All',
            'first_half'  => '1st Half',
            'second_half' => '2nd Half',
            'home'        => 'Home',
            'away'        => 'Away',
        ];
        $filterLabels = [
            'all'         => 'Full season standings',
            'first_half'  => 'Standings based on matchweeks in the first half of the season',
            'second_half' => 'Standings based on matchweeks in the second half of the season',
            'home'        => 'Standings based on home matches only',
            'away'        => 'Standings based on away matches only',
        ];

        $baseParams = $_GET;
        unset($baseParams['table_filter']);
        $baseUrl = '?' . http_build_query($baseParams);
        ?>
        <div style="display:flex;gap:8px;margin:10px 0 14px;flex-wrap:wrap;">
            <?php foreach ($filters as $key => $label):
                $isActive = ($activeFilter === $key) || ($key === 'all' && $activeFilter === '');
                $params   = $baseParams;
                if ($key !== 'all') {
                    $params['table_filter'] = $key;
                }
                $url = '?' . http_build_query($params);
                $style = $isActive
                    ? 'background:#5865F2;color:#fff;border:1px solid #5865F2;padding:7px 16px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:700;cursor:pointer;'
                    : 'background:#2f3136;color:#b9bbbe;border:1px solid rgba(255,255,255,0.1);padding:7px 16px;border-radius:6px;text-decoration:none;font-size:13px;cursor:pointer;';
            ?>
                <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" style="<?= $style ?>" title="<?= htmlspecialchars($filterLabels[$key], ENT_QUOTES, 'UTF-8') ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <?php if ($activeFilter !== 'all' && $activeFilter !== ''): ?>
                <span style="align-self:center;font-size:12px;color:#faa61a;margin-left:4px;">
                    Filtered view &mdash; standings computed from match data
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
}

/**
 * Render side-by-side Home Record and Away Record tables.
 */
if (!function_exists('football_stats_render_home_away_split')) {
    function football_stats_render_home_away_split(array $homeStandings, array $awayStandings, array $team_info)
    {
        if (empty($homeStandings) && empty($awayStandings)) {
            echo '<p style="color:#888;font-size:13px;margin-top:20px;">No match data available yet for home/away split.</p>';
            return;
        }

        $getInfo = function ($name) use ($team_info) {
            if (isset($team_info[$name])) return $team_info[$name];
            foreach ($team_info as $key => $info) {
                if (stripos($name, $key) !== false) return $info;
            }
            return ['name' => $name, 'common_name' => $name, 'short' => strtoupper(substr($name, 0, 3)), 'color' => '#888888'];
        };

        $renderHalf = function (array $standings, string $title, string $accentColor) use ($getInfo) {
            ?>
            <div style="min-width:0;">
                <h4 style="color:<?= $accentColor ?>;margin:0 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid <?= $accentColor ?>;padding-bottom:6px;"><?= htmlspecialchars($title) ?></h4>
                <?php if (empty($standings)): ?>
                    <p style="color:#888;font-size:12px;">No data</p>
                <?php else: ?>
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <thead>
                        <tr>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;">Pos</th>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:left;border-bottom:1px solid #3a3c40;font-size:11px;">Team</th>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;" title="Played">P</th>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;" title="Won">W</th>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;" title="Drawn">D</th>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;" title="Lost">L</th>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;" title="Goals For">GF</th>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;" title="Goals Against">GA</th>
                            <th style="background:#1e2023;color:#72767d;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;" title="Goal Difference">GD</th>
                            <th style="background:#1e2023;color:<?= $accentColor ?>;padding:6px 5px;text-align:center;border-bottom:1px solid #3a3c40;font-size:11px;" title="Points">Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standings as $team):
                            $info    = $getInfo($team['team_name']);
                            $gdColor = $team['gd'] > 0 ? '#43b581' : ($team['gd'] < 0 ? '#f04747' : '#888');
                        ?>
                        <tr>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;color:#72767d;text-align:center;"><?= $team['position'] ?></td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;">
                                <div style="display:flex;align-items:center;gap:5px;">
                                    <img src="<?= htmlspecialchars($team['team_crest']) ?>" style="width:14px;height:14px;object-fit:contain;flex-shrink:0;" onerror="this.style.display='none'">
                                    <span style="color:#dcddde;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($info['common_name'] ?? $info['name']) ?></span>
                                </div>
                            </td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;text-align:center;color:#b9bbbe;"><?= $team['played'] ?></td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;text-align:center;color:#b9bbbe;"><?= $team['won'] ?></td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;text-align:center;color:#b9bbbe;"><?= $team['drawn'] ?></td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;text-align:center;color:#b9bbbe;"><?= $team['lost'] ?></td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;text-align:center;color:#b9bbbe;"><?= $team['gf'] ?></td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;text-align:center;color:#b9bbbe;"><?= $team['ga'] ?></td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;text-align:center;font-weight:bold;color:<?= $gdColor ?>;"><?= $team['gd'] > 0 ? '+' . $team['gd'] : $team['gd'] ?></td>
                            <td style="padding:5px;border-bottom:1px solid #2a2c2e;text-align:center;font-weight:bold;color:<?= $accentColor ?>;"><?= $team['points'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php
        };
        ?>
        <div style="margin-top:30px;border-top:1px solid #333;padding-top:20px;">
            <h3 style="color:#dcddde;margin:0 0 16px;font-size:15px;font-weight:700;">Home &amp; Away Records</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                <?php $renderHalf($homeStandings, 'Home Record', '#43b581'); ?>
                <?php $renderHalf($awayStandings, 'Away Record', '#5865F2'); ?>
            </div>
        </div>
        <?php
    }
}