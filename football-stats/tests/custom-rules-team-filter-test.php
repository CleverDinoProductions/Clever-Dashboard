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

echo "Custom-rules team filter tests passed.\n";
