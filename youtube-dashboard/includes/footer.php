        <footer class="main-footer">
            <p>Last updated: <?= date('Y-m-d H:i:s') ?></p>
            <p>Database: <?= basename(DB_PATH) ?> | 
               Videos: <?= getValue("SELECT COUNT(*) FROM videos") ?> | 
               Channels: <?= getValue("SELECT COUNT(*) FROM channels") ?>
            </p>
        </footer>
    </div>
</body>
</html>
