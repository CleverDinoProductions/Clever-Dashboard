<?php
/**
 * QUEUE HISTORY TAB - Complete with Window Detail View
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$windowdate = $_GET['window'] ?? null;
$method = $_GET['method'] ?? 'auto';

$queuechannel = '#an-q';
$voicechannel = '#anonamouse.net';

$monthnames = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];

function strip_mirc_codes($text) {
    $text = preg_replace('/\x03[0-9]{1,2}(,[0-9]{1,2})?/', '', $text);
    $text = str_replace([chr(2), chr(15), chr(22), chr(31)], '', $text);
    return trim($text);
}

// Detection functions (same as before)
function detect_windows_by_voice($db, $year = null) {
    try {
        $year_filter = $year ? "AND strftime('%Y', time/1000, 'unixepoch') = :year" : "";

        $stmt = $db->prepare("
            SELECT DISTINCT 
                date(time/1000, 'unixepoch') as date,
                strftime('%w', time/1000, 'unixepoch') as dow
            FROM messages
            WHERE channel = '#anonamouse.net'
            AND type = 'mode'
            AND json_valid(msg) = 1
            AND json_extract(msg, '$.text') LIKE '+v %'
            AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
            $year_filter
            ORDER BY date DESC
        ");

        if ($year) {
            $stmt->execute(['year' => $year]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("detect_windows_by_voice: " . $e->getMessage());
        return [];
    }
}

function detect_windows_by_welcomes($db, $year = null) {
    try {
        $year_filter = $year ? "AND strftime('%Y', time/1000, 'unixepoch') = :year" : "";

        $stmt = $db->prepare("
            SELECT DISTINCT 
                date(time/1000, 'unixepoch') as date,
                strftime('%w', time/1000, 'unixepoch') as dow
            FROM messages
            WHERE channel = '#anonamouse.net'
            AND type = 'action'
            AND json_valid(msg) = 1
            AND json_extract(msg, '$.from.nick') = 'MouseBot'
            AND json_extract(msg, '$.text') LIKE 'welcomes %'
            AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
            $year_filter
            ORDER BY date DESC
        ");

        if ($year) {
            $stmt->execute(['year' => $year]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("detect_windows_by_welcomes: " . $e->getMessage());
        return [];
    }
}

function detect_windows_by_queue($db, $year = null) {
    try {
        $year_filter = $year ? "AND strftime('%Y', time/1000, 'unixepoch') = :year" : "";

        $stmt = $db->prepare("
            SELECT DISTINCT 
                date(time/1000, 'unixepoch') as date,
                strftime('%w', time/1000, 'unixepoch') as dow
            FROM messages
            WHERE channel = '#an-q'
            AND json_valid(msg) = 1
            AND json_extract(msg, '$.from.nick') = 'MouseBot'
            AND (json_extract(msg, '$.text') LIKE 'Inv queue %' 
                 OR json_extract(msg, '$.text') LIKE 'Sup queue %')
            AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
            $year_filter
            ORDER BY date DESC
        ");

        if ($year) {
            $stmt->execute(['year' => $year]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("detect_windows_by_queue: " . $e->getMessage());
        return [];
    }
}

function detect_windows_by_interviews($db, $year = null) {
    try {
        $year_filter = $year ? "AND strftime('%Y', time/1000, 'unixepoch') = :year" : "";

        $stmt = $db->prepare("
            SELECT DISTINCT 
                date(time/1000, 'unixepoch') as date,
                strftime('%w', time/1000, 'unixepoch') as dow
            FROM messages
            WHERE channel = '#anonamouse.net'
            AND json_valid(msg) = 1
            AND json_extract(msg, '$.from.nick') = 'MouseBot'
            AND json_extract(msg, '$.text') LIKE 'Now up for an%interview%'
            AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
            $year_filter
            ORDER BY date DESC
        ");

        if ($year) {
            $stmt->execute(['year' => $year]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("detect_windows_by_interviews: " . $e->getMessage());
        return [];
    }
}

function detect_windows_by_activity($db, $year = null) {
    try {
        $year_filter = $year ? "AND strftime('%Y', time/1000, 'unixepoch') = :year" : "";

        $stmt = $db->prepare("
            SELECT 
                date(time/1000, 'unixepoch') as date,
                strftime('%w', time/1000, 'unixepoch') as dow
            FROM messages
            WHERE channel IN ('#an-q', '#anonamouse.net', '#help')
            AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
            $year_filter
            GROUP BY date
            HAVING COUNT(*) > 50
            ORDER BY date DESC
        ");

        if ($year) {
            $stmt->execute(['year' => $year]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("detect_windows_by_activity: " . $e->getMessage());
        return [];
    }
}

function detect_windows_auto($db, $year = null) {
    $all_windows = [];

    foreach (detect_windows_by_voice($db, $year) as $w) {
        $w['method'] = 'voice';
        $all_windows[] = $w;
    }
    foreach (detect_windows_by_welcomes($db, $year) as $w) {
        $w['method'] = 'welcomes';
        $all_windows[] = $w;
    }
    foreach (detect_windows_by_queue($db, $year) as $w) {
        $w['method'] = 'queue';
        $all_windows[] = $w;
    }
    foreach (detect_windows_by_interviews($db, $year) as $w) {
        $w['method'] = 'interviews';
        $all_windows[] = $w;
    }
    foreach (detect_windows_by_activity($db, $year) as $w) {
        $w['method'] = 'activity';
        $all_windows[] = $w;
    }

    $unique = [];
    $seen = [];

    foreach ($all_windows as $window) {
        if (!in_array($window['date'], $seen)) {
            $seen[] = $window['date'];
            $unique[] = $window;
        }
    }

    usort($unique, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    return $unique;
}

// ==================== WINDOW DETAIL VIEW LOGIC ====================

$windowdetail = null;
$hourlydata = [];
$recententries = [];
$recentexits = [];

if ($windowdate) {
    // Calculate 25-hour window
    $starttime = strtotime($windowdate . ' 08:00:00 UTC');
    $endtime = $starttime + (25 * 3600);
    $dow = date('w', $starttime);

    $windowdetail = [
        'date' => $windowdate,
        'type' => $dow == 3 ? 'Wed→Thu' : 'Sat→Sun',
        'start' => date('l, M j H:i', $starttime) . ' UTC',
        'end' => date('l, M j H:i', $endtime) . ' UTC'
    ];

    // Get overall stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN json_valid(msg) = 1 AND json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as entries,
            COUNT(CASE WHEN json_valid(msg) = 1 AND json_extract(msg, '$.text') LIKE '-v %' THEN 1 END) as exits
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND time >= :start AND time <= :end
    ");
    $stmt->execute(['channel' => $voicechannel, 'start' => $starttime * 1000, 'end' => $endtime * 1000]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $windowdetail['entries'] = $stats['entries'];
    $windowdetail['exits'] = $stats['exits'];

    // Hourly breakdown
    $stmt = $db->prepare("
        SELECT 
            strftime('%Y-%m-%d %H:00:00', time/1000, 'unixepoch') as hour,
            COUNT(CASE WHEN json_valid(msg) = 1 AND json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as entries,
            COUNT(CASE WHEN json_valid(msg) = 1 AND json_extract(msg, '$.text') LIKE '-v %' THEN 1 END) as exits
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND time >= :start AND time <= :end
        GROUP BY hour
        ORDER BY hour
    ");
    $stmt->execute(['channel' => $voicechannel, 'start' => $starttime * 1000, 'end' => $endtime * 1000]);
    $hourlydata = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent entries
    $stmt = $db->prepare("
        SELECT datetime(time/1000, 'unixepoch') as timestamp, json_extract(msg, '$.text') as text
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND json_valid(msg) = 1
        AND json_extract(msg, '$.text') LIKE '+v %'
        AND time >= :start AND time <= :end
        ORDER BY time DESC
        LIMIT 20
    ");
    $stmt->execute(['channel' => $voicechannel, 'start' => $starttime * 1000, 'end' => $endtime * 1000]);
    $recententries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent exits with wait times
    $stmt = $db->prepare("
        SELECT datetime(time/1000, 'unixepoch') as timestamp, json_extract(msg, '$.text') as text, time
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND json_valid(msg) = 1
        AND json_extract(msg, '$.text') LIKE '-v %'
        AND time >= :start AND time <= :end
        ORDER BY time DESC
        LIMIT 20
    ");
    $stmt->execute(['channel' => $voicechannel, 'start' => $starttime * 1000, 'end' => $endtime * 1000]);

    $recentexits = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $exit) {
        if (preg_match('/\-v (.+)/', $exit['text'], $matches)) {
            $username = trim($matches[1]);

            // Find entry time
            $stmt2 = $db->prepare("
                SELECT time FROM messages
                WHERE channel = :channel
                AND type = 'mode'
                AND json_valid(msg) = 1
                AND json_extract(msg, '$.text') = :pattern
                AND time < :exittime
                ORDER BY time DESC
                LIMIT 1
            ");
            $stmt2->execute([
                'channel' => $voicechannel,
                'pattern' => '+v ' . $username,
                'exittime' => $exit['time']
            ]);
            $entry = $stmt2->fetch(PDO::FETCH_ASSOC);

            $waittime = 0;
            if ($entry) {
                $waitms = $exit['time'] - $entry['time'];
                $waittime = round($waitms / 60000, 1);
            }

            $recentexits[] = [
                'timestamp' => $exit['timestamp'],
                'username' => $username,
                'waittime' => $waittime
            ];
        }
    }
}

// ==================== MAIN LOGIC ====================

$windows = [];
$years = [];
$months = [];

if (!$windowdate) {
    try {
        if ($year && $month) {
            switch ($method) {
                case 'voice':
                    $windows = detect_windows_by_voice($db, $year);
                    break;
                case 'welcomes':
                    $windows = detect_windows_by_welcomes($db, $year);
                    break;
                case 'queue':
                    $windows = detect_windows_by_queue($db, $year);
                    break;
                case 'interviews':
                    $windows = detect_windows_by_interviews($db, $year);
                    break;
                case 'activity':
                    $windows = detect_windows_by_activity($db, $year);
                    break;
                default:
                    $windows = detect_windows_auto($db, $year);
            }

            $windows = array_filter($windows, function($w) use ($month) {
                return substr($w['date'], 5, 2) === $month;
            });

            foreach ($windows as $key => $win) {
                $windows[$key]['type'] = $win['dow'] == 3 ? 'Wed→Thu' : 'Sat→Sun';

                $starttime = strtotime($win['date'] . ' 08:00:00 UTC');
                $endtime = $starttime + (25 * 3600);

                $stmt = $db->prepare("
                    SELECT 
                        COUNT(CASE WHEN json_valid(msg) = 1 AND json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as entries,
                        COUNT(CASE WHEN json_valid(msg) = 1 AND json_extract(msg, '$.text') LIKE '-v %' THEN 1 END) as exits
                    FROM messages
                    WHERE channel = '#anonamouse.net'
                    AND type = 'mode'
                    AND time >= :start AND time <= :end
                ");
                $stmt->execute(['start' => $starttime * 1000, 'end' => $endtime * 1000]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                $windows[$key]['entries'] = $stats['entries'];
                $windows[$key]['exits'] = $stats['exits'];
            }

        } elseif ($year && !$month) {
            $stmt = $db->prepare("
                SELECT DISTINCT strftime('%m', time/1000, 'unixepoch') as monthnum
                FROM messages
                WHERE channel IN ('#anonamouse.net', '#an-q')
                AND strftime('%Y', time/1000, 'unixepoch') = :year
                AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
                ORDER BY monthnum DESC
            ");
            $stmt->execute(['year' => $year]);
            $months = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            $stmt = $db->query("
                SELECT DISTINCT strftime('%Y', time/1000, 'unixepoch') as year
                FROM messages
                WHERE channel IN ('#anonamouse.net', '#an-q')
                AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
                ORDER BY year DESC
            ");
            $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (Exception $e) {
        echo "<div class='panel' style='border: 2px solid #f04747;'>";
        echo "<h2 style='color: #f04747;'>Database Error</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

?>

<!-- HTML OUTPUT -->

<div class="channel-selector" style="margin-bottom: 20px;">
    <a href="?tab=windows<?php echo $year ? "&year=$year" : ""; ?><?php echo $month ? "&month=$month" : ""; ?>&method=auto" 
       class="channel-btn <?php echo $method == 'auto' ? 'active' : ''; ?>">🤖 Auto</a>
    <a href="?tab=windows<?php echo $year ? "&year=$year" : ""; ?><?php echo $month ? "&month=$month" : ""; ?>&method=voice" 
       class="channel-btn <?php echo $method == 'voice' ? 'active' : ''; ?>">🎤 Voice</a>
    <a href="?tab=windows<?php echo $year ? "&year=$year" : ""; ?><?php echo $month ? "&month=$month" : ""; ?>&method=welcomes" 
       class="channel-btn <?php echo $method == 'welcomes' ? 'active' : ''; ?>">🎉 Welcomes</a>
    <a href="?tab=windows<?php echo $year ? "&year=$year" : ""; ?><?php echo $month ? "&month=$month" : ""; ?>&method=queue" 
       class="channel-btn <?php echo $method == 'queue' ? 'active' : ''; ?>">📢 Queue</a>
    <a href="?tab=windows<?php echo $year ? "&year=$year" : ""; ?><?php echo $month ? "&month=$month" : ""; ?>&method=interviews" 
       class="channel-btn <?php echo $method == 'interviews' ? 'active' : ''; ?>">📞 Interviews</a>
</div>

<div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <span style="color: #888;">Navigate:</span>
    <a href="?tab=windows" style="color: #5865F2; text-decoration: none; margin: 0 5px;">Years</a>
    <?php if ($year): ?>
        → <a href="?tab=windows&year=<?php echo $year; ?>" style="color: #5865F2; text-decoration: none;"><?php echo $year; ?></a>
    <?php endif; ?>
    <?php if ($month): ?>
        → <a href="?tab=windows&year=<?php echo $year; ?>&month=<?php echo $month; ?>" style="color: #5865F2; text-decoration: none;"><?php echo $monthnames[$month]; ?></a>
    <?php endif; ?>
    <?php if ($windowdate): ?>
        → <span style="color: #5865F2;"><?php echo date('M j', strtotime($windowdate)); ?> Window</span>
    <?php endif; ?>
</div>

<?php if ($windowdate && $windowdetail): ?>
    <!-- WINDOW DETAIL VIEW -->
    <div class="panel">
        <h2><?php echo $windowdetail['type']; ?> Queue Window</h2>
        <h3 style="color: #888; font-size: 16px; margin-top: 5px;"><?php echo $windowdetail['start']; ?> → <?php echo $windowdetail['end']; ?></h3>
        <p style="color: #888; font-size: 12px;">25-hour window</p>
    </div>

    <!-- Stats -->
    <div class="grid">
        <div class="panel" style="background: #40444b;">
            <h3 style="color: #43b581; font-size: 16px;">Queue Entries (+v)</h3>
            <div style="font-size: 48px; font-weight: bold;"><?php echo number_format($windowdetail['entries']); ?></div>
        </div>
        <div class="panel" style="background: #40444b;">
            <h3 style="color: #f04747; font-size: 16px;">Queue Exits (-v)</h3>
            <div style="font-size: 48px; font-weight: bold;"><?php echo number_format($windowdetail['exits']); ?></div>
        </div>
    </div>

    <!-- Hourly Breakdown -->
    <div class="panel">
        <h2>Hourly Activity</h2>
        <table>
            <tr><th>Hour (UTC)</th><th>Entries</th><th>Exits</th></tr>
            <?php foreach ($hourlydata as $hour): ?>
                <tr>
                    <td><strong><?php echo date('D H:i', strtotime($hour['hour'])); ?></strong></td>
                    <td><span class="badge" style="background: #43b581;"><?php echo $hour['entries']; ?></span></td>
                    <td><span class="badge" style="background: #f04747;"><?php echo $hour['exits']; ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Recent Activity -->
    <div class="grid" style="grid-template-columns: 1fr 1fr;">
        <div class="panel">
            <h2>Recent Entries (+v)</h2>
            <?php if (empty($recententries)): ?>
                <p style="color: #888;">No entries</p>
            <?php else: ?>
                <table>
                    <tr><th>User</th><th>Time</th></tr>
                    <?php foreach ($recententries as $entry): ?>
                        <?php if (preg_match('/\+v (.+)/', $entry['text'], $matches)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($matches[1]); ?></td>
                                <td style="font-size: 11px;"><?php echo $entry['timestamp']; ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2>Recent Exits (-v)</h2>
            <?php if (empty($recentexits)): ?>
                <p style="color: #888;">No exits</p>
            <?php else: ?>
                <table>
                    <tr><th>User</th><th>Time</th><th>Wait</th></tr>
                    <?php foreach ($recentexits as $exit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exit['username']); ?></td>
                            <td style="font-size: 11px;"><?php echo $exit['timestamp']; ?></td>
                            <td>
                                <?php if ($exit['waittime'] > 0): ?>
                                    <span class="badge" style="background: <?php echo $exit['waittime'] <= 10 ? '#43b581' : ($exit['waittime'] <= 30 ? '#faa61a' : '#f04747'); ?>;">
                                        <?php echo $exit['waittime']; ?>min
                                    </span>
                                <?php else: ?>
                                    <span style="color: #888;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($year && $month && !empty($windows)): ?>
    <!-- WINDOWS LIST -->
    <div class="panel">
        <h2>Queue Windows - <?php echo $monthnames[$month]; ?> <?php echo $year; ?></h2>
        <p style="color: #888; margin-bottom: 15px;">Detected using: <strong><?php echo ucfirst($method); ?></strong> method</p>

        <table>
            <tr><th>Date</th><th>Type</th><th>Entries</th><th>Exits</th><th>Actions</th></tr>
            <?php foreach ($windows as $win): ?>
                <tr>
                    <td><strong><?php echo date('D, M j', strtotime($win['date'])); ?></strong></td>
                    <td><span class="badge <?php echo $win['type'] == 'Wed→Thu' ? 'badge-user' : 'badge-voice'; ?>"><?php echo htmlspecialchars($win['type']); ?></span></td>
                    <td><span class="badge" style="background: #43b581;"><?php echo number_format($win['entries']); ?></span></td>
                    <td><span class="badge" style="background: #f04747;"><?php echo number_format($win['exits']); ?></span></td>
                    <td><a href="?tab=windows&window=<?php echo $win['date']; ?>" class="badge badge-voice">View</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

<?php elseif ($year && !$month && !empty($months)): ?>
    <!-- MONTH VIEW -->
    <div class="panel">
        <h2><?php echo $year; ?> - Select Month</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($months as $m): ?>
                <a href="?tab=windows&year=<?php echo $year; ?>&month=<?php echo $m['monthnum']; ?>&method=<?php echo $method; ?>" style="text-decoration: none;">
                    <div style="background: #40444b; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.2s;" 
                         onmouseover="this.style.background='#4752c4'" onmouseout="this.style.background='#40444b'">
                        <div style="font-size: 24px; font-weight: bold; color: #5865F2;"><?php echo $monthnames[$m['monthnum']]; ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif (!$year && !empty($years)): ?>
    <!-- YEAR VIEW -->
    <div class="panel">
        <h2>Queue History - Select Year</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($years as $y): ?>
                <a href="?tab=windows&year=<?php echo $y; ?>&method=<?php echo $method; ?>" style="text-decoration: none;">
                    <div style="background: #40444b; padding: 30px; border-radius: 8px; text-align: center; transition: all 0.2s;" 
                         onmouseover="this.style.background='#4752c4'" onmouseout="this.style.background='#40444b'">
                        <div style="font-size: 36px; font-weight: bold; color: #5865F2;"><?php echo $y; ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

<?php else: ?>
    <div class="panel">
        <h2>No Data Found</h2>
        <p style="color: #888;">No queue windows found.</p>
    </div>
<?php endif; ?>