<?php
// Included only after admin.php checks authentication, football permissions and CSRF.
if (!isset($currentUser) || (empty($currentUser['is_admin']) && empty($currentUser['can_manage_football']))) {
    http_response_code(403);
    exit('Football editor or administrator access is required.');
}
$competitions = clever_competitions();
$ruleCode = (string)($_POST['competition_code'] ?? $_GET['competition_code'] ?? 'PL');
$ruleSeason = trim((string)($_POST['season_label'] ?? $_GET['season_label'] ?? ''));
if (!isset($competitions[$ruleCode])) $ruleCode = 'PL';
$effectiveRules = $football instanceof PDO ? clever_competition_rules($football, $ruleCode, $ruleSeason === '' ? null : $ruleSeason) : clever_bundled_competition_rules($ruleCode, $ruleSeason === '' ? null : $ruleSeason);
$ruleForm = $error && ($_POST['action'] ?? '') === 'save_competition_rules' ? $_POST : $effectiveRules;
$zoneRows = is_array($ruleForm['zones'] ?? null) ? array_filter($ruleForm['zones'], 'is_array') : [];
$zoneRows = array_values($zoneRows);
$zoneRows[] = ['key' => '', 'label' => '', 'from' => '', 'to' => '', 'color' => '#888888'];
$palette = football_stats_table_zone_palette();
$renderZoneRow = static function ($index, $zone) use ($h, $palette) {
    $color = $zone['color'] ?? $palette[$zone['key'] ?? ''][0] ?? '#888888';
?>
<tr>
    <td><select name="zones[<?= $h($index) ?>][key]" aria-label="Zone type"><option value="">Unused</option><?php foreach (clever_competition_zone_types() as $key => $label): ?><option value="<?= $h($key) ?>" <?= ($zone['key'] ?? '') === $key ? 'selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select></td>
    <td><input name="zones[<?= $h($index) ?>][label]" value="<?= $h($zone['label'] ?? '') ?>" maxlength="100" aria-label="Zone label"></td>
    <td><input type="number" name="zones[<?= $h($index) ?>][from]" value="<?= $h($zone['from'] ?? '') ?>" min="1" max="100" aria-label="First position"></td>
    <td><input type="number" name="zones[<?= $h($index) ?>][to]" value="<?= $h($zone['to'] ?? '') ?>" min="1" max="100" aria-label="Last position"></td>
    <td><input type="color" name="zones[<?= $h($index) ?>][color]" value="<?= $h($color) ?>" aria-label="Zone colour"></td>
    <td><button type="button" class="secondary compact remove-zone">Remove</button></td>
</tr>
<?php }; ?>
<section class="panel">
    <h2>Competition rules</h2>
    <p>Choose a league and season, then load its rules. Leave the season blank to edit league defaults. Season rules control the table fill and right edge; defaults control the left edge.</p>
    <form method="get" class="stack">
        <input type="hidden" name="section" value="rules">
        <label>League<select name="competition_code"><?php foreach ($competitions as $code => $label): ?><option value="<?= $h($code) ?>" <?= $ruleCode === $code ? 'selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select></label>
        <label>Season<input name="season_label" value="<?= $h($ruleSeason) ?>" placeholder="Blank for league defaults, or 2026-2027" pattern="[0-9]{4}-[0-9]{4}"></label>
        <button>Load rules</button>
    </form>
