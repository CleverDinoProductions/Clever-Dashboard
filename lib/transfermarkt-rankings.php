<?php

declare(strict_types=1);

/**
 * Transfermarkt all-time POINTS rankings, not squad market values or current standings.
 * Snapshot checked against indexed Transfermarkt tables on 15 October 2026.
 * Coverage and starting seasons differ by competition; these are not complete
 * English football history rankings. Keep source URLs alongside ordered club names.
 * Unverified divisions deliberately have no fallback to a different competition.
 */
function clever_transfermarkt_rankings(): array
{
    return [
        'PL' => [
            'source' => 'https://www.transfermarkt.co.uk/premier-league/ewigeTabelle/wettbewerb/GB1',
            'checked' => '2026-10-15',
            'teams' => ['Manchester United', 'Arsenal', 'Liverpool', 'Chelsea', 'Tottenham Hotspur', 'Manchester City', 'Everton', 'Newcastle United', 'Aston Villa', 'West Ham United', 'Southampton', 'Blackburn Rovers'],
        ],
        'ELC' => [
            'source' => 'https://www.transfermarkt.co.uk/championship/ewigeTabelle/wettbewerb/GB2',
            'checked' => '2026-10-15',
            'teams' => ['Cardiff City', 'Derby County', 'Queens Park Rangers', 'Preston North End', 'Ipswich Town', 'Middlesbrough', 'Leeds United', 'Reading', 'Bristol City', 'Watford', 'Norwich City', 'Millwall'],
        ],
        'L1' => [
            'source' => 'https://www.transfermarkt.co.uk/league-one/ewigeTabelle/wettbewerb/GB3',
            'checked' => '2026-10-15',
            'teams' => ['Peterborough United', 'Milton Keynes Dons', 'Doncaster Rovers', 'Walsall', 'Charlton Athletic', 'Oldham Athletic', 'Leyton Orient', 'Huddersfield Town', 'Swindon Town', 'Gillingham', 'Blackpool', 'Scunthorpe United'],
        ],
        'L2' => [
            'source' => 'https://www.transfermarkt.co.uk/league-two/ewigeTabelle/wettbewerb/GB4',
            'checked' => '2026-10-15',
            'teams' => ['Mansfield Town', 'Northampton Town', 'Cheltenham Town', 'Accrington Stanley', 'Morecambe', 'Grimsby Town', 'Wycombe Wanderers', 'Notts County', 'Exeter City', 'Bradford City', 'Newport County', 'Carlisle United'],
        ],
        'NL' => [
            'source' => 'https://www.transfermarkt.co.uk/national-league/ewigeTabelle/wettbewerb/CNAT',
            'checked' => '2026-10-15',
            'teams' => ['Wrexham', 'Woking', 'Aldershot Town', 'Forest Green Rovers', 'Gateshead', 'York City', 'FC Halifax Town', 'Kidderminster Harriers', 'Altrincham', 'Eastleigh', 'Dagenham & Redbridge', 'Solihull Moors'],
        ],
        'NLN' => [
            'source' => 'https://www.transfermarkt.co.uk/national-league-north/ewigeTabelle/wettbewerb/NLN6',
            'checked' => '2026-10-15',
            'teams' => ['Brackley Town', 'Boston United', 'Chorley', 'Chester', 'Kidderminster Harriers', 'Curzon Ashton', 'Alfreton Town', 'Spennymoor Town', 'AFC Fylde', 'Darlington', 'Gloucester City', 'Bradford (Park Avenue)'],
        ],
        'NLS' => [
            'source' => 'https://www.transfermarkt.co.uk/national-league-south/ewigeTabelle/wettbewerb/NLS6',
            'checked' => '2026-10-15',
            'teams' => ['Chelmsford City', 'Bath City', 'Hampton & Richmond Borough', 'Hemel Hempstead Town', 'Eastbourne Borough', 'Chippenham Town', 'Dartford', 'St Albans City', 'Slough Town', 'Maidstone United', 'Welling United', 'Dorking Wanderers'],
        ],
    ];
}
