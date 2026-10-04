<?php
// Include database configuration
require_once 'config.php';
require_once dirname(__DIR__) . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/football-settings.php';
$dashboardUser = clever_current_user();
$dashboardPreferences = $dashboardUser ? clever_user_preferences((int)$dashboardUser['id']) : [
    'default_season'=>'2025-2026', 'default_league'=>'premier-league', 'default_view'=>'table',
    'favourite_team'=>'', 'accent_color'=>'#FFD700', 'compact_navigation'=>0,
];
$dashboardSettings = clever_dashboard_settings($db);
$dashboardCanUpdate = $dashboardUser !== null && !empty($dashboardUser['can_update_data']);

// Tab model: tab = season/competition, league = league within that season, subtab = page within that league.
$seasonLabels = [
    '2025-2026' => '2025/26',
    '2026-2027' => '2026/27',
];

$seasonLeagueConfigs = [
    '2025-2026' => [
        'division-one' => [
            'label' => 'Division One',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/division-one/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/division-one/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/division-one/2025-2026/matches.php'],
                'compare' => ['label' => 'Compare', 'icon' => '🔀', 'file' => 'tabs/division-one/2025-2026/compare.php'],
                'compare-seasons' => ['label' => 'Season Compare', 'icon' => '↔️', 'file' => 'tabs/division-one/2025-2026/compare-seasons.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/division-one/2025-2026/blocks-overview.php'],
                'blocks-dynamic' => ['label' => 'Live Blocks', 'icon' => '🔥', 'file' => 'tabs/division-one/2025-2026/blocks-dynamic.php'],
                'blocks-1' => ['label' => 'Block 1', 'icon' => '🏆', 'file' => 'tabs/division-one/2025-2026/blocks-1.php'],
                'blocks-2' => ['label' => 'Block 2', 'icon' => '🎯', 'file' => 'tabs/division-one/2025-2026/blocks-2.php'],
                'blocks-3' => ['label' => 'Block 3', 'icon' => '⚖️', 'file' => 'tabs/division-one/2025-2026/blocks-3.php'],
                'blocks-4' => ['label' => 'Block 4', 'icon' => '⚠️', 'file' => 'tabs/division-one/2025-2026/blocks-4.php'],
                'blocks-5' => ['label' => 'Block 5', 'icon' => '🎯', 'file' => 'tabs/division-one/2025-2026/blocks-5.php'],
                'team-tracker' => ['label' => 'Team Tracker', 'icon' => '💛', 'file' => 'tabs/division-one/2025-2026/team-tracker.php'],
                'team-tracker-2' => ['label' => 'Team Dashboard', 'icon' => '📊', 'file' => 'tabs/division-one/2025-2026/team-tracker-2.php'],
                'team-tracker-3' => ['label' => 'Team Classic', 'icon' => '🎯', 'file' => 'tabs/division-one/2025-2026/team-tracker-3.php'],
                'whatifs' => ['label' => 'What-Ifs', 'icon' => '❓', 'file' => 'tabs/division-one/2025-2026/whatifs.php'],
                'simulation' => ['label' => 'Simulation', 'icon' => '🎲', 'file' => 'tabs/division-one/2025-2026/simulation.php'],
            ],
        ],
        'premier-league' => [
            'label' => 'Premier League',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/premier-league/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/premier-league/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/premier-league/2025-2026/matches.php'],
                'compare' => ['label' => 'Compare', 'icon' => '🔀', 'file' => 'tabs/premier-league/2025-2026/compare.php'],
                'compare-seasons' => ['label' => 'Season Compare', 'icon' => '↔️', 'file' => 'tabs/premier-league/2025-2026/compare-seasons.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/premier-league/2025-2026/blocks-overview.php'],
                'blocks-dynamic' => ['label' => 'Live Blocks', 'icon' => '🔥', 'file' => 'tabs/premier-league/2025-2026/blocks-dynamic.php'],
                'blocks-1' => ['label' => 'Block 1', 'icon' => '🏆', 'file' => 'tabs/premier-league/2025-2026/blocks-1.php'],
                'blocks-2' => ['label' => 'Block 2', 'icon' => '🌍', 'file' => 'tabs/premier-league/2025-2026/blocks-2.php'],
                'blocks-3' => ['label' => 'Block 3', 'icon' => '⚖️', 'file' => 'tabs/premier-league/2025-2026/blocks-3.php'],
                'blocks-4' => ['label' => 'Block 4', 'icon' => '⚠️', 'file' => 'tabs/premier-league/2025-2026/blocks-4.php'],
                'blocks-5' => ['label' => 'Block 5', 'icon' => '🔻', 'file' => 'tabs/premier-league/2025-2026/blocks-5.php'],
                'team-tracker' => ['label' => 'Team Tracker', 'icon' => '💛', 'file' => 'tabs/premier-league/2025-2026/team-tracker.php'],
                'team-tracker-2' => ['label' => 'Team Dashboard', 'icon' => '💙', 'file' => 'tabs/premier-league/2025-2026/team-tracker-2.php'],
                'team-tracker-3' => ['label' => 'Team Classic', 'icon' => '🤍', 'file' => 'tabs/premier-league/2025-2026/team-tracker-3.php'],
                'whatifs' => ['label' => 'What-Ifs', 'icon' => '❓', 'file' => 'tabs/premier-league/2025-2026/whatifs.php'],
                'simulation' => ['label' => 'Simulation', 'icon' => '🎲', 'file' => 'tabs/premier-league/2025-2026/simulation.php'],
            ],
        ],
        'championship' => [
            'label' => 'Championship',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/championship/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/championship/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/championship/2025-2026/matches.php'],
                'playoffs' => ['label' => 'Playoffs', 'icon' => '🎟️', 'file' => 'tabs/championship/2025-2026/playoffs.php'],
                'compare' => ['label' => 'Compare', 'icon' => '🔀', 'file' => 'tabs/championship/2025-2026/compare.php'],
                'compare-seasons' => ['label' => 'Season Compare', 'icon' => '↔️', 'file' => 'tabs/championship/2025-2026/compare-seasons.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/championship/2025-2026/blocks-overview.php'],
                'blocks-dynamic' => ['label' => 'Live Blocks', 'icon' => '🔥', 'file' => 'tabs/championship/2025-2026/blocks-dynamic.php'],
                'blocks-1' => ['label' => 'Block 1', 'icon' => '🏆', 'file' => 'tabs/championship/2025-2026/blocks-1.php'],
                'blocks-2' => ['label' => 'Block 2', 'icon' => '🎯', 'file' => 'tabs/championship/2025-2026/blocks-2.php'],
                'blocks-3' => ['label' => 'Block 3', 'icon' => '⚖️', 'file' => 'tabs/championship/2025-2026/blocks-3.php'],
                'blocks-4' => ['label' => 'Block 4', 'icon' => '⚠️', 'file' => 'tabs/championship/2025-2026/blocks-4.php'],
                'blocks-5' => ['label' => 'Block 5', 'icon' => '🔻', 'file' => 'tabs/championship/2025-2026/blocks-5.php'],
                'team-tracker' => ['label' => 'Team Tracker', 'icon' => '💛', 'file' => 'tabs/championship/2025-2026/team-tracker.php'],
                'team-tracker-2'   => ['label' => 'Team Dashboard', 'icon' => '💙', 'file' => 'tabs/championship/2025-2026/team-tracker-2.php'],
                'team-tracker-3'   => ['label' => 'Team Classic', 'icon' => '🤍', 'file' => 'tabs/championship/2025-2026/team-tracker-3.php'],
                'whatifs' => ['label' => 'What-Ifs', 'icon' => '❓', 'file' => 'tabs/championship/2025-2026/whatifs.php'],
                'simulation' => ['label' => 'Simulation', 'icon' => '🎲', 'file' => 'tabs/championship/2025-2026/simulation.php'],
            ],
        ],
        'league-one' => [
            'label' => 'League One',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/league-one/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/league-one/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/league-one/2025-2026/matches.php'],
                'playoffs' => ['label' => 'Playoffs', 'icon' => '🎟️', 'file' => 'tabs/league-one/2025-2026/playoffs.php'],
                'compare' => ['label' => 'Compare', 'icon' => '🔀', 'file' => 'tabs/league-one/2025-2026/compare.php'],
                'compare-seasons' => ['label' => 'Season Compare', 'icon' => '↔️', 'file' => 'tabs/league-one/2025-2026/compare-seasons.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/league-one/2025-2026/blocks-overview.php'],
                'blocks-dynamic' => ['label' => 'Live Blocks', 'icon' => '🔥', 'file' => 'tabs/league-one/2025-2026/blocks-dynamic.php'],
                'blocks-1' => ['label' => 'Block 1', 'icon' => '🏆', 'file' => 'tabs/league-one/2025-2026/blocks-1.php'],
                'blocks-2' => ['label' => 'Block 2', 'icon' => '🎯', 'file' => 'tabs/league-one/2025-2026/blocks-2.php'],
                'blocks-3' => ['label' => 'Block 3', 'icon' => '⚖️', 'file' => 'tabs/league-one/2025-2026/blocks-3.php'],
                'blocks-4' => ['label' => 'Block 4', 'icon' => '⚠️', 'file' => 'tabs/league-one/2025-2026/blocks-4.php'],
                'blocks-5' => ['label' => 'Block 5', 'icon' => '🔻', 'file' => 'tabs/league-one/2025-2026/blocks-5.php'],
                'team-tracker'   => ['label' => 'Team Tracker',   'icon' => '⚽', 'file' => 'tabs/league-one/2025-2026/team-tracker.php'],
                'team-tracker-2' => ['label' => 'Team Dashboard', 'icon' => '📊', 'file' => 'tabs/league-one/2025-2026/team-tracker-2.php'],
                'team-tracker-3' => ['label' => 'Team Classic',   'icon' => '🎯', 'file' => 'tabs/league-one/2025-2026/team-tracker-3.php'],
                'whatifs' => ['label' => 'What-Ifs', 'icon' => '❓', 'file' => 'tabs/league-one/2025-2026/whatifs.php'],
                'simulation' => ['label' => 'Simulation', 'icon' => '🎲', 'file' => 'tabs/league-one/2025-2026/simulation.php'],
            ],
        ],
        'league-two' => [
            'label' => 'League Two',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/league-two/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/league-two/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/league-two/2025-2026/matches.php'],
                'playoffs' => ['label' => 'Playoffs', 'icon' => '🎟️', 'file' => 'tabs/league-two/2025-2026/playoffs.php'],
                'compare' => ['label' => 'Compare', 'icon' => '🔀', 'file' => 'tabs/league-two/2025-2026/compare.php'],
                'compare-seasons' => ['label' => 'Season Compare', 'icon' => '↔️', 'file' => 'tabs/league-two/2025-2026/compare-seasons.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/league-two/2025-2026/blocks-overview.php'],
                'blocks-dynamic' => ['label' => 'Live Blocks', 'icon' => '🔥', 'file' => 'tabs/league-two/2025-2026/blocks-dynamic.php'],
                'blocks-1' => ['label' => 'Block 1', 'icon' => '🏆', 'file' => 'tabs/league-two/2025-2026/blocks-1.php'],
                'blocks-2' => ['label' => 'Block 2', 'icon' => '🎯', 'file' => 'tabs/league-two/2025-2026/blocks-2.php'],
                'blocks-3' => ['label' => 'Block 3', 'icon' => '⚖️', 'file' => 'tabs/league-two/2025-2026/blocks-3.php'],
                'blocks-4' => ['label' => 'Block 4', 'icon' => '⚠️', 'file' => 'tabs/league-two/2025-2026/blocks-4.php'],
                'blocks-5' => ['label' => 'Block 5', 'icon' => '🔻', 'file' => 'tabs/league-two/2025-2026/blocks-5.php'],
                'team-tracker'   => ['label' => 'Team Tracker',   'icon' => '⚽', 'file' => 'tabs/league-two/2025-2026/team-tracker.php'],
                'team-tracker-2' => ['label' => 'Team Dashboard', 'icon' => '📊', 'file' => 'tabs/league-two/2025-2026/team-tracker-2.php'],
                'team-tracker-3' => ['label' => 'Team Classic',   'icon' => '🎯', 'file' => 'tabs/league-two/2025-2026/team-tracker-3.php'],
                'whatifs' => ['label' => 'What-Ifs', 'icon' => '❓', 'file' => 'tabs/league-two/2025-2026/whatifs.php'],
                'simulation' => ['label' => 'Simulation', 'icon' => '🎲', 'file' => 'tabs/league-two/2025-2026/simulation.php'],
            ],
        ],
        'national-league' => [
            'label' => 'National League',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/national-league/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/national-league/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/national-league/2025-2026/matches.php'],
                'playoffs' => ['label' => 'Playoffs', 'icon' => '🎟️', 'file' => 'tabs/national-league/2025-2026/playoffs.php'],
                'compare' => ['label' => 'Compare', 'icon' => '🔀', 'file' => 'tabs/national-league/2025-2026/compare.php'],
                'compare-seasons' => ['label' => 'Season Compare', 'icon' => '↔️', 'file' => 'tabs/national-league/2025-2026/compare-seasons.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/national-league/2025-2026/blocks-overview.php'],
                'blocks-dynamic' => ['label' => 'Live Blocks', 'icon' => '🔥', 'file' => 'tabs/national-league/2025-2026/blocks-dynamic.php'],
                'blocks-1' => ['label' => 'Block 1', 'icon' => '🏆', 'file' => 'tabs/national-league/2025-2026/blocks-1.php'],
                'blocks-2' => ['label' => 'Block 2', 'icon' => '🎯', 'file' => 'tabs/national-league/2025-2026/blocks-2.php'],
                'blocks-3' => ['label' => 'Block 3', 'icon' => '⚖️', 'file' => 'tabs/national-league/2025-2026/blocks-3.php'],
                'blocks-4' => ['label' => 'Block 4', 'icon' => '⚠️', 'file' => 'tabs/national-league/2025-2026/blocks-4.php'],
                'blocks-5' => ['label' => 'Block 5', 'icon' => '🔻', 'file' => 'tabs/national-league/2025-2026/blocks-5.php'],
                'team-tracker'   => ['label' => 'Team Tracker',   'icon' => '⚽', 'file' => 'tabs/national-league/2025-2026/team-tracker.php'],
                'team-tracker-2' => ['label' => 'Team Dashboard', 'icon' => '📊', 'file' => 'tabs/national-league/2025-2026/team-tracker-2.php'],
                'team-tracker-3' => ['label' => 'Team Classic',   'icon' => '🎯', 'file' => 'tabs/national-league/2025-2026/team-tracker-3.php'],
                'whatifs' => ['label' => 'What-Ifs', 'icon' => '❓', 'file' => 'tabs/national-league/2025-2026/whatifs.php'],
                'simulation' => ['label' => 'Simulation', 'icon' => '🎲', 'file' => 'tabs/national-league/2025-2026/simulation.php'],
            ],
        ],
    ],
];

