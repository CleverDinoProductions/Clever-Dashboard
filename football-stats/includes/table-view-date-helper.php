<?php
// Helper to get the first date for a given competition, season, and matchweek
function football_stats_get_first_date_for_matchweek(PDO $db, $competitionCode, $seasonLabel, $matchweek) {
    $stmt = $db->prepare("SELECT match_date FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek = ? AND match_date IS NOT NULL AND match_date != '' ORDER BY match_date ASC, id ASC LIMIT 1");
    $stmt->execute([$competitionCode, $seasonLabel, $matchweek]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['match_date'] : '';
}

// Helper to get the matchweek(s) that had matches on a given date, e.g. "MW 15" or "MW 15/16"
function football_stats_get_matchweek_for_date(PDO $db, $competitionCode, $seasonLabel, $date) {
    $stmt = $db->prepare("SELECT DISTINCT matchweek FROM matches WHERE competition_code = ? AND season_label = ? AND match_date = ? AND matchweek IS NOT NULL ORDER BY matchweek ASC");
    $stmt->execute([$competitionCode, $seasonLabel, $date]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($rows)) return '';
    if (count($rows) === 1) return 'MW ' . (int)$rows[0];
    return 'MW ' . implode('/', array_map('intval', $rows));
}
