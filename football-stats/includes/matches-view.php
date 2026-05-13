<?php
/**
 * Shared rendering for the matches tab across all leagues.
 *
 * Expected in scope when included:
 *   $db              – PDO instance (global from config.php via index.php)
 *   $matches_config  – array with keys: comp_code, league_label
 *   $currentMainTab, $currentLeague, $currentSubTab (globals from index.php)
 */

require_once __DIR__ . '/table-view.php';

$_mc_comp  = $matches_config['comp_code'];
$_mc_label = $matches_config['league_label'];

// ── Live season from metadata ─────────────────────────────────────────────────
$_mc_meta = $db->prepare('SELECT season_label FROM live_table_metadata WHERE competition_code = ?');
$_mc_meta->execute([$_mc_comp]);
$_mc_meta_row  = $_mc_meta->fetch(PDO::FETCH_ASSOC);
$_mc_live_season = $_mc_meta_row['season_label'] ?? ($currentMainTab ?? '2025-2026');

// ── Available seasons from matches table ──────────────────────────────────────
$_mc_s_stmt = $db->prepare(
    'SELECT DISTINCT season_label FROM matches WHERE competition_code = ? ORDER BY season_label DESC'
);
$_mc_s_stmt->execute([$_mc_comp]);
$_mc_available_seasons = $_mc_s_stmt->fetchAll(PDO::FETCH_COLUMN);

// ── Resolve requested season ──────────────────────────────────────────────────
$_mc_req_season = isset($_GET['snapshot_season'])
    ? preg_replace('/[^0-9\-]/', '', (string) $_GET['snapshot_season'])
    : '';

if ($_mc_req_season === '' || !in_array($_mc_req_season, $_mc_available_seasons, true)) {
    $_mc_req_season = $_mc_live_season;
    if (!in_array($_mc_req_season, $_mc_available_seasons, true) && !empty($_mc_available_seasons)) {
        $_mc_req_season = $_mc_available_seasons[0];
    }
}

$_mc_is_current = ($_mc_req_season === $_mc_live_season);

// ── Matchweeks for the selected season (with first date) ──────────────────────
$_mc_mw_stmt = $db->prepare(
    'SELECT matchweek, MIN(match_date) AS first_date
     FROM matches WHERE competition_code = ? AND season_label = ?
     GROUP BY matchweek ORDER BY matchweek ASC'
);
$_mc_mw_stmt->execute([$_mc_comp, $_mc_req_season]);
$_mc_mw_rows = $_mc_mw_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Selected matchweek filter ─────────────────────────────────────────────────
$_mc_sel_mw = (isset($_GET['matchweek']) && is_numeric($_GET['matchweek']))
    ? (int) $_GET['matchweek']
    : null;

// Validate: if the selected matchweek doesn't exist for this season, clear it
$_mc_valid_mws = array_column($_mc_mw_rows, 'matchweek');
if ($_mc_sel_mw !== null && !in_array((string) $_mc_sel_mw, array_map('strval', $_mc_valid_mws), true)) {
    $_mc_sel_mw = null;
}

// ── Fetch matches ─────────────────────────────────────────────────────────────
if ($_mc_sel_mw !== null) {
    $_mc_q = $db->prepare(
        'SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek = ?
         ORDER BY match_date ASC, id ASC'
    );
    $_mc_q->execute([$_mc_comp, $_mc_req_season, $_mc_sel_mw]);
} else {
    $_mc_q = $db->prepare(
        'SELECT * FROM matches WHERE competition_code = ? AND season_label = ?
         ORDER BY matchweek ASC, match_date ASC, id ASC'
    );
    $_mc_q->execute([$_mc_comp, $_mc_req_season]);
}
$_mc_matches = $_mc_q->fetchAll(PDO::FETCH_ASSOC);

// ── Summary stats ─────────────────────────────────────────────────────────────
$_mc_n_played    = 0;
$_mc_n_total     = count($_mc_matches);
foreach ($_mc_matches as $_m) {
    if (is_numeric($_m['home_goals']) && is_numeric($_m['away_goals'])) $_mc_n_played++;
}
$_mc_n_remaining = $_mc_n_total - $_mc_n_played;

