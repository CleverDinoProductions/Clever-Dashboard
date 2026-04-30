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
        return $params;
    }
}

/**
 * Fetch standing data (Live or Snapshot)
 */
if (!function_exists('football_stats_get_table_view')) {
    function football_stats_get_table_view(PDO $db, $competitionCode, $liveTableName, $fallbackSeasonLabel)
    {
        // Get Live metadata
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

        // Get available weeks for this specific league
        $snapshotWeeksStmt = $db->prepare('SELECT DISTINCT matchweek FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? ORDER BY matchweek DESC');
        $snapshotWeeksStmt->execute([$competitionCode, $requestedSeasonLabel]);
        $availableMatchweeks = array_map('intval', $snapshotWeeksStmt->fetchAll(PDO::FETCH_COLUMN));

        $requestedView = (isset($_GET['table_view']) && $_GET['table_view'] === 'snapshot') ? 'snapshot' : 'live';
        $requestedMatchweek = isset($_GET['matchweek']) ? (int)$_GET['matchweek'] : null;

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
            // Live View
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
 * Render the UI Controls
 */
require_once __DIR__ . '/table-view-date-helper.php';

if (!function_exists('football_stats_render_table_view_controls')) {
    function football_stats_render_table_view_controls(array $tableView, $tab, $league, $subtab)
    {
        // 1. Map all Leagues to Competition Codes dynamically
        $leagueMap = [
            'premier-league'  => 'PL',
            'championship'    => 'ELC',
            'league-one'      => 'L1',
            'league-two'      => 'L2',
            'national-league' => 'NL'
        ];
        $competitionCode = $leagueMap[$league] ?? strtoupper($league);

        $isSnapshot = !empty($tableView['is_snapshot_view']);
        $seasonLabel = htmlspecialchars((string)($tableView['active_season_label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $controlId = 'table-view-' . preg_replace('/[^a-z0-9\-]/i', '-', (string)$subtab);

        // Fetch display date for summary
        $summaryDate = '';
        if (!empty($tableView['active_matchweek']) && isset($GLOBALS['db']) && function_exists('football_stats_get_first_date_for_matchweek')) {
            $summaryDate = football_stats_get_first_date_for_matchweek($GLOBALS['db'], $competitionCode, $tableView['active_season_label'], $tableView['active_matchweek']);
        }

        ?>
        <style>
            .table-view-switcher { margin: 14px 0 16px; padding: 14px; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; background: rgba(255, 255, 255, 0.03); }
            .table-view-summary { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; color: #dcddde; font-size: 13px; }
            .table-view-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: rgba(88, 101, 242, 0.15); border: 1px solid rgba(88, 101, 242, 0.35); color: #c7d2fe; font-weight: 600; }
            .table-view-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
            .table-view-select { min-width: 220px; padding: 10px 12px; border-radius: 8px; background: #2f3136; border: 1px solid rgba(255, 255, 255, 0.08); color: #dcddde; font-size: 12px; font-weight: 600; cursor: pointer; }
            .table-view-current { color: #c7d2fe; font-size: 12px; font-weight: 600; }
            .table-view-note { margin-top: 12px; color: #9ca3af; font-size: 11px; font-style: italic; }
        </style>

        <div class="table-view-switcher">
            <div class="table-view-summary">
                <span class="table-view-pill"><?php echo $isSnapshot ? 'Snapshot view' : 'Live table'; ?></span>
                <span>Season <?php echo $seasonLabel; ?></span>
                <?php if (!empty($tableView['active_matchweek'])): ?>
                    <span>Matchweek <?php echo (int) $tableView['active_matchweek']; ?></span>
                <?php endif; ?>
                <?php if ($summaryDate): ?>
                    <span style="color:#00ff88;">(<?php echo htmlspecialchars($summaryDate); ?>)</span>
                <?php endif; ?>
            </div>

            <div class="table-view-actions">
                <label for="<?php echo htmlspecialchars($controlId); ?>" class="table-view-current">Choose view</label>
                <select id="<?php echo htmlspecialchars($controlId); ?>" class="table-view-select" onchange="if (this.value) { window.location.href = this.value; }">
                    <option value="<?php echo htmlspecialchars(football_stats_build_table_view_url($tab, $league, $subtab, ['table_view' => 'live'])); ?>" <?php echo !$isSnapshot ? 'selected' : ''; ?>>
                        Live table
                    </option>
                    <?php 
                    foreach ($tableView['available_matchweeks'] as $mw): 
                        $snapshotUrl = football_stats_build_table_view_url($tab, $league, $subtab, [
                            'table_view' => 'snapshot',
                            'matchweek' => $mw,
                            'snapshot_season' => $tableView['requested_season_label'],
                        ]);
                        
                        $mwDate = '';
                        if (isset($GLOBALS['db']) && function_exists('football_stats_get_first_date_for_matchweek')) {
                            $mwDate = football_stats_get_first_date_for_matchweek($GLOBALS['db'], $competitionCode, $tableView['requested_season_label'], $mw);
                        }
                    ?>
                        <option value="<?php echo htmlspecialchars($snapshotUrl); ?>" <?php echo ($isSnapshot && (int)$tableView['active_matchweek'] === (int)$mw) ? 'selected' : ''; ?>>
                            Matchweek <?php echo (int)$mw; ?><?php if ($mwDate) echo ' (' . htmlspecialchars($mwDate) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="table-view-note">
                Viewing archives for **<?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $league))); ?>**.
            </div>
        </div>
        <?php
    }
}