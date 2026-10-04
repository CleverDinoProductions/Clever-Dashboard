<?php

if (!function_exists('football_stats_format_kickoff')) {
    /**
     * Format TheSportsDB's UTC strTimestamp, falling back to the date supplied by
     * older records that pre-date kickoff-time storage.
     */
    function football_stats_format_kickoff(?string $timestamp, ?string $date): string
    {
        $timestamp = trim((string) $timestamp);

        if ($timestamp !== '') {
            try {
                return (new DateTimeImmutable(
                    $timestamp,
                    new DateTimeZone('UTC')
                ))
                    ->setTimezone(new DateTimeZone('Europe/London'))
                    ->format('D j M Y, H:i T');
            } catch (Exception $exception) {
                // Fall through to the reliable date-only value.
            }
        }

        $date = trim((string) $date);

        if ($date === '') {
            return 'TBC';
        }

        $parsedDate = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date,
            new DateTimeZone('Europe/London')
        );

        return $parsedDate
            ? $parsedDate->format('D j M Y') . ', time TBC'
            : $date;
    }
}

require_once __DIR__ . '/table-view-date-helper.php';

if (!function_exists('football_stats_get_competition_rules')) {
    function football_stats_get_competition_rules($competitionCode, $seasonLabel = null)
    {
        static $rules = null;
        if ($rules === null) {
            $rules = require __DIR__ . '/../config/competition-rules.php';
        }
        $competition = $rules[(string)$competitionCode] ?? [];
        $resolved = array_replace($rules['default'], $competition['default'] ?? []);
        if ($seasonLabel !== null && isset($competition[(string)$seasonLabel])) {
            $resolved = array_replace($resolved, $competition[(string)$seasonLabel]);
        }
        return $resolved;
    }
}

if (!function_exists('football_stats_get_position_zone')) {
    function football_stats_get_position_zone($competitionCode, $seasonLabel, $position)
    {
        foreach (football_stats_get_competition_rules($competitionCode, $seasonLabel)['zones'] as $zone) {
            if ((int)$position >= (int)$zone['from'] && (int)$position <= (int)$zone['to']) return $zone;
        }
        return null;
    }
}

/** Return the competition's normal placing zone without applying a season override. */
if (!function_exists('football_stats_get_default_position_zone')) {
    function football_stats_get_default_position_zone($competitionCode, $position)
    {
        return football_stats_get_position_zone($competitionCode, null, $position);
    }
}

/** Return the final regular-season matchweek for a competition. */
if (!function_exists('football_stats_get_final_matchweek')) {
    function football_stats_get_final_matchweek($competitionCode, $seasonLabel = null)
    {
        return (int)football_stats_get_competition_rules($competitionCode, $seasonLabel)['regular_matchweeks'];
    }
}

/** Remove playoff and other post-season matchweeks from selector controls. */
if (!function_exists('football_stats_limit_matchweeks_to_regular_season')) {
    function football_stats_limit_matchweeks_to_regular_season(array $matchweeks, $competitionCode)
    {
        $finalMatchweek = football_stats_get_final_matchweek($competitionCode);

        return array_values(array_filter($matchweeks, static function ($matchweek) use ($finalMatchweek) {
            return (int)$matchweek >= 1 && (int)$matchweek <= $finalMatchweek;
        }));
    }
}

/**
 * Return the points deductions which apply to a competition season.
 *
 * Administrators maintain the `points_deductions` table through the control
 * panel. Empty seasons intentionally return no deductions.
 */
