<?php
// Include database configuration
require_once 'config.php';

// Tab model: tab = season/competition, league = league within that season, subtab = page within that league.
$seasonLabels = [
    '2025-2026' => '2025/26',
    '2026-2027' => '2026/27',
];

$seasonLeagueConfigs = [
    '2025-2026' => [
        'premier-league' => [
            'label' => 'Premier League',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/premier-league/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/premier-league/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/premier-league/2025-2026/matches.php'],
                'compare' => ['label' => 'Compare', 'icon' => '🔀', 'file' => 'tabs/premier-league/2025-2026/compare.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/premier-league/2025-2026/blocks-overview.php'],
                'blocks-dynamic' => ['label' => 'Live Blocks', 'icon' => '🔥', 'file' => 'tabs/premier-league/2025-2026/blocks-dynamic.php'],
                'blocks-1' => ['label' => 'Block 1', 'icon' => '🏆', 'file' => 'tabs/premier-league/2025-2026/blocks-1.php'],
                'blocks-2' => ['label' => 'Block 2', 'icon' => '🌍', 'file' => 'tabs/premier-league/2025-2026/blocks-2.php'],
                'blocks-3' => ['label' => 'Block 3', 'icon' => '⚖️', 'file' => 'tabs/premier-league/2025-2026/blocks-3.php'],
                'blocks-4' => ['label' => 'Block 4', 'icon' => '⚠️', 'file' => 'tabs/premier-league/2025-2026/blocks-4.php'],
                'blocks-5' => ['label' => 'Block 5', 'icon' => '🔻', 'file' => 'tabs/premier-league/2025-2026/blocks-5.php'],
                'leeds' => ['label' => 'Leeds Tracker', 'icon' => '💛', 'file' => 'tabs/premier-league/2025-2026/leeds.php'],
                'leeds-2' => ['label' => 'Leeds Dashboard', 'icon' => '💙', 'file' => 'tabs/premier-league/2025-2026/leeds2.php'],
                'leeds-3' => ['label' => 'Leeds Classic', 'icon' => '🤍', 'file' => 'tabs/premier-league/2025-2026/leeds3.php'],
                'whatifs' => ['label' => 'What-Ifs', 'icon' => '❓', 'file' => 'tabs/premier-league/2025-2026/whatifs.php'],
            ],
        ],
        'championship' => [
            'label' => 'Championship',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/championship/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/championship/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/championship/2025-2026/matches.php'],
                'compare' => ['label' => 'Compare', 'icon' => '🔀', 'file' => 'tabs/championship/2025-2026/compare.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/championship/2025-2026/blocks-overview.php'],
                'blocks-dynamic' => ['label' => 'Live Blocks', 'icon' => '🔥', 'file' => 'tabs/championship/2025-2026/blocks-dynamic.php'],
                'blocks-1' => ['label' => 'Block 1', 'icon' => '🏆', 'file' => 'tabs/championship/2025-2026/blocks-1.php'],
                'blocks-2' => ['label' => 'Block 2', 'icon' => '🎯', 'file' => 'tabs/championship/2025-2026/blocks-2.php'],
                'blocks-3' => ['label' => 'Block 3', 'icon' => '⚖️', 'file' => 'tabs/championship/2025-2026/blocks-3.php'],
                'blocks-4' => ['label' => 'Block 4', 'icon' => '⚠️', 'file' => 'tabs/championship/2025-2026/blocks-4.php'],
                'blocks-5' => ['label' => 'Block 5', 'icon' => '🔻', 'file' => 'tabs/championship/2025-2026/blocks-5.php'],
                'promotion' => ['label' => 'Promotion Race', 'icon' => '⬆️', 'file' => 'tabs/championship/2025-2026/promotion.php'],
                'playoff' => ['label' => 'Playoff Battle', 'icon' => '🎟️', 'file' => 'tabs/championship/2025-2026/playoff.php'],
                'relegation' => ['label' => 'Relegation Battle', 'icon' => '🚨', 'file' => 'tabs/championship/2025-2026/relegation.php'],
            ],
        ],
        'league-one' => [
            'label' => 'League One',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/league-one/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/league-one/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/league-one/2025-2026/matches.php'],
            ],
        ],
        'league-two' => [
            'label' => 'League Two',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/league-two/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/league-two/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/league-two/2025-2026/matches.php'],
            ],
        ],
        'national-league' => [
            'label' => 'National League',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/national-league/2025-2026/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/national-league/2025-2026/table2.php'],
                'matches' => ['label' => 'Matches', 'icon' => '📅', 'file' => 'tabs/national-league/2025-2026/matches.php'],
            ],
        ],
    ],
    '2026-2027' => [
        'premier-league' => [
            'label' => 'Premier League',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/premier-league/2026-2027/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/premier-league/2026-2027/table2.php'],
                'blocks-overview' => ['label' => 'Blocks of 4', 'icon' => '🧱', 'file' => 'tabs/premier-league/2026-2027/blocks-overview.php'],
                'relegation' => ['label' => 'Relegation Battle', 'icon' => '🚨', 'file' => 'tabs/premier-league/2026-2027/relegation.php'],
                'leeds' => ['label' => 'Leeds Tracker', 'icon' => '💛', 'file' => 'tabs/premier-league/2026-2027/leeds.php'],
            ],
        ],
        'championship' => [
            'label' => 'Championship',
            'defaultSubTab' => 'table',
            'tabs' => [
                'table' => ['label' => 'Regular Table', 'icon' => '📊', 'file' => 'tabs/championship/2026-2027/table.php'],
                'table-2' => ['label' => 'Deep Dive Table', 'icon' => '🔢', 'file' => 'tabs/championship/2026-2027/table2.php'],
                // Add matches tab for future season if/when file exists
            ],
        ],
    ],
];

$worldCupTabs = [
    'groups' => ['label' => 'Groups', 'icon' => '🔢', 'file' => 'tabs/world-cup/groups.php'],
    'knockout' => ['label' => 'Knockout Stage', 'icon' => '⚔️', 'file' => 'tabs/world-cup/knockout.php'],
    'predictions' => ['label' => 'Predictions', 'icon' => '🔮', 'file' => 'tabs/world-cup/predictions.php'],
    'standings' => ['label' => 'Overall Rankings', 'icon' => '🏆', 'file' => 'tabs/world-cup/standings.php'],
];

$currentMainTab = isset($_GET['tab']) ? $_GET['tab'] : '2025-2026';
$currentLeague = isset($_GET['league']) ? $_GET['league'] : null;
$currentSubTab = isset($_GET['subtab']) ? $_GET['subtab'] : null;

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
        include $contentFile;
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