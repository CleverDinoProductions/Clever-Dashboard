<?php

if (!function_exists('football_stats_get_team_colors')) {
    function football_stats_get_team_colors($teamName) {
        $map = [
            // Premier League
            'Arsenal'                    => ['#EF0107', '#FFFFFF'],
            'Aston Villa'                => ['#7B0D27', '#95BFE5'],
            'Brentford'                  => ['#E30613', '#FFFFFF'],
            'Brighton & Hove Albion'     => ['#0057B8', '#FFCD00'],
            'Brighton'                   => ['#0057B8', '#FFCD00'],
            'Chelsea'                    => ['#034694', '#FFFFFF'],
            'Crystal Palace'             => ['#1B458F', '#C4122E'],
            'Everton'                    => ['#003399', '#FFFFFF'],
            'Fulham'                     => ['#000000', '#FFFFFF'],
            'Ipswich Town'               => ['#3A64A3', '#FFFFFF'],
            'Leeds United'               => ['#1D428A', '#FFCD00'],
            'Leicester City'             => ['#003090', '#FDBE11'],
            'Liverpool'                  => ['#C8102E', '#F6EB61'],
            'Manchester City'            => ['#6CABDD', '#1C2C5B'],
            'Manchester United'          => ['#DA291C', '#FBE122'],
            'Newcastle United'           => ['#241F20', '#FFFFFF'],
            "Nottingham Forest"          => ['#DD0000', '#FFFFFF'],
            "Nott'm Forest"              => ['#DD0000', '#FFFFFF'],
            'Southampton'                => ['#D71920', '#FFFFFF'],
            'Tottenham Hotspur'          => ['#132257', '#FFFFFF'],
            'Tottenham'                  => ['#132257', '#FFFFFF'],
            'West Ham United'            => ['#7A263A', '#1BB1E7'],
            'West Ham'                   => ['#7A263A', '#1BB1E7'],
            'Wolverhampton Wanderers'    => ['#FDB913', '#231F20'],
            'Wolves'                     => ['#FDB913', '#231F20'],
            // Championship
            'Blackburn Rovers'           => ['#009EE0', '#FFFFFF'],
            'Bristol City'               => ['#E3001B', '#FFFFFF'],
            'Burnley'                    => ['#6C1D45', '#99D6EA'],
            'Cardiff City'               => ['#003399', '#D01317'],
            'Coventry City'              => ['#87CEEB', '#003087'],
            'Derby County'               => ['#001C4F', '#FFFFFF'],
            'Hull City'                  => ['#F5A12D', '#000000'],
            'Luton Town'                 => ['#F78F1E', '#FFFFFF'],
            'Middlesbrough'              => ['#E01A22', '#FFFFFF'],
            'Millwall'                   => ['#001D5E', '#FFFFFF'],
            'Norwich City'               => ['#00A650', '#FFF200'],
            'Oxford United'              => ['#002060', '#FFBD00'],
            'Plymouth Argyle'            => ['#007858', '#FFFFFF'],
            'Portsmouth'                 => ['#001C68', '#FFFFFF'],
            'Preston North End'          => ['#00314D', '#FFFFFF'],
            "Queen's Park Rangers"       => ['#005CAB', '#FFFFFF'],
            'QPR'                        => ['#005CAB', '#FFFFFF'],
            'Sheffield United'           => ['#EE2737', '#FFFFFF'],
            'Sheffield Wednesday'        => ['#0066B3', '#FFFFFF'],
            'Stoke City'                 => ['#E03A3E', '#FFFFFF'],
            'Sunderland'                 => ['#EB172B', '#FFFFFF'],
            'Swansea City'               => ['#000000', '#FFFFFF'],
            'Watford'                    => ['#FBEE23', '#ED2127'],
            'West Bromwich Albion'       => ['#122F67', '#FFFFFF'],
            'West Brom'                  => ['#122F67', '#FFFFFF'],
            // League One
            'Barnsley'                   => ['#CC0000', '#FFFFFF'],
            'Birmingham City'            => ['#003399', '#FFFFFF'],
            'Bolton Wanderers'           => ['#001C4F', '#FFFFFF'],
            'Bristol Rovers'             => ['#004494', '#FFFFFF'],
            'Burton Albion'              => ['#F6A11E', '#000000'],
            'Charlton Athletic'          => ['#D4122C', '#FFFFFF'],
            'Crawley Town'               => ['#C00000', '#FFFFFF'],
            'Exeter City'                => ['#C8102E', '#FFFFFF'],
            'Huddersfield Town'          => ['#0E63AD', '#FFFFFF'],
            'Leyton Orient'              => ['#E0001A', '#FFFFFF'],
            'Lincoln City'               => ['#C8102E', '#FFFFFF'],
            'Mansfield Town'             => ['#FDBE11', '#001F5B'],
            'Northampton Town'           => ['#9B1A2E', '#FFFFFF'],
            'Peterborough United'        => ['#1A69AD', '#FFFFFF'],
            'Rotherham United'           => ['#EE1C25', '#FFFFFF'],
            'Stevenage'                  => ['#FF0000', '#FFFFFF'],
            'Stockport County'           => ['#003B5C', '#FFFFFF'],
            'Wigan Athletic'             => ['#1B4887', '#FFFFFF'],
            'Wrexham'                    => ['#E0001A', '#FFFFFF'],
            // League Two
            'Accrington Stanley'         => ['#B60003', '#FFFFFF'],
            'AFC Wimbledon'              => ['#013D82', '#FFCD00'],
            'Bradford City'              => ['#7E0A24', '#FFBF00'],
            'Cambridge United'           => ['#F5A12D', '#000000'],
            'Cheltenham Town'            => ['#E01A22', '#FFFFFF'],
            'Chesterfield'               => ['#1D428A', '#FFFFFF'],
            'Colchester United'          => ['#003DA5', '#FFFFFF'],
            'Crewe Alexandra'            => ['#DD0000', '#FFFFFF'],
            'Doncaster Rovers'           => ['#C8102E', '#FFFFFF'],
            'Fleetwood Town'             => ['#E03A3E', '#FFFFFF'],
            'Gillingham'                 => ['#003DA5', '#FFFFFF'],
            'Grimsby Town'               => ['#000000', '#FFFFFF'],
            'Harrogate Town'             => ['#003087', '#FDE30C'],
            'Morecambe'                  => ['#FF0000', '#FFFFFF'],
            'Newport County'             => ['#000000', '#FEC001'],
            'Notts County'               => ['#000000', '#FFFFFF'],
            'Salford City'               => ['#DA291C', '#000000'],
            'Shrewsbury Town'            => ['#0065BD', '#FFBF00'],
            'Swindon Town'               => ['#CC0000', '#FFFFFF'],
            'Tranmere Rovers'            => ['#002067', '#FFFFFF'],
            'Walsall'                    => ['#C00000', '#FFFFFF'],
            // National League
            'Aldershot Town'        => ['#C8102E', '#FFFFFF'],
            'Altrincham'            => ['#C8102E', '#FFFFFF'],
            'Boreham Wood'          => ['#FFFFFF', '#000000'],
            'Boston United'         => ['#FFBF00', '#000000'],
            'Brackley Town'         => ['#C8102E', '#FFD700'],
            'Braintree Town'        => ['#F47920', '#000000'],
            'Carlisle United'       => ['#003DA5', '#FFFFFF'],
            'Eastleigh'             => ['#001489', '#FFFFFF'],
            'FC Halifax Town'       => ['#003087', '#F5A12D'],
            'Forest Green Rovers'   => ['#00712D', '#FFFFFF'],
            'Gateshead'             => ['#000000', '#FFFFFF'],
            'Hartlepool United'     => ['#003087', '#FFFFFF'],
            'Rochdale'              => ['#003DA5', '#FFFFFF'],
            'Scunthorpe United'     => ['#800020', '#FFFFFF'],
            'Solihull Moors'        => ['#FDB827', '#000000'],
            'Southend United'       => ['#003090', '#FFFFFF'],
            'Sutton United'         => ['#C8102E', '#FFCD00'],
            'Tamworth'              => ['#C8102E', '#000000'],
            'Truro City'            => ['#FFFFFF', '#000000'],
            'Wealdstone'            => ['#C8102E', '#FFFFFF'],
            'Woking'                => ['#C8102E', '#FFFFFF'],
            'Yeovil Town'           => ['#009A44', '#FFFFFF'],
            'York City'             => ['#C8102E', '#FFFFFF'],
        ];

        if (isset($map[$teamName])) {
            return ['primary' => $map[$teamName][0], 'secondary' => $map[$teamName][1]];
        }

        foreach ($map as $name => $colors) {
            if (stripos($teamName, $name) !== false || stripos($name, $teamName) !== false) {
                return ['primary' => $colors[0], 'secondary' => $colors[1]];
            }
        }

        return ['primary' => '#2e3136', 'secondary' => '#5865F2'];
    }
}

