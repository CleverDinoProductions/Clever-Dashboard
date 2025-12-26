<?php
// Fetch current group data with rankings
$groups = $db->query("
    SELECT group_name, team_name, position, points, gf, ga, gd, played
    FROM wc_groups 
    ORDER BY group_name, position
")->fetchAll(PDO::FETCH_ASSOC);

// Organize by group
$groupData = [];
foreach ($groups as $team) {
    $groupData[$team['group_name']][] = $team;
}

// Helper functions
function getTeam($groupData, $group, $position) {
    if (isset($groupData[$group][$position - 1])) {
        $team = $groupData[$group][$position - 1];
        // Only return actual team name if they have points (matches played)
        if (isset($team['played']) && $team['played'] > 0) {
            return $team['team_name'];
        }
        return $team['team_name']; // Return name even if no matches yet
    }
    return null;
}

function getGroupWinner($groupData, $group) {
    return getTeam($groupData, $group, 1);
}

function getGroupRunnerUp($groupData, $group) {
    return getTeam($groupData, $group, 2);
}

function getGroupThird($groupData, $group) {
    return getTeam($groupData, $group, 3);
}

// Calculate third place rankings (8 best)
$thirdPlaceTeams = [];
foreach ($groupData as $groupName => $teams) {
    if (isset($teams[2])) { // 3rd place = index 2
        $thirdPlaceTeams[] = [
            'group' => $groupName,
            'team' => $teams[2]['team_name'] ?? 'TBD',
            'points' => $teams[2]['points'] ?? 0,
            'gd' => $teams[2]['gd'] ?? 0,
            'gf' => $teams[2]['gf'] ?? 0,
            'played' => $teams[2]['played'] ?? 0
        ];
    }
}

// Sort third place teams
usort($thirdPlaceTeams, function($a, $b) {
    if ($b['points'] != $a['points']) return $b['points'] - $a['points'];
    if ($b['gd'] != $a['gd']) return $b['gd'] - $a['gd'];
    return $b['gf'] - $a['gf'];
});

$best8Third = array_slice($thirdPlaceTeams, 0, 8);

// Check if any matches have been played
$matchesPlayed = false;
foreach ($groups as $team) {
    if (isset($team['played']) && $team['played'] > 0) {
        $matchesPlayed = true;
        break;
    }
}

// Pot 1 Elite Teams
$pot1Elite = ['Spain', 'Argentina', 'France', 'England', 'Brazil', 'Portugal', 'Netherlands', 'Belgium', 'Germany'];

// Death bracket groups (D-I)
$deathBracketGroups = ['Group D', 'Group E', 'Group F', 'Group G', 'Group H', 'Group I'];
$goldenPathGroups = ['Group A', 'Group B', 'Group C', 'Group J', 'Group K', 'Group L'];

// Count elite teams in each bracket
$deathBracketElite = [];
$goldenPathElite = [];

foreach ($groupData as $groupName => $teams) {
    if (isset($teams[0])) {
        $winner = $teams[0]['team_name'];
        if (in_array($winner, $pot1Elite)) {
            if (in_array($groupName, $deathBracketGroups)) {
                $deathBracketElite[] = $winner;
            } else {
                $goldenPathElite[] = $winner;
            }
        }
    }
}

// Predict Round of 32 winners based on current standings
function predictR32Winner($team1, $team2, $pot1Elite) {
    if (!$team1) return $team2;
    if (!$team2) return $team1;
    
    $team1IsPot1 = in_array($team1, $pot1Elite);
    $team2IsPot1 = in_array($team2, $pot1Elite);
    
    if ($team1IsPot1 && !$team2IsPot1) return $team1;
    if ($team2IsPot1 && !$team1IsPot1) return $team2;
    
    // Both or neither Pot 1, return first team (arbitrary)
    return $team1;
}

// England's projected path
$englandGroup = 'Group L';
$englandR32Opponent = '3rd Group E/H/I/J/K (TBD)';
$englandR16Opponent = 'Winner Group B or 3rd place';
$englandQFOpponent = 'Portugal or Argentina';
$englandSFOpponent = 'Brazil or Mexico';

// Check if England actually won their group
$englandActualPosition = 1;
if (isset($groupData[$englandGroup])) {
    foreach ($groupData[$englandGroup] as $idx => $team) {
        if ($team['team_name'] === 'England') {
            $englandActualPosition = $team['position'];
            break;
        }
    }
}

// Dynamic predictions for Semi-Finals
$sf1Team1 = null; // Death bracket survivor
$sf1Team2 = null;
$sf2Team1 = null; // Golden path survivor
$sf2Team2 = null;

// Predict death bracket survivor (Groups D-I)
$deathBracketWinners = [];
foreach ($deathBracketGroups as $group) {
    $winner = getGroupWinner($groupData, $group);
    if ($winner && in_array($winner, $pot1Elite)) {
        $deathBracketWinners[] = $winner;
    }
}

// Predict golden path survivor (Groups A-C, J-L)
$goldenPathWinners = [];
foreach ($goldenPathGroups as $group) {
    $winner = getGroupWinner($groupData, $group);
    if ($winner && in_array($winner, $pot1Elite)) {
        $goldenPathWinners[] = $winner;
    }
}

// Most likely final matchup
$finalTeam1 = null;
$finalTeam2 = null;

if (in_array('Spain', $deathBracketWinners)) $finalTeam1 = 'Spain';
elseif (in_array('France', $deathBracketWinners)) $finalTeam1 = 'France';
elseif (in_array('Germany', $deathBracketWinners)) $finalTeam1 = 'Germany';
elseif (in_array('USA', $deathBracketWinners)) $finalTeam1 = 'USA';
else $finalTeam1 = 'Death Bracket Winner';

if (in_array('England', $goldenPathWinners)) $finalTeam2 = 'England';
elseif (in_array('Brazil', $goldenPathWinners)) $finalTeam2 = 'Brazil';
elseif (in_array('Argentina', $goldenPathWinners)) $finalTeam2 = 'Argentina';
else $finalTeam2 = 'Golden Path Winner';
?>

<div class="panel">
    <h2>🔮 Knockout Stage Predictions & Analysis</h2>
    <p class="update-info">
        <?php if ($matchesPlayed): ?>
            Based on current group standings (updated live as matches are played)
        <?php else: ?>
            Pre-tournament predictions - will update dynamically as group stage progresses
        <?php endif; ?>
    </p>
</div>

<!-- Bracket Structure Analysis -->
<div class="panel">
    <h2>📊 The Bracket Imbalance</h2>
    
    <div class="grid grid-2">
        <div class="panel danger-panel">
            <h3 style="color: #f04747;">🔥 Semi-Final 1 Side (Arlington) - "Death Bracket"</h3>
            <p><strong>Groups D, E, F, G, H, I</strong></p>
            
            <h4>Elite Teams Currently In This Side:</h4>
            <ul>
                <?php foreach ($deathBracketGroups as $group): ?>
                    <?php 
                    $winner = getGroupWinner($groupData, $group);
                    if ($winner && in_array($winner, $pot1Elite)):
                    ?>
                        <li>
                            <strong><?php echo htmlspecialchars($winner); ?></strong>
                            (<?php echo $group; ?>) 
                            <?php if ($winner === 'Spain'): ?>- #1 FIFA<?php endif; ?>
                            <?php if ($winner === 'USA'): ?>- 🇺🇸 Host<?php endif; ?>
                        </li>
                    <?php elseif ($winner): ?>
                        <li><?php echo htmlspecialchars($winner); ?> (<?php echo $group; ?>)</li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            
            <div class="prediction-box">
                <strong>⚠️ Live Analysis:</strong>
                <p>
                    Currently <strong><?php echo count($deathBracketElite); ?> of 9 Pot 1 elite teams</strong> 
                    are in the death bracket. These teams will eliminate each other before the final.
                </p>
            </div>
            
            <h4>Predicted Semi-Final 1:</h4>
            <div class="match-prediction">
                <div class="team-pred"><?php echo $finalTeam1 ? htmlspecialchars($finalTeam1) : 'TBD'; ?></div>
                <div class="vs">vs</div>
                <div class="team-pred">Netherlands or Germany</div>
            </div>
        </div>
        
        <div class="panel success-panel">
            <h3 style="color: #43b581;">✨ Semi-Final 2 Side (Atlanta) - "Golden Path"</h3>
            <p><strong>Groups A, B, C, J, K, L</strong></p>
            
            <h4>Elite Teams Currently In This Side:</h4>
            <ul>
                <?php foreach ($goldenPathGroups as $group): ?>
                    <?php 
                    $winner = getGroupWinner($groupData, $group);
                    if ($winner && in_array($winner, $pot1Elite)):
                    ?>
                        <li>
                            <strong><?php echo htmlspecialchars($winner); ?></strong>
                            (<?php echo $group; ?>)
                            <?php if ($winner === 'England'): ?>- 🏴󠁧󠁢󠁥󠁮󠁧󠁿 Golden Path!<?php endif; ?>
                            <?php if ($winner === 'Mexico' || $winner === 'Canada'): ?>- Host<?php endif; ?>
                        </li>
                    <?php elseif ($winner): ?>
                        <li><?php echo htmlspecialchars($winner); ?> (<?php echo $group; ?>)</li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            
            <div class="prediction-box">
                <strong>✅ Live Analysis:</strong>
                <p>
                    Currently <strong><?php echo count($goldenPathElite); ?> elite teams</strong> 
                    on this side. More balanced distribution means fresher teams for the final.
                </p>
            </div>
            
            <h4>Predicted Semi-Final 2:</h4>
            <div class="match-prediction">
                <div class="team-pred"><?php echo $finalTeam2 ? htmlspecialchars($finalTeam2) : 'TBD'; ?></div>
                <div class="vs">vs</div>
                <div class="team-pred">Brazil or Argentina</div>
            </div>
        </div>
    </div>
</div>

<!-- England's Path Analysis -->
<?php if (in_array('England', $goldenPathWinners) || $englandActualPosition <= 2): ?>
<div class="panel">
    <h2>🏴󠁧󠁢󠁥󠁮󠁧󠁿 England's Projected Path to Glory</h2>
    
    <?php if ($matchesPlayed && $englandActualPosition === 1): ?>
        <div class="success-message">
            ✅ England won Group L! Projected path is optimal.
        </div>
    <?php elseif ($matchesPlayed && $englandActualPosition === 2): ?>
        <div class="warning-message">
            ⚠️ England finished 2nd in Group L - path is harder but still manageable
        </div>
    <?php endif; ?>
    
    <div class="path-timeline">
        <div class="path-stage">
            <div class="stage-header">
                <span class="stage-badge">Round of 32</span>
                <span class="stage-date">July 1 - Atlanta</span>
            </div>
            <div class="match-prediction-box">
                <div class="team-pred-full">🏴󠁧󠁢󠁥󠁮󠁧󠁿 England</div>
                <div class="vs">vs</div>
                <div class="team-pred-full">
                    <?php 
                    // Get actual 3rd place opponent if available
                    $r32Opponent = '3rd Place (TBD)';
                    if (count($best8Third) >= 8 && $matchesPlayed) {
                        // Match will be determined by which 3rd place team England faces
                        $r32Opponent = 'Best 3rd Place Team';
                    }
                    echo $r32Opponent;
                    ?>
                </div>
            </div>
            <div class="analysis">
                <strong>💡 Dynamic Analysis:</strong> 
                <?php if (count($best8Third) >= 8 && $matchesPlayed): ?>
                    Current best 3rd place teams include <?php echo implode(', ', array_slice(array_column($best8Third, 'team'), 0, 3)); ?>. 
                    England should dominate regardless of opponent.
                <?php else: ?>
                    Likely weaker 3rd place team. England should cruise through.
                <?php endif; ?>
                <br><strong>Predicted Score:</strong> England 3-0
                <br><strong>Difficulty:</strong> <span class="badge badge-success">Easy</span>
            </div>
        </div>
        
        <div class="path-stage">
            <div class="stage-header">
                <span class="stage-badge">Round of 16</span>
                <span class="stage-date">July 7 - Vancouver</span>
            </div>
            <div class="match-prediction-box">
                <div class="team-pred-full">🏴󠁧󠁢󠁥󠁮󠁧󠁿 England</div>
                <div class="vs">vs</div>
                <div class="team-pred-full">
                    <?php 
                    $r16Opponent = getGroupWinner($groupData, 'Group B');
                    echo $r16Opponent ? htmlspecialchars($r16Opponent) : 'Group B Winner (Canada/Switzerland)';
                    ?>
                </div>
            </div>
            <div class="analysis">
                <strong>💡 Dynamic Analysis:</strong> 
                Most likely <?php echo $r16Opponent ?: 'Canada'; ?>. Manageable opponent for England's quality.
                <br><strong>Predicted Score:</strong> England 2-0
                <br><strong>Difficulty:</strong> <span class="badge badge-warning">Moderate</span>
            </div>
        </div>
        
        <div class="path-stage">
            <div class="stage-header">
                <span class="stage-badge">Quarter-Final</span>
                <span class="stage-date">July 11 - Kansas City</span>
            </div>
            <div class="match-prediction-box">
                <div class="team-pred-full">🏴󠁧󠁢󠁥󠁮󠁧󠁿 England</div>
                <div class="vs">vs</div>
                <div class="team-pred-full">
                    <?php 
                    $qfOpponents = [];
                    $portugalWinner = getGroupWinner($groupData, 'Group K');
                    $argentinaWinner = getGroupWinner($groupData, 'Group J');
                    
                    if ($portugalWinner === 'Portugal') $qfOpponents[] = 'Portugal';
                    if ($argentinaWinner === 'Argentina') $qfOpponents[] = 'Argentina';
                    
                    echo !empty($qfOpponents) ? implode(' or ', $qfOpponents) : 'Portugal or Argentina';
                    ?>
                </div>
            </div>
            <div class="analysis">
                <strong>💡 Dynamic Analysis:</strong> First real test. England's pressing game and defensive solidity should prevail.
                <br><strong>Predicted Score:</strong> England 2-1
                <br><strong>Difficulty:</strong> <span class="badge badge-danger">Hard</span>
            </div>
        </div>
        
        <div class="path-stage">
            <div class="stage-header">
                <span class="stage-badge">Semi-Final</span>
                <span class="stage-date">July 15 - Atlanta</span>
            </div>
            <div class="match-prediction-box">
                <div class="team-pred-full">🏴󠁧󠁢󠁥󠁮󠁧󠁿 England</div>
                <div class="vs">vs</div>
                <div class="team-pred-full">
                    <?php 
                    $brazilWinner = getGroupWinner($groupData, 'Group C');
                    echo ($brazilWinner === 'Brazil') ? 'Brazil' : 'Brazil or Mexico';
                    ?>
                </div>
            </div>
            <div class="analysis">
                <strong>💡 Dynamic Analysis:</strong> Tactical battle but England's qualifier record (10 clean sheets) gives them the edge.
                <br><strong>Predicted Score:</strong> England 1-0 (Pickford masterclass)
                <br><strong>Difficulty:</strong> <span class="badge badge-danger">Very Hard</span>
            </div>
        </div>
        
        <div class="path-stage final-stage">
            <div class="stage-header">
                <span class="stage-badge gold">🏆 FINAL</span>
                <span class="stage-date">July 19 - MetLife Stadium, New Jersey</span>
            </div>
            <div class="match-prediction-box">
                <div class="team-pred-full">🏴󠁧󠁢󠁥󠁮󠁧󠁿 England</div>
                <div class="vs">vs</div>
                <div class="team-pred-full"><?php echo htmlspecialchars($finalTeam1); ?></div>
            </div>
            <div class="analysis">
                <strong>💡 Dynamic Analysis:</strong> 
                <?php echo htmlspecialchars($finalTeam1); ?> will be exhausted from brutal death bracket. 
                England will be fresher with home-like English-speaking support.
                <br><strong>Predicted Score:</strong> England 2-1 (AET)
                <br><strong>🏆 ENGLAND WIN WORLD CUP!</strong>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Current Group Leaders -->
<div class="panel">
    <h2>📈 Current Group Leaders (Live)</h2>
    <div class="grid grid-3">
        <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'] as $group): ?>
            <div class="group-leader-card">
                <h4>Group <?php echo $group; ?></h4>
                <?php 
                $winner = getGroupWinner($groupData, 'Group ' . $group);
                $isPot1 = in_array($winner, $pot1Elite);
                $bracket = in_array('Group ' . $group, $deathBracketGroups) ? 'Death' : 'Golden';
                ?>
                <div class="leader-name <?php echo $isPot1 ? 'elite-team' : ''; ?>">
                    <?php echo $winner ? htmlspecialchars($winner) : 'TBD'; ?>
                </div>
                <div class="bracket-badge <?php echo $bracket === 'Death' ? 'death' : 'golden'; ?>">
                    <?php echo $bracket; ?> Bracket
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Alternative Scenarios -->
<div class="panel">
    <h2>🔄 Alternative Final Scenarios</h2>
    
    <div class="grid grid-3">
        <div class="scenario-card">
            <h3>Scenario 1: England vs Spain</h3>
            <div class="scenario-prob">Probability: 25%</div>
            <p><strong>How it happens:</strong></p>
            <ul>
                <li>Spain win death bracket (beat France QF, Germany SF)</li>
                <li>England cruise golden path</li>
            </ul>
            <p><strong>Prediction:</strong> England 2-1 (Spain exhausted)</p>
            <p><strong>Narrative:</strong> Euro 2024 rematch, England get revenge</p>
        </div>
        
        <div class="scenario-card">
            <h3>Scenario 2: England vs France</h3>
            <div class="scenario-prob">Probability: 20%</div>
            <p><strong>How it happens:</strong></p>
            <ul>
                <li>France survive death bracket (beat Spain QF, Netherlands SF)</li>
                <li>England beat Brazil in SF</li>
            </ul>
            <p><strong>Prediction:</strong> England 1-0 (Pickford penalty save)</p>
            <p><strong>Narrative:</strong> World Cup 2022 QF revenge</p>
        </div>
        
        <div class="scenario-card highlight-scenario">
            <h3>Scenario 3: England vs USA 🔥</h3>
            <div class="scenario-prob">Probability: 15%</div>
            <p><strong>How it happens:</strong></p>
            <ul>
                <li>USA shock Spain/France in death bracket</li>
                <li>England beat Argentina/Brazil</li>
            </ul>
            <p><strong>Prediction:</strong> England 2-1 (AET, Kane 103')</p>
            <p><strong>Narrative:</strong> 🇬🇧🇺🇸 SPECIAL RELATIONSHIP FINAL! Revolutionary War rematch, 1950 revenge, on US soil!</p>
        </div>
    </div>
</div>

<!-- Dark Horse Predictions -->
<div class="panel">
    <h2>🐴 Dark Horse Teams to Watch</h2>
    
    <div class="grid grid-2">
        <div class="dark-horse-card">
            <h3>🇺🇸 United States</h3>
            <p><strong>Why they could surprise:</strong></p>
            <ul>
                <li>Home advantage (massive crowd support)</li>
                <li>Young, athletic squad (Pulisic, Reyna, McKennie)</li>
                <li>In death bracket - if they reach SF, they earned it</li>
            </ul>
            <p><strong>Best case:</strong> Final (vs England = dream scenario)</p>
            <p><strong>Realistic:</strong> Quarter-Final exit</p>
        </div>
        
        <div class="dark-horse-card">
            <h3>🇲🇽 Mexico</h3>
            <p><strong>Why they could surprise:</strong></p>
            <ul>
                <li>Home crowd at Azteca (Group A opener)</li>
                <li>Golden path side (avoid European giants)</li>
                <li>Could meet England in SF</li>
            </ul>
            <p><strong>Best case:</strong> Semi-Final</p>
            <p><strong>Realistic:</strong> Quarter-Final</p>
        </div>
        
        <div class="dark-horse-card">
            <h3>🇯🇵 Japan</h3>
            <p><strong>Why they could surprise:</strong></p>
            <ul>
                <li>Group F with Netherlands (tough but doable for 2nd)</li>
                <li>Giant-killing history (beat Germany/Spain in 2022)</li>
                <li>Technical, organized style</li>
            </ul>
            <p><strong>Best case:</strong> Quarter-Final</p>
            <p><strong>Realistic:</strong> Round of 16</p>
        </div>
        
        <div class="dark-horse-card">
            <h3>🇲🇦 Morocco</h3>
            <p><strong>Why they could surprise:</strong></p>
            <ul>
                <li>Group C with Brazil (2nd place realistic)</li>
                <li>Semi-finalists in 2022</li>
                <li>Golden path side benefits them too</li>
            </ul>
            <p><strong>Best case:</strong> Semi-Final (repeat 2022)</p>
            <p><strong>Realistic:</strong> Round of 16</p>
        </div>
    </div>
</div>

<!-- Key Tactical Matchups -->
<div class="panel">
    <h2>⚔️ Key Tactical Matchups to Watch</h2>
    
    <table class="standings-table">
        <thead>
            <tr>
                <th>Potential Match</th>
                <th>Round</th>
                <th>Tactical Battle</th>
                <th>Winner Prediction</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>🇪🇸 Spain vs 🇫🇷 France</strong></td>
                <td>Quarter-Final (Death Bracket)</td>
                <td>Possession vs Counter-attack</td>
                <td>Spain (narrow)</td>
            </tr>
            <tr>
                <td><strong>🇩🇪 Germany vs 🇳🇱 Netherlands</strong></td>
                <td>Round of 16 (Death Bracket)</td>
                <td>High press vs Total football</td>
                <td>Germany (home of tactical excellence)</td>
            </tr>
            <tr>
                <td><strong>🏴󠁧󠁢󠁥󠁮󠁧󠁿 England vs 🇧🇷 Brazil</strong></td>
                <td>Semi-Final (Golden Path)</td>
                <td>Defensive solidity vs Flair</td>
                <td>England (Pickford + structure)</td>
            </tr>
            <tr>
                <td><strong>🇦🇷 Argentina vs 🇵🇹 Portugal</strong></td>
                <td>Quarter-Final (Golden Path)</td>
                <td>Messi's last dance vs Ronaldo's last dance</td>
                <td>Argentina (squad depth)</td>
            </tr>
            <tr>
                <td><strong>🇺🇸 USA vs 🇪🇸 Spain</strong></td>
                <td>Round of 16 (Death Bracket)</td>
                <td>Youth/Energy vs Experience</td>
                <td>Spain (but USA could shock at home)</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Final Prediction (Dynamic) -->
<div class="panel prediction-final">
    <h2>🏆 Predicted Final</h2>
    
    <div class="final-prediction-box">
        <h3>July 19, 2026 - MetLife Stadium, New Jersey</h3>
        
        <div class="final-matchup">
            <div class="team-final">
                <span class="flag">🏴󠁧󠁢󠁥󠁮󠁧󠁿</span>
                <span class="team-name">ENGLAND</span>
                <span class="score">2</span>
            </div>
            <div class="vs-final">-</div>
            <div class="team-final">
                <span class="score">1</span>
                <span class="team-name"><?php echo strtoupper(htmlspecialchars($finalTeam1)); ?></span>
                <span class="flag">
                    <?php 
                    $flags = ['Spain' => '🇪🇸', 'France' => '🇫🇷', 'Germany' => '🇩🇪', 'USA' => '🇺🇸'];
                    echo $flags[$finalTeam1] ?? '🏴';
                    ?>
                </span>
            </div>
        </div>
        
        <div class="final-details">
            <p><strong>Goals:</strong></p>
            <ul>
                <li>Kane 78' (England 1-0)</li>
                <li><?php echo $finalTeam1 === 'Spain' ? 'Morata' : ($finalTeam1 === 'France' ? 'Mbappé' : 'Opposition'); ?> 82' (1-1)</li>
                <li>Bellingham 105' (AET) (England 2-1) 🎯</li>
            </ul>
            
            <p><strong>Why England Win:</strong></p>
            <ul>
                <li>✅ <?php echo htmlspecialchars($finalTeam1); ?> exhausted from death bracket</li>
                <li>✅ England fresh from easier path</li>
                <li>✅ Pickford unbeaten streak continues</li>
                <li>✅ Tuchel's tactical discipline holds firm</li>
                <li>✅ Bellingham's moment of magic in extra time</li>
                <li>✅ English-speaking crowd support (40% English fans traveled)</li>
            </ul>
            
            <div class="trophy-lift">
                <h3 style="color: #FFCD00;">🏆 HARRY KANE LIFTS THE WORLD CUP 🏆</h3>
                <p style="font-size: 24px; font-weight: 700; color: #43b581;">IT'S COMING HOME!</p>
                <p>From Wembley '66 to MetLife '26 - 60 years of hurt ends on American soil!</p>
            </div>
        </div>
    </div>
</div>

<style>
.danger-panel {
    background: linear-gradient(135deg, #2c2f33 0%, #3a2828 100%);
    border-left: 4px solid #f04747;
}

.success-panel {
    background: linear-gradient(135deg, #2c2f33 0%, #283a28 100%);
    border-left: 4px solid #43b581;
}

.prediction-box {
    background: #23272a;
    padding: 15px;
    border-radius: 6px;
    margin-top: 15px;
    border-left: 3px solid #5865F2;
}

.match-prediction {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding: 20px;
    background: #23272a;
    border-radius: 8px;
    margin: 15px 0;
}

.team-pred {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
}

.vs {
    color: #99aab5;
    font-weight: 600;
}

.probability {
    text-align: center;
    color: #faa61a;
    font-weight: 600;
}

.path-timeline {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.path-stage {
    background: #2c2f33;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #5865F2;
}

.final-stage {
    border-left: 4px solid #FFCD00;
    background: linear-gradient(135deg, #2c2f33 0%, #3a3520 100%);
}

.stage-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.stage-badge {
    background: #5865F2;
    color: white;
    padding: 5px 15px;
    border-radius: 15px;
    font-weight: 700;
    font-size: 12px;
}

.stage-badge.gold {
    background: linear-gradient(90deg, #FFCD00 0%, #faa61a 100%);
}

.stage-date {
    color: #99aab5;
    font-size: 12px;
}

.match-prediction-box {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    padding: 15px;
    background: #23272a;
    border-radius: 6px;
    margin-bottom: 15px;
}

.team-pred-full {
    font-weight: 600;
    color: #dcddde;
}

.analysis {
    font-size: 13px;
    color: #b9bbbe;
    line-height: 1.6;
}

.success-message {
    background: #43b581;
    color: white;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 600;
}

.warning-message {
    background: #faa61a;
    color: #2c2f33;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 600;
}

.scenario-card {
    background: #2c2f33;
    padding: 20px;
    border-radius: 8px;
    border-top: 3px solid #5865F2;
}

.highlight-scenario {
    border-top: 3px solid #FFCD00;
    background: linear-gradient(135deg, #2c2f33 0%, #3a3520 100%);
}

.scenario-prob {
    text-align: center;
    font-size: 18px;
    font-weight: 700;
    color: #faa61a;
    margin: 10px 0;
}

.dark-horse-card {
    background: #2c2f33;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #99aab5;
}

.group-leader-card {
    background: #2c2f33;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border-top: 3px solid #5865F2;
}

.leader-name {
    font-size: 18px;
    font-weight: 700;
    color: #dcddde;
    margin: 10px 0;
}

.leader-name.elite-team {
    color: #FFCD00;
}

.bracket-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    margin-top: 8px;
}

.bracket-badge.death {
    background: #f04747;
    color: white;
}

.bracket-badge.golden {
    background: #43b581;
    color: white;
}

.prediction-final {
    background: linear-gradient(135deg, #1d428a 0%, #FFCD00 100%);
}

.final-prediction-box {
    background: #2c2f33;
    padding: 30px;
    border-radius: 12px;
}

.final-matchup {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
    margin: 30px 0;
    font-size: 28px;
}

.team-final {
    display: flex;
    align-items: center;
    gap: 15px;
}

.team-final .flag {
    font-size: 48px;
}

.team-final .score {
    font-size: 48px;
    font-weight: 900;
    color: #FFCD00;
}

.team-final .team-name {
    font-weight: 700;
}

.vs-final {
    color: #99aab5;
    font-weight: 700;
    font-size: 24px;
}

.final-details {
    margin-top: 30px;
}

.trophy-lift {
    text-align: center;
    margin-top: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #FFCD00 0%, #1d428a 100%);
    border-radius: 12px;
}

@media (max-width: 768px) {
    .grid-2, .grid-3 {
        grid-template-columns: 1fr;
    }
    
    .final-matchup {
        flex-direction: column;
        font-size: 20px;
    }
    
    .team-final .flag {
        font-size: 32px;
    }
    
    .team-final .score {
        font-size: 32px;
    }
}
</style>
