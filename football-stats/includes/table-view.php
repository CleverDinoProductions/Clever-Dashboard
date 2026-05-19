<?php

require_once __DIR__ . '/table-view-date-helper.php';

/**
 * Build URL for table view navigation
 */
if (!function_exists('football_stats_build_table_view_url')) {
    function football_stats_build_table_view_url($tab, $league = null, $subtab = null, array $extraParams = [])
    {
        $params = ['tab' => $tab];

        if ($league !== null) {
            $params['league'] = $league;
        }

        if ($subtab !== null) {
            $params['subtab'] = $subtab;
        }

        foreach ($extraParams as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $params[$key] = $value;
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
                'simulation',
            ],
            true
        );
    }
}

/**
 * Helper to get current URL params safely
 * This prevents the "Fatal error: Call to undefined function" in header.php
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
        return $params;
    }
}

/**
 * Fetch standing data (Live or Snapshot)
 */
if (!function_exists('football_stats_get_table_view')) {
    function football_stats_get_table_view(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        // 1. Get Live metadata
        $metadataStmt = $db->prepare('SELECT season_label, matchweek, updated_at FROM live_table_metadata WHERE competition_code = ?');
        $metadataStmt->execute([$competitionCode]);
        $metadata = $metadataStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $liveSeasonLabel = $metadata['season_label'] ?? $fallbackSeasonLabel;
        $liveMatchweek = isset($metadata['matchweek']) ? (int)$metadata['matchweek'] : null;

        // 2. Determine requested season
        $requestedSeasonLabel = isset($_GET['snapshot_season'])
            ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_season'])
            : $liveSeasonLabel;

        if ($requestedSeasonLabel === '') {
            $requestedSeasonLabel = $liveSeasonLabel;
        }

        // 3. Get available seasons for this specific league
        $snapshotSeasonsStmt = $db->prepare('SELECT DISTINCT season_label FROM league_table_snapshots WHERE competition_code = ? ORDER BY season_label DESC');
        $snapshotSeasonsStmt->execute([$competitionCode]);
        $availableSeasons = $snapshotSeasonsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($availableSeasons) && !in_array($requestedSeasonLabel, $availableSeasons, true)) {
            $requestedSeasonLabel = $liveSeasonLabel;
        }

        // 4. Get available weeks for the requested season
        $snapshotWeeksStmt = $db->prepare('SELECT DISTINCT matchweek FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? ORDER BY matchweek DESC');
        $snapshotWeeksStmt->execute([$competitionCode, $requestedSeasonLabel]);
        $availableMatchweeks = array_map('intval', $snapshotWeeksStmt->fetchAll(PDO::FETCH_COLUMN));

        $requestedView = (isset($_GET['table_view']) && $_GET['table_view'] === 'snapshot') ? 'snapshot' : 'live';
        $requestedMatchweek = isset($_GET['matchweek']) ? (int)$_GET['matchweek'] : null;

        // Default to latest matchweek if season is picked but matchweek is missing
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
 * Fetch standings calculated in order of match date (handles postponed matches correctly)
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
                : $availableSeasons[0];
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
            // Default to latest available date snapshot
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
 * Fetch standings for either calc mode based on $_GET['calc_mode']
 * Returns tableView array with an added 'calc_mode' key.
 */
if (!function_exists('football_stats_get_table_view_combined')) {
    function football_stats_get_table_view_combined(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        $calcMode = (($_GET['calc_mode'] ?? '') === 'by_date') ? 'by_date' : 'by_matchweek';
        if ($calcMode === 'by_date') {
            $tableView = football_stats_get_table_view_by_date($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        } else {
            $tableView = football_stats_get_table_view($db, $competitionCode, $liveTableName, $fallbackSeasonLabel);
        }
        $tableView['calc_mode'] = $calcMode;
        return $tableView;
    }
}

/**
 * Render the appropriate controls based on tableView['calc_mode']
 */
if (!function_exists('football_stats_render_combined_table_controls')) {
    function football_stats_render_combined_table_controls(array $tableView, $tab, $league, $subtab)
    {
        football_stats_render_table_view_controls($tableView, $tab, $league, $subtab);
    }
}

if (!function_exists('football_stats_render_matches_controls')) {
    function football_stats_render_matches_controls(array $availableSeasons, array $availableMatchweeks, $selectedSeason, $selectedMatchweek, $tab, $league, $subtab)
    {
        $controlId = 'matches-view-' . preg_replace('/[^a-z0-9\-]/i', '-', (string)$subtab);
        ?>
        <div class="table-view-switcher">
            <div class="table-view-summary">
                <span class="table-view-pill">Matches</span>
                <span>Season <?php echo htmlspecialchars((string)$selectedSeason); ?></span>
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
                            <option value="<?php echo htmlspecialchars($seasonUrl); ?>" <?php echo ($selectedSeason === (string)$season) ? 'selected' : ''; ?>>
                                Season <?php echo htmlspecialchars((string)$season); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-mw">Select Matchweek</label>
                    <select id="<?php echo $controlId; ?>-mw" class="table-view-select" onchange="window.location.href=this.value;">
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['snapshot_season' => $selectedSeason])); ?>" <?php echo ($selectedMatchweek === '') ? 'selected' : ''; ?>>
                            All Matchweeks
                        </option>
                        <?php foreach ($availableMatchweeks as $mw):
                            $mwUrl = football_stats_build_table_view_url($tab, $league, $subtab, [
                                'snapshot_season' => $selectedSeason,
                                'matchweek' => $mw,
                            ]);
                        ?>
                            <option value="<?php echo htmlspecialchars($mwUrl); ?>" <?php echo ($selectedMatchweek !== '' && (int)$selectedMatchweek === (int)$mw) ? 'selected' : ''; ?>>
                                Matchweek <?php echo (int)$mw; ?>
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
 * Compute filtered standings from the matches table.
 * Supports filters: first_half, second_half, home, away.
 */
if (!function_exists('football_stats_compute_filtered_standings')) {
    function football_stats_compute_filtered_standings(PDO $db, $competitionCode, $seasonLabel, $filter, $halfwayMatchweek, $liveTableName, $maxRegularMW = null, $maxDate = null)
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

        // Optional date cap (for "By Date" mode — only count matches up to the selected date)
        $dateCap = ($maxDate !== null && $maxDate !== '') ? $maxDate : null;

        // Fetch relevant matches (exclude playoff matches: mw=0 or mw>maxRegularMW)
        if ($filter === 'first_half' || $filter === 'first_half_home' || $filter === 'first_half_away') {
            // Cap at both the halfway point and the archive matchweek (if provided)
            $mwCap = ($maxRegularMW !== null) ? min($halfwayMatchweek, $maxRegularMW) : $halfwayMatchweek;
            $sql = "SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek <= ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL";
            $params = [$competitionCode, $seasonLabel, $mwCap];
            if ($dateCap !== null) { $sql .= " AND match_date <= ?"; $params[] = $dateCap; }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } elseif ($filter === 'second_half' || $filter === 'second_half_home' || $filter === 'second_half_away') {
            if ($maxRegularMW !== null) {
                $sql = "SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek > ? AND matchweek <= ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL";
                $params = [$competitionCode, $seasonLabel, $halfwayMatchweek, $maxRegularMW];
                if ($dateCap !== null) { $sql .= " AND match_date <= ?"; $params[] = $dateCap; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            } else {
                $sql = "SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek > ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL";
                $params = [$competitionCode, $seasonLabel, $halfwayMatchweek];
                if ($dateCap !== null) { $sql .= " AND match_date <= ?"; $params[] = $dateCap; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            }
        } else {
            if ($maxRegularMW !== null) {
                $sql = "SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND matchweek <= ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL";
                $params = [$competitionCode, $seasonLabel, $maxRegularMW];
                if ($dateCap !== null) { $sql .= " AND match_date <= ?"; $params[] = $dateCap; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            } else {
                $sql = "SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek >= 1 AND home_goals IS NOT NULL AND away_goals IS NOT NULL";
                $params = [$competitionCode, $seasonLabel];
                if ($dateCap !== null) { $sql .= " AND match_date <= ?"; $params[] = $dateCap; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            }
        }
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Seed every team in the competition for this season with zero stats so that
        // teams with no games on the counted side (e.g. no home games in MW20-21) still
        // appear in the table rather than being silently omitted.
        $stats = [];
        try {
            $allTeamsStmt = $db->prepare(
                "SELECT DISTINCT home_team AS team FROM matches WHERE competition_code = ? AND season_label = ? " .
                "UNION SELECT DISTINCT away_team AS team FROM matches WHERE competition_code = ? AND season_label = ?"
            );
            $allTeamsStmt->execute([$competitionCode, $seasonLabel, $competitionCode, $seasonLabel]);
            foreach ($allTeamsStmt->fetchAll(PDO::FETCH_COLUMN) as $team) {
                $stats[$team] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
            }
        } catch (Exception $e) {}

        if (empty($matches) && empty($stats)) {
            return [];
        }

        foreach ($matches as $m) {
            $hg   = (int)$m['home_goals'];
            $ag   = (int)$m['away_goals'];
            $home = $m['home_team'];
            $away = $m['away_team'];

            if (!isset($stats[$home])) $stats[$home] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
            if (!isset($stats[$away])) $stats[$away] = ['p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];

            if ($filter === 'home' || $filter === 'first_half_home' || $filter === 'second_half_home') {
                $stats[$home]['p']++;
                $stats[$home]['gf'] += $hg;
                $stats[$home]['ga'] += $ag;
                if ($hg > $ag)      { $stats[$home]['w']++; $stats[$home]['pts'] += 3; }
                elseif ($hg < $ag)  { $stats[$home]['l']++; }
                else                { $stats[$home]['d']++; $stats[$home]['pts']++; }
            } elseif ($filter === 'away' || $filter === 'first_half_away' || $filter === 'second_half_away') {
                $stats[$away]['p']++;
                $stats[$away]['gf'] += $ag;
                $stats[$away]['ga'] += $hg;
                if ($ag > $hg)      { $stats[$away]['w']++; $stats[$away]['pts'] += 3; }
                elseif ($ag < $hg)  { $stats[$away]['l']++; }
                else                { $stats[$away]['d']++; $stats[$away]['pts']++; }
            } else {
                // first_half or second_half — count both sides
                $stats[$home]['p']++; $stats[$away]['p']++;
                $stats[$home]['gf'] += $hg; $stats[$home]['ga'] += $ag;
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
            if ($b['gf'] !== $a['gf']) return $b['gf'] - $a['gf'];
            return $a['p'] <=> $b['p']; // teams with fewer games played sort lower when otherwise tied
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
            'all'              => 'All',
            'first_half'       => '1st Half',
            'second_half'      => '2nd Half',
            'home'             => 'Home',
            'away'             => 'Away',
            'first_half_home'  => '1st Half Home',
            'first_half_away'  => '1st Half Away',
            'second_half_home' => '2nd Half Home',
            'second_half_away' => '2nd Half Away',
        ];
        $filterLabels = [
            'all'              => 'Full season standings',
            'first_half'       => 'Standings based on matchweeks in the first half of the season',
            'second_half'      => 'Standings based on matchweeks in the second half of the season',
            'home'             => 'Standings based on home matches only',
            'away'             => 'Standings based on away matches only',
            'first_half_home'  => 'Standings based on home matches in the first half of the season',
            'first_half_away'  => 'Standings based on away matches in the first half of the season',
            'second_half_home' => 'Standings based on home matches in the second half of the season',
            'second_half_away' => 'Standings based on away matches in the second half of the season',
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

if (!function_exists('football_stats_render_table_view_controls')) {
    function football_stats_render_table_view_controls(array $tableView, $tab, $league, $subtab)
    {
        $leagueMap = [
            'premier-league'  => 'PL',
            'championship'    => 'ELC',
            'league-one'      => 'L1',
            'league-two'      => 'L2',
            'national-league' => 'NL'
        ];
        $competitionCode = $leagueMap[$league] ?? strtoupper($league);

        $isByDate  = (($tableView['calc_mode'] ?? 'by_matchweek') === 'by_date');
        $isSnapshot = !empty($tableView['is_snapshot_view']);
        $seasonLabel = htmlspecialchars((string)($tableView['active_season_label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $controlId = 'table-view-' . preg_replace('/[^a-z0-9\-]/i', '-', (string)$subtab);

        $summaryDate = '';
        if (!$isByDate && !empty($tableView['active_matchweek']) && isset($GLOBALS['db']) && function_exists('football_stats_get_first_date_for_matchweek')) {
            $summaryDate = football_stats_get_first_date_for_matchweek($GLOBALS['db'], $competitionCode, $tableView['active_season_label'], $tableView['active_matchweek']);
        }

        $summaryMW = '';
        if ($isByDate && !empty($tableView['active_date']) && isset($GLOBALS['db']) && function_exists('football_stats_get_matchweek_for_date')) {
            $summaryMW = football_stats_get_matchweek_for_date($GLOBALS['db'], $competitionCode, $tableView['active_season_label'], $tableView['active_date']);
        }

        ?>
        <style>
            .table-view-switcher { margin: 14px 0 16px; padding: 14px; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; background: rgba(255, 255, 255, 0.03); }
            .table-view-summary { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; color: #dcddde; font-size: 13px; }
            .table-view-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: rgba(88, 101, 242, 0.15); border: 1px solid rgba(88, 101, 242, 0.35); color: #c7d2fe; font-weight: 600; }
            .table-view-actions { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
            .table-view-group { display: flex; flex-direction: column; gap: 4px; }
            .table-view-select { min-width: 200px; padding: 10px 12px; border-radius: 8px; background: #2f3136; border: 1px solid rgba(255, 255, 255, 0.08); color: #dcddde; font-size: 12px; font-weight: 600; cursor: pointer; }
            .table-view-label { color: #8e9297; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        </style>

        <div class="table-view-switcher">
            <div class="table-view-summary">
                <span class="table-view-pill"><?php echo $isByDate ? 'By Date' : ($isSnapshot ? 'Archive View' : 'Live Table'); ?></span>
                <span>Season <?php echo $seasonLabel; ?></span>
                <?php if (!$isByDate && !empty($tableView['active_matchweek'])): ?>
                    <span>Matchweek <?php echo (int) $tableView['active_matchweek']; ?><?php if ($summaryDate): ?> <strong style="color:#00ff88; font-size:12px;">[<?php echo htmlspecialchars($summaryDate); ?>]</strong><?php endif; ?></span>
                <?php endif; ?>
                <?php if ($isByDate && !empty($tableView['active_date'])): ?>
                    <span><?php echo htmlspecialchars($tableView['active_date']); ?><?php if ($summaryMW): ?> <strong style="color:#00ff88; font-size:12px;">[<?php echo htmlspecialchars($summaryMW); ?>]</strong><?php endif; ?></span>
                <?php endif; ?>
            </div>

            <div class="table-view-actions">
                <!-- Dropdown 0: Calculation Mode -->
                <div class="table-view-group">
                    <label class="table-view-label">Calculation Mode</label>
                    <select class="table-view-select" onchange="window.location.href=this.value;">
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => $isSnapshot ? 'snapshot' : 'live', 'snapshot_season' => $tableView['requested_season_label'], 'matchweek' => $isSnapshot ? ($tableView['active_matchweek'] ?? null) : null])); ?>" <?php echo !$isByDate ? 'selected' : ''; ?>>
                            By Matchweek (original)
                        </option>
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_date', 'snapshot_season' => $tableView['requested_season_label']])); ?>" <?php echo $isByDate ? 'selected' : ''; ?>>
                            By Date (postponed-aware)
                        </option>
                    </select>
                </div>

                <!-- Dropdown 1: Season Selection -->
                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-season">Select Season</label>
                    <select id="<?php echo $controlId; ?>-season" class="table-view-select" onchange="window.location.href=this.value;">
                        <?php if ($isByDate): ?>
                            <?php foreach ($tableView['available_seasons'] as $season): ?>
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_date', 'snapshot_season' => $season])); ?>" <?php echo ($tableView['requested_season_label'] === $season) ? 'selected' : ''; ?>>
                                    Season <?php echo htmlspecialchars($season); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => 'live'])); ?>" <?php echo (!$isSnapshot && $tableView['requested_season_label'] === $tableView['live_season_label']) ? 'selected' : ''; ?>>
                                Current Season (Live)
                            </option>
                            <?php foreach ($tableView['available_seasons'] as $season): ?>
                                <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => 'snapshot', 'snapshot_season' => $season])); ?>" <?php echo ($tableView['requested_season_label'] === $season) ? 'selected' : ''; ?>>
                                    Season <?php echo htmlspecialchars($season); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if ($isByDate): ?>
                <!-- Dropdown 2 (By Date): Date Selection -->
                <div class="table-view-group">
                    <label class="table-view-label" for="<?php echo $controlId; ?>-date">Select Date</label>
                    <select id="<?php echo $controlId; ?>-date" class="table-view-select" onchange="window.location.href=this.value;">
                        <?php foreach ($tableView['available_dates'] as $date):
                            $dateMW = '';
                            if (isset($GLOBALS['db']) && function_exists('football_stats_get_matchweek_for_date')) {
                                $dateMW = football_stats_get_matchweek_for_date($GLOBALS['db'], $competitionCode, $tableView['requested_season_label'], $date);
                            }
                        ?>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_date', 'snapshot_season' => $tableView['requested_season_label'], 'snapshot_date' => $date])); ?>" <?php echo ($tableView['active_date'] === $date) ? 'selected' : ''; ?>>
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
                        <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => 'live'])); ?>" <?php echo !$isSnapshot ? 'selected' : ''; ?>>
                            Latest Live Table
                        </option>
                        <?php
                        foreach ($tableView['available_matchweeks'] as $mw):
                            $mwUrl = football_stats_build_table_view_url($tab, $league, $subtab, [
                                'table_view' => 'snapshot',
                                'matchweek' => $mw,
                                'snapshot_season' => $tableView['requested_season_label'],
                            ]);

                            $mwDate = '';
                            if (isset($GLOBALS['db']) && function_exists('football_stats_get_first_date_for_matchweek')) {
                                $mwDate = football_stats_get_first_date_for_matchweek($GLOBALS['db'], $competitionCode, $tableView['requested_season_label'], $mw);
                            }
                        ?>
                            <option value="<?php echo htmlspecialchars($mwUrl); ?>" <?php echo ($isSnapshot && (int)$tableView['active_matchweek'] === (int)$mw) ? 'selected' : ''; ?>>
                                    Matchweek <?php echo (int)$mw; ?><?php if ($mwDate) echo ' [' . htmlspecialchars($mwDate) . ']'; ?>
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