<?php
// ============================================
// 1. DATABASE CONNECTION & LOGIC
// ============================================
// 1. Fetch Knockout Matches
$knockout_stmt = $world_cup_db->query("SELECT * FROM wc_knockout");
$knockout_rows = $knockout_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Index matches by Match Number for easy lookup
$bracket_data = [];
foreach ($knockout_rows as $match) {
    $bracket_data[$match['match_number']] = $match;
}

// 4. Helper Function to Render a Match
function renderMatch($matchNum, $defaultHome, $defaultAway, $data) {
    // If we have data for this match number in the DB
    if (isset($data[$matchNum])) {
        $m = $data[$matchNum];
        
        // Use DB name if available, otherwise default
        $home = ($m['home_team'] && $m['home_team'] !== 'TBD') ? $m['home_team'] : $defaultHome;
        $away = ($m['away_team'] && $m['away_team'] !== 'TBD') ? $m['away_team'] : $defaultAway;
        
        $hScore = $m['home_score'];
        $aScore = $m['away_score'];
        $status = $m['status']; // SCHEDULED, IN_PLAY, PAUSED, FINISHED
        
        // CSS classes for scores
        $hClass = '';
        $aClass = '';
        
        if ($status == 'FINISHED' && $hScore !== null && $aScore !== null) {
            if ($hScore > $aScore) $hClass = 'winner';
            elseif ($aScore > $hScore) $aClass = 'winner';
        }
        
        return [
            'home' => $home,
            'away' => $away,
            'h_score' => $hScore,
            'a_score' => $aScore,
            'h_class' => $hClass,
            'a_class' => $aClass,
            'status_class' => ($status == 'IN_PLAY' || $status == 'PAUSED') ? 'live' : ''
        ];
    }
    
    // Fallback if no data yet
    return [
        'home' => $defaultHome,
        'away' => $defaultAway,
        'h_score' => '',
        'a_score' => '',
        'h_class' => '',
        'a_class' => '',
        'status_class' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>World Cup 2026 Bracket</title>
    <style>
        /* ============================================
           CSS STYLES
           ============================================ */
        body {
            background-color: #202225;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .panel {
            background: #2f3136;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #5865F2;
        }

        h2, h3 { margin-top: 0; color: #fff; }
        
        .update-info { color: #aaa; font-size: 0.9em; margin-bottom: 15px; }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-right: 5px;
            margin-bottom: 5px;
            color: #fff;
            background: #40444b;
        }
        .badge-champions { background: #FFD700; color: #000; }
        .badge-europa { background: #00ff88; color: #000; }
        .badge-team { background: #23272a; color: #dcddde; border: 1px solid #40444b; }
        
        /* Bracket Layout */
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
            gap: 15px; /* Space between matches */
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

        /* Match Card */
        .bracket-match {
            background: #2c2f33;
            border-radius: 6px;
            padding: 10px;
            border-left: 3px solid #5865F2;
            transition: all 0.2s ease;
            position: relative;
        }

        .bracket-match:hover {
            background: #36393e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Stage Colors */
        .bracket-match.r16 { border-left-color: #00ff88; }
        .bracket-match.qf { border-left-color: #faa61a; }
        .bracket-match.sf { border-left-color: #f04747; }
        .bracket-match.final { 
            border-left-color: #FFCD00; 
            background: linear-gradient(135deg, #2c2f33 0%, #3a3d42 100%);
        }
        .bracket-match.third-place { border-left-color: #cd7f32; }

        /* LIVE Status Animation */
        @keyframes pulse-live {
            0% { border-left-color: #f04747; box-shadow: 0 0 5px rgba(240, 71, 71, 0.5); }
            50% { border-left-color: #ff8888; box-shadow: 0 0 12px rgba(240, 71, 71, 0.8); }
            100% { border-left-color: #f04747; box-shadow: 0 0 5px rgba(240, 71, 71, 0.5); }
        }
        .bracket-match.live {
            animation: pulse-live 2s infinite;
        }

        .match-header {
            font-size: 11px;
            color: #99aab5;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
        }

        .match-team {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 8px;
            background: #23272a;
            margin-bottom: 4px;
            border-radius: 4px;
            color: #dcddde;
            font-size: 13px;
            font-weight: 500;
        }

        .match-team:last-of-type { margin-bottom: 0; }

        .match-team.winner {
            background: linear-gradient(90deg, #1d428a 0%, #2a5298 100%);
            color: #fff;
            font-weight: 700;
            border-left: 3px solid #FFCD00;
        }
        
        /* Final Winner Special Style */
        .final .match-team.winner {
            background: linear-gradient(90deg, #FFCD00 0%, #ffed4a 100%);
            color: #000;
            border-left: none;
        }

        .match-note {
            margin-top: 8px;
            font-size: 11px;
            color: #99aab5;
            font-style: italic;
            text-align: center;
        }

        /* Spacers for visual alignment */
        .bracket-spacer { height: 20px; }
        .bracket-spacer-large { height: 40px; }
        .bracket-spacer-xl { height: 80px; }
        .bracket-spacer-mega { height: 160px; }

        /* Grid for bottom analysis panel */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .text-danger { color: #f04747; }
        .text-success { color: #43b581; }
        
        ul { padding-left: 20px; margin: 5px 0; font-size: 0.9em; color: #dcddde; }
        li { margin-bottom: 4px; }
    </style>
</head>
<body>

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

<div class="bracket-container">
    
    <div class="bracket-round">
        <h3 class="round-title">Round of 32</h3>
        
        <?php $m74 = renderMatch(74, 'Winner Group E', '3rd Grp A/B/C/D/F', $bracket_data); ?>
        <div class="bracket-match <?= $m74['status_class'] ?>">
            <div class="match-header"><span>Match 74</span> <span>Jun 29</span></div>
            <div class="match-team <?= $m74['h_class'] ?>"><span><?= $m74['home'] ?></span><span><?= $m74['h_score'] ?></span></div>
            <div class="match-team <?= $m74['a_class'] ?>"><span><?= $m74['away'] ?></span><span><?= $m74['a_score'] ?></span></div>
        </div>

        <?php $m77 = renderMatch(77, 'Winner Group I', '3rd Grp C/D/F/G/H', $bracket_data); ?>
        <div class="bracket-match <?= $m77['status_class'] ?>">
            <div class="match-header"><span>Match 77</span> <span>Jun 30</span></div>
            <div class="match-team <?= $m77['h_class'] ?>"><span><?= $m77['home'] ?></span><span><?= $m77['h_score'] ?></span></div>
            <div class="match-team <?= $m77['a_class'] ?>"><span><?= $m77['away'] ?></span><span><?= $m77['a_score'] ?></span></div>
        </div>

        <?php $m73 = renderMatch(73, 'Runner-up Grp A', 'Runner-up Grp B', $bracket_data); ?>
        <div class="bracket-match <?= $m73['status_class'] ?>">
            <div class="match-header"><span>Match 73</span> <span>Jun 28</span></div>
            <div class="match-team <?= $m73['h_class'] ?>"><span><?= $m73['home'] ?></span><span><?= $m73['h_score'] ?></span></div>
            <div class="match-team <?= $m73['a_class'] ?>"><span><?= $m73['away'] ?></span><span><?= $m73['a_score'] ?></span></div>
        </div>

        <?php $m75 = renderMatch(75, 'Winner Group F', 'Runner-up Grp C', $bracket_data); ?>
        <div class="bracket-match <?= $m75['status_class'] ?>">
            <div class="match-header"><span>Match 75</span> <span>Jun 29</span></div>
            <div class="match-team <?= $m75['h_class'] ?>"><span><?= $m75['home'] ?></span><span><?= $m75['h_score'] ?></span></div>
            <div class="match-team <?= $m75['a_class'] ?>"><span><?= $m75['away'] ?></span><span><?= $m75['a_score'] ?></span></div>
        </div>

        <div class="bracket-spacer"></div>

        <?php $m81 = renderMatch(81, 'Winner Group D', '3rd Grp B/E/F/I/J', $bracket_data); ?>
        <div class="bracket-match <?= $m81['status_class'] ?>">
            <div class="match-header"><span>Match 81</span> <span>Jul 1</span></div>
            <div class="match-team <?= $m81['h_class'] ?>"><span><?= $m81['home'] ?></span><span><?= $m81['h_score'] ?></span></div>
            <div class="match-team <?= $m81['a_class'] ?>"><span><?= $m81['away'] ?></span><span><?= $m81['a_score'] ?></span></div>
        </div>

        <?php $m82 = renderMatch(82, 'Winner Group G', '3rd Grp A/E/H/I/J', $bracket_data); ?>
        <div class="bracket-match <?= $m82['status_class'] ?>">
            <div class="match-header"><span>Match 82</span> <span>Jul 1</span></div>
            <div class="match-team <?= $m82['h_class'] ?>"><span><?= $m82['home'] ?></span><span><?= $m82['h_score'] ?></span></div>
            <div class="match-team <?= $m82['a_class'] ?>"><span><?= $m82['away'] ?></span><span><?= $m82['a_score'] ?></span></div>
        </div>

        <?php $m83 = renderMatch(83, 'Runner-up Grp K', 'Runner-up Grp L', $bracket_data); ?>
        <div class="bracket-match <?= $m83['status_class'] ?>">
            <div class="match-header"><span>Match 83</span> <span>Jul 2</span></div>
            <div class="match-team <?= $m83['h_class'] ?>"><span><?= $m83['home'] ?></span><span><?= $m83['h_score'] ?></span></div>
            <div class="match-team <?= $m83['a_class'] ?>"><span><?= $m83['away'] ?></span><span><?= $m83['a_score'] ?></span></div>
        </div>

        <?php $m84 = renderMatch(84, 'Winner Group H', 'Runner-up Grp J', $bracket_data); ?>
        <div class="bracket-match <?= $m84['status_class'] ?>">
            <div class="match-header"><span>Match 84</span> <span>Jul 2</span></div>
            <div class="match-team <?= $m84['h_class'] ?>"><span><?= $m84['home'] ?></span><span><?= $m84['h_score'] ?></span></div>
            <div class="match-team <?= $m84['a_class'] ?>"><span><?= $m84['away'] ?></span><span><?= $m84['a_score'] ?></span></div>
        </div>

        <div class="bracket-spacer-large"></div>

        <?php $m76 = renderMatch(76, 'Winner Group C', 'Runner-up Grp F', $bracket_data); ?>
        <div class="bracket-match <?= $m76['status_class'] ?>">
            <div class="match-header"><span>Match 76</span> <span>Jun 29</span></div>
            <div class="match-team <?= $m76['h_class'] ?>"><span><?= $m76['home'] ?></span><span><?= $m76['h_score'] ?></span></div>
            <div class="match-team <?= $m76['a_class'] ?>"><span><?= $m76['away'] ?></span><span><?= $m76['a_score'] ?></span></div>
        </div>

        <?php $m78 = renderMatch(78, 'Runner-up Grp E', 'Runner-up Grp I', $bracket_data); ?>
        <div class="bracket-match <?= $m78['status_class'] ?>">
            <div class="match-header"><span>Match 78</span> <span>Jun 30</span></div>
            <div class="match-team <?= $m78['h_class'] ?>"><span><?= $m78['home'] ?></span><span><?= $m78['h_score'] ?></span></div>
            <div class="match-team <?= $m78['a_class'] ?>"><span><?= $m78['away'] ?></span><span><?= $m78['a_score'] ?></span></div>
        </div>

        <?php $m79 = renderMatch(79, 'Winner Group A', '3rd Grp C/E/F/H/I', $bracket_data); ?>
        <div class="bracket-match <?= $m79['status_class'] ?>">
            <div class="match-header"><span>Match 79</span> <span>Jun 30</span></div>
            <div class="match-team <?= $m79['h_class'] ?>"><span><?= $m79['home'] ?></span><span><?= $m79['h_score'] ?></span></div>
            <div class="match-team <?= $m79['a_class'] ?>"><span><?= $m79['away'] ?></span><span><?= $m79['a_score'] ?></span></div>
        </div>

        <?php $m80 = renderMatch(80, 'Winner Group L', '3rd Grp E/H/I/J/K', $bracket_data); ?>
        <div class="bracket-match <?= $m80['status_class'] ?>">
            <div class="match-header"><span>Match 80</span> <span>Jul 1</span></div>
            <div class="match-team <?= $m80['h_class'] ?>"><span><?= $m80['home'] ?></span><span><?= $m80['h_score'] ?></span></div>
            <div class="match-team <?= $m80['a_class'] ?>"><span><?= $m80['away'] ?></span><span><?= $m80['a_score'] ?></span></div>
        </div>

        <div class="bracket-spacer"></div>

        <?php $m86 = renderMatch(86, 'Winner Group J', 'Runner-up Grp H', $bracket_data); ?>
        <div class="bracket-match <?= $m86['status_class'] ?>">
            <div class="match-header"><span>Match 86</span> <span>Jul 3</span></div>
            <div class="match-team <?= $m86['h_class'] ?>"><span><?= $m86['home'] ?></span><span><?= $m86['h_score'] ?></span></div>
            <div class="match-team <?= $m86['a_class'] ?>"><span><?= $m86['away'] ?></span><span><?= $m86['a_score'] ?></span></div>
        </div>

        <?php $m88 = renderMatch(88, 'Runner-up Grp D', 'Runner-up Grp G', $bracket_data); ?>
        <div class="bracket-match <?= $m88['status_class'] ?>">
            <div class="match-header"><span>Match 88</span> <span>Jul 3</span></div>
            <div class="match-team <?= $m88['h_class'] ?>"><span><?= $m88['home'] ?></span><span><?= $m88['h_score'] ?></span></div>
            <div class="match-team <?= $m88['a_class'] ?>"><span><?= $m88['away'] ?></span><span><?= $m88['a_score'] ?></span></div>
        </div>

        <?php $m85 = renderMatch(85, 'Winner Group B', '3rd Grp E/F/G/I/J', $bracket_data); ?>
        <div class="bracket-match <?= $m85['status_class'] ?>">
            <div class="match-header"><span>Match 85</span> <span>Jul 2</span></div>
            <div class="match-team <?= $m85['h_class'] ?>"><span><?= $m85['home'] ?></span><span><?= $m85['h_score'] ?></span></div>
            <div class="match-team <?= $m85['a_class'] ?>"><span><?= $m85['away'] ?></span><span><?= $m85['a_score'] ?></span></div>
        </div>

        <?php $m87 = renderMatch(87, 'Winner Group K', '3rd Grp D/E/I/J/L', $bracket_data); ?>
        <div class="bracket-match <?= $m87['status_class'] ?>">
            <div class="match-header"><span>Match 87</span> <span>Jul 3</span></div>
            <div class="match-team <?= $m87['h_class'] ?>"><span><?= $m87['home'] ?></span><span><?= $m87['h_score'] ?></span></div>
            <div class="match-team <?= $m87['a_class'] ?>"><span><?= $m87['away'] ?></span><span><?= $m87['a_score'] ?></span></div>
        </div>
    </div>
    
    <div class="bracket-round">
        <h3 class="round-title">Round of 16</h3>
        
        <?php $m89 = renderMatch(89, 'Winner Match 74', 'Winner Match 77', $bracket_data); ?>
        <div class="bracket-match r16 <?= $m89['status_class'] ?>">
            <div class="match-header"><span>Match 89</span> <span>Jul 4</span></div>
            <div class="match-team <?= $m89['h_class'] ?>"><span><?= $m89['home'] ?></span><span><?= $m89['h_score'] ?></span></div>
            <div class="match-team <?= $m89['a_class'] ?>"><span><?= $m89['away'] ?></span><span><?= $m89['a_score'] ?></span></div>
        </div>

        <?php $m90 = renderMatch(90, 'Winner Match 73', 'Winner Match 75', $bracket_data); ?>
        <div class="bracket-match r16 <?= $m90['status_class'] ?>">
            <div class="match-header"><span>Match 90</span> <span>Jul 4</span></div>
            <div class="match-team <?= $m90['h_class'] ?>"><span><?= $m90['home'] ?></span><span><?= $m90['h_score'] ?></span></div>
            <div class="match-team <?= $m90['a_class'] ?>"><span><?= $m90['away'] ?></span><span><?= $m90['a_score'] ?></span></div>
        </div>

        <div class="bracket-spacer"></div>

        <?php $m94 = renderMatch(94, 'Winner Match 81', 'Winner Match 82', $bracket_data); ?>
        <div class="bracket-match r16 <?= $m94['status_class'] ?>">
            <div class="match-header"><span>Match 94</span> <span>Jul 6</span></div>
            <div class="match-team <?= $m94['h_class'] ?>"><span><?= $m94['home'] ?></span><span><?= $m94['h_score'] ?></span></div>
            <div class="match-team <?= $m94['a_class'] ?>"><span><?= $m94['away'] ?></span><span><?= $m94['a_score'] ?></span></div>
        </div>

        <?php $m93 = renderMatch(93, 'Winner Match 83', 'Winner Match 84', $bracket_data); ?>
        <div class="bracket-match r16 <?= $m93['status_class'] ?>">
            <div class="match-header"><span>Match 93</span> <span>Jul 6</span></div>
            <div class="match-team <?= $m93['h_class'] ?>"><span><?= $m93['home'] ?></span><span><?= $m93['h_score'] ?></span></div>
            <div class="match-team <?= $m93['a_class'] ?>"><span><?= $m93['away'] ?></span><span><?= $m93['a_score'] ?></span></div>
        </div>

        <div class="bracket-spacer-large"></div>

        <?php $m91 = renderMatch(91, 'Winner Match 76', 'Winner Match 78', $bracket_data); ?>
        <div class="bracket-match r16 <?= $m91['status_class'] ?>">
            <div class="match-header"><span>Match 91</span> <span>Jul 5</span></div>
            <div class="match-team <?= $m91['h_class'] ?>"><span><?= $m91['home'] ?></span><span><?= $m91['h_score'] ?></span></div>
            <div class="match-team <?= $m91['a_class'] ?>"><span><?= $m91['away'] ?></span><span><?= $m91['a_score'] ?></span></div>
        </div>

        <?php $m92 = renderMatch(92, 'Winner Match 79', 'Winner Match 80', $bracket_data); ?>
        <div class="bracket-match r16 <?= $m92['status_class'] ?>">
            <div class="match-header"><span>Match 92</span> <span>Jul 5</span></div>
            <div class="match-team <?= $m92['h_class'] ?>"><span><?= $m92['home'] ?></span><span><?= $m92['h_score'] ?></span></div>
            <div class="match-team <?= $m92['a_class'] ?>"><span><?= $m92['away'] ?></span><span><?= $m92['a_score'] ?></span></div>
        </div>

        <div class="bracket-spacer"></div>

        <?php $m95 = renderMatch(95, 'Winner Match 86', 'Winner Match 88', $bracket_data); ?>
        <div class="bracket-match r16 <?= $m95['status_class'] ?>">
            <div class="match-header"><span>Match 95</span> <span>Jul 7</span></div>
            <div class="match-team <?= $m95['h_class'] ?>"><span><?= $m95['home'] ?></span><span><?= $m95['h_score'] ?></span></div>
            <div class="match-team <?= $m95['a_class'] ?>"><span><?= $m95['away'] ?></span><span><?= $m95['a_score'] ?></span></div>
        </div>

        <?php $m96 = renderMatch(96, 'Winner Match 85', 'Winner Match 87', $bracket_data); ?>
        <div class="bracket-match r16 <?= $m96['status_class'] ?>">
            <div class="match-header"><span>Match 96</span> <span>Jul 7</span></div>
            <div class="match-team <?= $m96['h_class'] ?>"><span><?= $m96['home'] ?></span><span><?= $m96['h_score'] ?></span></div>
            <div class="match-team <?= $m96['a_class'] ?>"><span><?= $m96['away'] ?></span><span><?= $m96['a_score'] ?></span></div>
        </div>
    </div>
    
    <div class="bracket-round">
        <h3 class="round-title">Quarterfinals</h3>
        
        <?php $m97 = renderMatch(97, 'Winner Match 89', 'Winner Match 90', $bracket_data); ?>
        <div class="bracket-match qf <?= $m97['status_class'] ?>">
            <div class="match-header"><span>Match 97</span> <span>Jul 9</span></div>
            <div class="match-team <?= $m97['h_class'] ?>"><span><?= $m97['home'] ?></span><span><?= $m97['h_score'] ?></span></div>
            <div class="match-team <?= $m97['a_class'] ?>"><span><?= $m97['away'] ?></span><span><?= $m97['a_score'] ?></span></div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <?php $m98 = renderMatch(98, 'Winner Match 93', 'Winner Match 94', $bracket_data); ?>
        <div class="bracket-match qf <?= $m98['status_class'] ?>">
            <div class="match-header"><span>Match 98</span> <span>Jul 10</span></div>
            <div class="match-team <?= $m98['h_class'] ?>"><span><?= $m98['home'] ?></span><span><?= $m98['h_score'] ?></span></div>
            <div class="match-team <?= $m98['a_class'] ?>"><span><?= $m98['away'] ?></span><span><?= $m98['a_score'] ?></span></div>
        </div>
        
        <div class="bracket-spacer-xl"></div>
        
        <?php $m99 = renderMatch(99, 'Winner Match 91', 'Winner Match 92', $bracket_data); ?>
        <div class="bracket-match qf <?= $m99['status_class'] ?>">
            <div class="match-header"><span>Match 99</span> <span>Jul 11</span></div>
            <div class="match-team <?= $m99['h_class'] ?>"><span><?= $m99['home'] ?></span><span><?= $m99['h_score'] ?></span></div>
            <div class="match-team <?= $m99['a_class'] ?>"><span><?= $m99['away'] ?></span><span><?= $m99['a_score'] ?></span></div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <?php $m100 = renderMatch(100, 'Winner Match 95', 'Winner Match 96', $bracket_data); ?>
        <div class="bracket-match qf <?= $m100['status_class'] ?>">
            <div class="match-header"><span>Match 100</span> <span>Jul 11</span></div>
            <div class="match-team <?= $m100['h_class'] ?>"><span><?= $m100['home'] ?></span><span><?= $m100['h_score'] ?></span></div>
            <div class="match-team <?= $m100['a_class'] ?>"><span><?= $m100['away'] ?></span><span><?= $m100['a_score'] ?></span></div>
        </div>
    </div>
    
    <div class="bracket-round">
        <h3 class="round-title">Semifinals</h3>
        
        <?php $m101 = renderMatch(101, 'Winner Match 97', 'Winner Match 98', $bracket_data); ?>
        <div class="bracket-match sf <?= $m101['status_class'] ?>">
            <div class="match-header"><span>Match 101</span> <span>Jul 14</span></div>
            <div class="match-team <?= $m101['h_class'] ?>"><span><?= $m101['home'] ?></span><span><?= $m101['h_score'] ?></span></div>
            <div class="match-team <?= $m101['a_class'] ?>"><span><?= $m101['away'] ?></span><span><?= $m101['a_score'] ?></span></div>
            <div class="match-note">🔥 Side of Death Winner</div>
        </div>
        
        <div class="bracket-spacer-mega"></div>
        
        <?php $m102 = renderMatch(102, 'Winner Match 99', 'Winner Match 100', $bracket_data); ?>
        <div class="bracket-match sf <?= $m102['status_class'] ?>">
            <div class="match-header"><span>Match 102</span> <span>Jul 15</span></div>
            <div class="match-team <?= $m102['h_class'] ?>"><span><?= $m102['home'] ?></span><span><?= $m102['h_score'] ?></span></div>
            <div class="match-team <?= $m102['a_class'] ?>"><span><?= $m102['away'] ?></span><span><?= $m102['a_score'] ?></span></div>
            <div class="match-note">✨ Golden Path Winner</div>
        </div>
    </div>
    
    <div class="bracket-round">
        <h3 class="round-title">Final</h3>
        
        <?php $m104 = renderMatch(104, 'Winner SF 1', 'Winner SF 2', $bracket_data); ?>
        <div class="bracket-match final <?= $m104['status_class'] ?>">
            <div class="match-header">🏆 Match 104 - July 19, New Jersey</div>
            <div class="match-team <?= $m104['h_class'] ?>"><span><?= $m104['home'] ?></span><span><?= $m104['h_score'] ?></span></div>
            <div class="match-team <?= $m104['a_class'] ?>"><span><?= $m104['away'] ?></span><span><?= $m104['a_score'] ?></span></div>
            <div class="match-note">FIFA World Cup 2026 Champion</div>
        </div>
        
        <div class="bracket-spacer"></div>
        
        <?php $m103 = renderMatch(103, 'Loser SF 1', 'Loser SF 2', $bracket_data); ?>
        <div class="bracket-match third-place <?= $m103['status_class'] ?>">
            <div class="match-header">🥉 Match 103 - July 18, Miami</div>
            <div class="match-team <?= $m103['h_class'] ?>"><span><?= $m103['home'] ?></span><span><?= $m103['h_score'] ?></span></div>
            <div class="match-team <?= $m103['a_class'] ?>"><span><?= $m103['away'] ?></span><span><?= $m103['a_score'] ?></span></div>
            <div class="match-note">Third Place Playoff</div>
        </div>
    </div>
    
</div>

<div class="panel">
    <h2>📊 Bracket Structure Analysis</h2>
    
    <div class="grid">
        <div class="panel" style="background: #23272a; margin-bottom: 0;">
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
        
        <div class="panel" style="background: #23272a; margin-bottom: 0;">
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

</body>
</html>