<?php
// Helper to get the first date for a given competition, season, and matchweek
function football_stats_get_first_date_for_matchweek(PDO $db, $competitionCode, $seasonLabel, $matchweek) {
    $stmt = $db->prepare("SELECT match_date FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek = ? AND match_date IS NOT NULL AND match_date != '' ORDER BY match_date ASC, id ASC LIMIT 1");
    $stmt->execute([$competitionCode, $seasonLabel, $matchweek]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['match_date'] : '';
}
