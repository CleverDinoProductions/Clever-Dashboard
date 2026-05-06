<?php
// Fetch World Cup group standings (12 groups for 2026)
$stmt = $world_cup_db->query("SELECT DISTINCT group_name FROM wc_groups ORDER BY group_name");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Host nations are pre-assigned to specific groups
$host_groups = [
    'Group A' => 'Mexico',
    'Group B' => 'Canada', 
    'Group D' => 'United States'
];

// Top FIFA ranked teams (based on Pot 1 - November 2025 rankings)
$top_4_fifa = ['Spain', 'Argentina', 'France', 'England'];
$top_5_7_fifa = ['Brazil', 'Portugal', 'Netherlands'];
$top_8_12_fifa = ['Belgium', 'Germany', 'Croatia', 'Morocco', 'Colombia'];
?>

<div class="panel">
    <h2>🌍 World Cup 2026 - Group Stage</h2>
    
    <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #00ff88;">
        <strong style="color: #00ff88;">⚡ 2026 Expanded Format:</strong>
        <span style="color: #dcddde;">
            48 teams divided into <strong>12 groups of 4 teams</strong>
        </span>
        <br>
        <span style="color: #888; font-size: 12px;">
            📊 Advancement: Top 2 from each group (24 teams) + 8 best third-place teams → Round of 32
        </span>
        <br>
        <span style="color: #888; font-size: 11px; margin-top: 5px; display: inline-block;">
            🗓️ Draw: December 5, 2025 • 🏟️ Opening Match: Mexico vs TBD, June 11, 2026 @ Estadio Azteca
        </span>
    </div>
    
    <?php if (empty($groups)): ?>
        <div style="text-align: center; padding: 40px; background: #2a2a2a; border-radius: 8px;">
            <div style="font-size: 48px; margin-bottom: 15px;">🏆</div>
            <h3 style="color: #00ff88; margin-bottom: 10px;">World Cup 2026 Data Coming Soon</h3>
            <p style="color: #888; margin-bottom: 10px;">
                <strong>Draw Date:</strong> Friday, December 5, 2025 @ 12:00 PM EST<br>
                <strong>Location:</strong> Kennedy Center, Washington, D.C.
            </p>
            <p style="color: #dcddde; font-size: 14px; margin-bottom: 20px;">
                Tournament: June 11 - July 19, 2026
            </p>
            
            <div style="background: #1a1a1a; padding: 15px; border-radius: 6px; margin: 20px auto; max-width: 600px;">
                <div style="color: #00ff88; font-weight: bold; margin-bottom: 10px;">🇺🇸 🇨🇦 🇲🇽 Host Nations</div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 13px;">
                    <div style="background: #2a2a2a; padding: 10px; border-radius: 4px;">
                        <div style="color: #00ff88; font-weight: bold;">Group A</div>
                        <div style="color: #dcddde;">🇲🇽 Mexico</div>
                        <div style="color: #888; font-size: 11px;">Estadio Azteca</div>
                    </div>
                    <div style="background: #2a2a2a; padding: 10px; border-radius: 4px;">
                        <div style="color: #5865F2; font-weight: bold;">Group B</div>
                        <div style="color: #dcddde;">🇨🇦 Canada</div>
                        <div style="color: #888; font-size: 11px;">BMO Field, Toronto</div>
                    </div>
                    <div style="background: #2a2a2a; padding: 10px; border-radius: 4px;">
                        <div style="color: #f04747; font-weight: bold;">Group D</div>
                        <div style="color: #dcddde;">🇺🇸 USA</div>
                        <div style="color: #888; font-size: 11px;">SoFi Stadium, LA</div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 30px; text-align: left;">
                <div style="background: #1a1a1a; padding: 15px; border-radius: 6px;">
                    <div style="color: #00ff88; font-weight: bold; margin-bottom: 5px;">🌍 48 Teams</div>
                    <div style="font-size: 12px; color: #888;">First expanded tournament</div>
                    <div style="font-size: 11px; color: #dcddde; margin-top: 5px;">42 qualified, 6 TBD</div>
                </div>
                <div style="background: #1a1a1a; padding: 15px; border-radius: 6px;">
                    <div style="color: #5865F2; font-weight: bold; margin-bottom: 5px;">📊 12 Groups</div>
                    <div style="font-size: 12px; color: #888;">Groups A through L</div>
                    <div style="font-size: 11px; color: #dcddde; margin-top: 5px;">4 teams per group</div>
                </div>
                <div style="background: #1a1a1a; padding: 15px; border-radius: 6px;">
                    <div style="color: #faa61a; font-weight: bold; margin-bottom: 5px;">⚔️ Round of 32</div>
                    <div style="font-size: 12px; color: #888;">New knockout stage</div>
                    <div style="font-size: 11px; color: #dcddde; margin-top: 5px;">June 28 - July 3</div>
                </div>
                <div style="background: #1a1a1a; padding: 15px; border-radius: 6px;">
                    <div style="color: #f04747; font-weight: bold; margin-bottom: 5px;">🏟️ 16 Venues</div>
                    <div style="font-size: 12px; color: #888;">USA (11), Mexico (3), Canada (2)</div>
                    <div style="font-size: 11px; color: #dcddde; margin-top: 5px;">104 total matches</div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- 12 Groups Display Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
            <?php 
            // Define all 12 groups with host nations
            $all_groups = ['Group A', 'Group B', 'Group C', 'Group D', 'Group E', 'Group F', 
                          'Group G', 'Group H', 'Group I', 'Group J', 'Group K', 'Group L'];
            
            foreach ($all_groups as $expected_group): 
                // Find if this group exists in data
                $group_exists = false;
                $group_name = '';
                foreach ($groups as $group) {
                    if ($group['group_name'] == $expected_group) {
                        $group_exists = true;
                        $group_name = $group['group_name'];
                        break;
                    }
                }
                
                // Fetch teams for this group
                if ($group_exists) {
                    $teams_stmt = $world_cup_db->prepare("SELECT * FROM wc_groups WHERE group_name = ? ORDER BY position ASC");
                    $teams_stmt->execute([$group_name]);
                    $teams = $teams_stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $teams = [];
                    $group_name = $expected_group;
                }
                
                // Determine group color based on letter
                $group_letter = substr($group_name, -1);
                $group_colors = [
                    'A' => '#00ff88', 'B' => '#5865F2', 'C' => '#faa61a', 'D' => '#f04747',
                    'E' => '#00ff88', 'F' => '#5865F2', 'G' => '#faa61a', 'H' => '#f04747',
                    'I' => '#00ff88', 'J' => '#5865F2', 'K' => '#faa61a', 'L' => '#f04747'
                ];
                $border_color = isset($group_colors[$group_letter]) ? $group_colors[$group_letter] : '#00ff88';
                
                // Check if this is a host group
                $is_host_group = isset($host_groups[$group_name]);
                $host_nation = $is_host_group ? $host_groups[$group_name] : null;
            ?>
                
                <div style="background: #2a2a2a; padding: 15px; border-radius: 8px; border-top: 3px solid <?= $border_color ?>; <?= $is_host_group ? 'box-shadow: 0 0 10px rgba(0, 255, 136, 0.3);' : '' ?>">
                    <h3 style="margin-top: 0; color: <?= $border_color ?>; display: flex; justify-content: space-between; align-items: center;">
                        <span>
                            <?= htmlspecialchars($group_name) ?>
                            <?php if ($is_host_group): ?>
                                <span style="font-size: 11px; background: rgba(0, 255, 136, 0.2); padding: 2px 6px; border-radius: 3px; margin-left: 5px; color: #00ff88;">HOST</span>
                            <?php endif; ?>
                        </span>
                        <span style="font-size: 12px; font-weight: normal; color: #888;">
                            <?= count($teams) ?>/4
                        </span>
                    </h3>
                    
                    <?php if (empty($teams)): ?>
                        <div style="color: #888; text-align: center; padding: 20px; font-size: 13px;">
                            <?php if ($host_nation): ?>
                                <div style="background: #1a1a1a; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                                    <strong style="color: #00ff88;">1. <?= $host_nation ?> ★</strong>
                                    <div style="font-size: 11px; color: #888; margin-top: 3px;">Co-host nation</div>
                                </div>
                            <?php endif; ?>
                            <p>Remaining teams TBD<br>
                            <span style="font-size: 11px;">Draw: Dec 5, 2025</span></p>
                        </div>
                    <?php else: ?>
                        <table style="width: 100%; font-size: 13px;">
                            <tr style="background: #1a1a1a;">
                                <th style="text-align: left; padding: 8px; width: 30px;">Pos</th>
                                <th style="text-align: left;">Team</th>
                                <th style="width: 30px;">P</th>
                                <th style="width: 30px;">W</th>
                                <th style="width: 30px;">D</th>
                                <th style="width: 30px;">L</th>
                                <th style="width: 35px;">GD</th>
                                <th style="width: 35px;">Pts</th>
                            </tr>
                            <?php foreach ($teams as $team): ?>
                                <?php
                                $row_style = '';
                                $status_icon = '';
                                
                                // Check if team is in top FIFA rankings
                                $is_top_4 = in_array($team['team_name'], $top_4_fifa);
                                $is_top_5_7 = in_array($team['team_name'], $top_5_7_fifa);
                                $is_top_8_12 = in_array($team['team_name'], $top_8_12_fifa);
                                $is_host = in_array($team['team_name'], ['Mexico', 'Canada', 'United States']);
                                
                                // Determine row styling based on position
                                if ($team['position'] <= 2) {
                                    // Top 2 advance automatically
                                    if ($is_top_4) {
                                        $row_style = 'background: linear-gradient(90deg, rgba(255, 215, 0, 0.15), rgba(67, 181, 129, 0.15)); border-left: 3px solid #FFD700;';
                                    } elseif ($is_top_5_7) {
                                        $row_style = 'background: linear-gradient(90deg, rgba(192, 192, 192, 0.15), rgba(67, 181, 129, 0.15)); border-left: 3px solid #C0C0C0;';
                                    } else {
                                        $row_style = 'background: rgba(67, 181, 129, 0.15); border-left: 3px solid #43b581;';
                                    }
                                    $status_icon = '<span style="color: #43b581; font-size: 11px;">✓</span>';
                                } elseif ($team['position'] == 3) {
                                    // 3rd place - potential advancement
                                    if ($is_top_4) {
                                        $row_style = 'background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), rgba(250, 166, 26, 0.1)); border-left: 3px solid #FFD700;';
                                    } elseif ($is_top_5_7) {
                                        $row_style = 'background: linear-gradient(90deg, rgba(192, 192, 192, 0.1), rgba(250, 166, 26, 0.1)); border-left: 3px solid #C0C0C0;';
                                    } else {
                                        $row_style = 'background: rgba(250, 166, 26, 0.1); border-left: 3px solid #faa61a;';
                                    }
                                    $status_icon = '<span style="color: #faa61a; font-size: 11px;">?</span>';
                                } else {
                                    // 4th place - eliminated
                                    $row_style = 'background: rgba(136, 136, 136, 0.1);';
                                    $status_icon = '<span style="color: #888; font-size: 11px;">✗</span>';
                                }
                                ?>
                                <tr style="<?= $row_style ?>">
                                    <td style="padding: 6px;">
                                        <strong><?= $team['position'] ?></strong>
                                        <?= $status_icon ?>
                                    </td>
                                    <td style="font-size: 12px;">
                                        <?= htmlspecialchars($team['team_name']) ?>
                                        <?php if ($is_host): ?>
                                            <span style="color: #00ff88;" title="Host Nation">★</span>
                                        <?php endif; ?>
                                        <?php if ($is_top_4): ?>
                                            <span style="color: #FFD700;" title="Top 4 FIFA Ranked">🏆</span>
                                        <?php elseif ($is_top_5_7): ?>
                                            <span style="color: #C0C0C0;" title="Top 5-7 FIFA Ranked">⚡</span>
                                        <?php elseif ($is_top_8_12): ?>
                                            <span style="color: #CD7F32;" title="Top 8-12 FIFA Ranked">🔶</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;"><?= $team['played'] ?></td>
                                    <td style="text-align: center;"><?= $team['won'] ?></td>
                                    <td style="text-align: center;"><?= $team['drawn'] ?></td>
                                    <td style="text-align: center;"><?= $team['lost'] ?></td>
                                    <td style="text-align: center; color: <?= $team['gd'] > 0 ? '#43b581' : ($team['gd'] < 0 ? '#f04747' : '#dcddde') ?>; font-weight: bold;">
                                        <?= $team['gd'] > 0 ? '+' . $team['gd'] : $team['gd'] ?>
                                    </td>
                                    <td style="text-align: center;"><strong><?= $team['points'] ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Legend -->
        <div style="margin-top: 30px; padding: 15px; background: #40444b; border-radius: 8px;">
            <strong style="color: #00ff88;">Legend:</strong>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px; font-size: 12px;">
                <div>
                    <span style="color: #43b581;">✓</span> 
                    <strong style="color: #43b581;">Top 2:</strong> 
                    <span style="color: #dcddde;">Auto-qualify</span>
                </div>
                <div>
                    <span style="color: #faa61a;">?</span> 
                    <strong style="color: #faa61a;">3rd Place:</strong> 
                    <span style="color: #dcddde;">Best 8 advance</span>
                </div>
                <div>
                    <span style="color: #888;">✗</span> 
                    <strong style="color: #888;">4th Place:</strong> 
                    <span style="color: #dcddde;">Eliminated</span>
                </div>
                <div>
                    <span style="color: #00ff88;">★</span> 
                    <strong style="color: #00ff88;">Host:</strong> 
                    <span style="color: #dcddde;">USA/Canada/Mexico</span>
                </div>
                <div>
                    <span style="color: #FFD700;">🏆</span> 
                    <strong style="color: #FFD700;">Top 4 FIFA:</strong> 
                    <span style="color: #dcddde;">Pot 1 elite</span>
                </div>
                <div>
                    <span style="color: #C0C0C0;">⚡</span> 
                    <strong style="color: #C0C0C0;">Rank 5-7:</strong> 
                    <span style="color: #dcddde;">Top contenders</span>
                </div>
                <div>
                    <span style="color: #CD7F32;">🔶</span> 
                    <strong style="color: #CD7F32;">Rank 8-12:</strong> 
                    <span style="color: #dcddde;">Strong teams</span>
                </div>
            </div>
        </div>
        
        <!-- Tournament Info -->
        <div style="margin-top: 20px; padding: 15px; background: #2a2a2a; border-radius: 8px; font-size: 12px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; color: #dcddde;">
                <div>
                    <strong style="color: #00ff88;">📊 Total Teams:</strong> 48 (expanded from 32)
                </div>
                <div>
                    <strong style="color: #5865F2;">🏟️ Groups:</strong> 12 groups of 4 teams
                </div>
                <div>
                    <strong style="color: #faa61a;">⚔️ To Knockout:</strong> 32 teams (24 + 8 third-place)
                </div>
                <div>
                    <strong style="color: #f04747;">📅 Group Stage:</strong> June 11-27, 2026
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>