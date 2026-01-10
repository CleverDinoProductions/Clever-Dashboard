<?php
// TIMEZONES TAB - Global Activity Patterns During Invite Windows

$window_date = $_GET['window'] ?? null;
$view = $_GET['view'] ?? 'overview'; // overview, specific_window

$voice_channel = '#anonamouse.net';

// Timezone regions to track
$timezones = [
    'US-West' => ['offset' => -8, 'name' => 'US West (PST)', 'color' => '#5865F2'],
    'US-East' => ['offset' => -5, 'name' => 'US East (EST)', 'color' => '#5865F2'],
    'UK' => ['offset' => 0, 'name' => 'UK (GMT)', 'color' => '#43b581'],
    'EU-Central' => ['offset' => 1, 'name' => 'EU Central (CET)', 'color' => '#43b581'],
    'India' => ['offset' => 5.5, 'name' => 'India (IST)', 'color' => '#faa61a'],
    'China' => ['offset' => 8, 'name' => 'China (CST)', 'color' => '#faa61a'],
    'Australia' => ['offset' => 11, 'name' => 'Australia (AEDT)', 'color' => '#f04747'],
];

// Helper: Check if hour is "awake time" (6am-2am local = 06:00-26:00)
function is_awake_hour($hour_utc, $tz_offset) {
    $local_hour = $hour_utc + $tz_offset;
    if ($local_hour < 0) $local_hour += 24;
    if ($local_hour >= 24) $local_hour -= 24;
    
    // Awake = 6am to 2am next day (06:00-26:00, or 06:00-02:00 wrapped)
    return $local_hour >= 6 || $local_hour < 2;
}

