<?php
// Since this is inside the /admin/ folder protected by .htaccess,
// the user is already authenticated by the server.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dino Admin | Control Panel</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #1a1a1e;
            color: white;
            padding: 40px;
        }
        .admin-container { max-width: 1200px; margin: 0 auto; }
        .header { border-bottom: 2px solid #5865F2; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #5865F2; }
        .back-link { color: #888; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: white; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .admin-card { background: #2e3136; border-radius: 8px; padding: 25px; border-left: 5px solid #5865F2; }
        .admin-card h2 { margin-bottom: 15px; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        
        .tool-link {
            display: block;
            background: #40444b;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
            transition: background 0.2s;
            font-family: monospace;
        }
        .tool-link:hover { background: #4f545c; }
        .tool-link span { color: #5865F2; font-weight: bold; }

        .sys-info { font-size: 13px; line-height: 1.8; color: #bbb; background: #1a1a1e; padding: 15px; border-radius: 4px; }
        .badge { background: #43b581; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="header">
        <h1>🦖 Admin Command Center</h1>
        <a href="../index.php" class="back-link">← Return to Public Hub</a>
    </div>

    <div class="grid">
        <!-- Database Maintenance -->
        <div class="admin-card">
            <h2>🗄️ Database Checks</h2>
            <p style="color: #888; font-size: 13px; margin-bottom: 10px;">Verify SQLite integrity and table status.</p>
            <a href="../mam-dashboard/check-db.php" class="tool-link"><span>></span> MAM Database Check</a>
            <a href="../football-stats/check-db.php" class="tool-link"><span>></span> Football Stats Check</a>
            <a href="../youtube-dashboard/check-db.php" class="tool-link"><span>></span> YouTube Feed Check</a>
            <a href="/phpmyadmin" class="tool-link" style="margin-top: 15px; border: 1px solid #5865F2;"><span>[DB]</span> Launch phpMyAdmin</a>
        </div>

        <!-- System Controls -->
        <div class="admin-card">
            <h2>⚙️ Server Environment</h2>
            <div class="sys-info">
                <strong>OS:</strong> <?php echo PHP_OS; ?> <br>
                <strong>PHP Version:</strong> <?php echo phpversion(); ?> <br>
                <strong>Server IP:</strong> <?php echo $_SERVER['SERVER_ADDR']; ?> <br>
                <strong>Document Root:</strong> <br> <code style="color: #5865F2;"><?php echo $_SERVER['DOCUMENT_ROOT']; ?></code>
            </div>
            <h3 style="margin-top: 20px; font-size: 14px; color: #888;">Active Ports</h3>
            <p style="margin-top: 5px;">
                <span class="badge">Web: 80</span> 
                <span class="badge">IRC: 9000</span> 
                <span class="badge">MySQL: 3306</span>
            </p>
        </div>

        <!-- Activity & Logs -->
        <div class="admin-card">
            <h2>📜 Quick Access Logs</h2>
            <p style="color: #888; font-size: 13px; margin-bottom: 10px;">Direct links to internal tracking files.</p>
            <a href="/mam-dashboard/logs/irc_sync.log" class="tool-link"><span>#</span> IRC Sync Logs</a>
            <a href="/youtube-dashboard/logs/rss_fetch.log" class="tool-link"><span>#</span> RSS Fetcher Logs</a>
            <a href="/football-stats/logs/api_requests.log" class="tool-link"><span>#</span> API Usage Logs</a>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; color: #444; font-size: 12px;">
        CLEVERDINO HUB v2.0 | SECURE ADMIN SESSION
    </div>
</div>

</body>
</html>