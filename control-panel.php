<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/football-settings.php';
$accounts = clever_accounts_db();
if ((int)$accounts->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
    header('Location: /account/setup.php'); exit;
}
$currentUser = clever_require_admin();
$football = clever_football_db();
// Import bundled metadata once, then use SQLite as the editable source of truth.
$db = $football;
require_once __DIR__ . '/football-stats/includes/team-info.php';
$section = (string)($_GET['section'] ?? 'overview');
$allowedSections = ['overview', 'users', 'groups', 'teams', 'deductions'];
if (!in_array($section, $allowedSections, true)) $section = 'overview';
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clever_verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save_group') {
            $name = trim((string)$_POST['name']);
            if ($name === '') throw new RuntimeException('Group name is required.');
            if (!empty($_POST['id'])) {
                $stmt = $accounts->prepare('UPDATE user_groups SET name=?, description=?, is_admin=? WHERE id=?');
                $stmt->execute([$name, trim((string)$_POST['description']), isset($_POST['is_admin']) ? 1 : 0, (int)$_POST['id']]);
            } else {
                $stmt = $accounts->prepare('INSERT INTO user_groups (name, description, is_admin) VALUES (?, ?, ?)');
                $stmt->execute([$name, trim((string)$_POST['description']), isset($_POST['is_admin']) ? 1 : 0]);
            }
            $notice = 'Group saved.'; $section = 'groups';
        } elseif ($action === 'delete_group') {
            $stmt = $accounts->prepare('DELETE FROM user_groups WHERE id=? AND name NOT IN (\'Members\', \'Administrators\')');
            $stmt->execute([(int)$_POST['id']]);
            $notice = $stmt->rowCount() ? 'Group deleted.' : 'Built-in groups cannot be deleted.'; $section = 'groups';
        } elseif ($action === 'save_user') {
            $userId = (int)$_POST['id'];
            if ($userId === (int)$currentUser['id'] && ($_POST['status'] ?? '') !== 'active') throw new RuntimeException('You cannot disable your own account.');
            $accounts->beginTransaction();
            $stmt = $accounts->prepare('UPDATE users SET username=?, email=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $stmt->execute([trim((string)$_POST['username']), trim((string)$_POST['email']), $_POST['status'] === 'disabled' ? 'disabled' : 'active', $userId]);
            if ((string)($_POST['password'] ?? '') !== '') {
                if (strlen((string)$_POST['password']) < 10) throw new RuntimeException('Passwords must contain at least 10 characters.');
                $stmt = $accounts->prepare('UPDATE users SET password_hash=? WHERE id=?');
                $stmt->execute([password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $userId]);
            }
            $accounts->prepare('DELETE FROM user_group_memberships WHERE user_id=?')->execute([$userId]);
            $membership = $accounts->prepare('INSERT INTO user_group_memberships (user_id, group_id) VALUES (?, ?)');
            foreach ((array)($_POST['groups'] ?? []) as $groupId) $membership->execute([$userId, (int)$groupId]);
            $accounts->commit(); $notice = 'User updated.'; $section = 'users';
        } elseif ($action === 'delete_user') {
            if ((int)$_POST['id'] === (int)$currentUser['id']) throw new RuntimeException('You cannot delete your own account.');
            $accounts->prepare('DELETE FROM users WHERE id=?')->execute([(int)$_POST['id']]);
            $notice = 'User deleted.'; $section = 'users';
        } elseif ($action === 'save_team') {
            $color = strtoupper(trim((string)$_POST['color']));
            if (!preg_match('/^#[0-9A-F]{6}$/', $color)) throw new RuntimeException('Team colour must be a six-digit hex colour.');
            $stmt = $football->prepare('INSERT INTO team_metadata (competition_code, team_name, display_name, common_name, nickname, short_code, color) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT(competition_code, team_name) DO UPDATE SET display_name=excluded.display_name, common_name=excluded.common_name, nickname=excluded.nickname, short_code=excluded.short_code, color=excluded.color');
            $stmt->execute([strtoupper(trim((string)$_POST['competition_code'])), trim((string)$_POST['team_name']), trim((string)$_POST['display_name']), trim((string)$_POST['common_name']), trim((string)$_POST['nickname']), strtoupper(substr(trim((string)$_POST['short_code']), 0, 5)), $color]);
            $notice = 'Team information saved.'; $section = 'teams';
        } elseif ($action === 'delete_team') {
            $football->prepare('DELETE FROM team_metadata WHERE competition_code=? AND team_name=?')->execute([$_POST['competition_code'], $_POST['team_name']]);
            $notice = 'Team information deleted.'; $section = 'teams';
        } elseif ($action === 'save_deduction') {
            $points = (int)$_POST['points']; if ($points < 1) throw new RuntimeException('Deduction must be at least one point.');
            $stmt = $football->prepare('INSERT INTO points_deductions (competition_code, season_label, team_name, points, reason) VALUES (?, ?, ?, ?, ?) ON CONFLICT(competition_code, season_label, team_name, reason) DO UPDATE SET points=excluded.points');
            $stmt->execute([strtoupper(trim((string)$_POST['competition_code'])), trim((string)$_POST['season_label']), trim((string)$_POST['team_name']), $points, trim((string)$_POST['reason'])]);
            $notice = 'Points deduction saved.'; $section = 'deductions';
        } elseif ($action === 'delete_deduction') {
            $football->prepare('DELETE FROM points_deductions WHERE competition_code=? AND season_label=? AND team_name=? AND reason=?')->execute([$_POST['competition_code'], $_POST['season_label'], $_POST['team_name'], $_POST['reason']]);
            $notice = 'Points deduction deleted.'; $section = 'deductions';
        }
    } catch (Throwable $exception) {
        if ($accounts->inTransaction()) $accounts->rollBack();
        $error = $exception instanceof PDOException ? 'That value is already in use or is invalid.' : $exception->getMessage();
    }
}
$groups = $accounts->query('SELECT g.*, COUNT(ugm.user_id) AS member_count FROM user_groups g LEFT JOIN user_group_memberships ugm ON ugm.group_id=g.id GROUP BY g.id ORDER BY g.name')->fetchAll();
$users = $accounts->query("SELECT u.*, GROUP_CONCAT(g.name, ', ') AS group_names, GROUP_CONCAT(g.id) AS group_ids FROM users u LEFT JOIN user_group_memberships ugm ON ugm.user_id=u.id LEFT JOIN user_groups g ON g.id=ugm.group_id GROUP BY u.id ORDER BY u.username")->fetchAll();
$teams = $football->query('SELECT * FROM team_metadata ORDER BY competition_code, team_name')->fetchAll();
$deductions = $football->query('SELECT * FROM points_deductions ORDER BY season_label DESC, competition_code, team_name')->fetchAll();
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin panel | CleverDino</title><link rel="stylesheet" href="/account/account.css"></head><body><main class="shell"><header class="topbar"><div><a class="brand" href="/">🦖 Clever<span>Dino</span></a><div class="muted">Administration</div></div><nav class="nav"><span><?= $h($currentUser['username']) ?></span><a href="/account/">My account</a><a href="/account/logout.php">Sign out</a></nav></header><nav class="tabs"><?php foreach (['overview'=>'Overview','users'=>'Users','groups'=>'Groups','teams'=>'Team info','deductions'=>'Deductions'] as $key=>$label): ?><a class="<?= $section===$key?'active':'' ?>" href="?section=<?= $key ?>"><?= $label ?></a><?php endforeach; ?></nav><?php if ($notice): ?><div class="notice"><?= $h($notice) ?></div><?php endif; ?><?php if ($error): ?><div class="error"><?= $h($error) ?></div><?php endif; ?>
<?php if ($section === 'overview'): ?><div class="grid"><section class="panel"><h2>Accounts</h2><p><strong><?= count($users) ?></strong> users across <strong><?= count($groups) ?></strong> groups.</p><a class="btn" href="?section=users">Manage access</a></section><section class="panel"><h2>Football configuration</h2><p><strong><?= count($teams) ?></strong> team aliases and <strong><?= count($deductions) ?></strong> points deductions are stored in SQLite.</p><a class="btn" href="?section=teams">Manage data</a></section><section class="panel"><h2>System tools</h2><div class="stack"><a href="../football-stats/check-db.php">Football database check</a><a href="../youtube-dashboard/check-db.php">YouTube database check</a><a href="view-log.php">Football update logs</a></div></section></div>
<?php elseif ($section === 'groups'): ?><div class="grid"><section class="panel"><h2>Add a group</h2><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_group"><label>Name<input name="name" required></label><label>Description<textarea name="description"></textarea></label><label class="inline"><input type="checkbox" name="is_admin"> Grant administrator access</label><button>Save group</button></form></section><section class="panel"><h2>Groups</h2><div class="table-wrap"><table><tr><th>Name</th><th>Access</th><th>Members</th><th></th></tr><?php foreach ($groups as $group): ?><tr><td><strong><?= $h($group['name']) ?></strong><br><span class="muted"><?= $h($group['description']) ?></span></td><td><?= $group['is_admin']?'<span class="pill admin">Admin</span>':'Member' ?></td><td><?= (int)$group['member_count'] ?></td><td><?php if (!in_array($group['name'], ['Members','Administrators'], true)): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="delete_group"><input type="hidden" name="id" value="<?= (int)$group['id'] ?>"><button class="danger compact" onclick="return confirm('Delete this group?')">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div></section></div>
<?php elseif ($section === 'users'): ?><section class="panel"><h2>Users</h2><div class="stack"><?php foreach ($users as $user): $assigned=array_filter(explode(',', (string)$user['group_ids'])); ?><form method="post" class="panel"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_user"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><div class="grid"><label>Username<input name="username" value="<?= $h($user['username']) ?>" required></label><label>Email<input type="email" name="email" value="<?= $h($user['email']) ?>" required></label><label>Status<select name="status"><option value="active" <?= $user['status']==='active'?'selected':'' ?>>Active</option><option value="disabled" <?= $user['status']==='disabled'?'selected':'' ?>>Disabled</option></select></label><label>New password <span class="muted">(leave blank to retain)</span><input type="password" name="password" minlength="10"></label></div><p><strong>Groups</strong></p><div class="inline"><?php foreach ($groups as $group): ?><label class="inline"><input type="checkbox" name="groups[]" value="<?= (int)$group['id'] ?>" <?= in_array((string)$group['id'],$assigned,true)?'checked':'' ?>><?= $h($group['name']) ?></label><?php endforeach; ?></div><div class="actions" style="margin-top:15px"><button>Save user</button><?php if ((int)$user['id'] !== (int)$currentUser['id']): ?><button class="danger" name="action" value="delete_user" onclick="return confirm('Permanently delete this user?')">Delete user</button><?php endif; ?></div></form><?php endforeach; ?></div></section>
<?php elseif ($section === 'teams'): ?><section class="panel"><h2>Add or update team information</h2><form method="post" class="grid"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_team"><label>Competition code<input name="competition_code" placeholder="PL, ELC, L1, L2, NL" required></label><label>Lookup/API team name<input name="team_name" required></label><label>Display name<input name="display_name" required></label><label>Common name<input name="common_name" required></label><label>Nickname<input name="nickname"></label><label>Short code<input name="short_code" maxlength="5" required></label><label>Colour<input name="color" type="color" value="#5865F2" required></label><div><button style="margin-top:29px">Save team</button></div></form></section><section class="panel" style="margin-top:20px"><h2>Configured teams</h2><div class="table-wrap"><table><tr><th>Competition</th><th>Lookup name</th><th>Display</th><th>Nickname</th><th>Code</th><th></th></tr><?php foreach ($teams as $team): ?><tr><td><?= $h($team['competition_code']) ?></td><td><?= $h($team['team_name']) ?></td><td><span style="color:<?= $h($team['color']) ?>">●</span> <?= $h($team['display_name']) ?></td><td><?= $h($team['nickname']) ?></td><td><?= $h($team['short_code']) ?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="delete_team"><input type="hidden" name="competition_code" value="<?= $h($team['competition_code']) ?>"><input type="hidden" name="team_name" value="<?= $h($team['team_name']) ?>"><button class="danger compact" onclick="return confirm('Delete this team alias?')">Delete</button></form></td></tr><?php endforeach; ?></table></div></section>
<?php elseif ($section === 'deductions'): ?><div class="grid"><section class="panel"><h2>Add a points deduction</h2><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_deduction"><label>Competition code<input name="competition_code" placeholder="ELC" required></label><label>Season<input name="season_label" placeholder="2026-2027" pattern="[0-9]{4}-[0-9]{4}" required></label><label>Team<input name="team_name" required></label><label>Points deducted<input type="number" name="points" min="1" max="100" required></label><label>Reason<textarea name="reason"></textarea></label><button>Save deduction</button></form></section><section class="panel"><h2>Current deductions</h2><div class="table-wrap"><table><tr><th>Season</th><th>Team</th><th>Points</th><th></th></tr><?php foreach ($deductions as $item): ?><tr><td><?= $h($item['season_label']) ?><br><span class="pill"><?= $h($item['competition_code']) ?></span></td><td><?= $h($item['team_name']) ?><br><span class="muted"><?= $h($item['reason']) ?></span></td><td>&minus;<?= (int)$item['points'] ?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="delete_deduction"><?php foreach (['competition_code','season_label','team_name','reason'] as $key): ?><input type="hidden" name="<?= $key ?>" value="<?= $h($item[$key]) ?>"><?php endforeach; ?><button class="danger compact" onclick="return confirm('Delete this deduction?')">Delete</button></form></td></tr><?php endforeach; ?></table></div></section></div><?php endif; ?></main></body></html>