// ── URL builder ───────────────────────────────────────────────────────────────
if (!function_exists('matches_view_build_url')) {
    function matches_view_build_url($season, $mw, $live_season) {
        global $currentMainTab, $currentLeague, $currentSubTab;
        $params = [
            'tab'    => $currentMainTab ?? '2025-2026',
            'league' => $currentLeague  ?? '',
            'subtab' => $currentSubTab  ?? 'matches',
        ];
        // Only include snapshot_season when it's not the live/current season
        if ($season !== $live_season) {
            $params['snapshot_season'] = $season;
        }
        if ($mw !== null) {
            $params['matchweek'] = (int) $mw;
        }
        return '?' . http_build_query($params);
    }
}

// ── Season label for display ──────────────────────────────────────────────────
$_mc_season_display = str_replace('-', '/', $_mc_req_season);
?>

<div class="panel">
    <h2>
        <?= htmlspecialchars($_mc_label, ENT_QUOTES, 'UTF-8') ?>
        <?= htmlspecialchars($_mc_season_display, ENT_QUOTES, 'UTF-8') ?> – Matches<?php
        if ($_mc_sel_mw !== null): ?> – MW<?= (int) $_mc_sel_mw ?><?php endif; ?>
    </h2>

    <!-- Season / matchweek selectors (styled like table-view-switcher) -->
    <div class="table-view-switcher">
        <div class="table-view-summary">
            <span class="table-view-pill">
                <?= $_mc_is_current ? 'Current Season' : 'Archive Season' ?>
            </span>
            <span>Season <?= htmlspecialchars($_mc_req_season, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($_mc_sel_mw !== null): ?>
                <span>Matchweek <?= (int) $_mc_sel_mw ?></span>
            <?php endif; ?>
            <?php if ($_mc_n_total > 0): ?>
                <span style="color:#888; font-size:12px;">
                    <?= $_mc_n_played ?> played
                    <?php if ($_mc_n_remaining > 0): ?> · <?= $_mc_n_remaining ?> remaining<?php endif; ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="table-view-actions">
            <!-- Season selector -->
            <?php if (!empty($_mc_available_seasons)): ?>
            <div class="table-view-group">
                <label class="table-view-label">Season</label>
                <select class="table-view-select" onchange="window.location.href=this.value;">
                    <?php
                    // "Current Season" option: URL without snapshot_season
                    $url_current = matches_view_build_url($_mc_live_season, null, $_mc_live_season);
                    ?>
                    <option value="<?= htmlspecialchars($url_current, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $_mc_is_current && $_mc_sel_mw === null ? 'selected' : '' ?>>
                        Current Season (<?= htmlspecialchars(str_replace('-', '/', $_mc_live_season), ENT_QUOTES, 'UTF-8') ?>)
                    </option>
                    <?php foreach ($_mc_available_seasons as $_mc_s): ?>
                        <?php if ($_mc_s === $_mc_live_season) continue; ?>
                        <?php $url_s = matches_view_build_url($_mc_s, null, $_mc_live_season); ?>
                        <option value="<?= htmlspecialchars($url_s, ENT_QUOTES, 'UTF-8') ?>"
                            <?= (!$_mc_is_current && $_mc_req_season === $_mc_s) ? 'selected' : '' ?>>
                            Season <?= htmlspecialchars($_mc_s, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Matchweek selector -->
            <?php if (!empty($_mc_mw_rows)): ?>
            <div class="table-view-group">
                <label class="table-view-label">Matchweek</label>
                <select class="table-view-select" onchange="window.location.href=this.value;">
                    <?php $url_all = matches_view_build_url($_mc_req_season, null, $_mc_live_season); ?>
                    <option value="<?= htmlspecialchars($url_all, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $_mc_sel_mw === null ? 'selected' : '' ?>>
                        All Matchweeks
                    </option>
                    <?php foreach ($_mc_mw_rows as $_mc_mwr): ?>
                        <?php
                        $url_mw = matches_view_build_url($_mc_req_season, $_mc_mwr['matchweek'], $_mc_live_season);
                        $mw_label = 'MW' . (int) $_mc_mwr['matchweek'];
                        if (!empty($_mc_mwr['first_date'])) {
                            $mw_label .= ' (' . htmlspecialchars($_mc_mwr['first_date'], ENT_QUOTES, 'UTF-8') . ')';
                        }
                        ?>
                        <option value="<?= htmlspecialchars($url_mw, ENT_QUOTES, 'UTF-8') ?>"
                            <?= ((int) $_mc_sel_mw === (int) $_mc_mwr['matchweek']) ? 'selected' : '' ?>>
                            <?= $mw_label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($_mc_matches)): ?>
        <p style="color:#888; margin-top:16px;">No matches found for this selection.</p>
    <?php else: ?>

    <div class="table-container">
    <table style="width:100%; font-size:13px; border-collapse:collapse;">
        <thead>
            <tr style="background:#222; color:#00ff88;">
                <?php if ($_mc_sel_mw === null): ?>
                    <th style="padding:8px 10px; text-align:center; white-space:nowrap;">MW</th>
                <?php endif; ?>
                <th style="padding:8px 10px; white-space:nowrap;">Date</th>
                <th style="padding:8px 10px; text-align:right;">Home</th>
                <th style="padding:8px 14px; text-align:center; min-width:70px;">Score</th>
                <th style="padding:8px 10px; text-align:left;">Away</th>
                <th style="padding:8px 10px; text-align:center; white-space:nowrap;">Source</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $prev_mw = null;
        foreach ($_mc_matches as $_mc_m):
            $played  = is_numeric($_mc_m['home_goals']) && is_numeric($_mc_m['away_goals']);
            $cur_mw  = (int) $_mc_m['matchweek'];

            // Matchweek group header (only when showing all matchweeks)
            if ($_mc_sel_mw === null && $cur_mw !== $prev_mw):
                $prev_mw = $cur_mw;
                // Find date for this MW
                $mw_date = '';
                foreach ($_mc_mw_rows as $_mwr) {
                    if ((int) $_mwr['matchweek'] === $cur_mw) { $mw_date = $_mwr['first_date']; break; }
                }
        ?>
        <tr>
            <td colspan="6" style="padding:10px 10px 4px; background:#1e2124; color:#5865F2; font-weight:700;
                                   font-size:12px; text-transform:uppercase; letter-spacing:.5px;
                                   border-top:1px solid #3a3a3a;">
                Matchweek <?= $cur_mw ?>
                <?php if ($mw_date): ?>
                    <span style="color:#888; font-weight:400; text-transform:none; letter-spacing:0;">
                        – <?= htmlspecialchars($mw_date, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
        <tr style="<?= $played ? '' : 'opacity:.65;' ?>">
            <?php if ($_mc_sel_mw === null): ?>
                <td style="padding:7px 10px; text-align:center; color:#555; font-size:11px;">
                    <?= $cur_mw ?>
                </td>
            <?php endif; ?>
            <td style="padding:7px 10px; color:#888; font-size:12px; white-space:nowrap;">
                <?= htmlspecialchars($_mc_m['match_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td style="padding:7px 10px; text-align:right; font-weight:<?= $played ? '600' : '400' ?>;">
                <?= htmlspecialchars($_mc_m['home_team'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td style="padding:7px 14px; text-align:center; font-family:monospace; font-size:14px;
                        font-weight:700; color:<?= $played ? '#dcddde' : '#555' ?>; white-space:nowrap;">
                <?php if ($played): ?>
                    <?= (int) $_mc_m['home_goals'] ?> – <?= (int) $_mc_m['away_goals'] ?>
                <?php else: ?>
                    <span style="font-weight:400; font-size:12px; font-family:inherit;">vs</span>
                <?php endif; ?>
            </td>
            <td style="padding:7px 10px; font-weight:<?= $played ? '600' : '400' ?>;">
                <?= htmlspecialchars($_mc_m['away_team'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td style="padding:7px 10px; text-align:center; color:#555; font-size:11px;">
                <?= htmlspecialchars($_mc_m['source'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php endif; ?>
</div>
