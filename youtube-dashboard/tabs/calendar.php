<?php
$month = dashboard_input('month');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) || substr($month, 0, 4) < '0002' || substr($month, 0, 4) > '9998') $month = gmdate('Y-m');
$start = new DateTimeImmutable($month . '-01', new DateTimeZone('UTC'));
$days = [];
$rows = query("SELECT strftime('%d', published_date) AS day, COUNT(*) AS total FROM dashboard_videos WHERE strftime('%Y-%m', published_date) = :month GROUP BY day", [':month' => $month]);
while ($row = $rows->fetchArray(SQLITE3_ASSOC)) $days[(int) $row['day']] = $row['total'];
?>
<div class="content-area"><h2>Upload calendar</h2><p class="tab-description">Daily upload counts from the local cache, in UTC. Use the channel filter to inspect one channel's schedule.</p>
<form class="timeline-filters" method="get"><input type="hidden" name="tab" value="calendar"><?php dashboard_hidden_filters(); ?><label>Month<input type="month" name="month" value="<?= $month ?>" min="0002-01" max="9998-12"></label><button>Show month</button></form>
<div class="results-heading"><a href="<?= dashboard_url(['tab' => 'calendar', 'month' => $start->modify('-1 month')->format('Y-m')]) ?>">← Previous</a><h3><?= $start->format('F Y') ?> · <?= array_sum($days) ?> uploads</h3><a href="<?= dashboard_url(['tab' => 'calendar', 'month' => $start->modify('+1 month')->format('Y-m')]) ?>">Next →</a></div>
<div class="upload-calendar">
<?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day): ?><strong><?= $day ?></strong><?php endforeach; ?>
<?php for ($blank = 1; $blank < (int) $start->format('N'); $blank++): ?><div aria-hidden="true"></div><?php endfor; ?>
<?php for ($day = 1; $day <= (int) $start->format('t'); $day++): ?><div class="calendar-day <?= isset($days[$day]) ? 'has-uploads' : '' ?>"><strong><?= $day ?></strong><span><?= $days[$day] ?? 0 ?> uploads</span></div><?php endfor; ?>
</div></div>
