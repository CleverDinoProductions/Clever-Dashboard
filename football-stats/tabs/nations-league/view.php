<?php
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$editionLabel = football_stats_nations_league_editions()[$currentTournament];
$viewLabel = football_stats_nations_league_tabs($currentTournament, $currentLeague)[$currentSubTab]['label'];
$leagueLabel = ucwords(str_replace('-', ' ', $currentLeague));
?>
<section class="panel">
    <h2><?= $h('UEFA Nations League '.$editionLabel.' · '.$leagueLabel.' · '.$viewLabel) ?></h2>
    <?php if ($currentSubTab === 'format'): ?>
        <p>Teams play home and away within their group. A win earns three points, a draw one point, and a defeat zero points. Ties on points are resolved first by head-to-head criteria under UEFA regulations.</p>
        <?php if ($currentTournament === '2018-2019'): ?>
            <p>The inaugural edition used three-team groups in Leagues A and B, three- or four-team groups in League C, and four-team groups in League D. League A group winners advanced to the Finals. League membership was reorganised for 2020/21.</p>
        <?php else: ?>
            <p>Leagues A, B and C each have four groups of four teams. League D has two groups. Group winners in Leagues B, C and D earn promotion; relegation arrangements depend on the edition.</p>
        <?php endif; ?>
        <?php if ($currentLeague === 'league-a'): ?>
            <p><?= $currentTournament >= '2024-2025' ? 'Group winners and runners-up advance to two-legged quarter-finals. The four quarter-final winners reach the Finals.' : 'The four group winners advance directly to the Finals.' ?> The Finals comprise semi-finals, a third-place match and the final.</p>
        <?php endif; ?>
        <?php if ($currentTournament >= '2024-2025'): ?>
            <p>Promotion/relegation play-offs pair third-placed League A teams with League B runners-up, and third-placed League B teams with League C runners-up. The two best fourth-placed League C teams face League D runners-up; the other two fourth-placed League C teams are relegated directly.</p>
        <?php elseif (in_array($currentTournament, ['2020-2021', '2022-2023'], true) && $currentLeague === 'league-c'): ?>
            <p>League C relegation play-outs determined which participating fourth-placed teams moved to League D. The 2022/23 play-outs involved two teams after the suspension of Russia changed the league allocation.</p>
        <?php endif; ?>
        <p><a href="https://www.uefa.com/uefanationsleague/" target="_blank" rel="noopener noreferrer">UEFA Nations League tournament information and regulations</a></p>
    <?php else:
        // Optional imported tables. Never create or modify databases on a page request.
        $isGroups = $currentSubTab === 'groups';
        $table = $isGroups ? 'nl_standings' : 'nl_matches';
        $exists = $db->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?");
        $exists->execute([$table]);
        $rows = [];
        if ($exists->fetchColumn()) {
            $sql = "SELECT * FROM $table WHERE tournament = ? AND league = ?";
            $params = [$currentTournament, $currentLeague];
            if (!$isGroups && $currentSubTab !== 'matches') {
                $sql .= ' AND stage = ?';
                $params[] = $currentSubTab;
            }
            $sql .= $isGroups ? ' ORDER BY group_name, position, team_name' : ' ORDER BY match_date, id';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (!$rows): ?>
            <p role="status">No <?= $h(strtolower($viewLabel)) ?> data has been imported for <?= $h($editionLabel.' '.$leagueLabel) ?>.</p>
            <p>Select another tournament or league, or open Format &amp; Rules for information about this edition.</p>
        <?php else: ?>
            <div style="overflow-x:auto" tabindex="0" role="region" aria-label="<?= $h($viewLabel) ?>">
                <table>
                    <caption><?= $h($editionLabel.' '.$leagueLabel.' — '.$viewLabel) ?></caption>
                    <?php if ($isGroups): ?>
                        <thead><tr><?php foreach (['Group','Pos','Team','P','W','D','L','GF','GA','GD','Pts'] as $heading): ?><th scope="col"><?= $h($heading) ?></th><?php endforeach; ?></tr></thead>
                        <tbody><?php foreach ($rows as $row): ?><tr><?php foreach (['group_name','position','team_name','played','won','drawn','lost','gf','ga','gd','points'] as $column): ?><td><?= $h($row[$column]) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
                    <?php else: ?>
                        <thead><tr><th scope="col">Date (UTC)</th><th scope="col">Stage</th><th scope="col">Home</th><th scope="col">Score</th><th scope="col">Away</th><th scope="col">Status</th></tr></thead>
                        <tbody><?php foreach ($rows as $row): ?><tr>
                            <td><?= $h($row['match_date'] ?? 'TBC') ?></td><td><?= $h($row['round_name']) ?></td><td><?= $h($row['home_team']) ?></td>
                            <td><?= $row['home_score'] === null || $row['away_score'] === null ? '—' : $h($row['home_score'].'–'.$row['away_score']) ?></td>
                            <td><?= $h($row['away_team']) ?></td><td><?= $h($row['status']) ?></td>
                        </tr><?php endforeach; ?></tbody>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif;
    endif; ?>
</section>