$worldCupTabs = [
    'groups' => ['label' => 'Groups', 'icon' => '🔢', 'file' => 'tabs/world-cup/groups.php'],
    'knockout' => ['label' => 'Knockout Stage', 'icon' => '⚔️', 'file' => 'tabs/world-cup/knockout.php'],
    'predictions' => ['label' => 'Predictions', 'icon' => '🔮', 'file' => 'tabs/world-cup/predictions.php'],
    'standings' => ['label' => 'Overall Rankings', 'icon' => '🏆', 'file' => 'tabs/world-cup/standings.php'],
    'simulation' => ['label' => 'Simulation', 'icon' => '🎲', 'file' => 'tabs/world-cup/simulation.php'],
];

$hasExplicitDestination = isset($_GET['tab']) || isset($_GET['league']) || isset($_GET['subtab']);
$currentMainTab = isset($_GET['tab']) ? $_GET['tab'] : ($dashboardPreferences['default_season'] ?: '2025-2026');
$currentLeague = isset($_GET['league']) ? $_GET['league'] : ($hasExplicitDestination ? null : $dashboardPreferences['default_league']);
$currentSubTab = isset($_GET['subtab']) ? $_GET['subtab'] : ($hasExplicitDestination ? null : $dashboardPreferences['default_view']);

