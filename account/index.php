<?php
require_once dirname(__DIR__) . '/lib/auth.php';
$user = clever_require_login();
$notice = isset($_GET['created']) ? 'Your account is ready.' : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clever_verify_csrf();
    try {
        clever_save_user_preferences((int)$user['id'], $_POST);
        $notice = 'Your football dashboard preferences have been saved.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
$preferences = clever_user_preferences((int)$user['id']);
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$leagues = ['division-one'=>'Division One','premier-league'=>'Premier League','championship'=>'Championship','league-one'=>'League One','league-two'=>'League Two','national-league'=>'National League'];
$views = ['table'=>'Regular table','table-2'=>'Deep dive table','matches'=>'Matches','compare'=>'Compare teams','blocks-overview'=>'Blocks overview','team-tracker-2'=>'Team dashboard','simulation'=>'Simulation'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My account | CleverDino</title><link rel="stylesheet" href="/account/account.css"></head><body><main class="shell"><header class="topbar"><a class="brand" href="/">🦖 Clever<span>Dino</span></a><nav class="nav"><a href="/football-stats/">Football dashboard</a><?php if ($user['is_admin']): ?><a href="/admin/admin.php">Admin panel</a><?php endif; ?><a href="/account/logout.php">Sign out</a></nav></header>
<?php if ($notice): ?><div class="notice"><?= $h($notice) ?></div><?php endif; ?><?php if ($error): ?><div class="error"><?= $h($error) ?></div><?php endif; ?>
<section class="panel"><h1><?= $h($user['username']) ?></h1><div class="grid"><div><h3>Email</h3><p><?= $h($user['email']) ?></p></div><div><h3>Groups</h3><p><?= $h($user['groups'] ?: 'None') ?></p></div><div><h3>Member since</h3><p><?= $h($user['created_at']) ?></p></div><div><h3>Last sign in</h3><p><?= $h($user['last_login_at'] ?: 'This is your first sign in') ?></p></div></div></section>
<section class="panel" style="margin-top:20px"><h2>Football dashboard customisation</h2><p class="muted">Choose where the dashboard opens and personalise its appearance. These settings follow your account across devices.</p><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><div class="grid"><label>Default season<select name="default_season"><option value="2025-2026" <?= $preferences['default_season']==='2025-2026'?'selected':'' ?>>2025/26</option><option value="2026-2027" <?= $preferences['default_season']==='2026-2027'?'selected':'' ?>>2026/27</option><option value="world-cup" <?= $preferences['default_season']==='world-cup'?'selected':'' ?>>World Cup 2026</option></select></label><label>Default league<select name="default_league"><?php foreach ($leagues as $key=>$label): ?><option value="<?= $key ?>" <?= $preferences['default_league']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label><label>Default view<select name="default_view"><?php foreach ($views as $key=>$label): ?><option value="<?= $key ?>" <?= $preferences['default_view']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label><label>Favourite team<input name="favourite_team" maxlength="100" value="<?= $h($preferences['favourite_team']) ?>" placeholder="Used by Team Tracker"></label><label>Dashboard accent colour<input type="color" name="accent_color" value="<?= $h($preferences['accent_color']) ?>"></label></div><label class="inline"><input type="checkbox" name="compact_navigation" value="1" <?= $preferences['compact_navigation']?'checked':'' ?>> Use compact navigation (hide icons and reduce spacing)</label><div class="actions"><button>Save customisation</button><a class="btn secondary" href="/football-stats/">Preview dashboard</a></div></form></section></main></body></html>
