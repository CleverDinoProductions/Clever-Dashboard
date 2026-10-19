<?php
/** UEFA Nations League editions and routes, independent of domestic seasons. */
function football_stats_nations_league_editions(): array
{
    return ['2026-2027'=>'2026/27', '2024-2025'=>'2024/25', '2022-2023'=>'2022/23', '2020-2021'=>'2020/21', '2018-2019'=>'2018/19'];
}

function football_stats_nations_league_tabs(string $edition, string $league): array
{
    $tabs = [
        'groups'=>['label'=>'Groups & Standings', 'icon'=>'📊'],
        'matches'=>['label'=>'Fixtures & Results', 'icon'=>'📅'],
    ];
    if ($league === 'league-a') $tabs['finals'] = ['label'=>'Finals', 'icon'=>'🏆'];
    if (($edition >= '2024-2025') || (in_array($edition, ['2020-2021', '2022-2023'], true) && $league === 'league-c')) {
        $tabs['playoffs'] = ['label'=>'Play-offs', 'icon'=>'🎟️'];
    }
    $tabs['format'] = ['label'=>'Format & Rules', 'icon'=>'📖'];
    return $tabs;
}

function football_stats_nations_league_route(array $query): array
{
    $editions = football_stats_nations_league_editions();
    $edition = is_string($query['tournament'] ?? null) && isset($editions[$query['tournament']]) ? $query['tournament'] : array_key_first($editions);
    $league = is_string($query['league'] ?? null) && in_array($query['league'], ['league-a','league-b','league-c','league-d'], true) ? $query['league'] : 'league-a';
    $tabs = football_stats_nations_league_tabs($edition, $league);
    $view = is_string($query['subtab'] ?? null) && isset($tabs[$query['subtab']]) ? $query['subtab'] : 'groups';
    return ['tournament'=>$edition, 'league'=>$league, 'subtab'=>$view];
}

function football_stats_render_nations_league_navigation(string $edition, string $league, string $view): void
{
    $h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    ?>
    <section class="nations-navigation" aria-label="Nations League navigation">
        <form method="get" class="pill-nav">
            <input type="hidden" name="tab" value="nations-league">
            <input type="hidden" name="league" value="<?= $h($league) ?>">
            <input type="hidden" name="subtab" value="<?= $h($view) ?>">
            <label for="nations-tournament">Tournament (including historic editions)</label>
            <select id="nations-tournament" name="tournament">
                <?php foreach (football_stats_nations_league_editions() as $key=>$label): ?>
                <option value="<?= $h($key) ?>" <?= $edition === $key ? 'selected' : '' ?>><?= $h($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="pill-tab">View tournament</button>
        </form>
        <nav class="pill-nav" aria-label="Nations League leagues">
            <?php foreach (['league-a'=>'League A','league-b'=>'League B','league-c'=>'League C','league-d'=>'League D'] as $key=>$label):
                $targetView = isset(football_stats_nations_league_tabs($edition, $key)[$view]) ? $view : 'groups'; ?>
            <a class="pill-tab <?= $league === $key ? 'active' : '' ?>" <?= $league === $key ? 'aria-current="true"' : '' ?> href="<?= $h(build_tab_url('nations-league', $key, $targetView, ['tournament'=>$edition])) ?>"><?= $h($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <nav class="pill-nav" aria-label="Nations League views">
            <?php foreach (football_stats_nations_league_tabs($edition, $league) as $key=>$tab): ?>
            <a class="pill-tab <?= $view === $key ? 'active' : '' ?>" <?= $view === $key ? 'aria-current="page"' : '' ?> href="<?= $h(build_tab_url('nations-league', $league, $key, ['tournament'=>$edition])) ?>"><?= $h($tab['icon'].' '.$tab['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </section>
    <?php
}
