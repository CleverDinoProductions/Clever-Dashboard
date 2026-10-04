<?php

$source = file_get_contents(__DIR__ . '/../includes/table-view.php');

function team_filter_assert_contains($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\nMissing: " . $needle . "\n");
        exit(1);
    }
}

team_filter_assert_contains('data-team-filter-option', $source, 'The custom-rules team filter should render team checkboxes.');
team_filter_assert_contains('data-team-filter-preset="big-six"', $source, 'The custom-rules team filter should offer a Big Six preset.');
foreach (['Arsenal', 'Chelsea', 'Liverpool', 'Manchester City', 'Manchester United', 'Tottenham Hotspur'] as $team) {
    team_filter_assert_contains("'" . $team . "'", $source, 'The Big Six preset should include ' . $team . '.');
}
team_filter_assert_contains("selectedTeams.indexOf(box.dataset.team) === -1", $source, 'Combined filtering should accept every checked team.');
team_filter_assert_contains(".custom-match-option input[type=\"checkbox\"]", $source, 'Result selection must not include the team-filter checkboxes.');
team_filter_assert_contains("panel.querySelectorAll('[data-team-checkbox-select]')", $source, 'Every team-based select should be upgraded to the checkbox dropdown.');
foreach (['data-team-rule data-team-checkbox-select', 'data-result-filter-opponent data-team-checkbox-select', 'data-bulk-outcome-team data-team-checkbox-select', 'data-bulk-team-opponent data-team-checkbox-select'] as $selector) {
    team_filter_assert_contains($selector, $source, 'Team-based filter is missing its checkbox-dropdown enhancement: ' . $selector);
}
team_filter_assert_contains("teams.indexOf(select.dataset.homeTeam)", $source, 'Bulk outcome changes should accept multiple selected teams.');
team_filter_assert_contains("teamOpponents.indexOf(opponent)", $source, 'Bulk outcome changes should accept multiple selected opponents.');

echo "Custom-rules team filter tests passed.\n";
