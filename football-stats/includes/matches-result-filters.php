<?php
/** Custom-rules style selectors for fixture lists. */
function football_stats_matches_result_filter_request(array $query): array
{
    $list = static fn($key) => isset($query[$key]) && is_array($query[$key]) ? array_values(array_filter($query[$key], 'is_string')) : [];
    return [
        'mode' => in_array($query['mf_mode'] ?? null, ['only','exclude'], true) ? $query['mf_mode'] : '',
        'teams' => $list('mf_teams'), 'opponents' => $list('mf_opponents'),
        'week' => isset($query['mf_week']) && is_scalar($query['mf_week']) ? max(0,(int)$query['mf_week']) : 0,
        'venue' => in_array($query['mf_venue'] ?? null, ['home','away'], true) ? $query['mf_venue'] : 'all',
        'result' => in_array($query['mf_result'] ?? null, ['win','draw','loss'], true) ? $query['mf_result'] : 'all',
    ];
}

function football_stats_filter_match_results(array $matches, array $filter): array
{
    if (!$filter['mode']) return $matches;
    $normalize = 'football_stats_normalize_group_team';
    $teams = array_map($normalize,$filter['teams']); $opponents = array_map($normalize,$filter['opponents']);
    return array_values(array_filter($matches, static function ($m) use ($filter,$teams,$opponents,$normalize) {
        $matching = false;
        if (!$filter['week'] || (int)$m['matchweek'] === $filter['week']) {
            foreach (['home','away'] as $side) {
                if ($filter['venue'] !== 'all' && $filter['venue'] !== $side) continue;
                $other = $side === 'home' ? 'away' : 'home';
                if ($teams && !in_array($normalize($m[$side.'_team']),$teams,true)) continue;
                if ($opponents && !in_array($normalize($m[$other.'_team']),$opponents,true)) continue;
                if ($filter['result'] !== 'all') {
                    if ($m['home_goals'] === null || $m['away_goals'] === null) continue;
                    $for = (int)$m[$side.'_goals']; $against = (int)$m[$other.'_goals'];
                    $result = $for > $against ? 'win' : ($for === $against ? 'draw' : 'loss');
                    if ($result !== $filter['result']) continue;
                }
                $matching = true; break;
            }
        }
        return $filter['mode'] === 'only' ? $matching : !$matching;
    }));
}