// Get all historical windows
$stmt = $db->query("
    SELECT DISTINCT 
        date(time/1000, 'unixepoch') as date,
        strftime('%w', time/1000, 'unixepoch') as dow
    FROM messages
    WHERE channel = '$voice_channel'
    AND type = 'mode'
    AND json_extract(msg, '$.text') LIKE '+v %'
    AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
    ORDER BY date DESC
    LIMIT 10
");
$recent_windows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($window_date) {
    // SPECIFIC WINDOW VIEW
    $start_time = strtotime($window_date . ' 08:00:00 UTC');
    $end_time = $start_time + (25 * 3600);
    $dow = date('w', $start_time);
    
    $window_detail = [
        'date' => $window_date,
        'type' => ($dow == 3) ? 'Wednesday' : 'Saturday',
        'start' => date('l, M j, Y H:i', $start_time) . ' UTC',
        'end' => date('l, M j, Y H:i', $end_time) . ' UTC',
    ];
    
    // Get hourly activity for this window
    $stmt = $db->prepare("
        SELECT 
            CAST(strftime('%H', time/1000, 'unixepoch') as INTEGER) as hour_utc,
            COUNT(*) as activity
        FROM messages
        WHERE channel = :channel
        AND time >= :start_time
        AND time <= :end_time
        GROUP BY hour_utc
        ORDER BY hour_utc
    ");
    $stmt->execute([
        'channel' => $voice_channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $hourly_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build 25-hour array
    $activity_by_hour = array_fill(0, 25, 0);
    foreach ($hourly_activity as $row) {
        $hour = $row['hour_utc'];
        // Map to window hours (hour 0 = 08:00 UTC)
        if ($hour >= 8) {
            $window_hour = $hour - 8;
        } else {
            $window_hour = $hour + 16; // Next day hours
        }
        if ($window_hour < 25) {
            $activity_by_hour[$window_hour] = $row['activity'];
        }
    }
    
    $max_activity = max($activity_by_hour);
    if ($max_activity == 0) $max_activity = 1;
    
} else {
    // OVERVIEW: Calculate average activity by hour across all windows
    $stmt = $db->query("
        SELECT 
            CAST(strftime('%H', time/1000, 'unixepoch') as INTEGER) as hour_utc,
            COUNT(*) as total_activity,
            COUNT(DISTINCT date(time/1000, 'unixepoch')) as window_count
        FROM messages
        WHERE channel = '$voice_channel'
        AND strftime('%w', time/1000, 'unixepoch') IN ('3', '4', '6', '0')
        GROUP BY hour_utc
    ");
    $avg_hourly = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build average activity array
    $avg_activity_by_hour = [];
    foreach ($avg_hourly as $row) {
        $avg_activity_by_hour[$row['hour_utc']] = $row['window_count'] > 0 
            ? round($row['total_activity'] / $row['window_count'], 1) 
            : 0;
    }
    
    // Reorder to start at 08:00 UTC (window start)
    $activity_by_hour = [];
    for ($i = 0; $i < 25; $i++) {
        $utc_hour = (8 + $i) % 24;
        $activity_by_hour[$i] = $avg_activity_by_hour[$utc_hour] ?? 0;
    }
    
    $max_activity = max($activity_by_hour);
    if ($max_activity == 0) $max_activity = 1;
}
?>

<!-- TIMEZONES TAB -->

<div class="panel">
    <h2>🌍 Global Activity Patterns</h2>
    <?php if ($window_date): ?>
        <h3 style="color: #888; font-size: 16px; margin-top: 5px;">
            <?= $window_detail['type'] ?> Window • <?= $window_detail['start'] ?>
        </h3>
    <?php else: ?>
        <p style="color: #888;">Average activity across all invite windows • Shows when different timezones are likely awake</p>
    <?php endif; ?>
</div>

<!-- Recent Windows Selector -->
<?php if (!$window_date && !empty($recent_windows)): ?>
<div class="panel">
    <h3>📅 Select Specific Window</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
        <?php foreach ($recent_windows as $win): ?>
        <a href="?tab=timezones&window=<?= $win['date'] ?>" style="text-decoration: none;">
            <div style="background: #40444b; padding: 15px; border-radius: 8px; text-align: center; transition: all 0.2s;"
                 onmouseover="this.style.background='#4752c4'" 
                 onmouseout="this.style.background='#40444b'">
                <div style="font-size: 18px; font-weight: bold; color: #5865F2;">
                    <?= date('M j, Y', strtotime($win['date'])) ?>
                </div>
                <div style="color: #888; font-size: 13px; margin-top: 5px;">
                    <?= $win['dow'] == '3' ? 'Wednesday' : 'Saturday' ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <p style="margin-top: 15px;"><a href="?tab=timezones" style="color: #5865F2;">« Back to overview</a></p>
</div>
<?php elseif ($window_date): ?>
<div style="margin-bottom: 20px;">
    <a href="?tab=timezones" style="color: #5865F2; text-decoration: none;">« Back to overview</a>
</div>
<?php endif; ?>

<!-- Timezone Activity Grid -->
<div class="panel">
    <h2>⏰ 25-Hour Window Timeline</h2>
    <p style="color: #888; margin-bottom: 20px;">
        Green = Awake hours (6am-2am local) • Gray = Sleep hours (2am-6am local) • Bar = Activity level
    </p>
    
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 10px; position: sticky; left: 0; background: #2f3136; z-index: 10;">Timezone</th>
                    <?php for ($h = 0; $h < 25; $h++): ?>
                    <?php 
                    $utc_hour = (8 + $h) % 24;
                    $display_hour = $utc_hour;
                    ?>
                    <th style="padding: 5px; font-size: 11px; min-width: 40px;">
                        <?= sprintf('%02d:00', $display_hour) ?><br>
                        <span style="color: #888; font-size: 9px;">UTC</span>
                    </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timezones as $tz_key => $tz): ?>
                <tr>
                    <td style="padding: 10px; font-weight: bold; background: #2f3136; position: sticky; left: 0; z-index: 5;">
                        <span style="color: <?= $tz['color'] ?>;">●</span> <?= $tz['name'] ?>
                    </td>
                    <?php for ($h = 0; $h < 25; $h++): ?>
                    <?php
                    $utc_hour = (8 + $h) % 24;
                    $is_awake = is_awake_hour($utc_hour, $tz['offset']);
                    $local_hour = $utc_hour + $tz['offset'];
                    if ($local_hour < 0) $local_hour += 24;
                    if ($local_hour >= 24) $local_hour -= 24;
                    
                    $activity_pct = $max_activity > 0 ? ($activity_by_hour[$h] / $max_activity) : 0;
                    $bg_color = $is_awake 
                        ? "rgba(67, 181, 129, " . (0.2 + ($activity_pct * 0.8)) . ")" 
                        : "rgba(50, 50, 55, " . (0.3 + ($activity_pct * 0.4)) . ")";
                    ?>
                    <td style="padding: 5px; text-align: center; background: <?= $bg_color ?>; border: 1px solid #1a1a1e;" title="<?= sprintf('%02d:00 local', $local_hour) ?>">
                        <div style="font-size: 10px; font-weight: bold; color: <?= $is_awake ? '#dcddde' : '#666' ?>;">
                            <?= sprintf('%02d', $local_hour) ?>
                        </div>
                        <?php if ($activity_by_hour[$h] > 0): ?>
                        <div style="font-size: 9px; color: <?= $is_awake ? '#faa61a' : '#888' ?>;">
                            <?= round($activity_by_hour[$h]) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>
                
                <!-- Activity bar row -->
                <tr style="border-top: 2px solid #5865F2;">
                    <td style="padding: 10px; font-weight: bold; background: #2f3136; position: sticky; left: 0; z-index: 5;">
                        📊 Total Activity
                    </td>
                    <?php for ($h = 0; $h < 25; $h++): ?>
                    <?php
                    $activity_pct = $max_activity > 0 ? ($activity_by_hour[$h] / $max_activity) : 0;
                    $bar_height = $activity_pct * 40;
                    ?>
                    <td style="padding: 5px; text-align: center; vertical-align: bottom;">
                        <div style="display: inline-block; width: 30px; height: <?= $bar_height ?>px; background: #5865F2; border-radius: 3px 3px 0 0;"></div>
                        <div style="font-size: 10px; color: #dcddde; margin-top: 3px;">
                            <?= round($activity_by_hour[$h]) ?>
                        </div>
                    </td>
                    <?php endfor; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Peak Hours Analysis -->
<div class="panel">
    <h2>📈 Activity Analysis</h2>
    <div class="grid">
        <?php
        // Find peak hour
        $peak_hour_idx = array_search(max($activity_by_hour), $activity_by_hour);
        $peak_utc_hour = (8 + $peak_hour_idx) % 24;
        
        // Count awake regions per hour
        $awake_counts = [];
        for ($h = 0; $h < 25; $h++) {
            $utc_hour = (8 + $h) % 24;
            $awake_count = 0;
            foreach ($timezones as $tz) {
                if (is_awake_hour($utc_hour, $tz['offset'])) {
                    $awake_count++;
                }
            }
            $awake_counts[$h] = $awake_count;
        }
        $most_awake_hour_idx = array_search(max($awake_counts), $awake_counts);
        $most_awake_utc_hour = (8 + $most_awake_hour_idx) % 24;
        ?>
        
        <div style="background: #40444b; padding: 15px; border-radius: 8px;">
            <h3 style="color: #5865F2; font-size: 14px; margin-bottom: 5px;">Peak Activity Hour</h3>
            <div style="font-size: 32px; font-weight: bold;"><?= sprintf('%02d:00', $peak_utc_hour) ?> UTC</div>
            <p style="color: #888; font-size: 12px;">Highest message count</p>
        </div>
        
        <div style="background: #40444b; padding: 15px; border-radius: 8px;">
            <h3 style="color: #43b581; font-size: 14px; margin-bottom: 5px;">Most Global Hour</h3>
            <div style="font-size: 32px; font-weight: bold;"><?= sprintf('%02d:00', $most_awake_utc_hour) ?> UTC</div>
            <p style="color: #888; font-size: 12px;"><?= max($awake_counts) ?> regions awake</p>
        </div>
        
        <div style="background: #40444b; padding: 15px; border-radius: 8px;">
            <h3 style="color: #faa61a; font-size: 14px; margin-bottom: 5px;">Total Messages</h3>
            <div style="font-size: 32px; font-weight: bold;"><?= number_format(array_sum($activity_by_hour)) ?></div>
            <p style="color: #888; font-size: 12px;">Across 25-hour window</p>
        </div>
        
        <div style="background: #40444b; padding: 15px; border-radius: 8px;">
            <h3 style="color: #5865F2; font-size: 14px; margin-bottom: 5px;">Avg Per Hour</h3>
            <div style="font-size: 32px; font-weight: bold;"><?= round(array_sum($activity_by_hour) / 25, 1) ?></div>
            <p style="color: #888; font-size: 12px;">Messages per hour</p>
        </div>
    </div>
</div>

<!-- Regional Breakdown -->
<div class="panel">
    <h2>🌎 Regional Activity Patterns</h2>
    <table>
        <tr>
            <th>Region</th>
            <th>Active Hours in Window</th>
            <th>Peak Local Time</th>
            <th>Total Activity</th>
        </tr>
        <?php foreach ($timezones as $tz_key => $tz): ?>
        <?php
        // Count awake hours and calculate activity during awake hours
        $awake_hours = 0;
        $awake_activity = 0;
        $peak_local_activity = 0;
        $peak_local_hour = 0;
        
        for ($h = 0; $h < 25; $h++) {
            $utc_hour = (8 + $h) % 24;
            $local_hour = $utc_hour + $tz['offset'];
            if ($local_hour < 0) $local_hour += 24;
            if ($local_hour >= 24) $local_hour -= 24;
            
            if (is_awake_hour($utc_hour, $tz['offset'])) {
                $awake_hours++;
                $awake_activity += $activity_by_hour[$h];
                
                if ($activity_by_hour[$h] > $peak_local_activity) {
                    $peak_local_activity = $activity_by_hour[$h];
                    $peak_local_hour = $local_hour;
                }
            }
        }
        ?>
        <tr>
            <td><span style="color: <?= $tz['color'] ?>;">●</span> <strong><?= $tz['name'] ?></strong></td>
            <td><?= $awake_hours ?> hours</td>
            <td><?= sprintf('%02d:00', $peak_local_hour) ?> local</td>
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span><?= round($awake_activity) ?></span>
                    <div style="background: rgba(88, 101, 242, <?= $awake_activity / array_sum($activity_by_hour) ?>); height: 20px; width: <?= ($awake_activity / array_sum($activity_by_hour)) * 100 ?>px; border-radius: 3px;"></div>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Legend -->
<div class="panel">
    <h2>📖 Legend</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
        <div>
            <strong style="color: #43b581;">🟢 Awake Hours (Green)</strong>
            <p style="color: #888; font-size: 13px; margin-top: 5px;">6:00 AM - 2:00 AM local time</p>
        </div>
        <div>
            <strong style="color: #666;">⚫ Sleep Hours (Gray)</strong>
            <p style="color: #888; font-size: 13px; margin-top: 5px;">2:00 AM - 6:00 AM local time</p>
        </div>
        <div>
            <strong style="color: #5865F2;">📊 Activity Bar</strong>
            <p style="color: #888; font-size: 13px; margin-top: 5px;">Darker/taller = more messages</p>
        </div>
        <div>
            <strong style="color: #faa61a;">🕐 Local Time</strong>
            <p style="color: #888; font-size: 13px; margin-top: 5px;">Shown in each cell (2-digit hour)</p>
        </div>
    </div>
</div>