if (!$hasExplicitDestination && $dashboardPreferences['favourite_team'] !== '' && !isset($_GET['tracker_team'])) {
    $_GET['tracker_team'] = $dashboardPreferences['favourite_team'];
}

$tabAliases = [
    '2025/26' => '2025-2026',
    '2026/27' => '2026-2027',
];

if (isset($tabAliases[$currentMainTab])) {
    $currentMainTab = $tabAliases[$currentMainTab];
}

if (in_array($currentMainTab, ['premier-league', 'championship'], true)) {
    $currentLeague = $currentMainTab;
    $currentMainTab = '2025-2026';
}

if ($currentMainTab !== 'world-cup' && isset($seasonLeagueConfigs[$currentMainTab]) && $currentLeague === null && $currentSubTab !== null && isset($seasonLeagueConfigs[$currentMainTab][$currentSubTab])) {
    $currentLeague = $currentSubTab;
    $currentSubTab = null;
}

if ($currentMainTab === 'world-cup') {
    $currentLeague = 'world-cup';

    if ($currentSubTab === null || !isset($worldCupTabs[$currentSubTab])) {
        $currentSubTab = 'groups';
    }
} else {
    if (!isset($seasonLeagueConfigs[$currentMainTab])) {
        $currentMainTab = '2025-2026';
    }

    $availableLeagues = $seasonLeagueConfigs[$currentMainTab];

    if ($currentLeague === null || !isset($availableLeagues[$currentLeague])) {
        $currentLeague = array_key_first($availableLeagues);
    }

    $currentLeagueConfig = $availableLeagues[$currentLeague];

    if ($currentSubTab === null || !isset($currentLeagueConfig['tabs'][$currentSubTab])) {
        $currentSubTab = $currentLeagueConfig['defaultSubTab'];
    }
}

