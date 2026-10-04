<?php

$source = file_get_contents(__DIR__ . '/../includes/table-view.php');

function team_filter_assert_contains($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\nMissing: " . $needle . "\n");
        exit(1);
    }
}

function team_filter_assert_not_contains($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) !== false) {
        fwrite(STDERR, $message . "\nUnexpected: " . $needle . "\n");
        exit(1);
    }
}

team_filter_assert_contains('data-team-filter-option', $source, 'The custom-rules team filter should render team checkboxes.');
team_filter_assert_contains('data-team-filter-preset="big-six"', $source, 'The custom-rules team filter should offer a Big Six preset.');
foreach (['arsenal', 'chelsea', 'liverpool', 'manchester city', 'manchester united', 'tottenham hotspur'] as $team) {
    team_filter_assert_contains("'" . $team . "'", $source, 'The Big Six preset should include ' . $team . '.');
}
team_filter_assert_contains('function isBigSixTeam(teamName)', $source, 'The Big Six preset should use one shared team matcher.');
team_filter_assert_contains(".replace(/\\s+(?:football club|fc)$/i, '')", $source, 'The Big Six matcher should accept team names with an FC suffix.');
team_filter_assert_contains(".replace(/^tottenham$/, 'tottenham hotspur')", $source, 'The Big Six matcher should accept Tottenham as an alias.');
team_filter_assert_contains("preset === 'big-six' && isBigSixTeam(checkbox.value)", $source, 'Generated team selectors should match every Big Six club.');
team_filter_assert_contains("preset === 'big-six' && isBigSixTeam(box.value)", $source, 'Result filters should match every Big Six club.');
team_filter_assert_contains("'only' => ['title' => 'Include only matching results'", $source, 'The custom-rules filters should provide an include-only section.');
team_filter_assert_contains("'exclude' => ['title' => 'Include everything but matching results'", $source, 'The custom-rules filters should provide an exclude-matching section.');
team_filter_assert_contains("box.checked = includeOnly ? matches : !matches", $source, 'Each filtering section should replace the selection with its matching or inverse result set.');
team_filter_assert_contains("section.querySelectorAll('[data-team-filter-option]')", $source, 'Each filtering section should use its own selected teams.');
team_filter_assert_contains(".custom-match-option input[type=\"checkbox\"]", $source, 'Result selection must not include the team-filter checkboxes.');
team_filter_assert_not_contains("});\n                        updateTeamFilterSummary();\n                        updateSelectionStatus();", $source, 'The panel initializer must not call the section-scoped summary helper before wiring the recalculate button.');

echo "Custom-rules team filter tests passed.\n";