</section>
<section class="panel">
    <h2><?= $h($competitions[$ruleCode]) ?> — <?= $ruleSeason === '' ? 'League defaults' : $h($ruleSeason) ?></h2>
    <p>Saving creates a complete override for this league and season. An empty zone list turns off position highlights. Changes apply when the dashboard reloads.</p>
    <form method="post" class="stack">
        <input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>">
        <input type="hidden" name="action" value="save_competition_rules">
        <input type="hidden" name="competition_code" value="<?= $h($ruleCode) ?>">
        <input type="hidden" name="season_label" value="<?= $h($ruleSeason) ?>">
        <div class="grid"><?php foreach (clever_competition_numeric_fields() as $key => [$label, $min, $max]): ?>
            <label><?= $h($label) ?><input type="number" name="<?= $h($key) ?>" value="<?= $h($ruleForm[$key] ?? '') ?>" min="<?= $min ?>" max="<?= $max ?>" required></label>
        <?php endforeach; ?></div>
        <label>Quarter split boundaries<input name="quarter_boundaries" value="<?= $h(is_array($ruleForm['quarter_boundaries'] ?? null) ? implode(', ', $ruleForm['quarter_boundaries']) : ($ruleForm['quarter_boundaries'] ?? '')) ?>" required placeholder="12, 23, 35"></label>
        <p class="muted">Win, draw and loss points set the scoring system for this league or season (normally 3, 1, 0). Custom rules can override these for a what-if table. The three last-game numbers split quarter filters where available. Matchweeks exclude playoff fixtures from regular-season tables. Comparison points targets control the Table 2 points-needed and PPG columns.</p>
        <div class="table-wrap"><table><thead><tr><th>Type</th><th>Label</th><th>From</th><th>To</th><th>Colour</th><th></th></tr></thead><tbody id="rule-zones"><?php foreach ($zoneRows as $index => $zone) $renderZoneRow($index, $zone); ?></tbody></table></div>
        <button type="button" class="secondary" id="add-rule-zone">Add position zone</button>
        <button <?= !$football instanceof PDO ? 'disabled' : '' ?>>Save rules</button>
    </form>
    <form method="post" class="stack" style="margin-top:16px">
        <input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>">
        <input type="hidden" name="action" value="reset_competition_rules">
        <input type="hidden" name="competition_code" value="<?= $h($ruleCode) ?>">
        <input type="hidden" name="season_label" value="<?= $h($ruleSeason) ?>">
        <p class="muted">Reset removes the saved override and restores bundled season rules or inherited league defaults.</p>
        <button class="danger" <?= !$football instanceof PDO ? 'disabled' : '' ?> onclick="return confirm('Remove this saved override and restore fallback rules?')">Reset override</button>
    </form>
</section>
<section class="panel"><h2>Saved overrides</h2>
<?php $savedRules = $football instanceof PDO ? $football->query('SELECT competition_code, season_label FROM competition_rule_overrides ORDER BY competition_code, season_label DESC')->fetchAll(PDO::FETCH_ASSOC) : []; ?>
<?php if (!$savedRules): ?><p class="muted">No overrides saved. Bundled rules are active, including the Premier League 2025-2026 qualification places.</p><?php endif; ?>
<ul><?php foreach ($savedRules as $saved): ?><li><a href="?section=rules&amp;competition_code=<?= rawurlencode($saved['competition_code']) ?>&amp;season_label=<?= rawurlencode($saved['season_label']) ?>"><?= $h($competitions[$saved['competition_code']] ?? $saved['competition_code']) ?> — <?= $h($saved['season_label'] ?: 'League defaults') ?></a></li><?php endforeach; ?></ul>
</section>
<template id="rule-zone-template"><?php $renderZoneRow('__INDEX__', []); ?></template>
<script>
(() => {
    const rows = document.getElementById('rule-zones');
    let nextIndex = <?= count($zoneRows) ?>;
    document.getElementById('add-rule-zone').addEventListener('click', () => {
        const fragment = document.getElementById('rule-zone-template').content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach(input => input.name = input.name.replace('__INDEX__', nextIndex));
        nextIndex++;
        rows.appendChild(fragment);
    });
    rows.addEventListener('click', event => {
        if (event.target.closest('.remove-zone')) event.target.closest('tr').remove();
    });
    const colours = <?= json_encode(array_map(static fn($value) => $value[0], $palette), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    rows.addEventListener('change', event => {
        if (!event.target.name.endsWith('[key]')) return;
        const row = event.target.closest('tr');
        const label = row.querySelector('[name$="[label]"]');
        if (!label.value) label.value = event.target.selectedOptions[0].text;
        row.querySelector('[name$="[color]"]').value = colours[event.target.value] || '#888888';
    });
})();
</script>