$contentFile = null;
$placeholderTitle = null;
$placeholderMessage = null;

if ($currentMainTab === 'world-cup') {
    $contentFile = $worldCupTabs[$currentSubTab]['file'];
} else {
    $selectedTabConfig = $seasonLeagueConfigs[$currentMainTab][$currentLeague]['tabs'][$currentSubTab];
    $contentFile = $selectedTabConfig['file'];

    if (!is_file($contentFile)) {
        $placeholderTitle = ($seasonLabels[$currentMainTab] ?? $currentMainTab) . ' ' . $seasonLeagueConfigs[$currentMainTab][$currentLeague]['label'];
        $placeholderMessage = $selectedTabConfig['label'] . ' is not available yet for this season.';
        $contentFile = null;
    }
}

// Include header (which now contains tab navigation)
include 'includes/header.php';
?>

<!-- Main Content Area -->
<div class="content-wrapper">
    <?php
    if ($contentFile !== null) {
        try {
            include $contentFile;
        } catch (Throwable $exception) {
            // Keep the dashboard shell usable when a deployment is missing a
            // newly introduced table instead of returning a blank HTTP 500.
            error_log('Football dashboard view failed: ' . $exception->getMessage());
            ?>
            <section class="placeholder-panel" role="alert">
                <h2>Football data is temporarily unavailable</h2>
                <p>The dashboard could not read this view. Please try again later or ask an administrator to check the database configuration.</p>
            </section>
            <?php
        }
    } else {
        ?>
        <div class="panel">
            <h2><?php echo htmlspecialchars($placeholderTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p style="margin-top: 12px; color: #b9bbbe;">
                <?php echo htmlspecialchars($placeholderMessage, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
        <?php
    }
    ?>
</div>

<?php include 'includes/footer.php'; ?>