if (!function_exists('football_stats_get_best_text_color')) {
    function football_stats_get_best_text_color($primary, $secondary, $bg = '#2e3136') {
        $lum = function($hex) {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;
            $toLinear = function($c) { return $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4); };
            return 0.2126 * $toLinear($r) + 0.7152 * $toLinear($g) + 0.0722 * $toLinear($b);
        };
        $bgL  = $lum($bg) + 0.05;
        $priL = $lum($primary) + 0.05;
        $secL = $lum($secondary) + 0.05;
        $priContrast = max($priL, $bgL) / min($priL, $bgL);
        $secContrast = max($secL, $bgL) / min($secL, $bgL);
        return $secContrast >= $priContrast ? $secondary : $primary;
    }
}

if (!function_exists('football_stats_get_tracker_team')) {
    function football_stats_get_tracker_team($standings, $defaultTeamName = null) {
        $requestedTeam = isset($_GET['tracker_team']) ? trim($_GET['tracker_team']) : null;

        if ($requestedTeam) {
            foreach ($standings as $row) {
                if ($row['team_name'] === $requestedTeam) {
                    return $row;
                }
            }
        }

        if ($defaultTeamName) {
            foreach ($standings as $row) {
                if ($row['team_name'] === $defaultTeamName || stripos($row['team_name'], $defaultTeamName) !== false) {
                    return $row;
                }
            }
        }

        foreach ($standings as $row) {
            if ((int)$row['position'] === 1) {
                return $row;
            }
        }

        return !empty($standings) ? reset($standings) : null;
    }
}

