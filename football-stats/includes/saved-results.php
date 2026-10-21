<?php if (!in_array($currentMainTab, ['world-cup', 'nations-league'], true)):
$resultsEscape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$resultsSaves = $dashboardUser ? array_filter(clever_saved_results((int)$dashboardUser['id']), static fn($save) => $save['season'] === $currentMainTab && $save['league'] === $currentLeague) : [];
?>
<section class="panel" aria-label="Saved simulated results">
    <details <?= $savedResultsError ? 'open' : '' ?>>
        <summary>Saved simulated results · <?= count($resultsSaves) ?> saves</summary>
        <p>Apply custom match outcomes, then save each set by name. Load a set in another tab for this league and season. Loading replaces that tab’s custom rules.</p>
        <?php if ($savedResultsError): ?><p role="alert"><?= $resultsEscape($savedResultsError) ?></p><?php endif; ?>
        <?php if (!$dashboardUser): ?>
            <p><a href="/account/login.php">Sign in</a> to save result sets to your account.</p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>">
                <input type="hidden" name="results_action" value="save">
                <label>Save name <input name="save_name" maxlength="100" required placeholder="e.g. Promotion run"></label>
                <button type="submit" <?= empty($_GET['outcome_overrides']) ? 'disabled' : '' ?>>Save as new result set</button>
            </form>
            <?php if ($resultsSaves): ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>">
                <label>Result set <select name="save_id" required>
                    <?php foreach ($resultsSaves as $resultSave): ?>
                    <option value="<?= (int)$resultSave['id'] ?>"><?= $resultsEscape($resultSave['name'] . ' · ' . $resultSave['created_at'] . ' UTC') ?></option>
                    <?php endforeach; ?>
                </select></label>
                <button name="results_action" value="load">Load into this tab</button>
                <button name="results_action" value="delete">Delete save</button>
            </form>
            <?php else: ?><p>No saved result sets for this league and season yet.</p><?php endif; ?>
        <?php endif; ?>
    </details>
</section>
<?php endif; ?>