function football_stats_render_matches_result_filters(array $context, $tab, $league, $subtab): void
{
    $h = static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
    $filter = football_stats_matches_result_filter_request($_GET);
    $roster = $context['roster'];
    $presetNames = football_stats_match_group_labels();
    ?>
    <div class="matches-result-filters" data-matches-result-filters>
    <?php if (!$filter['mode'] && (($context['group'] ?? 'all') !== 'all' || ($context['opponent_group'] ?? 'all') !== 'all')): ?><p>Existing preset filter: <?= $h(football_stats_match_group_labels()[$context['group']]) ?> vs <?= $h(football_stats_match_group_labels()[$context['opponent_group']]) ?> (<?= $h($context['side']) ?>). Apply a panel below to replace it, or clear match filters.</p><?php endif; ?>
    <p>Each fixture appears once. Teams, opponents, venue and result must match the same team’s perspective. Empty team selections mean all teams. Applying either panel replaces the active filter.</p>
    <?php foreach (['only'=>'Include only matching results','exclude'=>'Include everything but matching results'] as $mode=>$title): $active=$filter['mode']===$mode; ?>
    <details class="matches-filter-panel" <?= $active?'open':'' ?>><summary><?= $title ?><?= $active?' · Applied':'' ?></summary>
    <form method="get">
    <input type="hidden" name="tab" value="<?= $h($tab) ?>"><input type="hidden" name="league" value="<?= $h($league) ?>"><input type="hidden" name="subtab" value="<?= $h($subtab) ?>"><input type="hidden" name="snapshot_season" value="<?= $h($context['season']) ?>"><input type="hidden" name="mf_mode" value="<?= $mode ?>">
    <p><?= $mode==='only'?'Include fixtures with a team result matching every field below.':'Exclude fixtures with a team result matching every field below.' ?> “All” leaves that field unrestricted.</p>
    <?php foreach(['teams'=>'Teams','opponents'=>'Opponent'] as $field=>$label): $values=$active?$filter[$field]:[]; ?>
    <div class="matches-filter-row matches-filter-teams"><span><?= $label ?></span>
    <details class="matches-team-menu" data-matches-team-menu><summary data-matches-team-summary><?= $values?count($values).' teams selected':'All teams' ?></summary>
    <div class="matches-team-options"><input type="search" placeholder="Search teams" aria-label="Search <?= strtolower($label) ?>" data-matches-team-search>
    <div class="matches-team-presets"><button type="button" data-matches-preset="all">All teams</button><button type="button" data-matches-preset="clear">Clear</button>
    <?php foreach($presetNames as $key=>$name): if($key==='all')continue; ?><button type="button" data-matches-preset="<?= $h($key) ?>" <?= str_starts_with($key,'current-')&&!$context['has_standings']?'disabled':'' ?>><?= $h($name) ?></button><?php endforeach; ?></div>
    <?php foreach($roster as $name): ?><label class="matches-team-choice"><input type="checkbox" name="mf_<?= $field ?>[]" value="<?= $h($name) ?>" <?= in_array($name,$values,true)?'checked':'' ?>> <?= $h($name) ?></label><?php endforeach; ?>
    </div></details></div>
    <?php endforeach; ?>
    <label class="matches-filter-row">Matchweek<select name="mf_week"><option value="0">All matchweeks</option><?php foreach($context['weeks'] as $week): ?><option value="<?= (int)$week ?>" <?= $active&&$filter['week']===(int)$week?'selected':'' ?>>MW<?= (int)$week ?></option><?php endforeach; ?></select></label>
    <label class="matches-filter-row">Venue<select name="mf_venue"><?php foreach(['all'=>'Home or away','home'=>'Home only','away'=>'Away only'] as $key=>$name): ?><option value="<?= $key ?>" <?= $active&&$filter['venue']===$key?'selected':'' ?>><?= $name ?></option><?php endforeach; ?></select></label>
    <label class="matches-filter-row">Result<select name="mf_result"><?php foreach(['all'=>'Every result','win'=>'Wins only','draw'=>'Draws only','loss'=>'Losses only'] as $key=>$name): ?><option value="<?= $key ?>" <?= $active&&$filter['result']===$key?'selected':'' ?>><?= $name ?></option><?php endforeach; ?></select></label>
    <label class="matches-filter-row">Group standings basis<select name="matches_standings_basis"><option value="season" <?= $context['basis']==='season'?'selected':'' ?>>Current / final season table</option><option value="matchweek" <?= $context['basis']==='matchweek'?'selected':'' ?>>After selected matchweek</option></select></label>
    <label class="matches-filter-row">Snapshot matchweek<select name="matchweek"><option value="">Choose snapshot week</option><?php foreach($context['weeks'] as $week): ?><option value="<?= (int)$week ?>" <?= $context['matchweek']===(int)$week?'selected':'' ?>>MW<?= (int)$week ?></option><?php endforeach; ?></select></label>
    <p>Snapshot controls set group preset membership. After changing the basis or snapshot week, apply filters to update preset membership, then choose a current-half preset. Checked teams remain explicit selections until you choose a preset again.</p>
    <button class="matches-filter-apply" type="submit"><?= $mode==='only'?'Include only matching results':'Exclude matching results' ?></button>
    </form></details>
    <?php endforeach; ?>
    <a href="<?= $h(football_stats_build_table_view_url($tab,$league,$subtab,['mf_mode'=>null,'mf_teams'=>null,'mf_opponents'=>null,'mf_week'=>null,'mf_venue'=>null,'mf_result'=>null,'mf_refresh'=>null,'matches_team_group'=>null,'matches_opponent_group'=>null,'matches_team_side'=>null,'matchweek'=>null])) ?>">Clear match filters</a>
    </div>
    <style>.matches-result-filters{width:100%;font-size:13px;color:#b9bbbe}.matches-filter-panel{border:1px solid #454976;border-radius:9px;background:#272a33;margin:12px 0;padding:12px}.matches-filter-panel>summary{cursor:pointer;font-weight:700;color:#eee;padding:4px}.matches-filter-row{display:flex;align-items:center;justify-content:space-between;gap:12px;background:#30333d;padding:10px;border-radius:8px;margin:8px 0;color:#ddd;font-weight:700}.matches-filter-teams{flex-direction:column;align-items:stretch}.matches-team-menu{width:100%}.matches-team-menu>summary{cursor:pointer;padding:10px;background:#1e1f22;border:1px solid #555;border-radius:7px;font-weight:400}.matches-team-options{display:grid;gap:6px;padding:10px;max-height:320px;overflow:auto;background:#1e1f22}.matches-team-presets{display:flex;flex-wrap:wrap;gap:5px}.matches-team-choice{display:flex;align-items:center;gap:6px;font-weight:400}.matches-team-choice[hidden]{display:none}.matches-result-filters select,.matches-result-filters input[type=search]{padding:8px;background:#1e1f22;color:#eee;border:1px solid #555;border-radius:6px;max-width:100%}.matches-result-filters button{padding:7px 10px;background:#4f545c;color:#fff;border:0;border-radius:6px;cursor:pointer}.matches-result-filters button:disabled{opacity:.4;cursor:not-allowed}.matches-filter-apply{width:100%;margin-top:8px}.matches-result-filters :focus-visible{outline:2px solid #a5b4fc;outline-offset:2px}@media(max-width:500px){.matches-filter-row{flex-wrap:wrap}.matches-filter-row select{width:100%}}</style>
    <script>
    (function(){
        var root=document.querySelector('[data-matches-result-filters]'); if(!root)return;
        var groups=<?= json_encode($context['groups'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
        var normalize=function(name){return String(name).trim().toLowerCase().replace(/\s+(?:football club|fc)$/i,'').replace(/^(?:tottenham|spurs)$/,'tottenham hotspur').replace(/^man (?:united|utd)$/,'manchester united').replace(/^man city$/,'manchester city').replace(/^leeds$/,'leeds united').replace(/^newcastle$/,'newcastle united').replace(/^palace$/,'crystal palace').replace(/^(?:brighton|brighton and hove albion)$/,'brighton & hove albion').replace(/^west ham$/,'west ham united');};
        root.querySelectorAll('[data-matches-team-menu]').forEach(function(menu){
            var boxes=Array.from(menu.querySelectorAll('input[type=checkbox]'));
            var summary=menu.querySelector('[data-matches-team-summary]');
            function update(){var selected=boxes.filter(function(b){return b.checked;});summary.textContent=selected.length?selected.length+' teams selected':'All teams';}
            menu.addEventListener('change',update);
            menu.querySelector('[data-matches-team-search]').addEventListener('input',function(){var q=normalize(this.value);boxes.forEach(function(b){b.closest('label').hidden=normalize(b.value).indexOf(q)===-1;});});
            menu.querySelectorAll('[data-matches-preset]').forEach(function(button){
                var preset=button.dataset.matchesPreset;var outside=preset.indexOf('outside-')===0;var key=(outside?preset.slice(8):preset).replace(/-/g,'_');var names=(groups[key]||[]).map(normalize);
                if(preset!=='all'&&preset!=='clear'&&!boxes.some(function(b){var member=names.indexOf(normalize(b.value))!==-1;return outside?!member:member;}))button.disabled=true;
                button.addEventListener('click',function(){boxes.forEach(function(b){var member=names.indexOf(normalize(b.value))!==-1;b.checked=preset==='all'||(preset!=='clear'&&(outside?!member:member));});update();});
            });
        });
    }());
    </script>
    <?php
}