if (!function_exists('football_stats_render_team_selector')) {
    function football_stats_render_team_selector($standings, $selectedTeamName) {
        $baseParams = [];
        foreach (['tab', 'league', 'subtab', 'table_view', 'snapshot_season', 'snapshot_date', 'matchweek', 'calc_mode'] as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $baseParams[$key] = $_GET[$key];
            }
        }
        ?>
        <div style="margin: 16px 0; text-align: center;">
            <form method="get" action="" style="display: inline-flex; align-items: center; gap: 12px; background: rgba(0,0,0,0.35); padding: 12px 20px; border-radius: 8px; border: 2px solid rgba(255,255,255,0.2);">
                <?php foreach ($baseParams as $key => $val): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES) ?>" value="<?= htmlspecialchars($val, ENT_QUOTES) ?>">
                <?php endforeach; ?>
                <label style="color: #FFFFFF; font-weight: bold; font-size: 15px; white-space: nowrap;">
                    🔍 Select Team:
                </label>
                <select name="tracker_team" onchange="this.form.submit()"
                        style="background: #40444b; color: #FFFFFF; border: 2px solid #5865F2; border-radius: 6px; padding: 8px 12px; font-size: 15px; cursor: pointer; min-width: 220px;">
                    <?php foreach ($standings as $row): ?>
                        <option value="<?= htmlspecialchars($row['team_name'], ENT_QUOTES) ?>"
                            <?= ($row['team_name'] === $selectedTeamName) ? 'selected' : '' ?>>
                            #<?= (int)$row['position'] ?> <?= htmlspecialchars($row['team_name']) ?>
                            (<?= (int)$row['points'] ?> pts)
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" style="background:#5865F2;color:#fff;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;">Go</button></noscript>
            </form>
        </div>
        <?php
    }
}
