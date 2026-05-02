<!DOCTYPE html>
<html lang="en">
<head>
    <title>Official Site for CleverDino</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Unified Styles */
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
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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
        
        .card a {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #5865F2, #4752c4);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.2s;
            width: 100%;
        }
        
        .card a:hover {
            background: linear-gradient(135deg, #4752c4, #3c44a8);
            transform: scale(1.02);
        }
        
        .status {
            display: inline-block;
            padding: 4px 12px;
            background: #43b581;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
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
        .admin-btn { background: linear-gradient(135deg, #4f545c, #2f3136) !important; border: 1px solid #4f545c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🦖 Official Site for CleverDino</h1>
            <p>Home of many Dashboards and Analytics</p>
        </div>

        <!-- Global Stats Section -->
        <div class="stats">
            <div class="stat-box">
                <div class="number">1</div>
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
            <!-- Football Stats Card -->
            <div class="card">
                <div>
                    <div class="card-icon">⚽</div>
                    <h2>Football Stats</h2>
                    <p>Live match tracking, player statistics, and league standings via FotMob.</p>
                </div>
                <div class="btn-group">
                    <a href="/football-stats/" target="_blank">Dashboard</a>
                    <a href="/football-stats/tabs/fixtures.php" target="_blank" class="btn-secondary">Fixtures</a>
                </div>
                <div><span class="status">● ONLINE</span></div>
            </div>
        </div>

        <!-- Admin Section -->
        <h2 class="section-title">🔐 Admin</h2>
        <div class="cards">
            <!-- New Admin Access Card -->
            <div class="card" style="border: 1px solid #4f545c;">
                <div>
                    <div class="card-icon">🔑</div>
                    <h2>Admin Portal</h2>
                    <p>Access system tools, database management, and server logs.</p>
                </div>
                <a href="/admin/admin.php" class="admin-btn">Secure Login</a>
                <div><span class="status" style="background: #72767d;">RESTRICTED</span></div>
            </div>
        </div>

        <div class="footer">
            <p><strong>CleverDino Hub</strong> • VPS Infrastructure • PebbleHost Deployment</p>
            <p style="margin-top: 10px; font-size: 11px; color: #666;">
                CleverDino Hub • VPS Infrastructure • Football Stats • PebbleHost Deployment
            </p>
        </div>
    </div>
</body>
</html>