if (!function_exists('football_stats_get_points_deductions')) {
    function football_stats_get_points_deductions(PDO $db, $competitionCode, $seasonLabel)
    {
        $deductions = [];

        try {
            $tableExists = $db->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'points_deductions'")->fetchColumn();
            if ($tableExists) {
                $stmt = $db->prepare(
                    'SELECT team_name, points, reason FROM points_deductions '
                    . 'WHERE competition_code = ? AND season_label = ? ORDER BY team_name, rowid'
                );
                $stmt->execute([$competitionCode, $seasonLabel]);
                $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $exception) {
            // A read-only or older database can still use the bundled entries.
        }

        $normalised = [];
        foreach ($deductions as $deduction) {
            $points = abs((int)($deduction['points'] ?? 0));
            $teamName = trim((string)($deduction['team_name'] ?? ''));
            if ($points === 0 || $teamName === '') {
                continue;
            }
            $normalised[] = [
                'team_name' => $teamName,
                'points' => $points,
                'reason' => trim((string)($deduction['reason'] ?? '')),
            ];
        }

        return $normalised;
    }
}

/** Apply season deductions, then recalculate positions for a computed table. */
if (!function_exists('football_stats_apply_points_deductions')) {
    function football_stats_apply_points_deductions(array $standings, array $deductions)
    {
        $pointsByTeam = [];
        foreach ($deductions as $deduction) {
            $pointsByTeam[$deduction['team_name']] = ($pointsByTeam[$deduction['team_name']] ?? 0) + (int)$deduction['points'];
        }

        foreach ($standings as &$team) {
            $deducted = $pointsByTeam[$team['team_name']] ?? 0;
            if ($deducted > 0) {
                $team['points'] = (int)$team['points'] - $deducted;
                $team['points_deducted'] = $deducted;
            }
        }
        unset($team);

        usort($standings, static function ($a, $b) {
            return ((int)$b['points'] <=> (int)$a['points'])
                ?: ((int)$b['gd'] <=> (int)$a['gd'])
                ?: ((int)$b['gf'] <=> (int)$a['gf'])
                ?: strcasecmp((string)$a['team_name'], (string)$b['team_name']);
        });
        foreach ($standings as $index => &$team) {
            $team['position'] = $index + 1;
        }
        unset($team);

        return $standings;
    }
}

/** Render the explanation directly below a deduction-adjusted league table. */
if (!function_exists('football_stats_render_points_deductions')) {
    function football_stats_render_points_deductions(array $deductions)
    {
        if (empty($deductions)) {
            return;
        }
        ?>
        <aside class="points-deductions" style="margin-top:12px;padding:12px 15px;background:rgba(240,71,71,.1);border-left:4px solid #f04747;border-radius:6px;color:#dcddde;" aria-label="Points deductions">
            <strong style="color:#f04747;">Points deductions applied</strong>
            <ul style="margin:7px 0 0;padding-left:20px;">
                <?php foreach ($deductions as $deduction): ?>
                    <li>
                        <?= htmlspecialchars($deduction['team_name'], ENT_QUOTES, 'UTF-8') ?>:
                        &minus;<?= (int)$deduction['points'] ?> point<?= (int)$deduction['points'] === 1 ? '' : 's' ?>
                        <?php if ($deduction['reason'] !== ''): ?>
                            &mdash; <?= htmlspecialchars($deduction['reason'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php
    }
}

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
            'table_view',
            'excluded_matches',
            'excluded_results',
            'outcome_overrides',
            'custom_points_deductions',
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
 * Render the season and matchweek selectors used by league match lists.
 */
if (!function_exists('football_stats_render_matches_controls')) {
    function football_stats_render_matches_controls(array $availableSeasons, array $availableMatchweeks, $selectedSeason, $selectedMatchweek, $tab, $league, $subtab)
    {
        $leagueMap = [
            'premier-league' => 'PL', 'championship' => 'ELC',
            'league-one' => 'L1', 'league-two' => 'L2', 'national-league' => 'NL',
            'division-one' => 'D1',
        ];
        $competitionCode = $leagueMap[$league] ?? strtoupper((string)$league);
        $availableMatchweeks = football_stats_limit_matchweeks_to_regular_season($availableMatchweeks, $competitionCode);

        $controlId = 'matches-view-' . preg_replace('/[^a-z0-9\-]/i', '-', (string)$subtab);
        ?>
        <div class="table-view-switcher">
            <div class="table-view-summary">
                <span class="table-view-pill">Matches</span>
                <span>Season <?php echo htmlspecialchars((string)$selectedSeason, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ($selectedMatchweek !== ''): ?>
                    <span>Matchweek <?php echo (int)$selectedMatchweek; ?></span>
                <?php else: ?>
                    <span>All Matchweeks</span>
                <?php endif; ?>
            </div>

            <div class="table-view-actions">
                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-season">Select Season</label>
                    <select id="<?php echo $controlId; ?>-season" class="table-view-select" onchange="window.location.href=this.value;">
                        <?php foreach ($availableSeasons as $season):
                            $seasonUrl = football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_season' => $season]);
                        ?>
                            <option value="<?php echo htmlspecialchars($seasonUrl, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selectedSeason === (string)$season) ? 'selected' : ''; ?>>
                                Season <?php echo htmlspecialchars((string)$season, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-mw">Select Matchweek</label>
                    <select id="<?php echo $controlId; ?>-mw" class="table-view-select" onchange="window.location.href=this.value;">
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_season' => $selectedSeason, 'matchweek' => null]), ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selectedMatchweek === '') ? 'selected' : ''; ?>>
                            All Matchweeks
                        </option>
                        <?php foreach ($availableMatchweeks as $matchweek):
                            $matchweekUrl = football_stats_build_table_view_url($tab, $league, $subtab, [
                                'snapshot_season' => $selectedSeason,
                                'matchweek' => $matchweek,
                            ]);
                        ?>
                            <option value="<?php echo htmlspecialchars($matchweekUrl, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selectedMatchweek !== '' && (int)$selectedMatchweek === (int)$matchweek) ? 'selected' : ''; ?>>
                                Matchweek <?php echo (int)$matchweek; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php
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

        $finalMatchweek = football_stats_get_final_matchweek($competitionCode);
        $snapshotWeeksStmt = $db->prepare('SELECT DISTINCT matchweek FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek <= ? ORDER BY matchweek DESC');
        $snapshotWeeksStmt->execute([$competitionCode, $requestedSeasonLabel, $finalMatchweek]);
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

        $finalMatchweek = football_stats_get_final_matchweek($competitionCode);
        $availableDatesStmt = $db->prepare(
            'SELECT DISTINCT snapshot_date FROM league_table_snapshots_by_date '
            . 'WHERE competition_code = ? AND season_label = ? '
            . 'AND snapshot_date <= ('
            . 'SELECT MAX(match_date) FROM matches WHERE competition_code = ? AND season_label = ? '
            . 'AND matchweek >= 1 AND matchweek <= ?'
            . ') ORDER BY snapshot_date DESC'
        );
        $availableDatesStmt->execute([
            $competitionCode,
            $requestedSeasonLabel,
            $competitionCode,
            $requestedSeasonLabel,
            $finalMatchweek,
        ]);
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
if (!function_exists('football_stats_get_match_roster')) {
    /**
     * Return every team participating in a competition season.
     *
     * Match-level tables must include clubs that have not played yet at the
     * selected point in time. Otherwise an early fixture can produce a table
     * containing only the clubs that happened to play first.
     */
    function football_stats_get_match_roster(PDO $db, $competitionCode, $seasonLabel)
    {
        $rosterStmt = $db->prepare(
            'SELECT team_name FROM ('
            . 'SELECT home_team AS team_name FROM matches WHERE competition_code = ? AND season_label = ? '
            . 'UNION '
            . 'SELECT away_team AS team_name FROM matches WHERE competition_code = ? AND season_label = ?'
            . ') ORDER BY team_name ASC'
        );
        $rosterStmt->execute([$competitionCode, $seasonLabel, $competitionCode, $seasonLabel]);

        return $rosterStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if (!function_exists('football_stats_get_team_crest_map')) {
    /**
     * Return crests for the clubs in one competition season.
     *
     * The live table only represents the current season, so it cannot be the
     * primary source when a historic season is selected. Prefer that season's
     * archived tables and use the live table only to fill any remaining gaps.
     */
    function football_stats_get_team_crest_map(PDO $db, $competitionCode, $seasonLabel, $liveTableName)
    {
        $crestMap = [];
        $archiveQueries = [
            'SELECT team_name, team_crest FROM league_table_snapshots '
                . "WHERE competition_code = ? AND season_label = ? AND team_crest IS NOT NULL AND team_crest != '' "
                . 'ORDER BY matchweek DESC',
            'SELECT team_name, team_crest FROM league_table_snapshots_by_date '
                . "WHERE competition_code = ? AND season_label = ? AND team_crest IS NOT NULL AND team_crest != '' "
                . 'ORDER BY snapshot_date DESC',
        ];

        foreach ($archiveQueries as $query) {
            try {
                $stmt = $db->prepare($query);
                $stmt->execute([$competitionCode, $seasonLabel]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $team) {
                    if (!isset($crestMap[$team['team_name']])) {
                        $crestMap[$team['team_name']] = $team['team_crest'];
                    }
                }
            } catch (Exception $exception) {
                // Older schemas may not have a crest column on every archive.
            }
        }

        try {
            foreach ($db->query("SELECT team_name, team_crest FROM $liveTableName")->fetchAll(PDO::FETCH_ASSOC) as $team) {
                if (!isset($crestMap[$team['team_name']]) && trim((string)$team['team_crest']) !== '') {
                    $crestMap[$team['team_name']] = $team['team_crest'];
                }
            }
        } catch (Exception $exception) {
            // Computed tables still render correctly when no crest source exists.
        }

        return $crestMap;
    }
}

if (!function_exists('football_stats_add_team_crests')) {
    /** Fill missing badges without replacing a crest stored on the standing. */
    function football_stats_add_team_crests(array $standings, array $crestMap)
    {
        foreach ($standings as &$team) {
            if (empty($team['team_crest']) && isset($crestMap[$team['team_name']])) {
                $team['team_crest'] = $crestMap[$team['team_name']];
            }
        }
        unset($team);

        return $standings;
    }
}

if (!function_exists('football_stats_empty_team_stats')) {
    function football_stats_empty_team_stats()
    {
        return ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
    }
}

if (!function_exists('football_stats_resolve_selected_match')) {
    /**
     * Resolve the selected fixture, keeping it inside the active match filter.
     *
     * Filter controls intentionally preserve the rest of the table state. When
     * that includes a match from the old filter, however, the browser displays
     * the first option in the new list while the table is still calculated for
     * the stale match ID. Pick the first fixture in the requested matchweek or
     * date in that case so the selector and calculated table stay in sync.
     */
    function football_stats_resolve_selected_match(PDO $db, $competitionCode, $seasonLabel, $selectedMatchId)
    {
        $conditions = [
            'competition_code = ?',
            'season_label = ?',
            'matchweek >= 1',
            'matchweek <= ?',
        ];
        $params = [
            $competitionCode,
            $seasonLabel,
            football_stats_get_final_matchweek($competitionCode),
        ];

        $hasFilter = false;
        $filterMode = $_GET['match_filter_mode'] ?? 'matchweek';
        if ($filterMode === 'date' && !empty($_GET['snapshot_date'])) {
            $conditions[] = 'match_date = ?';
            $params[] = (string)$_GET['snapshot_date'];
            $hasFilter = true;
        } elseif ($filterMode === 'matchweek' && isset($_GET['matchweek']) && $_GET['matchweek'] !== '') {
            $conditions[] = 'matchweek = ?';
            $params[] = (int)$_GET['matchweek'];
            $hasFilter = true;
        }

        if ($selectedMatchId) {
            $selectedConditions = $conditions;
            $selectedConditions[] = 'id = ?';
            $selectedParams = $params;
            $selectedParams[] = (int)$selectedMatchId;
            $stmt = $db->prepare('SELECT * FROM matches WHERE ' . implode(' AND ', $selectedConditions));
            $stmt->execute($selectedParams);
            $match = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($match || !$hasFilter) {
                return $match;
            }
        }

        if (!$hasFilter) {
            return null;
        }

        $stmt = $db->prepare(
            'SELECT * FROM matches WHERE ' . implode(' AND ', $conditions)
            . ' ORDER BY COALESCE(NULLIF(match_timestamp, ""), match_date) ASC, id ASC LIMIT 1'
        );
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

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

        $targetMatch = football_stats_resolve_selected_match(
            $db,
            $competitionCode,
            $requestedSeasonLabel,
            $selectedMatchId
        );
        $selectedMatchId = $targetMatch ? (int)$targetMatch['id'] : $selectedMatchId;

        $standings = [];
        if ($targetMatch) {
            $targetKickoff = !empty($targetMatch['match_timestamp'])
                ? $targetMatch['match_timestamp']
                : $targetMatch['match_date'];
            $mQuery = 'SELECT * FROM matches 
                       WHERE competition_code = ? AND season_label = ? 
                         AND matchweek >= 1 AND matchweek <= ?
                         AND home_goals IS NOT NULL AND away_goals IS NOT NULL
                         AND (
                           (COALESCE(NULLIF(match_timestamp, ""), match_date) < ?) OR
                           (COALESCE(NULLIF(match_timestamp, ""), match_date) = ? AND id <= ?)
                         )
                       ORDER BY COALESCE(NULLIF(match_timestamp, ""), match_date) ASC, id ASC';
            $mMatchesStmt = $db->prepare($mQuery);
            $mMatchesStmt->execute([
                $competitionCode,
                $requestedSeasonLabel,
                football_stats_get_final_matchweek($competitionCode),
                $targetKickoff,
                $targetKickoff,
                $targetMatch['id']
            ]);

            $playedMatches = $mMatchesStmt->fetchAll(PDO::FETCH_ASSOC);

            $crestMap = football_stats_get_team_crest_map(
                $db,
                $competitionCode,
                $requestedSeasonLabel,
                $liveTableName
            );

            $stats = [];
            foreach (football_stats_get_match_roster($db, $competitionCode, $requestedSeasonLabel) as $teamName) {
                $stats[$teamName] = football_stats_empty_team_stats();
            }
            foreach ($playedMatches as $m) {
                $hg = (int)$m['home_goals'];
                $ag = (int)$m['away_goals'];
                $home = $m['home_team'];
                $away = $m['away_team'];

                if (!isset($stats[$home])) $stats[$home] = football_stats_empty_team_stats();
                if (!isset($stats[$away])) $stats[$away] = football_stats_empty_team_stats();

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
 * Build the blank table for a season before any regular-season matches have
 * been played. Teams are ordered by name so the zero-point table is stable.
 */
if (!function_exists('football_stats_get_table_view_pre_season')) {
    function football_stats_get_table_view_pre_season(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        $metadataStmt = $db->prepare('SELECT season_label FROM live_table_metadata WHERE competition_code = ?');
        $metadataStmt->execute([$competitionCode]);
        $metadata = $metadataStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $liveSeasonLabel = $metadata['season_label'] ?? $fallbackSeasonLabel;

        $requestedSeasonLabel = isset($_GET['snapshot_season'])
            ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_season'])
            : $liveSeasonLabel;
        if ($requestedSeasonLabel === '') $requestedSeasonLabel = $liveSeasonLabel;

        $seasonsStmt = $db->prepare('SELECT DISTINCT season_label FROM matches WHERE competition_code = ? ORDER BY season_label DESC');
        $seasonsStmt->execute([$competitionCode]);
        $availableSeasons = $seasonsStmt->fetchAll(PDO::FETCH_COLUMN);
        $teams = football_stats_get_match_roster($db, $competitionCode, $requestedSeasonLabel);
        natcasesort($teams);
        $crestMap = football_stats_get_team_crest_map($db, $competitionCode, $requestedSeasonLabel, $liveTableName);

        $standings = [];
        $position = 1;
        foreach ($teams as $teamName) {
            $standings[] = [
                'position' => $position++, 'team_name' => $teamName,
                'team_crest' => $crestMap[$teamName] ?? '', 'played' => 0,
                'won' => 0, 'drawn' => 0, 'lost' => 0, 'gf' => 0,
                'ga' => 0, 'gd' => 0, 'points' => 0,
            ];
        }

        return [
            'standings' => $standings,
            'last_update' => ['ts' => null],
            'updated_label' => 'Pre-season',
            'is_snapshot_view' => true,
            'requested_season_label' => $requestedSeasonLabel,
            'available_seasons' => $availableSeasons,
            'active_season_label' => $requestedSeasonLabel,
            'live_season_label' => $liveSeasonLabel,
        ];
    }
}

/**
 * Fetch standings calculated precisely before a specific match ID.
 *
 * The optional override supports internal comparisons without changing the
 * match selected in the request.
 */
if (!function_exists('football_stats_get_table_view_by_match_before')) {
    function football_stats_get_table_view_by_match_before(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel, $selectedMatchIdOverride = null)
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

        $selectedMatchId = $selectedMatchIdOverride !== null
            ? (int)$selectedMatchIdOverride
            : (isset($_GET['match_id']) ? (int)$_GET['match_id'] : null);

        if ($selectedMatchIdOverride !== null) {
            // Internal movement comparisons deliberately step outside the
            // currently selected matchweek/date filter.
            $mStmt = $db->prepare(
                'SELECT * FROM matches WHERE id = ? AND competition_code = ? AND season_label = ? '
                . 'AND matchweek >= 1 AND matchweek <= ?'
            );
            $mStmt->execute([
                $selectedMatchId,
                $competitionCode,
                $requestedSeasonLabel,
                football_stats_get_final_matchweek($competitionCode),
            ]);
            $targetMatch = $mStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            $targetMatch = football_stats_resolve_selected_match(
                $db,
                $competitionCode,
                $requestedSeasonLabel,
                $selectedMatchId
            );
        }
        $selectedMatchId = $targetMatch ? (int)$targetMatch['id'] : $selectedMatchId;

        $standings = [];
        if ($targetMatch) {
            $targetKickoff = !empty($targetMatch['match_timestamp'])
                ? $targetMatch['match_timestamp']
                : $targetMatch['match_date'];
            $mQuery = 'SELECT * FROM matches 
                       WHERE competition_code = ? AND season_label = ? 
                         AND matchweek >= 1 AND matchweek <= ?
                         AND home_goals IS NOT NULL AND away_goals IS NOT NULL
                         AND (
                           (COALESCE(NULLIF(match_timestamp, ""), match_date) < ?) OR
                           (COALESCE(NULLIF(match_timestamp, ""), match_date) = ? AND id < ?)
                         )
                       ORDER BY COALESCE(NULLIF(match_timestamp, ""), match_date) ASC, id ASC';
            $mMatchesStmt = $db->prepare($mQuery);
            $mMatchesStmt->execute([
                $competitionCode,
                $requestedSeasonLabel,
                football_stats_get_final_matchweek($competitionCode),
                $targetKickoff,
                $targetKickoff,
                $targetMatch['id']
            ]);

            $playedMatches = $mMatchesStmt->fetchAll(PDO::FETCH_ASSOC);

            $crestMap = football_stats_get_team_crest_map(
                $db,
                $competitionCode,
                $requestedSeasonLabel,
                $liveTableName
            );

            $stats = [];
            foreach (football_stats_get_match_roster($db, $competitionCode, $requestedSeasonLabel) as $teamName) {
                $stats[$teamName] = football_stats_empty_team_stats();
            }
            foreach ($playedMatches as $m) {
                $hg = (int)$m['home_goals'];
                $ag = (int)$m['away_goals'];
                $home = $m['home_team'];
                $away = $m['away_team'];

                if (!isset($stats[$home])) $stats[$home] = football_stats_empty_team_stats();
                if (!isset($stats[$away])) $stats[$away] = football_stats_empty_team_stats();

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

/** Return the unique, positive match IDs submitted by the custom calculator. */
if (!function_exists('football_stats_get_excluded_match_ids')) {
    function football_stats_get_excluded_match_ids()
    {
        $rawIds = explode(',', (string)($_GET['excluded_matches'] ?? ''));
        $ids = array_values(array_unique(array_filter(array_map('intval', $rawIds), function ($id) {
            return $id > 0;
        })));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }
}

/** Return excluded individual team results (for example h42 or a42). */
if (!function_exists('football_stats_get_excluded_result_keys')) {
    function football_stats_get_excluded_result_keys()
    {
        $keys = [];
        foreach (explode(',', (string)($_GET['excluded_results'] ?? '')) as $key) {
            if (preg_match('/^[ha][1-9][0-9]*$/', $key)) $keys[$key] = true;
        }
        // Keep links made by the previous, whole-fixture UI working.
        foreach (football_stats_get_excluded_match_ids() as $id) {
            $keys['h' . $id] = true;
            $keys['a' . $id] = true;
        }
        $keys = array_keys($keys);
        sort($keys, SORT_NATURAL);
        return $keys;
    }
}

/**
 * Return valid custom outcome changes as [match id => home|draw|away].
 *
 * The compact query-string format is "42-h,57-d,61-a". Invalid and duplicate
 * entries are ignored (with the last valid value for a match taking priority).
 */
if (!function_exists('football_stats_get_outcome_overrides')) {
    function football_stats_get_outcome_overrides()
    {
        $overrides = [];
        $outcomeMap = ['h' => 'home', 'd' => 'draw', 'a' => 'away'];
        foreach (explode(',', (string)($_GET['outcome_overrides'] ?? '')) as $entry) {
            if (!preg_match('/^([1-9][0-9]*)-([hda])$/', $entry, $matches)) continue;
            $overrides[(int)$matches[1]] = $outcomeMap[$matches[2]];
        }
        ksort($overrides, SORT_NUMERIC);
        return $overrides;
    }
}

/** Return user-entered what-if points deductions as [team name => points]. */
if (!function_exists('football_stats_get_custom_points_deductions')) {
    function football_stats_get_custom_points_deductions()
    {
        $decoded = json_decode((string)($_GET['custom_points_deductions'] ?? ''), true);
        if (!is_array($decoded)) return [];

        $deductions = [];
        foreach ($decoded as $teamName => $points) {
            $teamName = trim((string)$teamName);
            if ($teamName === '' || !is_numeric($points)) continue;
            $points = max(0, min(100, (int)$points));
            if ($points > 0) $deductions[$teamName] = $points;
        }
        ksort($deductions, SORT_NATURAL | SORT_FLAG_CASE);
        return $deductions;
    }
}

/** Build human-readable labels for the filters and outcomes used by custom rules. */
if (!function_exists('football_stats_describe_custom_rules')) {
    function football_stats_describe_custom_rules(array $matches, array $excludedResults, array $outcomeOverrides)
    {
        $excludedLookup = array_fill_keys($excludedResults, true);
        $filters = [];
        $outcomes = [];

        foreach ($matches as $match) {
            $matchId = (int)($match['id'] ?? 0);
            if ($matchId < 1) continue;

            $homeKey = 'h' . $matchId;
            $awayKey = 'a' . $matchId;
            $homeExcluded = isset($excludedLookup[$homeKey]);
            $awayExcluded = isset($excludedLookup[$awayKey]);
            $played = $match['home_goals'] !== null && $match['away_goals'] !== null;
            $score = $played ? (string)$match['home_goals'] . '-' . (string)$match['away_goals'] : 'vs';
            $matchLabel = 'MW' . (int)($match['matchweek'] ?? 0) . ': '
                . (string)($match['home_team'] ?? '') . ' ' . $score . ' '
                . (string)($match['away_team'] ?? '');

            if ($homeExcluded && $awayExcluded) {
                $filters[] = 'Exclude both team results from ' . $matchLabel;
            } elseif ($homeExcluded) {
                $filters[] = 'Exclude ' . (string)$match['home_team'] . "'s result from " . $matchLabel;
            } elseif ($awayExcluded) {
                $filters[] = 'Exclude ' . (string)$match['away_team'] . "'s result from " . $matchLabel;
            }

            if (isset($outcomeOverrides[$matchId])) {
                $outcome = $outcomeOverrides[$matchId];
                $outcomeLabel = $outcome === 'draw'
                    ? 'Draw'
                    : (string)($match[$outcome === 'home' ? 'home_team' : 'away_team'] ?? '') . ' wins';
                $outcomes[] = $matchLabel . ' → ' . $outcomeLabel;
            }
        }

        return ['filters' => $filters, 'outcomes' => $outcomes];
    }
}

/** Change a score with the fewest added goals needed to produce an outcome. */
if (!function_exists('football_stats_apply_outcome_override')) {
    function football_stats_apply_outcome_override($homeGoals, $awayGoals, $outcome)
    {
        $homeGoals = (int)$homeGoals;
        $awayGoals = (int)$awayGoals;
        if ($outcome === 'draw') {
            $levelScore = max($homeGoals, $awayGoals);
            return [$levelScore, $levelScore];
        }
        if ($outcome === 'home' && $homeGoals <= $awayGoals) return [$awayGoals + 1, $awayGoals];
        if ($outcome === 'away' && $awayGoals <= $homeGoals) return [$homeGoals, $homeGoals + 1];
        return [$homeGoals, $awayGoals];
    }
}

/** Calculate a table from completed results plus selected unplayed what-if fixtures. */
if (!function_exists('football_stats_compute_custom_match_standings')) {
    function football_stats_compute_custom_match_standings(PDO $db, $competitionCode, $seasonLabel, $liveTableName, array $excludedResults, array $outcomeOverrides = [])
    {
        $finalMatchweek = football_stats_get_final_matchweek($competitionCode);
        $stmt = $db->prepare(
            'SELECT id, home_team, away_team, home_goals, away_goals FROM matches '
            . 'WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek <= ? '
            . 'ORDER BY COALESCE(NULLIF(match_timestamp, ""), match_date), id'
        );
        $stmt->execute([$competitionCode, $seasonLabel, $finalMatchweek]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $excludedLookup = array_fill_keys($excludedResults, true);
        $stats = [];
        foreach (football_stats_get_match_roster($db, $competitionCode, $seasonLabel) as $teamName) {
            $stats[$teamName] = football_stats_empty_team_stats();
        }

        foreach ($matches as $match) {
            $matchId = (int)$match['id'];
            $isPlayed = $match['home_goals'] !== null && $match['away_goals'] !== null;
            if (!$isPlayed && !isset($outcomeOverrides[$matchId])) continue;
            $includeHome = !isset($excludedLookup['h' . $matchId]);
            $includeAway = !isset($excludedLookup['a' . $matchId]);
            if (!$includeHome && !$includeAway) continue;
            $home = $match['home_team'];
            $away = $match['away_team'];
            $homeGoals = $isPlayed ? (int)$match['home_goals'] : 0;
            $awayGoals = $isPlayed ? (int)$match['away_goals'] : 0;
            if (isset($outcomeOverrides[$matchId])) {
                [$homeGoals, $awayGoals] = football_stats_apply_outcome_override(
                    $homeGoals,
                    $awayGoals,
                    $outcomeOverrides[$matchId]
                );
            }
            if (!isset($stats[$home])) $stats[$home] = football_stats_empty_team_stats();
            if (!isset($stats[$away])) $stats[$away] = football_stats_empty_team_stats();
            if ($includeHome) {
                $stats[$home]['p']++;
                $stats[$home]['gf'] += $homeGoals; $stats[$home]['ga'] += $awayGoals;
            }
            if ($includeAway) {
                $stats[$away]['p']++;
                $stats[$away]['gf'] += $awayGoals; $stats[$away]['ga'] += $homeGoals;
            }
            if ($homeGoals > $awayGoals) {
                if ($includeHome) { $stats[$home]['w']++; $stats[$home]['pts'] += 3; }
                if ($includeAway) $stats[$away]['l']++;
            } elseif ($awayGoals > $homeGoals) {
                if ($includeAway) { $stats[$away]['w']++; $stats[$away]['pts'] += 3; }
                if ($includeHome) $stats[$home]['l']++;
            } else {
                if ($includeHome) { $stats[$home]['d']++; $stats[$home]['pts']++; }
                if ($includeAway) { $stats[$away]['d']++; $stats[$away]['pts']++; }
            }
        }

        uasort($stats, function ($a, $b) {
            if ($a['pts'] !== $b['pts']) return $b['pts'] - $a['pts'];
            $gdA = $a['gf'] - $a['ga']; $gdB = $b['gf'] - $b['ga'];
            if ($gdA !== $gdB) return $gdB - $gdA;
            return $b['gf'] - $a['gf'];
        });

        $crestMap = football_stats_get_team_crest_map($db, $competitionCode, $seasonLabel, $liveTableName);
        $standings = []; $position = 1;
        foreach ($stats as $teamName => $team) {
            $standings[] = [
                'position' => $position++, 'team_name' => $teamName,
                'team_crest' => $crestMap[$teamName] ?? '', 'played' => $team['p'],
                'won' => $team['w'], 'drawn' => $team['d'], 'lost' => $team['l'],
                'gf' => $team['gf'], 'ga' => $team['ga'], 'gd' => $team['gf'] - $team['ga'],
                'points' => $team['pts'],
            ];
        }
        return $standings;
    }
}

/**
 * Fetch standings for any calculation mode based on $_GET['calc_mode']
 */
if (!function_exists('football_stats_get_table_view_combined')) {
    function football_stats_get_table_view_combined(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        $calcMode = $_GET['calc_mode'] ?? 'by_matchweek';
        $isPreSeason = (isset($_GET['pre_season']) && $_GET['pre_season'] === '1')
            || $calcMode === 'pre_season';
        // Pre-season is a navigation point within each time-based calculation,
        // rather than a calculation mode of its own. Keep old bookmarked URLs
        // working by treating the retired mode as the matchweek calculation.
        if ($calcMode === 'pre_season') {
            $calcMode = 'by_matchweek';
        }

        if ($isPreSeason) {
            $tableView = football_stats_get_table_view_pre_season($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        } elseif ($calcMode === 'custom_matches') {
            $tableView = football_stats_get_table_view($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
            $seasonLabel = (string)($tableView['active_season_label'] ?? $fallbackSeasonLabel);
            $excludedIds = football_stats_get_excluded_result_keys();
            $outcomeOverrides = football_stats_get_outcome_overrides();
            $customPointsDeductions = football_stats_get_custom_points_deductions();
            $tableView['standings'] = football_stats_compute_custom_match_standings(
                $db,
                $competitionCode,
                $seasonLabel,
                $liveTableName,
                $excludedIds,
                $outcomeOverrides
            );
            if ($customPointsDeductions) {
                $deductions = [];
                foreach ($customPointsDeductions as $teamName => $points) {
                    $deductions[] = ['team_name' => $teamName, 'points' => $points, 'reason' => 'Custom what-if deduction'];
                }
                $tableView['standings'] = football_stats_apply_points_deductions($tableView['standings'], $deductions);
            }
            // Keep separate baselines so movement can explain either half of a
            // custom calculation: changed outcomes or omitted team results.
            $tableView['custom_selected_original_standings'] = football_stats_compute_custom_match_standings(
                $db,
                $competitionCode,
                $seasonLabel,
                $liveTableName,
                $excludedIds
            );
            $tableView['custom_all_overridden_standings'] = football_stats_compute_custom_match_standings(
                $db,
                $competitionCode,
                $seasonLabel,
                $liveTableName,
                [],
                $outcomeOverrides
            );
            $tableView['is_snapshot_view'] = true;
            $tableView['excluded_match_ids'] = $excludedIds;
            $tableView['outcome_overrides'] = $outcomeOverrides;
            $tableView['custom_points_deductions'] = $customPointsDeductions;
        } elseif ($calcMode === 'by_match') {
            $tableView = football_stats_get_table_view_by_match($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        } elseif ($calcMode === 'by_match_before') {
            $tableView = football_stats_get_table_view_by_match_before($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        } elseif ($calcMode === 'by_date') {
            $tableView = football_stats_get_table_view_by_date($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        } else {
            $tableView = football_stats_get_table_view($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        }

        $tableView['calc_mode'] = $calcMode;
        $tableView['is_pre_season'] = $isPreSeason;
        $seasonLabel = (string)($tableView['active_season_label'] ?? $tableView['requested_season_label'] ?? '');
        $crestMap = football_stats_get_team_crest_map($db, $competitionCode, $seasonLabel, $liveTableName);
        $tableView['standings'] = football_stats_add_team_crests($tableView['standings'], $crestMap);
        // Keep one canonical, completed-results table available to every view.
        // Table filters are applied by the individual league templates, so
        // movement arrows can use this as an alternative to the contextually
        // relevant (unfiltered/previous-period) comparison.
        $tableView['completed_standings'] = football_stats_compute_custom_match_standings(
            $db,
            $competitionCode,
            $seasonLabel,
            $liveTableName,
            []
        );
        $isHistoricTable = !$isPreSeason && (in_array($calcMode, ['by_date', 'custom_matches'], true)
            || (in_array($calcMode, ['by_match', 'by_match_before'], true) && !empty($tableView['target_match']))
            || ($calcMode === 'by_matchweek' && !empty($tableView['is_snapshot_view'])));
        $tableView['points_deductions'] = $isHistoricTable
            ? football_stats_get_points_deductions($db, $competitionCode, $seasonLabel)
            : [];

        // A table view is most useful when it also explains how the table
        // changed. Compare it with the closest earlier archived period
        // (rather than assuming snapshots exist for every week or date).
        $tableView['position_movements'] = [];
        $tableView['movement_comparison_matchweek'] = null;
        $tableView['movement_comparison_season_label'] = null;
        if (!$isPreSeason && $calcMode === 'by_matchweek' && !empty($tableView['active_matchweek'])) {
            $activeMatchweek = (int)($tableView['active_matchweek'] ?? 0);
            $seasonLabel = (string)($tableView['active_season_label'] ?? '');

            $previousWeekStmt = $db->prepare(
                'SELECT MAX(matchweek) FROM league_table_snapshots '
                . 'WHERE competition_code = ? AND season_label = ? AND matchweek < ?'
            );
            $previousWeekStmt->execute([$competitionCode, $seasonLabel, $activeMatchweek]);
            $previousMatchweek = $previousWeekStmt->fetchColumn();

            if ($previousMatchweek !== false && $previousMatchweek !== null) {
                $previousPositionsStmt = $db->prepare(
                    'SELECT team_name, position FROM league_table_snapshots '
                    . 'WHERE competition_code = ? AND season_label = ? AND matchweek = ?'
                );
                $previousPositionsStmt->execute([$competitionCode, $seasonLabel, (int)$previousMatchweek]);
                $previousPositions = [];
                foreach ($previousPositionsStmt->fetchAll(PDO::FETCH_ASSOC) as $previousTeam) {
                    $previousPositions[$previousTeam['team_name']] = (int)$previousTeam['position'];
                }

                foreach ($tableView['standings'] as $team) {
                    if (isset($previousPositions[$team['team_name']])) {
                        // Positive means the team climbed (for example 5th to 3rd).
                        $tableView['position_movements'][$team['team_name']] =
                            $previousPositions[$team['team_name']] - (int)$team['position'];
                    }
                }
                $tableView['movement_comparison_matchweek'] = (int)$previousMatchweek;
                $tableView['movement_comparison_label'] = 'since matchweek ' . (int)$previousMatchweek;
            }
        } elseif (!$isPreSeason && $calcMode === 'custom_matches') {
            // Show how each team's position changes when the unchecked matches
            // are removed, using the complete played-match table as the baseline.
            $completeStandings = $tableView['completed_standings'];
            $completeStandings = football_stats_apply_points_deductions(
                $completeStandings,
                $tableView['points_deductions']
            );
            $selectedStandings = football_stats_apply_points_deductions(
                $tableView['standings'],
                $tableView['points_deductions']
            );
            $completePositions = [];
            foreach ($completeStandings as $completeTeam) {
                $completePositions[$completeTeam['team_name']] = (int)$completeTeam['position'];
            }
            foreach ($selectedStandings as $team) {
                if (isset($completePositions[$team['team_name']])) {
                    $tableView['position_movements'][$team['team_name']] =
                        $completePositions[$team['team_name']] - (int)$team['position'];
                }
            }
            $tableView['movement_comparison_label'] = 'compared with all completed matches';
        } elseif ($calcMode === 'by_match' && !empty($tableView['target_match'])) {
            // For a specific-match snapshot, compare the table immediately
            // after that result with the table immediately before it.
            $beforeMatchView = football_stats_get_table_view_by_match_before(
                $db,
                $competitionCode,
                $liveTableName,
                $fallbackSeasonLabel
            );
            $beforePositions = [];
            foreach ($beforeMatchView['standings'] as $beforeTeam) {
                $beforePositions[$beforeTeam['team_name']] = (int)$beforeTeam['position'];
            }
            foreach ($tableView['standings'] as $team) {
                if (isset($beforePositions[$team['team_name']])) {
                    $tableView['position_movements'][$team['team_name']] =
                        $beforePositions[$team['team_name']] - (int)$team['position'];
                }
            }
            $tableView['movement_comparison_label'] = 'after this match';
        } elseif ($calcMode === 'by_match_before' && !empty($tableView['target_match'])) {
            // The table before this fixture includes the result of the fixture
            // immediately preceding it. Compare both pre-match states so the
            // arrows show the movement caused by that preceding result.
            $targetMatch = $tableView['target_match'];
            $targetKickoff = !empty($targetMatch['match_timestamp'])
                ? $targetMatch['match_timestamp']
                : $targetMatch['match_date'];
            $previousMatchStmt = $db->prepare(
                'SELECT id FROM matches '
                . 'WHERE competition_code = ? AND season_label = ? '
                . 'AND matchweek >= 1 AND matchweek <= ? '
                . 'AND home_goals IS NOT NULL AND away_goals IS NOT NULL '
                . 'AND ((COALESCE(NULLIF(match_timestamp, ""), match_date) < ?) '
                . 'OR (COALESCE(NULLIF(match_timestamp, ""), match_date) = ? AND id < ?)) '
                . 'ORDER BY COALESCE(NULLIF(match_timestamp, ""), match_date) DESC, id DESC LIMIT 1'
            );
            $previousMatchStmt->execute([
                $competitionCode,
                $seasonLabel,
                football_stats_get_final_matchweek($competitionCode),
                $targetKickoff,
                $targetKickoff,
                $targetMatch['id'],
            ]);
            $previousMatch = $previousMatchStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($previousMatch) {
                $previousBeforeView = football_stats_get_table_view_by_match_before(
                    $db,
                    $competitionCode,
                    $liveTableName,
                    $fallbackSeasonLabel,
                    (int)$previousMatch['id']
                );
                $previousPositions = [];
                foreach ($previousBeforeView['standings'] as $previousTeam) {
                    $previousPositions[$previousTeam['team_name']] = (int)$previousTeam['position'];
                }
                foreach ($tableView['standings'] as $team) {
                    if (isset($previousPositions[$team['team_name']])) {
                        $tableView['position_movements'][$team['team_name']] =
                            $previousPositions[$team['team_name']] - (int)$team['position'];
                    }
                }
                $tableView['movement_comparison_label'] = 'after the previous match';
            }
        } elseif ($calcMode === 'by_date' && !empty($tableView['active_date'])) {
            $activeDate = (string)($tableView['active_date'] ?? '');
            $seasonLabel = (string)($tableView['active_season_label'] ?? '');

            $previousDateStmt = $db->prepare(
                'SELECT MAX(snapshot_date) FROM league_table_snapshots_by_date '
                . 'WHERE competition_code = ? AND season_label = ? AND snapshot_date < ?'
            );
            $previousDateStmt->execute([$competitionCode, $seasonLabel, $activeDate]);
            $previousDate = $previousDateStmt->fetchColumn();

            if ($previousDate !== false && $previousDate !== null) {
                $previousPositionsStmt = $db->prepare(
                    'SELECT team_name, position FROM league_table_snapshots_by_date '
                    . 'WHERE competition_code = ? AND season_label = ? AND snapshot_date = ?'
                );
                $previousPositionsStmt->execute([$competitionCode, $seasonLabel, $previousDate]);
                $previousPositions = [];
                foreach ($previousPositionsStmt->fetchAll(PDO::FETCH_ASSOC) as $previousTeam) {
                    $previousPositions[$previousTeam['team_name']] = (int)$previousTeam['position'];
                }

                foreach ($tableView['standings'] as $team) {
                    if (isset($previousPositions[$team['team_name']])) {
                        // Positive means the team climbed (for example 5th to 3rd).
                        $tableView['position_movements'][$team['team_name']] =
                            $previousPositions[$team['team_name']] - (int)$team['position'];
                    }
                }
                $tableView['movement_comparison_matchweek'] = $previousDate;
                $tableView['movement_comparison_label'] = 'since ' . $previousDate;
            }
        }
        return $tableView;
    }
}

/** Render a snapshot's movement since the closest preceding archived matchweek. */
if (!function_exists('football_stats_render_position_movement')) {
    function football_stats_render_position_movement(array $tableView, $teamName)
    {
        $movement = (int)($tableView['position_movements'][$teamName] ?? 0);
        if ($movement === 0) {
            return;
        }

        $wentUp = $movement > 0;
        $places = abs($movement);
        // Keep the original, descriptive movement treatment as the default;
        // the shorter treatments are opt-in display preferences.
        $style = $tableView['movement_style'] ?? 'detailed';
        if (!in_array($style, ['compact', 'badge', 'detailed'], true)) {
            $style = 'detailed';
        }
        $comparisonLabel = (string)($tableView['movement_comparison_label'] ?? 'since the previous match');
        $label = sprintf(
            '%s %d %s %s',
            $wentUp ? 'Up' : 'Down',
            $places,
            $places === 1 ? 'place' : 'places',
            $comparisonLabel
        );
        ?>
        <span class="position-movement position-movement-<?= htmlspecialchars($style, ENT_QUOTES, 'UTF-8') ?> <?= $wentUp ? 'position-movement-up' : 'position-movement-down' ?>"
              title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
              aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"><span class="position-movement-arrow" aria-hidden="true"><?= $wentUp ? '&#9650;' : '&#9660;' ?></span><span class="position-movement-text" aria-hidden="true"><?php if ($style === 'detailed'): ?><span class="position-movement-detailed-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span><?php else: ?><span class="position-movement-compact-count"><?= $places ?></span><?php endif; ?></span></span>
        <?php
        return;
    }
}

/**
 * Add movement data for a filtered table relative to its unfiltered baseline.
 *
 * Filtered tables are calculated independently, so their arrows cannot use the
 * historic movement already attached to the main table view.  Comparing the
 * two supplied tables keeps the meaning useful: an up arrow shows how much
 * higher a team ranks under the active filter than it does across all relevant
 * completed matches.
 */
if (!function_exists('football_stats_add_filtered_position_movements')) {
    function football_stats_add_filtered_position_movements(array $tableView, array $filteredStandings, array $baselineStandings, $comparisonLabel = 'compared with all completed matches')
    {
        $baselinePositions = [];
        foreach ($baselineStandings as $team) {
            if (isset($team['team_name'], $team['position'])) {
                $baselinePositions[$team['team_name']] = (int)$team['position'];
            }
        }

        $tableView['position_movements'] = [];
        foreach ($filteredStandings as $team) {
            if (isset($team['team_name'], $team['position'], $baselinePositions[$team['team_name']])) {
                $tableView['position_movements'][$team['team_name']] =
                    $baselinePositions[$team['team_name']] - (int)$team['position'];
            }
        }
        $tableView['movement_comparison_label'] = $comparisonLabel;

        return $tableView;
    }
}

/** Apply the user's movement-arrow comparison choice to the displayed table. */
if (!function_exists('football_stats_apply_movement_preference')) {
    function football_stats_apply_movement_preference(array $tableView, array $displayedStandings, array $relevantBaseline, $hasActiveFilter = false)
    {
        $preference = $_GET['movement_compare'] ?? 'relevant';
        if (!in_array($preference, ['relevant', 'completed', 'custom_outcomes', 'custom_selection', 'off'], true)) {
            $preference = 'relevant';
        }
        $tableView['movement_compare'] = $preference;
        $style = $_GET['movement_style'] ?? 'detailed';
        $tableView['movement_style'] = in_array($style, ['detailed', 'compact', 'badge'], true) ? $style : 'detailed';

        if ($preference === 'off') {
            $tableView['position_movements'] = [];
            $tableView['movement_comparison_label'] = '';
            return $tableView;
        }

        if ($preference === 'completed') {
            $baseline = $tableView['completed_standings'] ?? [];
            if (!empty($tableView['points_deductions'])) {
                $baseline = football_stats_apply_points_deductions($baseline, $tableView['points_deductions']);
            }
            return football_stats_add_filtered_position_movements(
                $tableView,
                $displayedStandings,
                $baseline,
                'compared with all completed matches'
            );
        }

        $customBaselines = [
            'custom_outcomes' => [
                'key' => 'custom_selected_original_standings',
                'label' => 'compared with the selected results at their original outcomes',
            ],
            'custom_selection' => [
                'key' => 'custom_all_overridden_standings',
                'label' => 'compared with all completed matches using the custom outcomes',
            ],
        ];
        if (isset($customBaselines[$preference])) {
            $customBaseline = $customBaselines[$preference];
            $baseline = $tableView[$customBaseline['key']] ?? [];
            if (!empty($tableView['points_deductions'])) {
                $baseline = football_stats_apply_points_deductions($baseline, $tableView['points_deductions']);
            }
            return football_stats_add_filtered_position_movements(
                $tableView,
                $displayedStandings,
                $baseline,
                $customBaseline['label']
            );
        }

        if ($hasActiveFilter) {
            return football_stats_add_filtered_position_movements(
                $tableView,
                $displayedStandings,
                $relevantBaseline,
                'compared with the unfiltered calculation'
            );
        }

        return $tableView;
    }
}

/** Available visual treatments for the movement column. */
if (!function_exists('football_stats_get_movement_style_options')) {
    function football_stats_get_movement_style_options()
    {
        return [
            'detailed' => [
                'label' => 'Classic',
                'description' => 'Show the full movement wording and comparison used previously.',
            ],
            'compact' => [
                'label' => 'Compact arrows',
                'description' => 'Show a clean arrow and the number of places moved.',
            ],
            'badge' => [
                'label' => 'Colour badges',
                'description' => 'Place each arrow and count inside a coloured pill.',
            ],
        ];
    }
}

/** Describe movement choices in terms of the calculation currently on screen. */
if (!function_exists('football_stats_get_movement_preference_options')) {
    function football_stats_get_movement_preference_options($calcMode, $hasActiveFilter = false)
    {
        if ($hasActiveFilter) {
            $defaultLabel = 'Unfiltered calculation (default)';
            $defaultDescription = 'Compare this filtered table with the same calculation before the table filter is applied.';
        } else {
            $modeDefaults = [
                'by_matchweek' => [
                    'Previous matchweek (default)',
                    'Show movement since the closest earlier archived matchweek.',
                ],
                'by_date' => [
                    'Previous date (default)',
                    'Show movement since the closest earlier date with archived standings.',
                ],
                'by_match_before' => [
                    'Previous match (default)',
                    'Show the movement caused by the fixture immediately before the selected match.',
                ],
                'by_match' => [
                    'Before selected match (default)',
                    'Show the movement caused by the selected match.',
                ],
                'custom_matches' => [
                    'All completed matches (default)',
                    'Compare the custom-rules table with standings from every completed match.',
                ],
            ];
            [$defaultLabel, $defaultDescription] = $modeDefaults[$calcMode] ?? $modeDefaults['by_matchweek'];
        }

        $options = [
            'relevant' => ['label' => $defaultLabel, 'description' => $defaultDescription],
        ];
        if (!$hasActiveFilter && $calcMode === 'custom_matches') {
            $options['custom_outcomes'] = [
                'label' => 'Original outcomes for selection',
                'description' => 'Show movement caused only by changing outcomes, while keeping the same selected team results.',
            ];
            $options['custom_selection'] = [
                'label' => 'All matches with custom outcomes',
                'description' => 'Show movement caused only by removing team results, while retaining your changed outcomes.',
            ];
        }
        if (!$hasActiveFilter && $calcMode !== 'custom_matches') {
            $options['completed'] = [
                'label' => 'All completed matches',
                'description' => 'Compare this table with the latest standings calculated from all completed fixtures.',
            ];
        } elseif ($hasActiveFilter) {
            $options['completed'] = [
                'label' => 'All completed matches',
                'description' => 'Compare the filtered table with standings from all completed fixtures.',
            ];
        }
        $options['off'] = [
            'label' => 'Hide movement arrows',
            'description' => 'Do not show position movement for this table.',
        ];

        return $options;
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

/** Render a URL-backed slider from normalized label/value/url items. */
if (!function_exists('football_stats_render_navigation_slider')) {
    function football_stats_render_navigation_slider(array $items, $activeValue, $title, $idSeed, $helpText)
    {
        if (empty($items)) {
            echo '<p class="historic-slider-empty">No navigation points are available.</p>';
            return;
        }

        $activeIndex = 0;
        foreach ($items as $index => $item) {
            if ((string)$item['value'] === (string)$activeValue) $activeIndex = $index;
        }
        $controlId = 'historic-league-slider-' . substr(hash('sha256', $idSeed), 0, 10);
        ?>
        <div class="historic-league-slider" data-navigation-slider>
            <div class="historic-slider-heading">
                <label for="<?php echo $controlId; ?>"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                    <strong data-slider-label><?php echo htmlspecialchars($items[$activeIndex]['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </label>
            </div>
            <div class="historic-slider-controls">
                <button type="button" class="historic-slider-step" data-slider-previous aria-label="Previous option">&#8249;</button>
                <input id="<?php echo $controlId; ?>" type="range" min="0" max="<?php echo count($items) - 1; ?>"
                       step="1" value="<?php echo $activeIndex; ?>" aria-describedby="<?php echo $controlId; ?>-help">
                <button type="button" class="historic-slider-step" data-slider-next aria-label="Next option">&#8250;</button>
            </div>
            <small id="<?php echo $controlId; ?>-help"><?php echo htmlspecialchars($helpText, ENT_QUOTES, 'UTF-8'); ?></small>
        </div>
        <script>
        (function () {
            var slider = document.getElementById(<?php echo json_encode($controlId); ?>);
            var root = slider.closest('[data-navigation-slider]');
            var labels = <?php echo json_encode(array_column($items, 'label'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var urls = <?php echo json_encode(array_column($items, 'url'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var label = root.querySelector('[data-slider-label]');
            var previous = root.querySelector('[data-slider-previous]');
            var next = root.querySelector('[data-slider-next]');
            var describe = function () {
                var index = Number(slider.value);
                label.textContent = labels[index];
                slider.setAttribute('aria-valuetext', labels[index]);
                previous.disabled = index === 0;
                next.disabled = index === labels.length - 1;
            };
            var navigate = function () { window.location.assign(urls[Number(slider.value)]); };
            slider.addEventListener('input', describe);
            slider.addEventListener('change', navigate);
            previous.addEventListener('click', function () { slider.value = Number(slider.value) - 1; describe(); navigate(); });
            next.addEventListener('click', function () { slider.value = Number(slider.value) + 1; describe(); navigate(); });
            describe();
        }());
        </script>
        <?php
    }
}

/** Render a matchweek slider for the snapshots available in one season. */
if (!function_exists('football_stats_render_historic_league_table_slider')) {
    function football_stats_render_historic_league_table_slider(array $tableView, $tab = null, $league = null, $subtab = null)
    {
        $matchweeks = array_values(array_filter(
            array_unique(array_map('intval', $tableView['available_matchweeks'] ?? [])),
            static function ($matchweek) { return $matchweek !== 0; }
        ));
        sort($matchweeks, SORT_NUMERIC);
        $matchweeks[] = 0;

        if (empty($matchweeks)) {
            echo '<p class="historic-slider-empty">No historic matchweek snapshots are available for this season.</p>';
            return;
        }

        $season = (string)($tableView['active_season_label'] ?? $tableView['requested_season_label'] ?? '');
        $activeMatchweek = (int)($tableView['active_matchweek'] ?? end($matchweeks));
        $items = [];
        foreach ($matchweeks as $matchweek) {
            $items[] = [
                'value' => $matchweek,
                'label' => $matchweek === 0 ? 'Pre-season' : 'Matchweek ' . $matchweek,
                'url' => football_stats_build_table_view_url($tab, $league, $subtab, [
                    'calc_mode' => 'by_matchweek', 'table_view' => 'snapshot',
                    'snapshot_season' => $season,
                    'matchweek' => $matchweek === 0 ? null : $matchweek,
                    'pre_season' => $matchweek === 0 ? '1' : null,
                    'table_filter' => $matchweek === 0 ? null : ($_GET['table_filter'] ?? null),
                ]),
            ];
        }
        football_stats_render_navigation_slider($items, $activeMatchweek, 'Historic league table', implode('|', [$tab, $league, $subtab, $season, 'matchweek']), "Drag or use the arrow keys, then release to view that week's standings.");
    }
}

/**
 * Render a matchweek slider for the snapshots available in one season.
 *
 * The slider uses indexes rather than matchweek numbers so it also works when
 * the archive has gaps (for example, weeks 0, 1, 3 and 4). Moving the control
 * updates its label immediately; releasing it loads the selected snapshot.
 */
if (!function_exists('football_stats_render_historic_league_table_slider')) {
    function football_stats_render_historic_league_table_slider(array $tableView, $tab = null, $league = null, $subtab = null)
    {
        $matchweeks = array_values(array_unique(array_map('intval', $tableView['available_matchweeks'] ?? [])));
        sort($matchweeks, SORT_NUMERIC);

        if (empty($matchweeks)) {
            echo '<p class="historic-slider-empty">No historic matchweek snapshots are available for this season.</p>';
            return;
        }

        $season = (string)($tableView['active_season_label'] ?? $tableView['requested_season_label'] ?? '');
        $activeMatchweek = (int)($tableView['active_matchweek'] ?? end($matchweeks));
        $activeIndex = array_search($activeMatchweek, $matchweeks, true);
        if ($activeIndex === false) {
            $activeIndex = count($matchweeks) - 1;
        }

        $urls = [];
        foreach ($matchweeks as $matchweek) {
            $urls[] = football_stats_build_table_view_url($tab, $league, $subtab, [
                'calc_mode' => 'by_matchweek',
                'table_view' => 'snapshot',
                'snapshot_season' => $season,
                'matchweek' => $matchweek,
            ]);
        }

        $controlId = 'historic-league-slider-' . substr(hash('sha256', implode('|', [$tab, $league, $subtab, $season])), 0, 10);
        $previousIndex = max(0, $activeIndex - 1);
        $nextIndex = min(count($matchweeks) - 1, $activeIndex + 1);
        ?>
        <div class="historic-league-slider" data-historic-slider>
            <div class="historic-slider-heading">
                <label for="<?php echo $controlId; ?>">
                    Historic league table
                    <strong data-historic-label><?php echo $activeMatchweek === 0 ? 'Pre-season' : 'Matchweek ' . $activeMatchweek; ?></strong>
                </label>
                <a class="historic-slider-live" href="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => 'live', 'matchweek' => null]), ENT_QUOTES, 'UTF-8'); ?>">Latest table</a>
            </div>
            <div class="historic-slider-controls">
                <a class="historic-slider-step<?php echo $activeIndex === 0 ? ' is-disabled' : ''; ?>"
                   href="<?php echo htmlspecialchars($urls[$previousIndex], ENT_QUOTES, 'UTF-8'); ?>"
                   aria-label="Previous available matchweek"<?php echo $activeIndex === 0 ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&#8249;</a>
                <input id="<?php echo $controlId; ?>" type="range" min="0" max="<?php echo count($matchweeks) - 1; ?>"
                       step="1" value="<?php echo $activeIndex; ?>" aria-describedby="<?php echo $controlId; ?>-help">
                <a class="historic-slider-step<?php echo $activeIndex === count($matchweeks) - 1 ? ' is-disabled' : ''; ?>"
                   href="<?php echo htmlspecialchars($urls[$nextIndex], ENT_QUOTES, 'UTF-8'); ?>"
                   aria-label="Next available matchweek"<?php echo $activeIndex === count($matchweeks) - 1 ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&#8250;</a>
            </div>
            <small id="<?php echo $controlId; ?>-help">Drag or use the arrow keys, then release to view that week's standings.</small>
        </div>
        <script>
        (function () {
            var root = document.getElementById(<?php echo json_encode($controlId); ?>).closest('[data-historic-slider]');
            var slider = root.querySelector('input[type="range"]');
            var label = root.querySelector('[data-historic-label]');
            var weeks = <?php echo json_encode($matchweeks); ?>;
            var urls = <?php echo json_encode($urls, JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
            var describe = function () {
                var week = weeks[Number(slider.value)];
                label.textContent = week === 0 ? 'Pre-season' : 'Matchweek ' + week;
                slider.setAttribute('aria-valuetext', label.textContent);
            };
            slider.addEventListener('input', describe);
            slider.addEventListener('change', function () {
                window.location.assign(urls[Number(slider.value)]);
            });
            describe();
        }());
        </script>
        <?php
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
            'national-league' => 'NL',
            'division-one'    => 'D1'
        ];
        $competitionCode = $leagueMap[$league] ?? strtoupper((string)$league);

        $calcMode = $_GET['calc_mode'] ?? $tableView['calc_mode'] ?? 'by_matchweek';
        if ($calcMode === 'pre_season') $calcMode = 'by_matchweek';
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
        $isPreSeason = (isset($_GET['pre_season']) && $_GET['pre_season'] === '1')
            || !empty($tableView['is_pre_season']);
        $selectedMatchweek = isset($_GET['matchweek']) ? (int)$_GET['matchweek'] : null;
        $selectedDate = $_GET['snapshot_date'] ?? '';
        $selectedMatchId = isset($tableView['selected_match_id'])
            ? (int)$tableView['selected_match_id']
            : (isset($_GET['match_id']) ? (int)$_GET['match_id'] : null);
        $isSnapshot = isset($_GET['table_view']) && $_GET['table_view'] === 'snapshot';

        $availableMatchweeks = $tableView['available_matchweeks'] ?? [];
        $availableDates = $tableView['available_dates'] ?? [];
        $availableMatches = [];

        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            $finalMatchweek = football_stats_get_final_matchweek($competitionCode);

            if (empty($availableDates)) {
                $dStmt = $GLOBALS['db']->prepare('SELECT DISTINCT match_date FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek <= ? AND match_date IS NOT NULL AND match_date != "" ORDER BY match_date DESC');
                $dStmt->execute([$competitionCode, $activeSeason, $finalMatchweek]);
                $availableDates = $dStmt->fetchAll(PDO::FETCH_COLUMN);
            }

            if (empty($availableMatchweeks)) {
                $mwStmt = $GLOBALS['db']->prepare('SELECT DISTINCT matchweek FROM matches WHERE competition_code = ? AND season_label = ? ORDER BY matchweek ASC');
                $mwStmt->execute([$competitionCode, $activeSeason]);
                $availableMatchweeks = array_map('intval', $mwStmt->fetchAll(PDO::FETCH_COLUMN));
            }
            $availableMatchweeks = football_stats_limit_matchweeks_to_regular_season($availableMatchweeks, $competitionCode);

            $mQuery = 'SELECT id, matchweek, match_date, match_timestamp, home_team, away_team, home_goals, away_goals FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek <= ?';
            $params = [$competitionCode, $activeSeason, $finalMatchweek];

            if ($calcMode !== 'custom_matches' && $matchFilterMode === 'matchweek' && $selectedMatchweek !== null) {
                $mQuery .= ' AND matchweek = ?';
                $params[] = $selectedMatchweek;
            } elseif ($calcMode !== 'custom_matches' && $matchFilterMode === 'date' && $selectedDate !== '') {
                $mQuery .= ' AND match_date = ?';
                $params[] = $selectedDate;
            }

            $mQuery .= ' ORDER BY COALESCE(NULLIF(match_timestamp, ""), match_date) ASC, id ASC';
            $mStmt = $GLOBALS['db']->prepare($mQuery);
            $mStmt->execute($params);
            $availableMatches = $mStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $dateSliderItems = [];
        foreach (array_reverse($availableDates) as $date) {
            $dateSliderItems[] = [
                'value' => $date,
                'label' => (string)$date,
                'url' => football_stats_build_table_view_url($tab, $league, $subtab, [
                    'calc_mode' => 'by_date', 'snapshot_season' => $activeSeason,
                    'snapshot_date' => $date, 'pre_season' => null,
                ]),
            ];
        }
        $dateSliderItems[] = [
            'value' => 'pre_season',
            'label' => 'Pre-season',
            'url' => football_stats_build_table_view_url($tab, $league, $subtab, [
                'calc_mode' => 'by_date', 'snapshot_season' => $activeSeason,
                'snapshot_date' => null, 'pre_season' => '1', 'table_filter' => null,
            ]),
        ];
        $matchSliderItems = [];
        foreach ($availableMatches as $match) {
            $score = ($match['home_goals'] !== null && $match['away_goals'] !== null)
                ? " {$match['home_goals']}-{$match['away_goals']} " : ' vs ';
            $kickoff = football_stats_format_kickoff($match['match_timestamp'] ?? null, $match['match_date'] ?? null);
            $matchSliderItems[] = [
                'value' => (int)$match['id'],
                'label' => "MW{$match['matchweek']} [$kickoff]: {$match['home_team']}{$score}{$match['away_team']}",
                'url' => football_stats_build_table_view_url($tab, $league, $subtab, ['match_id' => (int)$match['id'], 'pre_season' => null]),
            ];
        }
        $matchSliderItems[] = [
            'value' => 'pre_season',
            'label' => 'Pre-season',
            'url' => football_stats_build_table_view_url($tab, $league, $subtab, [
                'match_id' => null, 'matchweek' => null, 'snapshot_date' => null,
                'pre_season' => '1', 'table_filter' => null,
            ]),
        ];

        ?>
        <style>
            .table-view-switcher { margin: 14px 0 16px; padding: 14px; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; background: rgba(255, 255, 255, 0.03); }
            .table-view-summary { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; color: #dcddde; font-size: 13px; }
            .table-view-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: rgba(88, 101, 242, 0.15); border: 1px solid rgba(88, 101, 242, 0.35); color: #c7d2fe; font-weight: 600; }
            .custom-rules-applied { flex: 1 1 100%; padding: 10px 12px; border: 1px solid rgba(88,101,242,.28); border-radius: 8px; background: rgba(88,101,242,.07); }
            .custom-rules-applied summary { display: flex; flex-wrap: wrap; gap: 7px; align-items: center; color: #dcddde; cursor: pointer; list-style-position: inside; }
            .custom-rules-applied[open] summary { margin-bottom: 8px; }
            .custom-rules-applied-details { display: grid; gap: 7px; padding-top: 2px; }
            .custom-rules-applied-heading { color: #c7d2fe; font-size: 11px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }
            .custom-rules-applied-group { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
            .custom-rules-applied-label { color: #b9bbbe; font-weight: 700; }
            .custom-rules-applied-item { padding: 4px 7px; border-radius: 5px; background: rgba(255,255,255,.07); color: #dcddde; font-size: 12px; }
            .custom-rules-applied-empty { color: #8e9297; font-size: 12px; }
            .table-view-actions { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
            .table-view-group { display: flex; flex-direction: column; gap: 4px; }
            .table-view-select { min-width: 180px; padding: 10px 12px; border-radius: 8px; background: #2f3136; border: 1px solid rgba(255, 255, 255, 0.08); color: #dcddde; font-size: 12px; font-weight: 600; cursor: pointer; }
            .table-view-label { color: #8e9297; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
            .historic-league-slider { flex: 1 1 360px; min-width: min(100%, 280px); padding: 10px 12px; border-radius: 8px; background: #2f3136; border: 1px solid rgba(255, 255, 255, 0.08); }
            .historic-slider-heading, .historic-slider-controls { display: flex; align-items: center; gap: 10px; }
            .historic-slider-heading { justify-content: space-between; margin-bottom: 7px; color: #8e9297; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
            .historic-slider-heading strong { margin-left: 6px; color: #c7d2fe; }
            .historic-slider-live { color: #c7d2fe; text-transform: none; white-space: nowrap; }
            .historic-slider-controls input { width: 100%; accent-color: #5865f2; cursor: pointer; }
            .historic-slider-step { display: grid; flex: 0 0 28px; height: 28px; place-items: center; border-radius: 6px; background: rgba(88, 101, 242, 0.2); color: #fff; font-size: 22px; text-decoration: none; }
            .historic-slider-step.is-disabled { opacity: 0.3; pointer-events: none; }
            .historic-league-slider small, .historic-slider-empty { color: #8e9297; font-size: 11px; }
            .custom-match-panel { flex: 1 1 100%; border: 1px solid rgba(88, 101, 242, 0.35); border-radius: 8px; background: #25272b; }
            .custom-match-panel summary { padding: 12px 14px; color: #c7d2fe; font-weight: 700; cursor: pointer; }
            .custom-match-toolbar { padding: 0 14px 12px; color: #b9bbbe; font-size: 12px; }
            .custom-match-toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 10px; }
            .custom-match-toolbar button { padding: 7px 10px; border: 0; border-radius: 6px; background: #4f545c; color: #fff; cursor: pointer; }
            .custom-match-toolbar label { font-weight: 700; color: #dcddde; }
            .custom-match-toolbar select { padding: 7px 28px 7px 9px; border: 1px solid #4f545c; border-radius: 6px; background: #1e1f22; color: #fff; cursor: pointer; }
            .custom-match-sections { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 8px; }
            .custom-match-section { min-width: 0; border: 1px solid rgba(255,255,255,.09); border-radius: 7px; background: rgba(255,255,255,.025); }
            .custom-match-section[open] { border-color: rgba(88,101,242,.4); background: rgba(88,101,242,.06); }
            .custom-match-section summary { display: flex; align-items: center; justify-content: space-between; padding: 9px 10px; color: #dcddde; font-size: 12px; font-weight: 700; cursor: pointer; user-select: none; }
            .custom-match-section summary::after { content: '+'; color: #8e9297; font-size: 17px; line-height: 1; }
            .custom-match-section[open] summary::after { content: '\2212'; }
            .custom-match-section-controls { display: flex; flex-direction: column; gap: 7px; padding: 0 9px 9px; }
            .custom-match-rule { display: inline-flex; align-items: center; gap: 6px; padding: 5px 7px; border-radius: 7px; background: rgba(255,255,255,.035); }
            .custom-match-rule label { flex: 1; white-space: nowrap; }
            .custom-match-rule select { min-width: 0; max-width: 145px; }
            .custom-team-filter { position: relative; align-items: stretch; flex-direction: column; }
            .custom-team-filter > span { font-weight: 700; color: #dcddde; }
            .custom-team-filter-menu { width: 100%; }
            .custom-team-filter-menu > summary { padding: 7px 9px; border: 1px solid #4f545c; border-radius: 6px; background: #1e1f22; color: #fff; font-weight: 400; cursor: pointer; list-style-position: inside; }
            .custom-team-filter-menu[open] > summary { border-color: #5865f2; }
            .custom-team-filter-options { display: grid; gap: 5px; max-height: 230px; overflow-y: auto; margin-top: 5px; padding: 8px; border: 1px solid #4f545c; border-radius: 6px; background: #18191c; }
            .custom-team-filter-presets { display: flex; flex-wrap: wrap; gap: 5px; padding-bottom: 6px; border-bottom: 1px solid rgba(255,255,255,.1); }
            .custom-match-toolbar .custom-team-filter-presets button { padding: 5px 7px; font-size: 11px; }
            .custom-team-filter-option { display: flex; gap: 7px; align-items: center; padding: 3px; font-weight: 400 !important; cursor: pointer; }
            .custom-team-filter-option input { accent-color: #5865f2; }
            .custom-match-outcome-rule { display: grid; grid-template-columns: minmax(110px, 1fr) minmax(110px, 1fr); gap: 6px; }
            .custom-match-outcome-rule label { grid-column: 1 / -1; }
            .custom-match-outcome-rule button { grid-column: 1 / -1; }
            .custom-match-toolbar .custom-match-reset { background: #3a3c41; }
            .custom-match-toolbar .custom-match-apply { margin-left: auto; background: #5865f2; font-weight: 700; }
            .custom-match-pending { flex: 1 1 100%; min-width: 260px; }
            .custom-match-pending .custom-rules-applied-heading { margin-right: 3px; }
            .custom-match-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 7px; max-height: 420px; overflow: auto; padding: 0 14px 14px; }
            .custom-match-week { border: 1px solid rgba(255,255,255,.09); border-radius: 7px; background: rgba(255,255,255,.02); }
            .custom-match-week > summary { padding: 9px 10px; color: #c7d2fe; font-size: 12px; font-weight: 700; cursor: pointer; }
            .custom-match-week-options { display: grid; gap: 7px; padding: 0 8px 8px; }
            .custom-match-option { display: flex; gap: 9px; align-items: flex-start; padding: 8px; border-radius: 6px; background: rgba(255,255,255,.035); color: #dcddde; font-size: 12px; cursor: pointer; }
            .custom-match-option input { margin-top: 2px; accent-color: #5865f2; }
            .custom-match-result { display: inline-flex; gap: 4px; align-items: center; white-space: nowrap; }
            .custom-match-outcome { margin-left: auto; padding: 5px 7px; border: 1px solid #4f545c; border-radius: 6px; background: #1e1f22; color: #fff; }
            .custom-match-range { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        </style>

        <div class="table-view-switcher">
            <div class="table-view-summary">
                <span class="table-view-pill">
                    <?php 
                        if ($calcMode === 'custom_matches') echo 'Selected Matches';
                        elseif ($calcMode === 'by_match') echo 'By Specific Match';
                        elseif ($calcMode === 'by_match_before') echo 'By Matchweek Before Specific Match';
                        elseif ($calcMode === 'by_date') echo 'By Date';
                        else echo 'By Matchweek';
                    ?>
                </span>
                <span>Season <?php echo htmlspecialchars($activeSeason); ?></span>
                <?php if ($isPreSeason): ?><span style="color:#00ff88;font-weight:bold;">Pre-season</span><?php endif; ?>
                <?php if ($calcMode === 'custom_matches'): ?>
                    <?php
                    $availableResultKeys = [];
                    foreach ($availableMatches as $availableMatch) {
                        if ($availableMatch['home_goals'] !== null && $availableMatch['away_goals'] !== null) {
                            $availableResultKeys[] = 'h' . (int)$availableMatch['id'];
                            $availableResultKeys[] = 'a' . (int)$availableMatch['id'];
                        }
                    }
                    $excludedCount = count(array_intersect($availableResultKeys, football_stats_get_excluded_result_keys()));
                    ?>
                    <?php $alteredOutcomeCount = count(football_stats_get_outcome_overrides()); ?>
                    <?php $customPointsDeductions = football_stats_get_custom_points_deductions(); ?>
                    <span><?php echo count($availableResultKeys) - $excludedCount; ?> of <?php echo count($availableResultKeys); ?> completed team results included</span>
                    <span><?php echo $alteredOutcomeCount; ?> match outcome<?php echo $alteredOutcomeCount === 1 ? '' : 's'; ?> altered</span>
                    <span><?php echo count($customPointsDeductions); ?> points deduction<?php echo count($customPointsDeductions) === 1 ? '' : 's'; ?></span>
                    <?php $appliedCustomRules = football_stats_describe_custom_rules($availableMatches, football_stats_get_excluded_result_keys(), football_stats_get_outcome_overrides()); ?>
                    <details class="custom-rules-applied" aria-label="Applied custom rules">
                        <summary>
                            <span class="custom-rules-applied-heading">Applied custom rules</span>
                            <span><?php echo count($appliedCustomRules['filters']); ?> filter<?php echo count($appliedCustomRules['filters']) === 1 ? '' : 's'; ?> &bull; <?php echo count($appliedCustomRules['outcomes']); ?> altered outcome<?php echo count($appliedCustomRules['outcomes']) === 1 ? '' : 's'; ?></span>
                            <span class="custom-rules-applied-empty">(toggle details)</span>
                        </summary>
                        <div class="custom-rules-applied-details">
                        <?php if (empty($appliedCustomRules['filters']) && empty($appliedCustomRules['outcomes']) && empty($customPointsDeductions)): ?>
                            <span class="custom-rules-applied-empty">No filters or altered outcomes are applied. All actual team results are included.</span>
                        <?php else: ?>
                            <?php if (!empty($appliedCustomRules['filters'])): ?>
                                <span class="custom-rules-applied-group"><span class="custom-rules-applied-label">Filters:</span>
                                <?php foreach ($appliedCustomRules['filters'] as $appliedFilter): ?><span class="custom-rules-applied-item"><?php echo htmlspecialchars($appliedFilter, ENT_QUOTES, 'UTF-8'); ?></span><?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($appliedCustomRules['outcomes'])): ?>
                                <span class="custom-rules-applied-group"><span class="custom-rules-applied-label">Outcomes:</span>
                                <?php foreach ($appliedCustomRules['outcomes'] as $appliedOutcome): ?><span class="custom-rules-applied-item"><?php echo htmlspecialchars($appliedOutcome, ENT_QUOTES, 'UTF-8'); ?></span><?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($customPointsDeductions)): ?>
                                <span class="custom-rules-applied-group"><span class="custom-rules-applied-label">Deductions:</span>
                                <?php foreach ($customPointsDeductions as $teamName => $points): ?><span class="custom-rules-applied-item"><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?>: &minus;<?php echo (int)$points; ?> pts</span><?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                        </div>
                    </details>
                <?php endif; ?>
                <?php if ($calcMode === 'by_matchweek'): ?>
                    <span>Matchweek <?php echo (int)($tableView['active_matchweek'] ?? 0); ?><?php if ($summaryDate): ?> <strong style="color:#00ff88; font-size:12px;">[<?php echo htmlspecialchars($summaryDate); ?>]</strong><?php endif; ?></span>
                <?php endif; ?>
                <?php if ($calcMode === 'by_date'): ?>
                    <span><?php echo htmlspecialchars((string)($tableView['active_date'] ?? '')); ?><?php if ($summaryMW): ?> <strong style="color:#00ff88; font-size:12px;">[<?php echo htmlspecialchars($summaryMW); ?>]</strong><?php endif; ?></span>
                <?php endif; ?>
                <?php if ($calcMode === 'by_match' && !empty($tableView['target_match'])): ?>
                    <?php $tm = $tableView['target_match']; ?>
                    <span style="color:#00ff88; font-weight:bold;">
                        After: <?php echo htmlspecialchars("{$tm['home_team']} {$tm['home_goals']}-{$tm['away_goals']} {$tm['away_team']}"); ?> (<?php echo htmlspecialchars(football_stats_format_kickoff($tm['match_timestamp'] ?? null, $tm['match_date'] ?? null)); ?>)
                    </span>
                <?php endif; ?>
                <?php if ($calcMode === 'by_match_before' && !empty($tableView['target_match'])): ?>
                    <?php $tm = $tableView['target_match']; ?>
                    <span style="color:#00ff88; font-weight:bold;">
                        Before: <?php echo htmlspecialchars("{$tm['home_team']} {$tm['home_goals']}-{$tm['away_goals']} {$tm['away_team']}"); ?> (<?php echo htmlspecialchars(football_stats_format_kickoff($tm['match_timestamp'] ?? null, $tm['match_date'] ?? null)); ?>)
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
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_date', 'pre_season' => null])); ?>" <?php echo ($calcMode === 'by_date') ? 'selected="selected"' : ''; ?>>
                            By Date (postponed-aware)
                        </option>
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_match_before', 'pre_season' => null])); ?>" <?php echo ($calcMode === 'by_match_before') ? 'selected="selected"' : ''; ?>>
                            By Specific Match (Before)
                        </option>
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_match', 'pre_season' => null])); ?>" <?php echo ($calcMode === 'by_match') ? 'selected="selected"' : ''; ?>>
                            By Specific Match (After)
                        </option>
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'custom_matches', 'excluded_matches' => null, 'excluded_results' => null, 'outcome_overrides' => null, 'custom_points_deductions' => null])); ?>" <?php echo ($calcMode === 'custom_matches') ? 'selected="selected"' : ''; ?>>
                            By Custom Rules
                        </option>
                    </select>
                </div>

                <!-- Dropdown 2: Select Season -->
                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-season">Select Season</label>
                    <select id="<?php echo $controlId; ?>-season" class="table-view-select" onchange="window.location.href=this.value;">
                        <?php foreach ($tableView['available_seasons'] as $season): ?>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_season' => $season, 'excluded_matches' => null, 'excluded_results' => null, 'outcome_overrides' => null, 'custom_points_deductions' => null])); ?>" <?php echo ((string)$season === $activeSeason) ? 'selected="selected"' : ''; ?>>
                                Season <?php echo htmlspecialchars($season); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($calcMode === 'custom_matches'): ?>
                    <?php
                    $excludedLookup = array_fill_keys(football_stats_get_excluded_result_keys(), true);
                    $outcomeOverrides = football_stats_get_outcome_overrides();
                    $customPointsDeductions = football_stats_get_custom_points_deductions();
                    $completedMatchweeks = [];
                    $allMatchweeks = [];
                    foreach ($availableMatches as $availableMatch) {
                        $allMatchweeks[(int)$availableMatch['matchweek']] = true;
                        if ($availableMatch['home_goals'] !== null && $availableMatch['away_goals'] !== null) {
                            $completedMatchweeks[(int)$availableMatch['matchweek']] = true;
                        }
                    }
                    $completedMatchweeks = array_keys($completedMatchweeks);
                    sort($completedMatchweeks, SORT_NUMERIC);
                    $allMatchweeks = array_keys($allMatchweeks);
                    sort($allMatchweeks, SORT_NUMERIC);
                    $customRuleTeams = [];
                    foreach ($availableMatches as $availableMatch) {
                        $customRuleTeams[$availableMatch['home_team']] = true;
                        $customRuleTeams[$availableMatch['away_team']] = true;
                    }
                    $customRuleTeams = array_keys($customRuleTeams);
                    natcasesort($customRuleTeams);
                    ?>
                    <details class="custom-match-panel" data-custom-match-panel>
                        <summary>Show / hide match selection</summary>
                        <div class="custom-match-toolbar">
                            <div class="custom-match-toolbar-actions">
                                <span>Choose which team results count, then optionally change a fixture's outcome for a what-if table.</span>
                                <button type="button" data-match-select-all>Select all</button>
                                <button type="button" data-match-clear-all>Clear all</button>
                            </div>
                            <?php
                            $customResultRules = [
                                'win' => 'Wins',
                                'draw' => 'Draws',
                                'loss' => 'Losses',
                                'team' => 'Team',
                                'home' => 'Home (Team A)',
                                'home_win' => 'Home Wins (Team A)',
                                'home_draw' => 'Home Draws (Team A)',
                                'home_loss' => 'Home Losses (Team A)',
                                'away' => 'Away (Team B)',
                                'away_win' => 'Away Wins (Team B)',
                                'away_draw' => 'Away Draws (Team B)',
                                'away_loss' => 'Away Losses (Team B)',
                            ];
                            $customRuleSections = [
                                'Team' => ['win', 'draw', 'loss', 'team'],
                                'Home' => ['home', 'home_win', 'home_draw', 'home_loss'],
                                'Away' => ['away', 'away_win', 'away_draw', 'away_loss'],
                            ];
                            ?>
                            <div class="custom-match-sections">
                                <details class="custom-match-section">
                                    <summary>Matchweek</summary>
                                    <div class="custom-match-section-controls">
                                        <?php foreach (['add' => 'Add Matchweek', 'remove' => 'Remove Matchweek', 'only' => 'Only Matchweek'] as $matchweekAction => $matchweekLabel): ?>
                                            <span class="custom-match-rule">
                                                <label for="<?php echo $controlId; ?>-custom-matchweek-<?php echo $matchweekAction; ?>"><?php echo $matchweekLabel; ?></label>
                                                <select id="<?php echo $controlId; ?>-custom-matchweek-<?php echo $matchweekAction; ?>" data-matchweek-<?php echo $matchweekAction; ?>>
                                                    <option value="">Choose&hellip;</option>
                                                    <?php foreach ($completedMatchweeks as $completedMatchweek): ?>
                                                        <option value="<?php echo $completedMatchweek; ?>">MW<?php echo $completedMatchweek; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </span>
                                        <?php endforeach; ?>
                                        <span class="custom-match-rule custom-match-range">
                                            <label for="<?php echo $controlId; ?>-range-start">Range</label>
                                            <span></span>
                                            <select id="<?php echo $controlId; ?>-range-start" data-matchweek-range-start aria-label="Range start matchweek">
                                                <?php foreach ($completedMatchweeks as $completedMatchweek): ?><option value="<?php echo $completedMatchweek; ?>">MW<?php echo $completedMatchweek; ?></option><?php endforeach; ?>
                                            </select>
                                            <select data-matchweek-range-end aria-label="Range end matchweek">
                                                <?php foreach ($completedMatchweeks as $completedMatchweek): ?><option value="<?php echo $completedMatchweek; ?>"<?php echo $completedMatchweek === end($completedMatchweeks) ? ' selected' : ''; ?>>MW<?php echo $completedMatchweek; ?></option><?php endforeach; ?>
                                            </select>
                                            <button type="button" data-matchweek-range-action="add">Add range</button>
                                            <button type="button" data-matchweek-range-action="remove">Remove range</button>
                                            <button type="button" data-matchweek-range-action="only">Only range</button>
                                        </span>
                                    </div>
                                </details>
                                <?php foreach ($customRuleSections as $sectionLabel => $sectionRules): ?>
                                <details class="custom-match-section">
                                    <summary><?php echo $sectionLabel; ?></summary>
                                    <div class="custom-match-section-controls">
                                    <?php foreach ($sectionRules as $ruleResult):
                                        $ruleResultLabel = $customResultRules[$ruleResult];
                                        $ruleControlId = $controlId . '-custom-add-' . $ruleResult;
                                    ?>
                                        <span class="custom-match-rule">
                                            <label for="<?php echo $ruleControlId; ?>">Add <?php echo $ruleResultLabel; ?></label>
                                            <select id="<?php echo $ruleControlId; ?>" data-team-rule data-team-checkbox-select data-rule-action="add" data-rule-result="<?php echo $ruleResult; ?>">
                                                <option value="">Choose&hellip;</option>
                                                <option value="__all__">All teams</option>
                                                <?php foreach ($customRuleTeams as $customRuleTeam): ?>
                                                    <option value="<?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </span>
                                    <?php endforeach; ?>
                                    </div>
                                </details>
                                <?php endforeach; ?>
                                <details class="custom-match-section">
                                    <summary>Remove</summary>
                                    <div class="custom-match-section-controls">
                            <?php foreach ($customResultRules as $ruleResult => $ruleResultLabel):
                                    $ruleControlId = $controlId . '-custom-remove-' . $ruleResult;
                            ?>
                                <span class="custom-match-rule">
                                    <label for="<?php echo $ruleControlId; ?>">Remove <?php echo $ruleResultLabel; ?></label>
                                    <select id="<?php echo $ruleControlId; ?>" data-team-rule data-team-checkbox-select data-rule-action="remove" data-rule-result="<?php echo $ruleResult; ?>">
                                        <option value="">Choose&hellip;</option>
                                        <option value="__all__">All teams</option>
                                        <?php foreach ($customRuleTeams as $customRuleTeam): ?>
                                            <option value="<?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </span>
                            <?php
                            endforeach;
                            ?>
                                    </div>
                                </details>
                                <?php foreach ([
                                    'only' => ['title' => 'Include only matching results', 'help' => 'Include only team results matching every field below. “All” leaves that field unrestricted.', 'button' => 'Include only matching results'],
                                    'exclude' => ['title' => 'Include everything but matching results', 'help' => 'Include every team result except those matching every field below. “All” leaves that field unrestricted.', 'button' => 'Exclude matching results'],
                                ] as $filterAction => $filterSection):
                                    $filterIdPrefix = $controlId . '-result-filter-' . $filterAction;
                                ?>
                                <details class="custom-match-section" data-result-filter-section>
                                    <summary><?php echo htmlspecialchars($filterSection['title'], ENT_QUOTES, 'UTF-8'); ?></summary>
                                    <div class="custom-match-section-controls">
                                        <p style="margin:0;color:#b9bbbe;line-height:1.45;"><?php echo htmlspecialchars($filterSection['help'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <div class="custom-match-rule custom-team-filter">
                                            <span id="<?php echo $filterIdPrefix; ?>-team-label">Teams</span>
                                            <details class="custom-team-filter-menu" data-result-filter-team>
                                                <summary data-team-filter-summary aria-labelledby="<?php echo $filterIdPrefix; ?>-team-label">All teams</summary>
                                                <div class="custom-team-filter-options">
                                                    <div class="custom-team-filter-presets">
                                                        <button type="button" data-team-filter-preset="all">All teams</button>
                                                        <button type="button" data-team-filter-preset="big-six">Big Six</button>
                                                        <button type="button" data-team-filter-preset="none">Clear</button>
                                                    </div>
                                                    <?php foreach ($customRuleTeams as $customRuleTeam): ?>
                                                        <label class="custom-team-filter-option"><input type="checkbox" value="<?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?>" data-team-filter-option> <?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?></label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </details>
                                        </div>
                                        <span class="custom-match-rule">
                                            <label for="<?php echo $filterIdPrefix; ?>-opponent">Opponent</label>
                                            <select id="<?php echo $filterIdPrefix; ?>-opponent" data-result-filter-opponent data-team-checkbox-select>
                                                <option value="all">All opponents</option>
                                                <?php foreach ($customRuleTeams as $customRuleTeam): ?><option value="<?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                                            </select>
                                        </span>
                                        <span class="custom-match-rule">
                                            <label for="<?php echo $filterIdPrefix; ?>-matchweek">Matchweek</label>
                                            <select id="<?php echo $filterIdPrefix; ?>-matchweek" data-result-filter-matchweek>
                                                <option value="all">All matchweeks</option>
                                                <?php foreach ($completedMatchweeks as $completedMatchweek): ?><option value="<?php echo $completedMatchweek; ?>">MW<?php echo $completedMatchweek; ?></option><?php endforeach; ?>
                                            </select>
                                        </span>
                                        <span class="custom-match-rule">
                                            <label for="<?php echo $filterIdPrefix; ?>-venue">Venue</label>
                                            <select id="<?php echo $filterIdPrefix; ?>-venue" data-result-filter-venue>
                                                <option value="all">Home or away</option><option value="home">Home only</option><option value="away">Away only</option>
                                            </select>
                                        </span>
                                        <span class="custom-match-rule">
                                            <label for="<?php echo $filterIdPrefix; ?>-outcome">Result</label>
                                            <select id="<?php echo $filterIdPrefix; ?>-outcome" data-result-filter-outcome>
                                                <option value="all">Every result</option><option value="win">Wins only</option><option value="draw">Draws only</option><option value="loss">Losses only</option>
                                            </select>
                                        </span>
                                        <button type="button" data-result-filter-action="<?php echo $filterAction; ?>"><?php echo htmlspecialchars($filterSection['button'], ENT_QUOTES, 'UTF-8'); ?></button>
                                    </div>
                                </details>
                                <?php endforeach; ?>
                                <details class="custom-match-section">
                                    <summary>Alter outcomes</summary>
                                    <div class="custom-match-section-controls">
                                        <span class="custom-match-rule">
                                            <label for="<?php echo $controlId; ?>-bulk-fixture-status">Fixtures</label>
                                            <select id="<?php echo $controlId; ?>-bulk-fixture-status" data-bulk-fixture-status>
                                                <option value="all">Played and unplayed</option>
                                                <option value="played">Played only</option>
                                                <option value="unplayed">Unplayed only</option>
                                            </select>
                                        </span>
                                        <span class="custom-match-rule custom-match-outcome-rule">
                                            <label for="<?php echo $controlId; ?>-bulk-outcome">All fixtures</label>
                                            <select data-bulk-current-outcome aria-label="Current outcomes to change across all fixtures">
                                                <option value="all">All current outcomes</option>
                                                <option value="home">Current Team A wins only</option>
                                                <option value="draw">Current draws only</option>
                                                <option value="away">Current Team B wins only</option>
                                                <option value="actual">Currently not simulated only</option>
                                            </select>
                                            <select id="<?php echo $controlId; ?>-bulk-outcome" data-bulk-outcome>
                                                <option value="actual">Change to actual outcome</option>
                                                <option value="home">Change to Team A win</option>
                                                <option value="draw">Change to draw</option>
                                                <option value="away">Change to Team B win</option>
                                            </select>
                                            <button type="button" data-bulk-outcome-apply="all">Apply to all</button>
                                        </span>
                                        <span class="custom-match-rule custom-match-outcome-rule">
                                            <label for="<?php echo $controlId; ?>-bulk-outcome-matchweek">By matchweek</label>
                                            <select id="<?php echo $controlId; ?>-bulk-outcome-matchweek" data-bulk-outcome-matchweek>
                                                <?php foreach ($allMatchweeks as $matchweek): ?>
                                                    <option value="<?php echo $matchweek; ?>">MW<?php echo $matchweek; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select data-bulk-matchweek-current-outcome aria-label="Current outcomes to change in selected matchweek">
                                                <option value="all">All current outcomes</option>
                                                <option value="home">Current Team A wins only</option>
                                                <option value="draw">Current draws only</option>
                                                <option value="away">Current Team B wins only</option>
                                                <option value="actual">Currently not simulated only</option>
                                            </select>
                                            <select data-bulk-matchweek-outcome aria-label="Outcome for selected matchweek">
                                                <option value="actual">Change to actual outcome</option>
                                                <option value="home">Change to Team A win</option>
                                                <option value="draw">Change to draw</option>
                                                <option value="away">Change to Team B win</option>
                                            </select>
                                            <button type="button" data-bulk-outcome-apply="matchweek">Apply to matchweek</button>
                                        </span>
                                        <span class="custom-match-rule custom-match-outcome-rule">
                                            <label for="<?php echo $controlId; ?>-bulk-outcome-team">By team</label>
                                            <select id="<?php echo $controlId; ?>-bulk-outcome-team" data-bulk-outcome-team data-team-checkbox-select>
                                                <?php foreach ($customRuleTeams as $customRuleTeam): ?>
                                                    <option value="<?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select data-bulk-team-current-outcome aria-label="Current outcomes to change for selected team">
                                                <option value="all">All current outcomes</option>
                                                <option value="win">Current wins only</option>
                                                <option value="draw">Current draws only</option>
                                                <option value="loss">Current losses only</option>
                                                <option value="unplayed">Currently not simulated only</option>
                                            </select>
                                            <select data-bulk-team-venue aria-label="Fixture venue for selected team">
                                                <option value="all">All venues</option>
                                                <option value="home">Home fixtures only</option>
                                                <option value="away">Away fixtures only</option>
                                            </select>
                                            <select data-bulk-team-opponent data-team-checkbox-select aria-label="Opponent for selected team">
                                                <option value="all">All opponents</option>
                                                <?php foreach ($customRuleTeams as $customRuleTeam): ?>
                                                    <option value="<?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select data-bulk-team-outcome aria-label="Outcome for selected team">
                                                <option value="actual">Change to actual outcome</option>
                                                <option value="win">Change to team win</option>
                                                <option value="draw">Change to team draw</option>
                                                <option value="loss">Change to team loss</option>
                                            </select>
                                            <button type="button" data-bulk-outcome-apply="team">Apply to team</button>
                                        </span>
                                    </div>
                                </details>
                                <details class="custom-match-section">
                                    <summary>Deduct points</summary>
                                    <div class="custom-match-section-controls">
                                        <?php foreach ($customRuleTeams as $customRuleTeam): ?>
                                        <label class="custom-match-rule">
                                            <span><?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <input type="number" min="0" max="100" step="1" value="<?php echo (int)($customPointsDeductions[$customRuleTeam] ?? 0); ?>" data-points-deduction="<?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Points to deduct from <?php echo htmlspecialchars($customRuleTeam, ENT_QUOTES, 'UTF-8'); ?>">
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            </div>
                            <div class="custom-match-toolbar-actions" style="margin-top:10px; margin-bottom:0;">
                                <button type="button" class="custom-match-reset" data-match-reset>Reset to actual results</button>
                                <span data-match-selection-status aria-live="polite"></span>
                                <details class="custom-rules-applied custom-match-pending" data-match-pending-rules>
                                    <summary><span class="custom-rules-applied-heading">Ready to calculate</span> <span data-match-pending-summary></span> <span class="custom-rules-applied-empty">(toggle details)</span></summary>
                                    <div class="custom-rules-applied-details" data-match-pending-details></div>
                                </details>
                                <button type="button" class="custom-match-apply" data-match-apply>Recalculate table</button>
                            </div>
                        </div>
                        <div class="custom-match-list">
                            <?php
                            $matchesByMatchweek = [];
                            foreach ($availableMatches as $match) {
                                if ($match['home_goals'] !== null && $match['away_goals'] !== null) {
                                    $matchesByMatchweek[(int)$match['matchweek']][] = $match;
                                }
                            }
                            ksort($matchesByMatchweek, SORT_NUMERIC);
                            foreach ($matchesByMatchweek as $matchweek => $matchweekMatches):
                            ?>
                            <details class="custom-match-week">
                                <summary>Matchweek <?php echo $matchweek; ?> (<?php echo count($matchweekMatches); ?> fixtures)</summary>
                                <div class="custom-match-week-options">
                                <?php foreach ($matchweekMatches as $match):
                                    $matchId = (int)$match['id'];
                                    $homeGoals = (int)$match['home_goals'];
                                    $awayGoals = (int)$match['away_goals'];
                                    $homeResult = $homeGoals === $awayGoals ? 'draw' : ($homeGoals > $awayGoals ? 'win' : 'loss');
                                    $awayResult = $homeGoals === $awayGoals ? 'draw' : ($awayGoals > $homeGoals ? 'win' : 'loss');
                                    $actualOutcome = $homeGoals === $awayGoals ? 'draw' : ($homeGoals > $awayGoals ? 'home' : 'away');
                                    $selectedOutcome = $outcomeOverrides[$matchId] ?? 'actual';
                                    ?>
                                    <div class="custom-match-option">
                                        <span><?php echo htmlspecialchars("{$match['home_team']} {$match['home_goals']}-{$match['away_goals']} {$match['away_team']}", ENT_QUOTES, 'UTF-8'); ?></span>
                                        <label class="custom-match-result"><input type="checkbox" value="h<?php echo $matchId; ?>" data-result-side="home" data-matchweek="<?php echo $matchweek; ?>" data-team="<?php echo htmlspecialchars($match['home_team'], ENT_QUOTES, 'UTF-8'); ?>" data-opponent="<?php echo htmlspecialchars($match['away_team'], ENT_QUOTES, 'UTF-8'); ?>" data-result="<?php echo $homeResult; ?>" <?php echo isset($excludedLookup['h' . $matchId]) ? '' : 'checked'; ?>> Team A</label>
                                        <label class="custom-match-result"><input type="checkbox" value="a<?php echo $matchId; ?>" data-result-side="away" data-matchweek="<?php echo $matchweek; ?>" data-team="<?php echo htmlspecialchars($match['away_team'], ENT_QUOTES, 'UTF-8'); ?>" data-opponent="<?php echo htmlspecialchars($match['home_team'], ENT_QUOTES, 'UTF-8'); ?>" data-result="<?php echo $awayResult; ?>" <?php echo isset($excludedLookup['a' . $matchId]) ? '' : 'checked'; ?>> Team B</label>
                                        <select class="custom-match-outcome" data-outcome-match="<?php echo $matchId; ?>" data-fixture-status="played" data-matchweek="<?php echo $matchweek; ?>" data-home-team="<?php echo htmlspecialchars($match['home_team'], ENT_QUOTES, 'UTF-8'); ?>" data-away-team="<?php echo htmlspecialchars($match['away_team'], ENT_QUOTES, 'UTF-8'); ?>" data-home-result="<?php echo $homeResult; ?>" data-away-result="<?php echo $awayResult; ?>" data-actual-outcome="<?php echo $actualOutcome; ?>" aria-label="What-if outcome for <?php echo htmlspecialchars($match['home_team'] . ' versus ' . $match['away_team'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <option value="actual"<?php echo $selectedOutcome === 'actual' ? ' selected' : ''; ?>>Actual: <?php echo ucfirst($actualOutcome); ?></option>
                                            <option value="home"<?php echo $selectedOutcome === 'home' ? ' selected' : ''; ?>>Team A wins</option>
                                            <option value="draw"<?php echo $selectedOutcome === 'draw' ? ' selected' : ''; ?>>Draw</option>
                                            <option value="away"<?php echo $selectedOutcome === 'away' ? ' selected' : ''; ?>>Team B wins</option>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </details>
                            <?php endforeach; ?>
                            <?php
                            $unplayedByMatchweek = [];
                            foreach ($availableMatches as $match) {
                                if ($match['home_goals'] === null || $match['away_goals'] === null) {
                                    $unplayedByMatchweek[(int)$match['matchweek']][] = $match;
                                }
                            }
                            if ($unplayedByMatchweek):
                            ?>
                            <details class="custom-match-week" open>
                                <summary>Simulate unplayed fixtures (<?php echo array_sum(array_map('count', $unplayedByMatchweek)); ?> remaining)</summary>
                                <div class="custom-match-week-options">
                                <?php foreach ($unplayedByMatchweek as $matchweek => $fixtures): foreach ($fixtures as $match):
                                    $matchId = (int)$match['id'];
                                    $selectedOutcome = $outcomeOverrides[$matchId] ?? 'actual';
                                ?>
                                    <div class="custom-match-option custom-match-unplayed">
                                        <span>MW<?php echo $matchweek; ?>: <?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <select class="custom-match-outcome" data-outcome-match="<?php echo $matchId; ?>" data-fixture-status="unplayed" data-matchweek="<?php echo $matchweek; ?>" data-home-team="<?php echo htmlspecialchars($match['home_team'], ENT_QUOTES, 'UTF-8'); ?>" data-away-team="<?php echo htmlspecialchars($match['away_team'], ENT_QUOTES, 'UTF-8'); ?>" data-home-result="unplayed" data-away-result="unplayed" data-actual-outcome="actual" aria-label="Simulated outcome for <?php echo htmlspecialchars($match['home_team'] . ' versus ' . $match['away_team'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <option value="actual"<?php echo $selectedOutcome === 'actual' ? ' selected' : ''; ?>>Not simulated</option>
                                            <option value="home"<?php echo $selectedOutcome === 'home' ? ' selected' : ''; ?>><?php echo htmlspecialchars($match['home_team'], ENT_QUOTES, 'UTF-8'); ?> wins</option>
                                            <option value="draw"<?php echo $selectedOutcome === 'draw' ? ' selected' : ''; ?>>Draw</option>
                                            <option value="away"<?php echo $selectedOutcome === 'away' ? ' selected' : ''; ?>><?php echo htmlspecialchars($match['away_team'], ENT_QUOTES, 'UTF-8'); ?> wins</option>
                                        </select>
                                    </div>
                                <?php endforeach; endforeach; ?>
                                </div>
                            </details>
                            <?php endif; ?>
                        </div>
                    </details>
                    <script>
                    (function () {
                        var panel = document.querySelector('[data-custom-match-panel]');
                        if (!panel) return;
                        var boxes = Array.prototype.slice.call(panel.querySelectorAll('.custom-match-option input[type="checkbox"]'));
                        var outcomeSelects = Array.prototype.slice.call(panel.querySelectorAll('[data-outcome-match]'));
                        var deductionInputs = Array.prototype.slice.call(panel.querySelectorAll('[data-points-deduction]'));
                        var selectionStatus = panel.querySelector('[data-match-selection-status]');
                        var pendingSummary = panel.querySelector('[data-match-pending-summary]');
                        var pendingDetails = panel.querySelector('[data-match-pending-details]');
                        var bigSixTeams = ['Arsenal', 'Chelsea', 'Liverpool', 'Manchester City', 'Manchester United', 'Tottenham Hotspur', 'Tottenham'];
                        function selectedTeamValues(select) {
                            return Array.prototype.filter.call(select.options, function (option) {
                                return option.selected && option.value && option.value !== 'all' && option.value !== '__all__';
                            }).map(function (option) { return option.value; });
                        }
                        function upgradeTeamSelect(select) {
                            var emptyLabel = select.matches('[data-team-rule], [data-bulk-outcome-team]') ? 'Choose teams' : 'All teams';
                            select.multiple = true;
                            select.classList.add('custom-team-source-select');
                            Array.prototype.forEach.call(select.options, function (option) { option.selected = false; });
                            var menu = document.createElement('details');
                            menu.className = 'custom-team-filter-menu custom-team-checkbox-select';
                            var summary = document.createElement('summary');
                            summary.textContent = emptyLabel;
                            var options = document.createElement('div');
                            options.className = 'custom-team-filter-options';
                            var presets = document.createElement('div');
                            presets.className = 'custom-team-filter-presets';
                            [['all', 'All teams'], ['big-six', 'Big Six'], ['none', 'Clear']].forEach(function (preset) {
                                var button = document.createElement('button');
                                button.type = 'button';
                                button.dataset.generatedTeamPreset = preset[0];
                                button.textContent = preset[1];
                                presets.appendChild(button);
                            });
                            options.appendChild(presets);
                            Array.prototype.forEach.call(select.options, function (option) {
                                if (!option.value || option.value === 'all' || option.value === '__all__') return;
                                var label = document.createElement('label');
                                label.className = 'custom-team-filter-option';
                                var checkbox = document.createElement('input');
                                checkbox.type = 'checkbox';
                                checkbox.value = option.value;
                                checkbox.addEventListener('change', function () {
                                    option.selected = checkbox.checked;
                                    updateSummary();
                                    select.dispatchEvent(new Event('change'));
                                });
                                label.appendChild(checkbox);
                                label.appendChild(document.createTextNode(' ' + option.textContent.trim()));
                                options.appendChild(label);
                            });
                            function updateSummary() {
                                var values = selectedTeamValues(select);
                                if (!values.length) summary.textContent = emptyLabel;
                                else if (values.length <= 2) summary.textContent = values.join(', ');
                                else summary.textContent = values.length + ' teams selected';
                            }
                            presets.addEventListener('click', function (event) {
                                var preset = event.target.dataset.generatedTeamPreset;
                                if (!preset) return;
                                Array.prototype.forEach.call(options.querySelectorAll('input[type="checkbox"]'), function (checkbox) {
                                    checkbox.checked = preset === 'all' || (preset === 'big-six' && bigSixTeams.indexOf(checkbox.value) !== -1);
                                    Array.prototype.forEach.call(select.options, function (option) {
                                        if (option.value === checkbox.value) option.selected = checkbox.checked;
                                    });
                                });
                                updateSummary();
                                select.dispatchEvent(new Event('change'));
                            });
                            menu.appendChild(summary);
                            menu.appendChild(options);
                            select.insertAdjacentElement('afterend', menu);
                        }
                        Array.prototype.forEach.call(panel.querySelectorAll('[data-team-checkbox-select]'), upgradeTeamSelect);
                        function addPendingGroup(label, items) {
                            var group = document.createElement('span');
                            group.className = 'custom-rules-applied-group';
                            var heading = document.createElement('span');
                            heading.className = 'custom-rules-applied-label';
                            heading.textContent = label + ':';
                            group.appendChild(heading);
                            items.forEach(function (item) {
                                var tag = document.createElement('span');
                                tag.className = 'custom-rules-applied-item';
                                tag.textContent = item;
                                group.appendChild(tag);
                            });
                            pendingDetails.appendChild(group);
                        }
                        function updatePendingRules() {
                            var excluded = boxes.filter(function (box) { return !box.checked; });
                            var altered = outcomeSelects.filter(function (select) { return select.value !== 'actual'; });
                            var deductions = deductionInputs.filter(function (input) { return Number(input.value) > 0; });
                            var filters = excluded.map(function (box) {
                                var fixture = box.closest('.custom-match-option').querySelector('span').textContent.trim();
                                return 'Exclude ' + box.dataset.team + "'s result from MW" + box.dataset.matchweek + ': ' + fixture;
                            });
                            var outcomes = altered.map(function (select) {
                                var fixture = select.closest('.custom-match-option').querySelector('span').textContent.trim();
                                var label = select.options[select.selectedIndex].text;
                                if (label === 'Team A wins') label = select.dataset.homeTeam + ' wins';
                                if (label === 'Team B wins') label = select.dataset.awayTeam + ' wins';
                                return 'MW' + select.dataset.matchweek + ': ' + fixture + ' \u2192 ' + label;
                            });
                            pendingSummary.textContent = filters.length + ' filter' + (filters.length === 1 ? '' : 's') + ' \u2022 ' + altered.length + ' simulated/altered outcome' + (altered.length === 1 ? '' : 's') + ' \u2022 ' + deductions.length + ' deduction' + (deductions.length === 1 ? '' : 's');
                            pendingDetails.textContent = '';
                            if (!filters.length && !outcomes.length && !deductions.length) {
                                var empty = document.createElement('span');
                                empty.className = 'custom-rules-applied-empty';
                                empty.textContent = 'All actual team results will be included.';
                                pendingDetails.appendChild(empty);
                                return;
                            }
                            if (filters.length) addPendingGroup('Filters', filters);
                            if (outcomes.length) addPendingGroup('Outcomes', outcomes);
                            if (deductions.length) addPendingGroup('Deductions', deductions.map(function (input) { return input.dataset.pointsDeduction + ': -' + Number(input.value) + ' pts'; }));
                        }
                        function updateSelectionStatus() {
                            var selected = boxes.filter(function (box) { return box.checked; }).length;
                            var altered = outcomeSelects.filter(function (select) { return select.value !== 'actual'; }).length;
                            selectionStatus.textContent = selected + ' of ' + boxes.length + ' team results selected; ' + altered + ' outcomes altered';
                            updatePendingRules();
                        }
                        Array.prototype.forEach.call(panel.querySelectorAll('[data-result-filter-section]'), function (section) {
                            var filterTeamBoxes = Array.prototype.slice.call(section.querySelectorAll('[data-team-filter-option]'));
                            var filterTeamSummary = section.querySelector('[data-team-filter-summary]');
                            function updateTeamFilterSummary() {
                                var selectedTeams = filterTeamBoxes.filter(function (box) { return box.checked; }).map(function (box) { return box.value; });
                                if (!selectedTeams.length || selectedTeams.length === filterTeamBoxes.length) {
                                    filterTeamSummary.textContent = 'All teams';
                                } else if (selectedTeams.length <= 2) {
                                    filterTeamSummary.textContent = selectedTeams.join(', ');
                                } else {
                                    filterTeamSummary.textContent = selectedTeams.length + ' teams selected';
                                }
                            }
                            filterTeamBoxes.forEach(function (box) {
                                box.addEventListener('change', updateTeamFilterSummary);
                            });
                            Array.prototype.forEach.call(section.querySelectorAll('[data-team-filter-preset]'), function (button) {
                                button.addEventListener('click', function () {
                                    var preset = this.dataset.teamFilterPreset;
                                    filterTeamBoxes.forEach(function (box) {
                                        box.checked = preset === 'all' || (preset === 'big-six' && bigSixTeams.indexOf(box.value) !== -1);
                                    });
                                    updateTeamFilterSummary();
                                });
                            });
                            updateTeamFilterSummary();
                        });
                        panel.querySelector('[data-match-select-all]').addEventListener('click', function () {
                            boxes.forEach(function (box) { box.checked = true; });
                            updateSelectionStatus();
                        });
                        panel.querySelector('[data-match-clear-all]').addEventListener('click', function () {
                            boxes.forEach(function (box) { box.checked = false; });
                            updateSelectionStatus();
                        });
                        panel.querySelector('[data-matchweek-add]').addEventListener('change', function () {
                            var matchweek = this.value;
                            if (!matchweek) return;
                            boxes.forEach(function (box) {
                                box.checked = box.dataset.matchweek === matchweek;
                                if (box.dataset.matchweek === matchweek) box.checked = true;
                            });
                            this.value = '';
                            updateSelectionStatus();
                        });
                        panel.querySelector('[data-matchweek-remove]').addEventListener('change', function () {
                            var matchweek = this.value;
                            if (!matchweek) return;
                            boxes.forEach(function (box) {
                                if (box.dataset.matchweek === matchweek) box.checked = false;
                            });
                            this.value = '';
                            updateSelectionStatus();
                        });
                        panel.querySelector('[data-matchweek-only]').addEventListener('change', function () {
                            var matchweek = this.value;
                            if (!matchweek) return;
                            boxes.forEach(function (box) {
                                // Narrow the existing selection rather than replacing it. This
                                // lets a matchweek constraint be stacked on team/result filters.
                                if (box.dataset.matchweek !== matchweek) box.checked = false;
                            });
                            this.value = '';
                            updateSelectionStatus();
                        });
                        Array.prototype.forEach.call(panel.querySelectorAll('[data-matchweek-range-action]'), function (button) {
                            button.addEventListener('click', function () {
                                var start = Number(panel.querySelector('[data-matchweek-range-start]').value);
                                var end = Number(panel.querySelector('[data-matchweek-range-end]').value);
                                if (start > end) { var swap = start; start = end; end = swap; }
                                var action = this.dataset.matchweekRangeAction;
                                boxes.forEach(function (box) {
                                    var week = Number(box.dataset.matchweek);
                                    var inRange = week >= start && week <= end;
                                    if (action === 'add' && inRange) box.checked = true;
                                    if (action === 'remove' && inRange) box.checked = false;
                                    // As with "Only Matchweek", retain exclusions already made
                                    // inside the range so independently chosen filters compose.
                                    if (action === 'only' && !inRange) box.checked = false;
                                });
                                updateSelectionStatus();
                            });
                        });
                        Array.prototype.forEach.call(panel.querySelectorAll('[data-team-rule]'), function (select) {
                            select.addEventListener('change', function () {
                                var teams = selectedTeamValues(this);
                                var result = this.dataset.ruleResult;
                                var include = this.dataset.ruleAction === 'add';
                                if (!teams.length) return;
                                boxes.forEach(function (box) {
                                    var isSelectedTeam = teams.indexOf(box.dataset.team) !== -1;
                                    var isHomeResult = box.dataset.resultSide === 'home';
                                    var isAwayResult = box.dataset.resultSide === 'away';
                                    var ruleParts = result.split('_');
                                    var ruleSide = ruleParts[0];
                                    var sideResult = ruleParts[1] || '';
                                    var teamMatches = isSelectedTeam;
                                    var matchesRule = (result === 'team' && teamMatches)
                                        || (result === 'home' && teamMatches && isHomeResult)
                                        || (result === 'away' && teamMatches && isAwayResult)
                                        || (ruleSide === 'home' && sideResult !== '' && teamMatches && isHomeResult && box.dataset.result === sideResult)
                                        || (ruleSide === 'away' && sideResult !== '' && teamMatches && isAwayResult && box.dataset.result === sideResult)
                                        || (ruleParts.length === 1 && result !== 'team' && result !== 'home' && result !== 'away'
                                            && teamMatches && box.dataset.result === result);
                                    if (matchesRule) box.checked = include;
                                });
                                updateSelectionStatus();
                            });
                        });
                        Array.prototype.forEach.call(panel.querySelectorAll('[data-result-filter-action]'), function (button) {
                            button.addEventListener('click', function () {
                                var section = this.closest('[data-result-filter-section]');
                                var selectedTeams = Array.prototype.filter.call(section.querySelectorAll('[data-team-filter-option]'), function (box) { return box.checked; }).map(function (box) { return box.value; });
                                var opponent = section.querySelector('[data-result-filter-opponent]').value;
                                var matchweek = section.querySelector('[data-result-filter-matchweek]').value;
                                var venue = section.querySelector('[data-result-filter-venue]').value;
                                var outcome = section.querySelector('[data-result-filter-outcome]').value;
                                var includeOnly = this.dataset.resultFilterAction === 'only';
                                boxes.forEach(function (box) {
                                    var matches = (!selectedTeams.length || selectedTeams.indexOf(box.dataset.team) !== -1)
                                        && (opponent === 'all' || box.dataset.opponent === opponent)
                                        && (matchweek === 'all' || box.dataset.matchweek === matchweek)
                                        && (venue === 'all' || box.dataset.resultSide === venue)
                                        && (outcome === 'all' || box.dataset.result === outcome);
                                    box.checked = includeOnly ? matches : !matches;
                                });
                                updateSelectionStatus();
                            });
                        });
                        panel.querySelector('[data-match-reset]').addEventListener('click', function () {
                            boxes.forEach(function (box) { box.checked = true; });
                            outcomeSelects.forEach(function (select) { select.value = 'actual'; });
                            deductionInputs.forEach(function (input) { input.value = 0; });
                            var url = new URL(window.location.href);
                            url.searchParams.delete('excluded_matches');
                            url.searchParams.delete('excluded_results');
                            url.searchParams.delete('outcome_overrides');
                            url.searchParams.delete('custom_points_deductions');
                            window.location.assign(url.toString());
                        });
                        boxes.forEach(function (box) {
                            box.addEventListener('change', updateSelectionStatus);
                        });
                        outcomeSelects.forEach(function (select) {
                            select.addEventListener('change', updateSelectionStatus);
                        });
                        deductionInputs.forEach(function (input) { input.addEventListener('input', updateSelectionStatus); });
                        function hasCurrentFixtureOutcome(select, outcome) {
                            if (outcome === 'all') return true;
                            var current = select.value === 'actual' ? select.dataset.actualOutcome : select.value;
                            return current === outcome;
                        }
                        function getCurrentTeamOutcome(select, isHomeTeam) {
                            var fixtureOutcome = select.value === 'actual' ? select.dataset.actualOutcome : select.value;
                            if (fixtureOutcome === 'actual') return 'unplayed';
                            if (fixtureOutcome === 'draw') return 'draw';
                            return (fixtureOutcome === 'home') === isHomeTeam ? 'win' : 'loss';
                        }
                        Array.prototype.forEach.call(panel.querySelectorAll('[data-bulk-outcome-apply]'), function (button) {
                            button.addEventListener('click', function () {
                                var scope = this.dataset.bulkOutcomeApply;
                                var matchweek = panel.querySelector('[data-bulk-outcome-matchweek]').value;
                                var teams = selectedTeamValues(panel.querySelector('[data-bulk-outcome-team]'));
                                var currentTeamOutcome = panel.querySelector('[data-bulk-team-current-outcome]').value;
                                var teamVenue = panel.querySelector('[data-bulk-team-venue]').value;
                                var teamOpponents = selectedTeamValues(panel.querySelector('[data-bulk-team-opponent]'));
                                var fixtureStatus = panel.querySelector('[data-bulk-fixture-status]').value;
                                var currentFixtureOutcome = scope === 'all'
                                    ? panel.querySelector('[data-bulk-current-outcome]').value
                                    : panel.querySelector('[data-bulk-matchweek-current-outcome]').value;
                                var outcome = scope === 'all'
                                    ? panel.querySelector('[data-bulk-outcome]').value
                                    : panel.querySelector(scope === 'matchweek' ? '[data-bulk-matchweek-outcome]' : '[data-bulk-team-outcome]').value;

                                outcomeSelects.forEach(function (select) {
                                    if (fixtureStatus !== 'all' && select.dataset.fixtureStatus !== fixtureStatus) return;
                                    if (scope === 'all' && !hasCurrentFixtureOutcome(select, currentFixtureOutcome)) return;
                                    if (scope === 'matchweek' && (select.dataset.matchweek !== matchweek || !hasCurrentFixtureOutcome(select, currentFixtureOutcome))) return;
                                    if (scope === 'team') {
                                        if (!teams.length) return;
                                        var homeSelected = teams.indexOf(select.dataset.homeTeam) !== -1;
                                        var awaySelected = teams.indexOf(select.dataset.awayTeam) !== -1;
                                        if (teamVenue === 'home' && !homeSelected) return;
                                        if (teamVenue === 'away' && !awaySelected) return;
                                        if (teamVenue === 'all' && !homeSelected && !awaySelected) return;
                                        // When both clubs are selected, use the requested venue;
                                        // otherwise the only selected participant is unambiguous.
                                        var isHomeTeam = teamVenue === 'home' || (teamVenue === 'all' && homeSelected);
                                        var opponent = isHomeTeam ? select.dataset.awayTeam : select.dataset.homeTeam;
                                        if (teamOpponents.length && teamOpponents.indexOf(opponent) === -1) return;
                                        var effectiveTeamOutcome = getCurrentTeamOutcome(select, isHomeTeam);
                                        if (currentTeamOutcome !== 'all' && effectiveTeamOutcome !== currentTeamOutcome) return;
                                        if (outcome === 'actual' || outcome === 'draw') {
                                            select.value = outcome;
                                        } else if (outcome === 'win') {
                                            select.value = isHomeTeam ? 'home' : 'away';
                                        } else {
                                            select.value = isHomeTeam ? 'away' : 'home';
                                        }
                                        return;
                                    }
                                    select.value = outcome;
                                });
                                updateSelectionStatus();
                            });
                        });
                        updateSelectionStatus();
                        panel.querySelector('[data-match-apply]').addEventListener('click', function () {
                            var excluded = boxes.filter(function (box) { return !box.checked; }).map(function (box) { return box.value; });
                            var url = new URL(window.location.href);
                            url.searchParams.delete('excluded_matches');
                            if (excluded.length) url.searchParams.set('excluded_results', excluded.join(','));
                            else url.searchParams.delete('excluded_results');
                            var outcomeCodes = { home: 'h', draw: 'd', away: 'a' };
                            var overrides = outcomeSelects.filter(function (select) {
                                return select.value !== 'actual';
                            }).map(function (select) {
                                return select.dataset.outcomeMatch + '-' + outcomeCodes[select.value];
                            });
                            if (overrides.length) url.searchParams.set('outcome_overrides', overrides.join(','));
                            else url.searchParams.delete('outcome_overrides');
                            var deductions = {};
                            deductionInputs.forEach(function (input) {
                                var points = Math.max(0, Math.min(100, Math.floor(Number(input.value) || 0)));
                                if (points) deductions[input.dataset.pointsDeduction] = points;
                            });
                            if (Object.keys(deductions).length) url.searchParams.set('custom_points_deductions', JSON.stringify(deductions));
                            else url.searchParams.delete('custom_points_deductions');
                            window.location.assign(url.toString());
                        });
                    }());
                    </script>
                <?php elseif ($calcMode === 'by_match'): ?>
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
                                $kickoff = football_stats_format_kickoff($m['match_timestamp'] ?? null, $m['match_date'] ?? null);
                                $label = "MW{$m['matchweek']} [$kickoff]: {$m['home_team']}{$score}{$m['away_team']}";
                            ?>
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_id' => $mId, 'pre_season' => null])); ?>" <?php echo (!$isPreSeason && $selectedMatchId === $mId) ? 'selected="selected"' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_id' => null, 'matchweek' => null, 'snapshot_date' => null, 'pre_season' => '1', 'table_filter' => null])); ?>" <?php echo $isPreSeason ? 'selected="selected"' : ''; ?>>
                                Pre-season
                            </option>
                        </select>
                    </div>
                    <?php football_stats_render_navigation_slider($matchSliderItems, $isPreSeason ? 'pre_season' : $selectedMatchId, 'Browse games', implode('|', [$controlId, 'after', $matchFilterMode, $selectedMatchweek, $selectedDate]), 'Move through the filtered games, then release to view the table after that result.'); ?>

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
                                $kickoff = football_stats_format_kickoff($m['match_timestamp'] ?? null, $m['match_date'] ?? null);
                                $label = "MW{$m['matchweek']} [$kickoff]: {$m['home_team']}{$score}{$m['away_team']}";
                            ?>
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_id' => $mId, 'pre_season' => null])); ?>" <?php echo (!$isPreSeason && $selectedMatchId === $mId) ? 'selected="selected"' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['match_id' => null, 'matchweek' => null, 'snapshot_date' => null, 'pre_season' => '1', 'table_filter' => null])); ?>" <?php echo $isPreSeason ? 'selected="selected"' : ''; ?>>
                                Pre-season
                            </option>
                        </select>
                    </div>
                    <?php football_stats_render_navigation_slider($matchSliderItems, $isPreSeason ? 'pre_season' : $selectedMatchId, 'Browse games', implode('|', [$controlId, 'before', $matchFilterMode, $selectedMatchweek, $selectedDate]), 'Move through the filtered games, then release to view the table before that result.'); ?>
                
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
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_date', 'snapshot_season' => $activeSeason, 'snapshot_date' => $date, 'pre_season' => null])); ?>" <?php echo (!$isPreSeason && $activeDate === (string)$date) ? 'selected="selected"' : ''; ?>>
                                <?php echo htmlspecialchars($date); ?><?php if ($dateMW) echo ' [' . htmlspecialchars($dateMW) . ']'; ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_date' => null, 'pre_season' => '1', 'table_filter' => null])); ?>" <?php echo $isPreSeason ? 'selected="selected"' : ''; ?>>Pre-season</option>
                    </select>
                </div>
                <?php football_stats_render_navigation_slider($dateSliderItems, $isPreSeason ? 'pre_season' : ($tableView['active_date'] ?? $selectedDate), 'Browse dates', $controlId . '|date', 'Move through available snapshot dates, then release to view the standings.'); ?>
                <?php else: ?>
                <!-- Dropdown 2 (By Matchweek): Matchweek Selection -->
                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-mw">Select Matchweek</label>
                    <select id="<?php echo $controlId; ?>-mw" class="table-view-select" onchange="window.location.href=this.value;">
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => 'live', 'matchweek' => null, 'pre_season' => null])); ?>" <?php echo (!$isSnapshot && !$isPreSeason) ? 'selected="selected"' : ''; ?>>
                            Latest Live Table
                        </option>
                        <?php
                        $activeMW = (int)($tableView['active_matchweek'] ?? 0);
                        foreach ($tableView['available_matchweeks'] as $mw):
                            $mwUrl = football_stats_build_table_view_url($tab, $league, $subtab, [
                                'table_view' => 'snapshot',
                                'matchweek' => $mw,
                                'snapshot_season' => $activeSeason,
                                'pre_season' => null,
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
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => 'snapshot', 'matchweek' => null, 'pre_season' => '1', 'table_filter' => null])); ?>" <?php echo $isPreSeason ? 'selected="selected"' : ''; ?>>Pre-season</option>
                    </select>
                </div>
                <?php football_stats_render_historic_league_table_slider($tableView, $tab, $league, $subtab); ?>
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
        $crestMap = football_stats_get_team_crest_map($db, $competitionCode, $seasonLabel, $liveTableName);

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
        $movementPreference = $_GET['movement_compare'] ?? 'relevant';
        if (!in_array($movementPreference, ['relevant', 'completed', 'custom_outcomes', 'custom_selection', 'off'], true)) {
            $movementPreference = 'relevant';
        }
        $movementStyle = $_GET['movement_style'] ?? 'detailed';
        $movementStyles = football_stats_get_movement_style_options();
        if (!isset($movementStyles[$movementStyle])) {
            $movementStyle = 'detailed';
        }

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
        $calcMode = $_GET['calc_mode'] ?? 'by_matchweek';
        $hasActiveFilter = $activeFilter !== 'all' && $activeFilter !== '';
        $movementOptions = football_stats_get_movement_preference_options($calcMode, $hasActiveFilter);
        // Preferences that do not apply to the current calculation resolve to
        // its contextually relevant default.
        if (!isset($movementOptions[$movementPreference])) {
            $movementPreference = 'relevant';
        }
        ?>
        <?php
        $preferenceParams = $_GET;
        unset($preferenceParams['movement_compare'], $preferenceParams['movement_style']);
        ?>
        <details class="movement-comparison-panel">
            <summary>
                <span>Show / hide movement arrow preferences</span>
                <small><?= htmlspecialchars($movementOptions[$movementPreference]['label'], ENT_QUOTES, 'UTF-8') ?></small>
            </summary>
            <form class="movement-preferences-form" method="get">
            <?php foreach ($preferenceParams as $key => $value): ?>
                <?php if (is_scalar($value)): ?>
                    <input type="hidden" name="<?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <fieldset class="movement-preference-group">
                <legend>Arrow comparison</legend>
                <div class="movement-comparison-options">
            <?php foreach ($movementOptions as $key => $option): ?>
                <label class="movement-preference-button <?= $movementPreference === $key ? 'is-active' : '' ?>">
                    <input type="radio" name="movement_compare" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $movementPreference === $key ? 'checked' : '' ?>>
                    <span><strong><?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($option['description'], ENT_QUOTES, 'UTF-8') ?></small></span>
                </label>
            <?php endforeach; ?>
                </div>
            </fieldset>
            <fieldset class="movement-preference-group">
                <legend>Column style</legend>
                <div class="movement-style-options">
                <?php foreach ($movementStyles as $key => $option): ?>
                    <label class="movement-preference-button movement-style-button <?= $movementStyle === $key ? 'is-active' : '' ?>">
                        <input type="radio" name="movement_style" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $movementStyle === $key ? 'checked' : '' ?>>
                        <span class="movement-style-preview movement-style-preview-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"><?= $key === 'detailed' ? '▲ Up 2 places' : '▲ 2' ?></span>
                        <span><strong><?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($option['description'], ENT_QUOTES, 'UTF-8') ?></small></span>
                    </label>
                <?php endforeach; ?>
                </div>
            </fieldset>
            <button class="movement-preferences-submit" type="submit">Apply movement preferences</button>
            </form>
        </details>
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
