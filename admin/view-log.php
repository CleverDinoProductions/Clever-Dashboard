<?php
// Protected by your parent directory's .htaccess rule
$log_path = '/var/log/update-stats.log';

// If this is an AJAX request from our JavaScript, just return the log content directly and exit
if (isset($_GET['ajax'])) {
    if (file_exists($log_path)) {
        $file = file($log_path);
        $lines = array_slice($file, -50); // Grab the last 50 entries
        foreach ($lines as $line) {
            echo htmlspecialchars($line);
        }
    } else {
        echo "Log file not found.";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dino Admin | Live Stats Stream</title>
    <meta charset="UTF-8">
    <style>
        body { background: #1a1a1e; color: #43b581; font-family: 'Consolas', 'Courier New', monospace; padding: 30px; }
        .log-container { max-width: 1200px; margin: 0 auto; }
        .log-header { border-bottom: 2px solid #43b581; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .log-header h1 { margin: 0; font-size: 24px; color: white; }
        .btn { background: #40444b; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 14px; font-family: sans-serif; display: inline-block; cursor: pointer; border: none; }
        .btn:hover { background: #4f545c; }
        .terminal { background: #111214; border: 1px solid #202225; border-radius: 6px; padding: 20px; overflow-x: auto; white-space: pre-wrap; line-height: 1.5; font-size: 13px; box-shadow: inset 0 0 10px #000; min-height: 300px; }
        .status-dot { width: 8px; height: 8px; background: #43b581; display: inline-block; border-radius: 50%; margin-right: 6px; box-shadow: 0 0 8px #43b581; }
        .pulse { animation: blink 2s infinite; }
        @keyframes blink { 0% { opacity: 0.3; } 50% { opacity: 1; } 100% { opacity: 0.3; } }
    </style>
</head>
<body>

<div class="log-container">
    <div class="log-header">
        <h1>🖥️ Live Stats Stream <span style="font-size: 14px; color: #888; font-weight: normal; margin-left: 10px;"><span class="status-dot pulse"></span>Watching crontab...</span></h1>
        <div>
            <a href="index.php" class="btn">← Back to Command Center</a>
            <button onclick="updateLog()" class="btn" style="background: #5865F2;">🔄 Manual Check</button>
        </div>
    </div>

    <div id="log-terminal" class="terminal">Loading latest execution logs...</div>
</div>

<script>
    const terminal = document.getElementById('log-terminal');

    function updateLog() {
        // Fetch just the log output using our AJAX flag
        fetch('view-log.php?ajax=1')
            .then(response => response.text())
            .then(data => {
                // If it's a massive matchday wall of text, keep the view scrolled to the bottom
                const shouldScroll = terminal.scrollTop + terminal.clientHeight === terminal.scrollHeight;
                
                terminal.textContent = data;
                
                if (shouldScroll) {
                    terminal.scrollTop = terminal.scrollHeight;
                }
            })
            .catch(err => {
                terminal.textContent = "Error streaming log updates: " + err;
            });
    }

    // Run right away on page load
    updateLog();

    // Check for updates every 30 seconds. If your PHP script skips writing, 
    // nothing changes visually. The second a goal goes in and a change is written, 
    // it paints instantly!
    setInterval(updateLog, 30000);
</script>

</body>
</html>