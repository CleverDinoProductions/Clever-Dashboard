<?php

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
                    <span>Matchweek <?php echo (int) $tableView['active_matchweek']; ?></span>
                <?php endif; ?>
                <?php if ($isByDate && !empty($tableView['active_date'])): ?>
                    <span><?php echo htmlspecialchars($tableView['active_date']); ?></span>
                <?php endif; ?>
                <?php if ($summaryDate): ?>
                    <span style="color:#00ff88;">(<?php echo htmlspecialchars($summaryDate); ?>)</span>
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
                        <?php foreach ($tableView['available_dates'] as $date): ?>
                            <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['calc_mode' => 'by_date', 'snapshot_season' => $tableView['requested_season_label'], 'snapshot_date' => $date])); ?>" <?php echo ($tableView['active_date'] === $date) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($date); ?>
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
                                Matchweek <?php echo (int)$mw; ?><?php if ($mwDate) echo ' (' . htmlspecialchars($mwDate) . ')'; ?>
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