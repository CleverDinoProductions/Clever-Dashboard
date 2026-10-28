        <footer class="main-footer">
            <p>Public RSS upload history · <?= number_format(getValue("SELECT COUNT(*) FROM videos")) ?> videos · <?= number_format(getValue("SELECT COUNT(*) FROM channels")) ?> channels</p>
            <p>Page rendered <?= date('j M Y, H:i T') ?>. Reloading this page does not fetch new uploads.</p>
        </footer>
    </main>
</body>
</html>
