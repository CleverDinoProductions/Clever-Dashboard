<?php
session_start();

/** 
 * ADMIN CREDENTIALS
 * Change these for your PebbleHost VPS security!
 */
$admin_user = "admin";
$admin_pass = "DinoMaster2026"; // Change this to something strong

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$error = "";

// Handle Login Attempt
if (isset($_POST['login'])) {
    if ($_POST['user'] === $admin_user && $_POST['pass'] === $admin_pass) {
        $_SESSION['logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Access Denied: Invalid Credentials";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Official Site for CleverDino</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1e 0%, #2e3136 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 50px;
            color: white;
            position: relative;
        }
        
        .header h1 {
            font-size: 48px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            font-size: 18px;
            color: #888;
        }

        .logout-link {
            position: absolute;
            right: 0;
            top: 0;
            color: #e74c3c;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            border: 1px solid #e74c3c;
            padding: 5px 15px;
            border-radius: 5px;
        }

        .logout-link:hover {
            background: #e74c3c;
            color: white;
        }
        
        .section-title {
            color: white;
            font-size: 24px;
            margin: 40px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #40444b;
        }
        
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .card {
            background: #2e3136;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border: 2px solid transparent;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(88,101,242,0.3);
            border-color: #5865F2;
        }
        
        .card-icon {
            font-size: 64px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }
        
        .card h2 {
            color: white;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .card p {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .card a, .login-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #5865F2, #4752c4);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            width: 100%;
        }
        
        .card a:hover, .login-btn:hover {
            background: linear-gradient(135deg, #4752c4, #3c44a8);
            transform: scale(1.02);
        }

        /* Login Form Styles */
        .login-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            background: #1e2124;
            border: 1px solid #40444b;
            color: white;
            border-radius: 6px;
        }

        .error-msg {
            color: #f04747;
            font-size: 13px;
            margin-top: 10px;
        }
        
        .status {
            display: inline-block;
            padding: 4px 12px;
            background: #43b581;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .footer {
            text-align: center;
            color: #888;
            margin-top: 50px;
            padding: 20px;
            border-top: 1px solid #40444b;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-box {
            background: #2e3136;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #5865F2;
        }
        
        .stat-box .number {
            font-size: 36px;
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-box .label {
            color: #888;
            font-size: 14px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-group a {
            flex: 1;
            min-width: 100px;
        }
        
        .btn-secondary { background: linear-gradient(135deg, #43b581, #3a9d6f) !important; }
        .btn-tertiary { background: linear-gradient(135deg, #faa61a, #f39c12) !important; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if($is_logged_in): ?>
                <a href="?logout=1" class="logout-link">Logout</a>
            <?php endif; ?>
            <h1>🦖 Official Site for CleverDino</h1>
            <p>Home of many Dashboards and Analytics</p>
        </div>

        <!-- Global Stats Section -->
        <div class="stats">
            <div class="stat-box">
                <div class="number">6</div>
                <div class="label">Active Services</div>
            </div>
            <div class="stat-box">
                <div class="number">3</div>
                <div class="label">Analytics Dashboards</div>
            </div>
            <div class="stat-box">
                <div class="number">24/7</div>
                <div class="label">Uptime</div>
            </div>
            <div class="stat-box">
                <div class="number">Hosted by</div>
                <div class="label">PebbleHost</div>
            </div>
        </div>

        <!-- PUBLIC SECTION: Analytics -->
        <h2 class="section-title">📊 Analytics Dashboards</h2>
        <div class="cards">
            <div class="card">
                <div class="card-icon">📊</div>
                <h2>MAM Analytics</h2>
                <p>Real-time IRC queue tracking, activity heatmaps, and historical trends</p>
                <div class="btn-group">
                    <a href="/mam-dashboard/" target="_blank">Dashboard</a>
                    <a href="/mam-dashboard/?tab=queue" target="_blank" class="btn-secondary">Queue</a>
                </div>
                <div class="status">● ONLINE</div>
            </div>

            <div class="card">
                <div class="card-icon">⚽</div>
                <h2>Football Stats</h2>
                <p>Live match tracking, player statistics, and league standings via FotMob</p>
                <div class="btn-group">
                    <a href="/football-stats/" target="_blank">Dashboard</a>
                    <a href="/football-stats/tabs/fixtures.php" target="_blank" class="btn-secondary">Fixtures</a>
                </div>
                <div class="status">● ONLINE</div>
            </div>

            <div class="card">
                <div class="card-icon">📺</div>
                <h2>YouTube Tracker</h2>
                <p>Multi-channel monitoring and upload timelines via RSS feeds</p>
                <div class="btn-group">
                    <a href="/youtube-dashboard/" target="_blank">Dashboard</a>
                    <a href="/youtube-dashboard/tabs/analytics.php" target="_blank" class="btn-tertiary">Analytics</a>
                </div>
                <div class="status">● ONLINE</div>
            </div>
        </div>

        <!-- PUBLIC SECTION: IRC -->
        <h2 class="section-title">💬 Communication & IRC</h2>
        <div class="cards">
            <div class="card">
                <div class="card-icon">💬</div>
                <h2>CleverLounge</h2>
                <p>Custom IRC client for MyAnonaMouse with persistent SQLite history</p>
                <div class="btn-group">
                    <a href="http://irc.cleverdino.net:9000" target="_blank">🌐 Network</a>
                </div>
                <div class="status">● ONLINE</div>
            </div>
        </div>

        <!-- LOCKED SECTION: Admin Tools -->
        <h2 class="section-title">🛠️ System & Admin Tools</h2>
        <div class="cards">
            <?php if ($is_logged_in): ?>
                <!-- Database Admin -->
                <div class="card">
                    <div class="card-icon">🗄️</div>
                    <h2>Database Admin</h2>
                    <p>Manage SQLite and MySQL databases, browse tables, and execute queries.</p>
                    <a href="/phpmyadmin" target="_blank">Open phpMyAdmin</a>
                    <div class="status">● AUTHORIZED</div>
                </div>

                <!-- Quick Links -->
                <div class="card">
                    <div class="card-icon">🔗</div>
                    <h2>Quick Links</h2>
                    <div style="text-align: left;">
                        <a href="/mam-dashboard/check-db.php" style="display: block; margin: 8px 0; padding: 8px; background: #40444b; color: white; text-decoration: none; border-radius: 4px;">🔍 MAM DB Check</a>
                        <a href="/football-stats/check-db.php" style="display: block; margin: 8px 0; padding: 8px; background: #40444b; color: white; text-decoration: none; border-radius: 4px;">⚽ Football DB Check</a>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card">
                    <div class="card-icon">⚙️</div>
                    <h2>System Info</h2>
                    <p style="text-align: left;">
                        <strong>Server:</strong> <?php echo explode(' ', $_SERVER['SERVER_SOFTWARE'])[0]; ?><br>
                        <strong>PHP:</strong> <?php echo phpversion(); ?><br>
                        <strong>Platform:</strong> <?php echo PHP_OS; ?><br>
                        <strong>Web Port:</strong> <?php echo $_SERVER['SERVER_PORT']; ?><br>
                        <strong>Client IP:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?>
                    </p>
                </div>
            <?php else: ?>
                <!-- LOGIN FORM CARD -->
                <div class="card" style="border: 1px dashed #5865F2;">
                    <div class="card-icon">🔒</div>
                    <h2>Admin Login</h2>
                    <p>Sensitive tools are currently restricted.</p>
                    <form method="POST">
                        <input type="text" name="user" class="login-input" placeholder="Username" required>
                        <input type="password" name="pass" class="login-input" placeholder="Password" required>
                        <button type="submit" name="login" class="login-btn">Unlock Tools</button>
                    </form>
                    <?php if($error): ?>
                        <p class="error-msg"><?php echo $error; ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p><strong>CleverDino Hub</strong> • VPS Infrastructure • PebbleHost Deployment</p>
            <p style="margin-top: 10px; font-size: 11px; color: #666;">
                MAM Analytics • Football Stats • YouTube Tracker • CleverLounge IRC • 100% Self-Hosted
            </p>
        </div>
    </div>
</body>
</html>