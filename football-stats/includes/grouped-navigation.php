<?php
function football_stats_navigation_function(string $key): string
{
    if (str_starts_with($key,'blocks-')) return 'Season Blocks';
    if (str_starts_with($key,'team-tracker') || str_starts_with($key,'leeds')) return 'Team Tracking';
    if (in_array($key,['compare','compare-seasons','cross-season'],true)) return 'Comparisons';
    if (in_array($key,['simulation','whatifs','predictions'],true)) return 'Simulations & Predictions';
    return 'Tables & Results';
}
function football_stats_navigation_league_group(string $key): string
{
    require_once dirname(__DIR__, 2) . '/lib/english-pyramid.php';
    $regional = clever_regional_competitions()[$key] ?? null;
    if ($regional !== null) return 'Tier ' . $regional['tier'] . ' · ' . ($regional['tier'] === 6 ? 'National League North & South' : 'Regional leagues');
    return match($key) {
        'championship','league-one','league-two'=>'EFL',
        'premier-league'=>'Premier League · FA sanctioned',
        'national-league'=>'National League · FA pyramid',
        'division-one'=>'Historic Football League',
        default=>'Other competitions',
    };
}
function football_stats_render_grouped_navigation(array $configs,array $worldTabs,string $season,?string $league,string $activeTab,array $tableParams,array $trackerParams,string $storageScope): void
{
    $h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
    $englishSeason=$season==='world-cup'?'2025-2026':$season;
    $leagues=$configs[$englishSeason]??[]; $catalog=[];
    foreach($leagues as $leagueKey=>$config) foreach($config['tabs'] as $key=>$tab) {
        $params=football_stats_tab_supports_table_view($key)?$tableParams:[];
        if(str_starts_with($key,'team-tracker')||str_starts_with($key,'leeds'))$params=array_merge($params,$trackerParams);
        $catalog[$leagueKey.'/'.$key]=['label'=>$tab['label'],'icon'=>$tab['icon'],'context'=>$config['label'],'url'=>build_tab_url($englishSeason,$leagueKey,$key,$params),'active'=>$season!=='world-cup'&&$league===$leagueKey&&$activeTab===$key];
    }
    foreach($worldTabs as $key=>$tab)$catalog['world-cup/'.$key]=['label'=>$tab['label'],'icon'=>$tab['icon'],'context'=>'World Cup','url'=>build_tab_url('world-cup',null,$key),'active'=>$season==='world-cup'&&$activeTab===$key];
    $tabs=$season==='world-cup'?$worldTabs:($leagues[$league]['tabs']??[]);
    $functions=[];foreach($tabs as $key=>$tab)$functions[football_stats_navigation_function($key)][$key]=$tab;
    $leagueGroups=[];foreach($leagues as $key=>$config)$leagueGroups[football_stats_navigation_league_group($key)][$key]=$config;
    ?>
    <nav class="grouped-navigation" aria-label="Football dashboard navigation" data-grouped-navigation data-storage-scope="<?= $h($storageScope) ?>">
    <div class="navigation-tools"><span>Browse by competition or function</span><button type="button" data-nav-expand>Expand all</button><button type="button" data-nav-collapse>Collapse all</button><span data-nav-status role="status">Pins are saved in this browser.</span></div>
    <details class="navigation-group" data-nav-group="favorites" open><summary>★ Pinned tabs</summary><div class="navigation-links" data-nav-favorites></div><p data-nav-empty>Use ☆ beside a tab to pin it here.</p></details>
    <?php if($season!=='world-cup'): ?><div class="navigation-heading">League competitions</div><div class="navigation-grid">
    <?php foreach($leagueGroups as $name=>$members): $active=isset($members[$league]); ?><details class="navigation-group" data-nav-group="leagues-<?= $h(array_key_first($members)) ?>" <?= $active?'open':'' ?> data-active-group="<?= $active?'1':'0' ?>"><summary><?= $h($name) ?></summary><div class="navigation-links">
    <?php foreach($members as $key=>$config): ?><a class="pill-tab <?= $league===$key?'active':'' ?>" href="<?= $h(build_tab_url($season,$key,$config['defaultSubTab'])) ?>" <?= $league===$key?'aria-current="true"':'' ?>><?= $h($config['label']) ?></a><?php endforeach; ?>
    </div></details><?php endforeach; ?></div><?php endif; ?>
    <div class="navigation-heading"><?= $h($season==='world-cup'?'World Cup tools':($leagues[$league]['label']??'League').' tools') ?></div><div class="navigation-grid">
    <?php foreach($functions as $name=>$members): $active=isset($members[$activeTab]); ?><details class="navigation-group" data-nav-group="tools-<?= $h(strtolower(str_replace(' ','-',$name))) ?>" <?= $active?'open':'' ?> data-active-group="<?= $active?'1':'0' ?>"><summary><?= $h($name) ?> <small><?= count($members) ?></small></summary><div class="navigation-links">
    <?php foreach($members as $key=>$tab): $id=($season==='world-cup'?'world-cup':$league).'/'.$key; $entry=$catalog[$id]; ?><span class="navigation-tab"><a class="pill-tab <?= $entry['active']?'active':'' ?>" href="<?= $h($entry['url']) ?>" <?= $entry['active']?'aria-current="page"':'' ?>><span class="nav-icon"><?= $h($entry['icon']) ?></span> <?= $h($entry['label']) ?></a><button type="button" data-nav-pin="<?= $h($id) ?>" aria-label="Pin <?= $h($entry['label']) ?>" aria-pressed="false">☆</button></span><?php endforeach; ?>
    </div></details><?php endforeach; ?></div>
    <script type="application/json" data-nav-catalog><?= json_encode($catalog,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
    </nav>
    <link rel="stylesheet" href="assets/grouped-navigation.css">
    <script src="assets/grouped-navigation.js" defer></script>
    <?php
}
