<?php
// Fetch all groups to determine winners/runners-up (currently TBD)
$groups = $db->query("
    SELECT group_name, team_name, position, points 
    FROM wc_groups 
    ORDER BY group_name, position
")->fetchAll(PDO::FETCH_ASSOC);

// Organize by group
$groupData = [];
foreach ($groups as $team) {
    $groupData[$team['group_name']][] = $team;
}

// Helper function to get team from group
function getTeam($groupData, $group, $position) {
    if (isset($groupData[$group][$position - 1])) {
        return $groupData[$group][$position - 1]['team_name'];
    }
    return ucfirst($position) . ' ' . $group;
}
?>

<div class="panel">
    <h2>⚔️ Knockout Stage Bracket</h2>
    <p class="update-info">Tournament bracket structure - Teams will be determined after group stage (June 27, 2026)</p>
    
    <div class="bracket-info">
        <span class="badge badge-champions">🏆 Pot 1 Elite</span>
        <span class="badge badge-europa">🌟 Host Nations</span>
        <span class="badge badge-team">Round of 32: June 28 - July 3</span>
        <span class="badge badge-team">Round of 16: July 4-7</span>
        <span class="badge badge-team">Quarterfinals: July 9-11</span>
        <span class="badge badge-team">Semifinals: July 14-15</span>
        <span class="badge badge-team">Final: July 19</span>
    </div>
</div>

<!-- Bracket Container -->
<div class="bracket-container">
    
    <!-- Round of 32 -->
    <div class="bracket-round">
        <h3 class="round-title">Round of 32</h3>
        
        <!-- TOP HALF (Leading to Semi-Final 1 - Arlington) -->
        <div class="bracket-match">
            <div class="match-header">Match 74 - Jun 29, Foxborough</div>
            <div class="match-team">Winner Group E</div>
            <div class="match-team">3rd Group A/B/C/D/F</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 77 - Jun 30, East Rutherford</div>
            <div class="match-team">Winner Group I</div>
            <div class="match-team">3rd Group C/D/F/G/H</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 73 - Jun 28, Inglewood</div>
            <div class="match-team">Runner-up Group A</div>
            <div class="match-team">Runner-up Group B</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 75 - Jun 29, Guadalupe</div>
            <div class="match-team">Winner Group F</div>
            <div class="match-team">Runner-up Group C</div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <div class="bracket-match">
            <div class="match-header">Match 81 - Jul 1, Santa Clara</div>
            <div class="match-team">Winner Group D</div>
            <div class="match-team">3rd Group B/E/F/I/J</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 82 - Jul 1, Seattle</div>
            <div class="match-team">Winner Group G</div>
            <div class="match-team">3rd Group A/E/H/I/J</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 83 - Jul 2, Toronto</div>
            <div class="match-team">Runner-up Group K</div>
            <div class="match-team">Runner-up Group L</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 84 - Jul 2, Inglewood</div>
            <div class="match-team">Winner Group H</div>
            <div class="match-team">Runner-up Group J</div>
        </div>
        
        <div class="bracket-spacer-large"></div>
        
        <!-- BOTTOM HALF (Leading to Semi-Final 2 - Atlanta) -->
        <div class="bracket-match">
            <div class="match-header">Match 76 - Jun 29, Houston</div>
            <div class="match-team">Winner Group C</div>
            <div class="match-team">Runner-up Group F</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 78 - Jun 30, Arlington</div>
            <div class="match-team">Runner-up Group E</div>
            <div class="match-team">Runner-up Group I</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 79 - Jun 30, Mexico City</div>
            <div class="match-team">Winner Group A</div>
            <div class="match-team">3rd Group C/E/F/H/I</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 80 - Jul 1, Atlanta</div>
            <div class="match-team">Winner Group L</div>
            <div class="match-team">3rd Group E/H/I/J/K</div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <div class="bracket-match">
            <div class="match-header">Match 86 - Jul 3, Miami Gardens</div>
            <div class="match-team">Winner Group J</div>
            <div class="match-team">Runner-up Group H</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 88 - Jul 3, Arlington</div>
            <div class="match-team">Runner-up Group D</div>
            <div class="match-team">Runner-up Group G</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 85 - Jul 2, Vancouver</div>
            <div class="match-team">Winner Group B</div>
            <div class="match-team">3rd Group E/F/G/I/J</div>
        </div>
        
        <div class="bracket-match">
            <div class="match-header">Match 87 - Jul 3, Kansas City</div>
            <div class="match-team">Winner Group K</div>
            <div class="match-team">3rd Group D/E/I/J/L</div>
        </div>
    </div>
    
    <!-- Round of 16 -->
    <div class="bracket-round">
        <h3 class="round-title">Round of 16</h3>
        
        <div class="bracket-match r16">
            <div class="match-header">Match 89 - Jul 4, Philadelphia</div>
            <div class="match-team">Winner Match 74</div>
            <div class="match-team">Winner Match 77</div>
        </div>
        
        <div class="bracket-match r16">
            <div class="match-header">Match 90 - Jul 4, Houston</div>
            <div class="match-team">Winner Match 73</div>
            <div class="match-team">Winner Match 75</div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <div class="bracket-match r16">
            <div class="match-header">Match 94 - Jul 6, Seattle</div>
            <div class="match-team">Winner Match 81</div>
            <div class="match-team">Winner Match 82</div>
        </div>
        
        <div class="bracket-match r16">
            <div class="match-header">Match 93 - Jul 6, Arlington</div>
            <div class="match-team">Winner Match 83</div>
            <div class="match-team">Winner Match 84</div>
        </div>
        
        <div class="bracket-spacer-large"></div>
        
        <div class="bracket-match r16">
            <div class="match-header">Match 91 - Jul 5, East Rutherford</div>
            <div class="match-team">Winner Match 76</div>
            <div class="match-team">Winner Match 78</div>
        </div>
        
        <div class="bracket-match r16">
            <div class="match-header">Match 92 - Jul 5, Mexico City</div>
            <div class="match-team">Winner Match 79</div>
            <div class="match-team">Winner Match 80</div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <div class="bracket-match r16">
            <div class="match-header">Match 95 - Jul 7, Atlanta</div>
            <div class="match-team">Winner Match 86</div>
            <div class="match-team">Winner Match 88</div>
        </div>
        
        <div class="bracket-match r16">
            <div class="match-header">Match 96 - Jul 7, Vancouver</div>
            <div class="match-team">Winner Match 85</div>
            <div class="match-team">Winner Match 87</div>
        </div>
    </div>
    
    <!-- Quarter-finals -->
    <div class="bracket-round">
        <h3 class="round-title">Quarterfinals</h3>
        
        <div class="bracket-match qf">
            <div class="match-header">Match 97 - Jul 9, Foxborough</div>
            <div class="match-team">Winner Match 89</div>
            <div class="match-team">Winner Match 90</div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <div class="bracket-match qf">
            <div class="match-header">Match 98 - Jul 10, Inglewood</div>
            <div class="match-team">Winner Match 93</div>
            <div class="match-team">Winner Match 94</div>
        </div>
        
        <div class="bracket-spacer-xl"></div>
        
        <div class="bracket-match qf">
            <div class="match-header">Match 99 - Jul 11, Miami Gardens</div>
            <div class="match-team">Winner Match 91</div>
            <div class="match-team">Winner Match 92</div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <div class="bracket-match qf">
            <div class="match-header">Match 100 - Jul 11, Kansas City</div>
            <div class="match-team">Winner Match 95</div>
            <div class="match-team">Winner Match 96</div>
        </div>
    </div>
    
    <!-- Semi-finals -->
    <div class="bracket-round">
        <h3 class="round-title">Semifinals</h3>
        
        <div class="bracket-match sf">
            <div class="match-header">Match 101 - Jul 14, Arlington</div>
            <div class="match-team">Winner Match 97</div>
            <div class="match-team">Winner Match 98</div>
            <div class="match-note">🔥 Side of Death Winner</div>
        </div>
        
        <div class="bracket-spacer-mega"></div>
        
        <div class="bracket-match sf">
            <div class="match-header">Match 102 - Jul 15, Atlanta</div>
            <div class="match-team">Winner Match 99</div>
            <div class="match-team">Winner Match 100</div>
            <div class="match-note">✨ Golden Path Winner</div>
        </div>
    </div>
    
    <!-- Final -->
    <div class="bracket-round">
        <h3 class="round-title">Final</h3>
        
        <div class="bracket-match final">
            <div class="match-header">🏆 Match 104 - July 19, MetLife Stadium, New Jersey</div>
            <div class="match-team winner">Winner Match 101</div>
            <div class="match-team winner">Winner Match 102</div>
            <div class="match-note">FIFA World Cup 2026 Champion</div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <div class="bracket-match third-place">
            <div class="match-header">🥉 Match 103 - July 18, Miami Gardens</div>
            <div class="match-team">Loser Match 101</div>
            <div class="match-team">Loser Match 102</div>
            <div class="match-note">Third Place Playoff</div>
        </div>
    </div>
    
</div>

<!-- Bracket Analysis -->
<div class="panel">
    <h2>📊 Bracket Structure Analysis</h2>
    
    <div class="grid">
        <div class="panel">
            <h3 style="color: #f04747;">🔥 Side of Death (Semi-Final 1)</h3>
            <p><strong>Groups D, E, F, G, H, I</strong></p>
            <ul>
                <li>🇪🇸 Spain (Group H) - #1 FIFA</li>
                <li>🇫🇷 France (Group I) - Pot 1</li>
                <li>🇩🇪 Germany (Group E) - Pot 1</li>
                <li>🇳🇱 Netherlands (Group F) - Pot 1</li>
                <li>🇧🇪 Belgium (Group G) - Pot 1</li>
                <li>🇺🇸 USA (Group D) - Host</li>
            </ul>
            <p class="text-danger"><strong>5 of 9 Pot 1 teams on this side!</strong></p>
        </div>
        
        <div class="panel">
            <h3 style="color: #43b581;">✨ Golden Path (Semi-Final 2)</h3>
            <p><strong>Groups A, B, C, J, K, L</strong></p>
            <ul>
                <li>🏴󠁧󠁢󠁥󠁮󠁧󠁿 England (Group L) - Pot 1</li>
                <li>🇦🇷 Argentina (Group J) - Defending Champs</li>
                <li>🇵🇹 Portugal (Group K) - Pot 1</li>
                <li>🇧🇷 Brazil (Group C) - Pot 1</li>
                <li>🇲🇽 Mexico (Group A) - Host</li>
                <li>🇨🇦 Canada (Group B) - Host</li>
            </ul>
            <p class="text-success"><strong>More balanced distribution!</strong></p>
        </div>
    </div>
</div>

<style>
.bracket-container {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding: 20px;
    background: #23272a;
    border-radius: 8px;
    margin-bottom: 20px;
}

.bracket-round {
    min-width: 280px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.round-title {
    text-align: center;
    color: #5865F2;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 10px;
    padding: 10px;
    background: #2c2f33;
    border-radius: 6px;
    border-bottom: 3px solid #5865F2;
}

.bracket-match {
    background: #2c2f33;
    border-radius: 6px;
    padding: 10px;
    border-left: 3px solid #5865F2;
    transition: all 0.3s ease;
}

.bracket-match:hover {
    background: #36393e;
    transform: translateX(3px);
    box-shadow: 0 2px 8px rgba(88, 101, 242, 0.3);
}

.bracket-match.r16 {
    border-left-color: #00ff88;
}

.bracket-match.qf {
    border-left-color: #faa61a;
}

.bracket-match.sf {
    border-left-color: #f04747;
}

.bracket-match.final {
    border-left-color: #FFCD00;
    background: linear-gradient(135deg, #2c2f33 0%, #3a3d42 100%);
}

.bracket-match.third-place {
    border-left-color: #cd7f32;
}

.match-header {
    font-size: 11px;
    color: #99aab5;
    margin-bottom: 8px;
    font-weight: 600;
    text-transform: uppercase;
}

.match-team {
    padding: 8px;
    background: #23272a;
    margin-bottom: 4px;
    border-radius: 4px;
    color: #dcddde;
    font-size: 13px;
    font-weight: 500;
}

.match-team:last-of-type {
    margin-bottom: 0;
}

.match-team.winner {
    background: linear-gradient(90deg, #FFCD00 0%, #1d428a 100%);
    color: #ffffff;
    font-weight: 700;
}

.match-note {
    margin-top: 8px;
    font-size: 11px;
    color: #99aab5;
    font-style: italic;
    text-align: center;
}

.bracket-spacer {
    height: 20px;
}

.bracket-spacer-large {
    height: 40px;
}

.bracket-spacer-xl {
    height: 80px;
}

.bracket-spacer-mega {
    height: 160px;
}

.bracket-info {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .bracket-container {
        padding: 10px;
    }
    
    .bracket-round {
        min-width: 240px;
    }
    
    .round-title {
        font-size: 14px;
    }
    
    .match-team {
        font-size: 12px;
    }
}
</style>
