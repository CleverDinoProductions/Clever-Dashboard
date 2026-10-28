<?php
require_once __DIR__ . '/lib/auth.php';
$siteUser = clever_current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>CleverDino · Dashboard Hub</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore CleverDino football analytics, YouTube uploads and IRC activity dashboards.">
    <link rel="stylesheet" href="/assets/dashboard-shell.css">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <nav class="dashboard-account-bar" aria-label="Account">
        <?php if ($siteUser): ?>
            <span>Signed in as <strong><?= htmlspecialchars($siteUser['username'], ENT_QUOTES, 'UTF-8') ?></strong></span>
            <a href="/account/">My account</a>
            <?php if ($siteUser['is_admin']): ?><a href="/admin/admin.php">Admin</a><?php endif; ?>
            <a href="/account/logout.php">Sign out</a>
        <?php else: ?>
            <a href="/account/login.php">Sign in</a><a href="/account/register.php">Create account</a>
        <?php endif; ?>
    </nav>
    <header class="site-header">
        <h1 class="site-title">🦖 CleverDino Dashboard Hub</h1>
        <p class="site-subtitle">Football, channels and community. All in one place.</p>
    </header>
    <nav class="pill-nav" aria-label="Dashboards">
        <a class="pill-tab active" href="/" aria-current="page">Home</a>
        <a class="pill-tab" href="/football-stats/">⚽ Football Stats</a>
        <a class="pill-tab" href="/youtube-dashboard/">▶ YouTube Monitor</a>
        <a class="pill-tab" href="/irc-dashboard/">💬 IRC Activity</a>
    </nav>
    <main id="main-content" class="hub-main">
        <h2 class="section-title">Explore your dashboards</h2>
        <p class="section-description">Choose a workspace to explore the latest imported data and trends.</p>
        <div class="hub-cards">
            <article class="hub-card">
                <span class="card-icon" aria-hidden="true">⚽</span>
                <h3>Football Stats</h3>
                <p>Explore league tables, fixtures, team trackers and simulations across the English pyramid and international competitions.</p>
                <div class="card-actions">
                    <a class="pill-tab primary" href="/football-stats/">Open Football</a>
                    <a class="pill-tab" href="/football-stats/?tab=2025-2026&amp;league=premier-league&amp;subtab=matches">Fixtures</a>
                </div>
            </article>
            <article class="hub-card">
                <span class="card-icon" aria-hidden="true">▶</span>
                <h3>YouTube Monitor</h3>
                <p>Follow channel uploads, spot gaps and discover publishing patterns with searchable, locally cached RSS history.</p>
                <div class="card-actions"><a class="pill-tab primary" href="/youtube-dashboard/">Open YouTube</a><a class="pill-tab" href="/youtube-dashboard/?tab=channels">Channels</a></div>
            </article>
            <article class="hub-card">
                <span class="card-icon" aria-hidden="true">💬</span>
                <h3>IRC Activity</h3>
                <p>Explore community activity, message trends and queue reporting from your IRC logs.</p>
                <div class="card-actions"><a class="pill-tab primary" href="/irc-dashboard/">Open IRC</a></div>
            </article>
        </div>
        <h2 class="section-title">Manage your workspace</h2>
        <p class="section-description">Personal preferences and protected administration tools.</p>
        <div class="hub-cards">
            <article class="hub-card"><h3>My account</h3><p>Choose your favourite team, default competition and dashboard preferences.</p><div class="card-actions"><a class="pill-tab" href="/account/">Account settings</a></div></article>
            <article class="hub-card"><h3>Admin portal</h3><p>Manage accounts, football configuration and data tools. Authorised access required.</p><div class="card-actions"><a class="pill-tab" href="/admin/admin.php">Open administration</a></div></article>
        </div>
        <footer class="hub-footer">CleverDino · Football Stats · YouTube Monitor · IRC Activity</footer>
    </main>
</body>
</html>
