<?php
require_once dirname(__DIR__, 2) . '/lib/english-pyramid.php';

function provider_check(array $league, ?string $expected): void
{
    $actual = clever_regional_provider_code($league);
    if ($actual !== $expected) {
        throw new RuntimeException('Wrong provider mapping: ' . json_encode([$league, $expected, $actual]));
    }
}

foreach (clever_regional_competitions() as $regional) {
    provider_check(['strSport'=>'Soccer', 'strLeague'=>'English ' . $regional['label']], $regional['code']);
}
foreach (['Central'=>'SLPC', 'South'=>'SLPS'] as $division=>$code) {
    foreach (["Southern Premier League $division Division", "Southern Football League Division $division"] as $name) {
        provider_check(['strSport'=>'Soccer', 'strLeague'=>'English ' . $name], $code);
        provider_check(['strSport'=>'Soccer', 'strLeague'=>'Unrecognized name', 'strLeagueAlternate'=>'Other; english ' . strtolower($name) . ' ;Another'], $code);
        provider_check(['strSport'=>'Baseball', 'strLeague'=>$name], null);
    }
    provider_check(['strSport'=>'Soccer', 'strLeague'=>"English Southern Premier League $division Division One"], null);
    provider_check(['strSport'=>'Soccer', 'strLeague'=>"English Southern Football League Division 1 $division"], null);
}
provider_check(['strSport'=>'Soccer', 'strLeague'=>'English Northern Premier League'], 'NPL');
provider_check(['strSport'=>'Soccer', 'strLeague'=>'English Isthmian Premier League'], 'ILP');
provider_check([], null);
echo "Regional provider tests passed.\n";
