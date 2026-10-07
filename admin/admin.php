<?php
require_once dirname(__DIR__) . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/football-settings.php';
require_once dirname(__DIR__) . '/football-stats/includes/table-view.php';
$accounts = clever_accounts_db();
if ((int)$accounts->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
    header('Location: /account/setup.php'); exit;
}
$currentUser = clever_require_login();
if (!$currentUser['is_admin'] && empty($currentUser['can_manage_football'])) {
    http_response_code(403); exit('Football editor or administrator access is required.');
}
$isAccountAdmin = (bool)$currentUser['is_admin'];
$football = null;
$footballError = '';
try {
    $football = clever_football_db();
    // Import bundled metadata once, then use SQLite as the editable source of truth.
    $db = $football;
    require_once dirname(__DIR__) . '/football-stats/includes/team-info.php';
} catch (Throwable $exception) {
    // Account and group administration must remain available even if the
    // separately managed football database is absent or read-only.
    error_log('Admin football database unavailable: ' . $exception->getMessage());
    $footballError = 'Football configuration is unavailable. Check CLEVER_FOOTBALL_DB and make sure the database directory is writable by PHP.';
}
$section = (string)($_GET['section'] ?? ($isAccountAdmin ? 'overview' : 'teams'));
$allowedSections = $isAccountAdmin ? ['overview', 'users', 'groups', 'teams', 'deductions', 'rules', 'dashboard'] : ['teams', 'deductions', 'rules', 'dashboard'];
if (!in_array($section, $allowedSections, true)) $section = $isAccountAdmin ? 'overview' : 'teams';
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clever_verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if (in_array($action, ['save_group', 'delete_group', 'save_user', 'delete_user'], true) && !$isAccountAdmin) {
            throw new RuntimeException('Administrator access is required for account changes.');
        }
        if ($action === 'save_group') {
            $name = trim((string)$_POST['name']);
            if ($name === '') throw new RuntimeException('Group name is required.');
            if (!empty($_POST['id'])) {
                $stmt = $accounts->prepare('UPDATE user_groups SET name=?, description=?, is_admin=?, can_manage_football=?, can_update_data=? WHERE id=?');
                $stmt->execute([$name, trim((string)$_POST['description']), isset($_POST['is_admin']) ? 1 : 0, isset($_POST['can_manage_football']) ? 1 : 0, isset($_POST['can_update_data']) ? 1 : 0, (int)$_POST['id']]);
            } else {
                $stmt = $accounts->prepare('INSERT INTO user_groups (name, description, is_admin, can_manage_football, can_update_data) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, trim((string)$_POST['description']), isset($_POST['is_admin']) ? 1 : 0, isset($_POST['can_manage_football']) ? 1 : 0, isset($_POST['can_update_data']) ? 1 : 0]);
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
            if (($_POST['status'] ?? '') !== 'active' || (string)($_POST['password'] ?? '') !== '') {
                $accounts->prepare('DELETE FROM login_tokens WHERE user_id=?')->execute([$userId]);
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
            if (!$football instanceof PDO) throw new RuntimeException($footballError);
            $color = strtoupper(trim((string)$_POST['color']));
            if (!preg_match('/^#[0-9A-F]{6}$/', $color)) throw new RuntimeException('Team colour must be a six-digit hex colour.');
            $stmt = $football->prepare('INSERT INTO team_metadata (competition_code, team_name, display_name, common_name, nickname, short_code, color) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT(competition_code, team_name) DO UPDATE SET display_name=excluded.display_name, common_name=excluded.common_name, nickname=excluded.nickname, short_code=excluded.short_code, color=excluded.color');
            $stmt->execute([strtoupper(trim((string)$_POST['competition_code'])), trim((string)$_POST['team_name']), trim((string)$_POST['display_name']), trim((string)$_POST['common_name']), trim((string)$_POST['nickname']), strtoupper(substr(trim((string)$_POST['short_code']), 0, 5)), $color]);
            if (isset($_POST['original_competition_code'], $_POST['original_team_name'])
                && ($_POST['original_competition_code'] !== strtoupper(trim((string)$_POST['competition_code'])) || $_POST['original_team_name'] !== trim((string)$_POST['team_name']))) {
                $football->prepare('DELETE FROM team_metadata WHERE competition_code=? AND team_name=?')->execute([$_POST['original_competition_code'], $_POST['original_team_name']]);
            }
            $notice = 'Team information saved.'; $section = 'teams';
        } elseif ($action === 'delete_team') {
            if (!$football instanceof PDO) throw new RuntimeException($footballError);
            $football->prepare('DELETE FROM team_metadata WHERE competition_code=? AND team_name=?')->execute([$_POST['competition_code'], $_POST['team_name']]);
            $notice = 'Team information deleted.'; $section = 'teams';
        } elseif ($action === 'save_deduction') {
            if (!$football instanceof PDO) throw new RuntimeException($footballError);
            $points = (int)$_POST['points']; if ($points < 1) throw new RuntimeException('Deduction must be at least one point.');
            $stmt = $football->prepare('INSERT INTO points_deductions (competition_code, season_label, team_name, points, reason) VALUES (?, ?, ?, ?, ?) ON CONFLICT(competition_code, season_label, team_name, reason) DO UPDATE SET points=excluded.points');
            $stmt->execute([strtoupper(trim((string)$_POST['competition_code'])), trim((string)$_POST['season_label']), trim((string)$_POST['team_name']), $points, trim((string)$_POST['reason'])]);
            if (isset($_POST['original_competition_code'], $_POST['original_season_label'], $_POST['original_team_name'], $_POST['original_reason'])) {
                $old = [$_POST['original_competition_code'], $_POST['original_season_label'], $_POST['original_team_name'], $_POST['original_reason']];
                $new = [strtoupper(trim((string)$_POST['competition_code'])), trim((string)$_POST['season_label']), trim((string)$_POST['team_name']), trim((string)$_POST['reason'])];
                if ($old !== $new) $football->prepare('DELETE FROM points_deductions WHERE competition_code=? AND season_label=? AND team_name=? AND reason=?')->execute($old);
            }
            $notice = 'Points deduction saved.'; $section = 'deductions';
        } elseif ($action === 'delete_deduction') {
            if (!$football instanceof PDO) throw new RuntimeException($footballError);
            $football->prepare('DELETE FROM points_deductions WHERE competition_code=? AND season_label=? AND team_name=? AND reason=?')->execute([$_POST['competition_code'], $_POST['season_label'], $_POST['team_name'], $_POST['reason']]);
            $notice = 'Points deduction deleted.'; $section = 'deductions';
        } elseif ($action === 'save_competition_rules' || $action === 'reset_competition_rules') {
            if (!$football instanceof PDO) throw new RuntimeException($footballError);
            $code = strtoupper(trim((string)($_POST['competition_code'] ?? '')));
            $season = trim((string)($_POST['season_label'] ?? ''));
            if ($action === 'save_competition_rules') {
                clever_save_competition_rules($football, $code, $season, $_POST);
                $notice = 'Competition rules saved.';
            } else {
                clever_reset_competition_rules($football, $code, $season);
                $notice = 'Competition rule override reset.';
            }
            $section = 'rules';
        } elseif ($action === 'save_team_groups') {
            if (!$football instanceof PDO) throw new RuntimeException($footballError);
            clever_save_team_groups($football, (array)($_POST['team_groups'] ?? []));
            $notice = 'Custom Rules team groups saved.'; $section = 'dashboard';
        } elseif ($action === 'save_dashboard') {
            if (!$football instanceof PDO) throw new RuntimeException($footballError);
            $allowed = ['dashboard_title','dashboard_subtitle','english_data_provider','world_cup_data_provider','show_update_controls'];
            $stmt = $football->prepare('INSERT INTO dashboard_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value');
            foreach ($allowed as $key) {
                $value = $key === 'show_update_controls' ? (isset($_POST[$key]) ? '1' : '0') : trim((string)($_POST[$key] ?? ''));
                if (strlen($value) > 300) throw new RuntimeException('Dashboard text must be 300 characters or fewer.');
                $stmt->execute([$key, $value]);
            }
            $notice = 'Dashboard settings saved.'; $section = 'dashboard';
        }
    } catch (Throwable $exception) {
        if ($accounts->inTransaction()) $accounts->rollBack();
        $error = $exception instanceof PDOException ? 'That value is already in use or is invalid.' : $exception->getMessage();
    }
}
$groups = $accounts->query('SELECT g.*, COUNT(ugm.user_id) AS member_count FROM user_groups g LEFT JOIN user_group_memberships ugm ON ugm.group_id=g.id GROUP BY g.id ORDER BY g.name')->fetchAll();
$users = $accounts->query("SELECT u.*, GROUP_CONCAT(g.name, ', ') AS group_names, GROUP_CONCAT(g.id) AS group_ids FROM users u LEFT JOIN user_group_memberships ugm ON ugm.user_id=u.id LEFT JOIN user_groups g ON g.id=ugm.group_id GROUP BY u.id ORDER BY u.username")->fetchAll();
$teams = $football instanceof PDO ? $football->query('SELECT * FROM team_metadata ORDER BY competition_code, team_name')->fetchAll() : [];
$deductions = $football instanceof PDO ? $football->query('SELECT * FROM points_deductions ORDER BY season_label DESC, competition_code, team_name')->fetchAll() : [];
$dashboardSettings = $football instanceof PDO ? clever_dashboard_settings($football) : [];
$editGroup = null;
foreach ($groups as $row) if ((int)$row['id'] === (int)($_GET['edit_group'] ?? 0)) $editGroup = $row;
$editTeam = null;
foreach ($teams as $row) if ($row['competition_code'] === ($_GET['edit_competition'] ?? null) && $row['team_name'] === ($_GET['edit_team'] ?? null)) $editTeam = $row;
$editDeduction = null;
foreach ($deductions as $row) if ($row['competition_code'] === ($_GET['edit_competition'] ?? null) && $row['season_label'] === ($_GET['edit_season'] ?? null) && $row['team_name'] === ($_GET['edit_team'] ?? null) && $row['reason'] === ($_GET['edit_reason'] ?? null)) $editDeduction = $row;

$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin panel | CleverDino</title><link rel="stylesheet" href="/account/account.css"></head><body><main class="shell"><header class="topbar"><div><a class="brand" href="/">🦖 Clever<span>Dino</span></a><div class="muted">Administration</div></div><nav class="nav"><span><?= $h($currentUser['username']) ?></span><a href="/account/">My account</a><a href="/account/logout.php">Sign out</a></nav></header><nav class="tabs"><?php $adminTabs = $isAccountAdmin ? ['overview'=>'Overview','users'=>'Users','groups'=>'Groups','teams'=>'Team info','deductions'=>'Deductions','rules'=>'Competition rules','dashboard'=>'Dashboard'] : ['teams'=>'Team info','deductions'=>'Deductions','rules'=>'Competition rules','dashboard'=>'Dashboard']; foreach ($adminTabs as $key=>$label): ?><a class="<?= $section===$key?'active':'' ?>" href="?section=<?= $key ?>"><?= $label ?></a><?php endforeach; ?></nav><?php if ($notice): ?><div class="notice"><?= $h($notice) ?></div><?php endif; ?><?php if ($error): ?><div class="error"><?= $h($error) ?></div><?php endif; ?><?php if ($footballError): ?><div class="error"><?= $h($footballError) ?></div><?php endif; ?>
<?php if ($section === 'overview'): ?><div class="grid"><section class="panel"><h2>Accounts</h2><p><strong><?= count($users) ?></strong> users across <strong><?= count($groups) ?></strong> groups.</p><a class="btn" href="?section=users">Manage access</a></section><section class="panel"><h2>Football configuration</h2><p><strong><?= count($teams) ?></strong> team aliases and <strong><?= count($deductions) ?></strong> points deductions are stored in SQLite.</p><a class="btn" href="?section=teams">Manage data</a></section><section class="panel"><h2>System tools</h2><div class="stack"><a href="../football-stats/check-db.php">Football database check</a><a href="../youtube-dashboard/check-db.php">YouTube database check</a><a href="view-log.php">Football update logs</a></div></section></div>
<?php elseif ($section === 'groups'): ?><div class="grid"><section class="panel"><h2><?= $editGroup ? 'Edit group' : 'Add a group' ?></h2><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_group"><input type="hidden" name="id" value="<?= (int)($editGroup['id'] ?? 0) ?>"><label>Name<input name="name" value="<?= $h($editGroup['name'] ?? '') ?>" required></label><label>Description<textarea name="description"><?= $h($editGroup['description'] ?? '') ?></textarea></label><label class="inline"><input type="checkbox" name="is_admin" <?= !empty($editGroup['is_admin'])?'checked':'' ?>> Full administrator access</label><label class="inline"><input type="checkbox" name="can_manage_football" <?= !empty($editGroup['can_manage_football'])?'checked':'' ?>> Edit football configuration</label><label class="inline"><input type="checkbox" name="can_update_data" <?= !empty($editGroup['can_update_data'])?'checked':'' ?>> Run data updates</label><button>Save group</button></form></section><section class="panel"><h2>Groups</h2><div class="table-wrap"><table><tr><th>Name</th><th>Access</th><th>Members</th><th></th></tr><?php foreach ($groups as $group): ?><tr><td><strong><?= $h($group['name']) ?></strong><br><span class="muted"><?= $h($group['description']) ?></span></td><td><?= $group['is_admin']?'<span class="pill admin">Admin</span>':'Member' ?></td><td><?= (int)$group['member_count'] ?></td><td><a class="btn compact secondary" href="?section=groups&amp;edit_group=<?= (int)$group['id'] ?>">Edit</a> <?php if (!in_array($group['name'], ['Members','Administrators'], true)): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="delete_group"><input type="hidden" name="id" value="<?= (int)$group['id'] ?>"><button class="danger compact" onclick="return confirm('Delete this group?')">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div></section></div>
<?php elseif ($section === 'users'): ?><section class="panel"><h2>Users</h2><div class="stack"><?php foreach ($users as $user): $assigned=array_filter(explode(',', (string)$user['group_ids'])); ?><form method="post" class="panel"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_user"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><div class="grid"><label>Username<input name="username" value="<?= $h($user['username']) ?>" required></label><label>Email<input type="email" name="email" value="<?= $h($user['email']) ?>" required></label><label>Status<select name="status"><option value="active" <?= $user['status']==='active'?'selected':'' ?>>Active</option><option value="disabled" <?= $user['status']==='disabled'?'selected':'' ?>>Disabled</option></select></label><label>New password <span class="muted">(leave blank to retain)</span><input type="password" name="password" minlength="10"></label></div><p><strong>Groups</strong></p><div class="inline"><?php foreach ($groups as $group): ?><label class="inline"><input type="checkbox" name="groups[]" value="<?= (int)$group['id'] ?>" <?= in_array((string)$group['id'],$assigned,true)?'checked':'' ?>><?= $h($group['name']) ?></label><?php endforeach; ?></div><div class="actions" style="margin-top:15px"><button>Save user</button><?php if ((int)$user['id'] !== (int)$currentUser['id']): ?><button class="danger" name="action" value="delete_user" onclick="return confirm('Permanently delete this user?')">Delete user</button><?php endif; ?></div></form><?php endforeach; ?></div></section>
<?php elseif ($section === 'teams'): ?><section class="panel"><h2><?= $editTeam ? 'Edit team information' : 'Add team information' ?></h2><form method="post" class="grid"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_team"><input type="hidden" name="original_competition_code" value="<?= $h($editTeam['competition_code'] ?? '') ?>"><input type="hidden" name="original_team_name" value="<?= $h($editTeam['team_name'] ?? '') ?>"><label>Competition code<input name="competition_code" value="<?= $h($editTeam['competition_code'] ?? '') ?>" placeholder="PL, ELC, L1, L2, NL" required></label><label>Lookup/API team name<input name="team_name" value="<?= $h($editTeam['team_name'] ?? '') ?>" required></label><label>Display name<input name="display_name" value="<?= $h($editTeam['display_name'] ?? '') ?>" required></label><label>Common name<input name="common_name" value="<?= $h($editTeam['common_name'] ?? '') ?>" required></label><label>Nickname<input name="nickname" value="<?= $h($editTeam['nickname'] ?? '') ?>"></label><label>Short code<input name="short_code" maxlength="5" value="<?= $h($editTeam['short_code'] ?? '') ?>" required></label><label>Colour<input name="color" type="color" value="<?= $h($editTeam['color'] ?? '#5865F2') ?>" required></label><div><button style="margin-top:29px">Save team</button></div></form></section><section class="panel" style="margin-top:20px"><h2>Configured teams</h2><div class="table-wrap"><table><tr><th>Competition</th><th>Lookup name</th><th>Display</th><th>Nickname</th><th>Code</th><th></th></tr><?php foreach ($teams as $team): ?><tr><td><?= $h($team['competition_code']) ?></td><td><?= $h($team['team_name']) ?></td><td><span style="color:<?= $h($team['color']) ?>">●</span> <?= $h($team['display_name']) ?></td><td><?= $h($team['nickname']) ?></td><td><?= $h($team['short_code']) ?></td><td><a class="btn compact secondary" href="?section=teams&amp;edit_competition=<?= rawurlencode($team['competition_code']) ?>&amp;edit_team=<?= rawurlencode($team['team_name']) ?>">Edit</a><form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="delete_team"><input type="hidden" name="competition_code" value="<?= $h($team['competition_code']) ?>"><input type="hidden" name="team_name" value="<?= $h($team['team_name']) ?>"><button class="danger compact" onclick="return confirm('Delete this team alias?')">Delete</button></form></td></tr><?php endforeach; ?></table></div></section>
<?php elseif ($section === 'rules'): require __DIR__ . '/competition-rules.php'; ?>
<?php elseif ($section === 'deductions'): ?><div class="grid"><section class="panel"><h2><?= $editDeduction ? 'Edit points deduction' : 'Add a points deduction' ?></h2><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_deduction"><?php foreach (['competition_code','season_label','team_name','reason'] as $key): ?><input type="hidden" name="original_<?= $key ?>" value="<?= $h($editDeduction[$key] ?? '') ?>"><?php endforeach; ?><label>Competition code<input name="competition_code" value="<?= $h($editDeduction['competition_code'] ?? '') ?>" placeholder="ELC" required></label><label>Season<input name="season_label" value="<?= $h($editDeduction['season_label'] ?? '') ?>" placeholder="2026-2027" pattern="[0-9]{4}-[0-9]{4}" required></label><label>Team<input name="team_name" value="<?= $h($editDeduction['team_name'] ?? '') ?>" required></label><label>Points deducted<input type="number" name="points" min="1" max="100" value="<?= (int)($editDeduction['points'] ?? 1) ?>" required></label><label>Reason<textarea name="reason"><?= $h($editDeduction['reason'] ?? '') ?></textarea></label><button>Save deduction</button></form></section><section class="panel"><h2>Current deductions</h2><div class="table-wrap"><table><tr><th>Season</th><th>Team</th><th>Points</th><th></th></tr><?php foreach ($deductions as $item): ?><tr><td><?= $h($item['season_label']) ?><br><span class="pill"><?= $h($item['competition_code']) ?></span></td><td><?= $h($item['team_name']) ?><br><span class="muted"><?= $h($item['reason']) ?></span></td><td>&minus;<?= (int)$item['points'] ?></td><td><a class="btn compact secondary" href="?section=deductions&amp;edit_competition=<?= rawurlencode($item['competition_code']) ?>&amp;edit_season=<?= rawurlencode($item['season_label']) ?>&amp;edit_team=<?= rawurlencode($item['team_name']) ?>&amp;edit_reason=<?= rawurlencode($item['reason']) ?>">Edit</a><form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="delete_deduction"><?php foreach (['competition_code','season_label','team_name','reason'] as $key): ?><input type="hidden" name="<?= $key ?>" value="<?= $h($item[$key]) ?>"><?php endforeach; ?><button class="danger compact" onclick="return confirm('Delete this deduction?')">Delete</button></form></td></tr><?php endforeach; ?></table></div></section></div>
<?php elseif ($section === 'dashboard'): ?><section class="panel"><h2>Dashboard content</h2><p class="muted">Change presentation text and operational controls without editing PHP.</p><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_dashboard"><label>Dashboard title<input name="dashboard_title" maxlength="100" value="<?= $h($dashboardSettings['dashboard_title'] ?? '') ?>" required></label><label>Subtitle<textarea name="dashboard_subtitle" maxlength="300"><?= $h($dashboardSettings['dashboard_subtitle'] ?? '') ?></textarea></label><div class="grid"><label>English data provider<input name="english_data_provider" maxlength="100" value="<?= $h($dashboardSettings['english_data_provider'] ?? '') ?>"></label><label>World Cup data provider<input name="world_cup_data_provider" maxlength="100" value="<?= $h($dashboardSettings['world_cup_data_provider'] ?? '') ?>"></label></div><label class="inline"><input type="checkbox" name="show_update_controls" <?= ($dashboardSettings['show_update_controls'] ?? '1') === '1' ? 'checked' : '' ?>> Show data-update controls to authorised operators</label><button>Save dashboard settings</button></form></section><section class="panel"><h2>Custom Rules team groups</h2><p class="muted">Enter one club per line, or separate clubs with commas. Each list controls its Big and Outside Big buttons independently. Existing memberships are used until you save; an empty list selects no clubs.</p><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="action" value="save_team_groups"><?php $teamGroups = clever_team_groups($dashboardSettings); foreach (['big_six'=>'Big 6', 'big_eight'=>'Big 8', 'big_twelve'=>'Big 12'] as $groupKey=>$groupLabel): ?><label><?= $h($groupLabel) ?><textarea name="team_groups[<?= $groupKey ?>]" rows="12"><?= $h(implode("\n", $teamGroups[$groupKey])) ?></textarea></label><?php endforeach; ?><button>Save team groups</button></form></section><?php endif; ?></main></body></html>
