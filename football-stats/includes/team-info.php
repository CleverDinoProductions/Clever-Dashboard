<?php
/**
 * Team metadata for all EFL/National League tiers.
 * Keys cover all name variants used by SportsDB so lookups work regardless
 * of which form the API returns (e.g. "Wolves" vs "Wolverhampton Wanderers",
 * "Brighton & Hove Albion" vs "Brighton and Hove Albion", etc.).
 *
 * Each entry: name, common_name, nickname, short (3-letter code), color (hex).
 */

// ---------------------------------------------------------------------------
// Premier League
// ---------------------------------------------------------------------------
$team_info_PL = [
    'Arsenal' => ['name' => 'Arsenal', 'common_name' => 'Arsenal', 'nickname' => 'The Gunners', 'short' => 'ARS', 'color' => '#EF0107'],
    'Aston Villa' => ['name' => 'Aston Villa', 'common_name' => 'Villa', 'nickname' => 'The Villans', 'short' => 'AVL', 'color' => '#95BFE5'],
    'Bournemouth' => ['name' => 'Bournemouth', 'common_name' => 'Bournemouth', 'nickname' => 'The Cherries', 'short' => 'BOU', 'color' => '#DA291C'],
    'AFC Bournemouth' => ['name' => 'Bournemouth', 'common_name' => 'Bournemouth', 'nickname' => 'The Cherries', 'short' => 'BOU', 'color' => '#DA291C'],
    'Brentford' => ['name' => 'Brentford', 'common_name' => 'Brentford', 'nickname' => 'The Bees', 'short' => 'BRE', 'color' => '#D20000'],
    'Brighton and Hove Albion' => ['name' => 'Brighton', 'common_name' => 'Brighton', 'nickname' => 'The Seagulls', 'short' => 'BHA', 'color' => '#0057B8'],
    'Brighton & Hove Albion' => ['name' => 'Brighton', 'common_name' => 'Brighton', 'nickname' => 'The Seagulls', 'short' => 'BHA', 'color' => '#0057B8'],
    'Burnley' => ['name' => 'Burnley', 'common_name' => 'Burnley', 'nickname' => 'The Clarets', 'short' => 'BUR', 'color' => '#6C1D45'],
    'Chelsea' => ['name' => 'Chelsea', 'common_name' => 'Chelsea', 'nickname' => 'The Blues', 'short' => 'CHE', 'color' => '#034694'],
    'Crystal Palace' => ['name' => 'Crystal Palace', 'common_name' => 'Palace', 'nickname' => 'The Eagles', 'short' => 'CRY', 'color' => '#1B458F'],
    'Everton' => ['name' => 'Everton', 'common_name' => 'Everton', 'nickname' => 'The Toffees', 'short' => 'EVE', 'color' => '#003399'],
    'Fulham' => ['name' => 'Fulham', 'common_name' => 'Fulham', 'nickname' => 'The Cottagers', 'short' => 'FUL', 'color' => '#000000'],
    'Ipswich Town' => ['name' => 'Ipswich', 'common_name' => 'Ipswich', 'nickname' => 'The Tractor Boys', 'short' => 'IPS', 'color' => '#0033FF'],
    'Leeds United' => ['name' => 'Leeds', 'common_name' => 'Leeds', 'nickname' => 'The Whites / Peacocks', 'short' => 'LEE', 'color' => '#FFCD00'],
    'Leicester City' => ['name' => 'Leicester', 'common_name' => 'Leicester', 'nickname' => 'The Foxes', 'short' => 'LEI', 'color' => '#003090'],
    'Liverpool' => ['name' => 'Liverpool', 'common_name' => 'Liverpool', 'nickname' => 'The Reds', 'short' => 'LIV', 'color' => '#C8102E'],
    'Luton' => ['name' => 'Luton', 'common_name' => 'Luton', 'nickname' => 'The Hatters', 'short' => 'LUT', 'color' => '#FFCD00'],
    'Luton Town' => ['name' => 'Luton', 'common_name' => 'Luton', 'nickname' => 'The Hatters', 'short' => 'LUT', 'color' => '#FFCD00'],
    'Manchester City' => ['name' => 'Manchester City', 'common_name' => 'Man City', 'nickname' => 'The Citizens / City', 'short' => 'MCI', 'color' => '#6CABDD'],
    'Manchester United' => ['name' => 'Manchester United', 'common_name' => 'Man United', 'nickname' => 'The Red Devils', 'short' => 'MUN', 'color' => '#DA291C'],
    'Newcastle United' => ['name' => 'Newcastle', 'common_name' => 'Newcastle', 'nickname' => 'The Magpies / Toon', 'short' => 'NEW', 'color' => '#241F20'],
    'Nottingham Forest' => ['name' => 'Nottingham Forest', 'common_name' => 'Forest', 'nickname' => 'The Reds / Forest', 'short' => 'NFO', 'color' => '#DD0000'],
    'Sheffield United' => ['name' => 'Sheffield United', 'common_name' => 'Sheff Utd', 'nickname' => 'The Blades', 'short' => 'SHU', 'color' => '#EE2737'],
    'Southampton' => ['name' => 'Southampton', 'common_name' => 'Southampton', 'nickname' => 'The Saints', 'short' => 'SOU', 'color' => '#D71920'],
    'Sunderland' => ['name' => 'Sunderland', 'common_name' => 'Sunderland', 'nickname' => 'The Black Cats', 'short' => 'SUN', 'color' => '#FF0000'],
    'Tottenham Hotspur' => ['name' => 'Tottenham Hotspur', 'common_name' => 'Spurs', 'nickname' => 'Spurs', 'short' => 'TOT', 'color' => '#132257'],
    'West Ham United' => ['name' => 'West Ham', 'common_name' => 'West Ham', 'nickname' => 'The Hammers / Irons', 'short' => 'WHU', 'color' => '#7A263A'],
    'Wolverhampton Wanderers' => ['name' => 'Wolverhampton Wanderers', 'common_name' => 'Wolves', 'nickname' => 'Wolves', 'short' => 'WOL', 'color' => '#FDB913'],
    'Wolves' => ['name' => 'Wolverhampton Wanderers', 'common_name' => 'Wolves', 'nickname' => 'Wolves', 'short' => 'WOL', 'color' => '#FDB913'],
];

// ---------------------------------------------------------------------------
// Championship (ELC)
// ---------------------------------------------------------------------------
$team_info_ELC = [
    'Birmingham City' => ['name' => 'Birmingham City', 'common_name' => 'Birmingham', 'nickname' => 'The Blues', 'short' => 'BIR', 'color' => '#0000FF'],
    'Blackburn Rovers' => ['name' => 'Blackburn Rovers', 'common_name' => 'Blackburn', 'nickname' => 'Rovers', 'short' => 'BLB', 'color' => '#0054A6'],
    'Bristol City' => ['name' => 'Bristol City', 'common_name' => 'Bristol City', 'nickname' => 'The Robins', 'short' => 'BRC', 'color' => '#BC0000'],
    'Charlton Athletic' => ['name' => 'Charlton Athletic', 'common_name' => 'Charlton', 'nickname' => 'The Addicks', 'short' => 'CHA', 'color' => '#E30613'],
    'Coventry City' => ['name' => 'Coventry City', 'common_name' => 'Coventry', 'nickname' => 'The Sky Blues', 'short' => 'COV', 'color' => '#87CEEB'],
    'Derby County' => ['name' => 'Derby County', 'common_name' => 'Derby', 'nickname' => 'The Rams', 'short' => 'DER', 'color' => '#000000'],
    'Hull City' => ['name' => 'Hull City', 'common_name' => 'Hull', 'nickname' => 'The Tigers', 'short' => 'HUL', 'color' => '#F5A100'],
    'Ipswich Town' => ['name' => 'Ipswich Town', 'common_name' => 'Ipswich', 'nickname' => 'The Tractor Boys', 'short' => 'IPS', 'color' => '#0033FF'],
    'Leeds United' => ['name' => 'Leeds United', 'common_name' => 'Leeds', 'nickname' => 'The Whites / Peacocks', 'short' => 'LEE', 'color' => '#FFFFFF'],
    'Leicester City' => ['name' => 'Leicester City', 'common_name' => 'Leicester', 'nickname' => 'The Foxes', 'short' => 'LEI', 'color' => '#003090'],
    'Middlesbrough' => ['name' => 'Middlesbrough', 'common_name' => 'Boro', 'nickname' => 'The Smoggies', 'short' => 'MID', 'color' => '#E21B23'],
    'Millwall' => ['name' => 'Millwall', 'common_name' => 'Millwall', 'nickname' => 'The Lions', 'short' => 'MIL', 'color' => '#00254B'],
    'Norwich City' => ['name' => 'Norwich City', 'common_name' => 'Norwich', 'nickname' => 'The Canaries', 'short' => 'NOR', 'color' => '#FFF200'],
    'Oxford United' => ['name' => 'Oxford United', 'common_name' => 'Oxford', 'nickname' => "The U's", 'short' => 'OXF', 'color' => '#FFFF00'],
    'Portsmouth' => ['name' => 'Portsmouth', 'common_name' => 'Pompey', 'nickname' => 'Pompey', 'short' => 'POR', 'color' => '#001489'],
    'Preston North End' => ['name' => 'Preston North End', 'common_name' => 'Preston', 'nickname' => 'The Lilywhites', 'short' => 'PNE', 'color' => '#FFFFFF'],
    'Queens Park Rangers' => ['name' => 'Queens Park Rangers', 'common_name' => 'QPR', 'nickname' => 'The Hoops', 'short' => 'QPR', 'color' => '#0000FF'],
    "Queen's Park Rangers" => ['name' => 'Queens Park Rangers', 'common_name' => 'QPR', 'nickname' => 'The Hoops', 'short' => 'QPR', 'color' => '#0000FF'],
    'QPR' => ['name' => 'Queens Park Rangers', 'common_name' => 'QPR', 'nickname' => 'The Hoops', 'short' => 'QPR', 'color' => '#0000FF'],
    'Sheffield United' => ['name' => 'Sheffield United', 'common_name' => 'Sheff Utd', 'nickname' => 'The Blades', 'short' => 'SHU', 'color' => '#EE2737'],
    'Sheffield Wednesday' => ['name' => 'Sheffield Wednesday', 'common_name' => 'Sheff Wed', 'nickname' => 'The Owls', 'short' => 'SHW', 'color' => '#0000FF'],
    'Southampton' => ['name' => 'Southampton', 'common_name' => 'Saints', 'nickname' => 'The Saints', 'short' => 'SOU', 'color' => '#D71920'],
    'Stoke City' => ['name' => 'Stoke City', 'common_name' => 'Stoke', 'nickname' => 'The Potters', 'short' => 'STK', 'color' => '#E03A3E'],
    'Swansea City' => ['name' => 'Swansea City', 'common_name' => 'Swansea', 'nickname' => 'The Swans', 'short' => 'SWA', 'color' => '#FFFFFF'],
    'Watford' => ['name' => 'Watford', 'common_name' => 'Watford', 'nickname' => 'The Hornets', 'short' => 'WAT', 'color' => '#FBEE23'],
    'West Bromwich Albion' => ['name' => 'West Brom', 'common_name' => 'West Brom', 'nickname' => 'The Baggies', 'short' => 'WBA', 'color' => '#122F67'],
    'West Brom' => ['name' => 'West Brom', 'common_name' => 'West Brom', 'nickname' => 'The Baggies', 'short' => 'WBA', 'color' => '#122F67'],
    'Wrexham' => ['name' => 'Wrexham', 'common_name' => 'Wrexham', 'nickname' => 'The Red Dragons', 'short' => 'WRE', 'color' => '#FF0000'],
    'Wrexham AFC' => ['name' => 'Wrexham', 'common_name' => 'Wrexham', 'nickname' => 'The Red Dragons', 'short' => 'WRE', 'color' => '#FF0000'],
];

// ---------------------------------------------------------------------------
// League One (L1)
// ---------------------------------------------------------------------------
$team_info_L1 = [
    'Barnsley' => ['name' => 'Barnsley', 'common_name' => 'Barnsley', 'nickname' => 'The Tykes', 'short' => 'BAR', 'color' => '#E81734'],
    'Blackpool' => ['name' => 'Blackpool', 'common_name' => 'Blackpool', 'nickname' => 'The Seasiders', 'short' => 'BLP', 'color' => '#F68712'],
    'Bolton Wanderers' => ['name' => 'Bolton Wanderers', 'common_name' => 'Bolton', 'nickname' => 'The Trotters', 'short' => 'BOL', 'color' => '#FFFFFF'],
    'Burton Albion' => ['name' => 'Burton Albion', 'common_name' => 'Burton', 'nickname' => 'The Brewers', 'short' => 'BUR', 'color' => '#FFD200'],
    'Cambridge United' => ['name' => 'Cambridge United', 'common_name' => 'Cambridge', 'nickname' => "The U's", 'short' => 'CAM', 'color' => '#FFD200'],
    'Exeter City' => ['name' => 'Exeter City', 'common_name' => 'Exeter', 'nickname' => 'The Grecians', 'short' => 'EXE', 'color' => '#E81734'],
    'Huddersfield Town' => ['name' => 'Huddersfield Town', 'common_name' => 'Huddersfield', 'nickname' => 'The Terriers', 'short' => 'HUD', 'color' => '#0072CE'],
    'Leyton Orient' => ['name' => 'Leyton Orient', 'common_name' => 'Orient', 'nickname' => "The O's", 'short' => 'LEY', 'color' => '#E81734'],
    'Lincoln City' => ['name' => 'Lincoln City', 'common_name' => 'Lincoln', 'nickname' => 'The Imps', 'short' => 'LIN', 'color' => '#D11241'],
    'Mansfield Town' => ['name' => 'Mansfield Town', 'common_name' => 'Mansfield', 'nickname' => 'The Stags', 'short' => 'MAN', 'color' => '#FDBE11'],
    'Northampton Town' => ['name' => 'Northampton Town', 'common_name' => 'Northampton', 'nickname' => 'The Cobblers', 'short' => 'NOR', 'color' => '#7B1639'],
    'Peterborough United' => ['name' => 'Peterborough United', 'common_name' => 'Peterborough', 'nickname' => 'The Posh', 'short' => 'PET', 'color' => '#0054A6'],
    'Reading' => ['name' => 'Reading', 'common_name' => 'Reading', 'nickname' => 'The Royals', 'short' => 'REA', 'color' => '#004494'],
    'Rotherham United' => ['name' => 'Rotherham United', 'common_name' => 'Rotherham', 'nickname' => 'The Millers', 'short' => 'ROT', 'color' => '#D00027'],
    'Shrewsbury Town' => ['name' => 'Shrewsbury Town', 'common_name' => 'Shrewsbury', 'nickname' => 'The Shrews', 'short' => 'SHR', 'color' => '#FFC20E'],
    'Stevenage' => ['name' => 'Stevenage', 'common_name' => 'Stevenage', 'nickname' => 'The Boro', 'short' => 'STE', 'color' => '#E30613'],
    'Stevenage FC' => ['name' => 'Stevenage', 'common_name' => 'Stevenage', 'nickname' => 'The Boro', 'short' => 'STE', 'color' => '#E30613'],
    'Stockport County' => ['name' => 'Stockport County', 'common_name' => 'Stockport', 'nickname' => 'The Hatters', 'short' => 'STP', 'color' => '#004A99'],
    'Wigan Athletic' => ['name' => 'Wigan Athletic', 'common_name' => 'Wigan', 'nickname' => 'The Latics', 'short' => 'WIG', 'color' => '#0033A0'],
    'Wycombe Wanderers' => ['name' => 'Wycombe Wanderers', 'common_name' => 'Wycombe', 'nickname' => 'The Chairboys', 'short' => 'WYC', 'color' => '#002D56'],
];

// ---------------------------------------------------------------------------
// League Two (L2)
// ---------------------------------------------------------------------------
$team_info_L2 = [
    'Accrington Stanley' => ['name' => 'Accrington Stanley', 'common_name' => 'Accrington', 'nickname' => 'Stanley', 'short' => 'ACC', 'color' => '#D11241'],
    'AFC Wimbledon' => ['name' => 'AFC Wimbledon', 'common_name' => 'Wimbledon', 'nickname' => 'The Dons', 'short' => 'WIM', 'color' => '#213D8A'],
    'Barnet' => ['name' => 'Barnet', 'common_name' => 'Barnet', 'nickname' => 'The Bees', 'short' => 'BNT', 'color' => '#000000'],
    'Barrow' => ['name' => 'Barrow', 'common_name' => 'Barrow', 'nickname' => 'The Bluebirds', 'short' => 'BRW', 'color' => '#005DAA'],
    'Bradford City' => ['name' => 'Bradford City', 'common_name' => 'Bradford', 'nickname' => 'The Bantams', 'short' => 'BRA', 'color' => '#FFB81C'],
    'Bristol Rovers' => ['name' => 'Bristol Rovers', 'common_name' => 'Bristol Rovers', 'nickname' => 'The Gas', 'short' => 'BRS', 'color' => '#003399'],
    'Bromley' => ['name' => 'Bromley', 'common_name' => 'Bromley', 'nickname' => 'The Lilywhites', 'short' => 'BRO', 'color' => '#FFB81C'],
    'Carlisle United' => ['name' => 'Carlisle United', 'common_name' => 'Carlisle', 'nickname' => 'The Cumbrians', 'short' => 'CAR', 'color' => '#004A99'],
    'Cambridge United' => ['name' => 'Cambridge United', 'common_name' => 'Cambridge', 'nickname' => "The U's", 'short' => 'CAM', 'color' => '#FFD200'],
    'Cheltenham Town' => ['name' => 'Cheltenham Town', 'common_name' => 'Cheltenham', 'nickname' => 'The Robins', 'short' => 'CHT', 'color' => '#E30613'],
    'Chesterfield' => ['name' => 'Chesterfield', 'common_name' => 'Chesterfield', 'nickname' => 'The Spireites', 'short' => 'CHS', 'color' => '#0054A6'],
    'Colchester United' => ['name' => 'Colchester United', 'common_name' => 'Colchester', 'nickname' => "The U's", 'short' => 'COL', 'color' => '#0000FF'],
    'Crawley Town' => ['name' => 'Crawley Town', 'common_name' => 'Crawley', 'nickname' => 'The Red Devils', 'short' => 'CRA', 'color' => '#E30613'],
    'Crewe Alexandra' => ['name' => 'Crewe Alexandra', 'common_name' => 'Crewe', 'nickname' => 'The Railwaymen', 'short' => 'CRE', 'color' => '#D00027'],
    'Doncaster Rovers' => ['name' => 'Doncaster Rovers', 'common_name' => 'Doncaster', 'nickname' => 'Donny', 'short' => 'DON', 'color' => '#E30613'],
    'Fleetwood Town' => ['name' => 'Fleetwood Town', 'common_name' => 'Fleetwood', 'nickname' => 'The Cod Army', 'short' => 'FLE', 'color' => '#E30613'],
    'Gillingham' => ['name' => 'Gillingham', 'common_name' => 'Gillingham', 'nickname' => 'The Gills', 'short' => 'GIL', 'color' => '#0000FF'],
    'Grimsby Town' => ['name' => 'Grimsby Town', 'common_name' => 'Grimsby', 'nickname' => 'The Mariners', 'short' => 'GRI', 'color' => '#000000'],
    'Harrogate Town' => ['name' => 'Harrogate Town', 'common_name' => 'Harrogate', 'nickname' => 'The Town', 'short' => 'HAR', 'color' => '#FFD200'],
    'Milton Keynes Dons' => ['name' => 'Milton Keynes Dons', 'common_name' => 'MK Dons', 'nickname' => 'The Dons', 'short' => 'MKD', 'color' => '#FFFFFF'],
    'MK Dons' => ['name' => 'Milton Keynes Dons', 'common_name' => 'MK Dons', 'nickname' => 'The Dons', 'short' => 'MKD', 'color' => '#FFFFFF'],
    'Morecambe' => ['name' => 'Morecambe', 'common_name' => 'Morecambe', 'nickname' => 'The Shrimps', 'short' => 'MOR', 'color' => '#E30613'],
    'Newport County' => ['name' => 'Newport County', 'common_name' => 'Newport', 'nickname' => 'The Exiles', 'short' => 'NEW', 'color' => '#FFB81C'],
    'Newport County AFC' => ['name' => 'Newport County', 'common_name' => 'Newport', 'nickname' => 'The Exiles', 'short' => 'NEW', 'color' => '#FFB81C'],
    'Notts County' => ['name' => 'Notts County', 'common_name' => 'Notts Co', 'nickname' => 'The Magpies', 'short' => 'NTC', 'color' => '#000000'],
    'Oldham Athletic' => ['name' => 'Oldham Athletic', 'common_name' => 'Oldham', 'nickname' => 'The Latics', 'short' => 'OLD', 'color' => '#005DAA'],
    'Port Vale' => ['name' => 'Port Vale', 'common_name' => 'Port Vale', 'nickname' => 'The Valiants', 'short' => 'PVL', 'color' => '#FFFFFF'],
    'Salford City' => ['name' => 'Salford City', 'common_name' => 'Salford', 'nickname' => 'The Ammies', 'short' => 'SAL', 'color' => '#E30613'],
    'Shrewsbury Town' => ['name' => 'Shrewsbury Town', 'common_name' => 'Shrewsbury', 'nickname' => 'The Shrews', 'short' => 'SHR', 'color' => '#FFC20E'],
    'Swindon Town' => ['name' => 'Swindon Town', 'common_name' => 'Swindon', 'nickname' => 'The Robins', 'short' => 'SWI', 'color' => '#E30613'],
    'Tranmere Rovers' => ['name' => 'Tranmere Rovers', 'common_name' => 'Tranmere', 'nickname' => 'The Rovers', 'short' => 'TRA', 'color' => '#FFFFFF'],
    'Walsall' => ['name' => 'Walsall', 'common_name' => 'Walsall', 'nickname' => 'The Saddlers', 'short' => 'WAL', 'color' => '#E30613'],
];

// ---------------------------------------------------------------------------
// National League (NL)
// ---------------------------------------------------------------------------
$team_info_NL = [
    'Aldershot Town' => ['name' => 'Aldershot Town', 'common_name' => 'Aldershot', 'nickname' => 'The Shots', 'short' => 'ALD', 'color' => '#E30613'],
    'Altrincham' => ['name' => 'Altrincham', 'common_name' => 'Alty', 'nickname' => 'The Robins', 'short' => 'ALT', 'color' => '#E30613'],
    'Barnet' => ['name' => 'Barnet', 'common_name' => 'Barnet', 'nickname' => 'The Bees', 'short' => 'BRN', 'color' => '#FDBE11'],
    'Barnet FC' => ['name' => 'Barnet', 'common_name' => 'Barnet', 'nickname' => 'The Bees', 'short' => 'BRN', 'color' => '#FDBE11'],
    'Braintree Town' => ['name' => 'Braintree Town', 'common_name' => 'Braintree', 'nickname' => 'The Iron', 'short' => 'BRA', 'color' => '#FDBE11'],
    'Dagenham & Redbridge' => ['name' => 'Dagenham & Redbridge', 'common_name' => 'Daggers', 'nickname' => 'The Daggers', 'short' => 'DAG', 'color' => '#E30613'],
    'Dagenham and Redbridge' => ['name' => 'Dagenham & Redbridge', 'common_name' => 'Daggers', 'nickname' => 'The Daggers', 'short' => 'DAG', 'color' => '#E30613'],
    'Eastleigh' => ['name' => 'Eastleigh', 'common_name' => 'Eastleigh', 'nickname' => 'The Spitfires', 'short' => 'EAS', 'color' => '#005DAA'],
    'Ebbsfleet United' => ['name' => 'Ebbsfleet United', 'common_name' => 'Ebbsfleet', 'nickname' => 'The Fleet', 'short' => 'EBB', 'color' => '#E30613'],
    'FC Halifax Town' => ['name' => 'FC Halifax Town', 'common_name' => 'Halifax', 'nickname' => 'The Shaymen', 'short' => 'HAL', 'color' => '#005DAA'],
    'Halifax Town' => ['name' => 'FC Halifax Town', 'common_name' => 'Halifax', 'nickname' => 'The Shaymen', 'short' => 'HAL', 'color' => '#005DAA'],
    'Forest Green Rovers' => ['name' => 'Forest Green Rovers', 'common_name' => 'Forest Green', 'nickname' => 'The Green', 'short' => 'FGR', 'color' => '#00FF00'],
    'Gateshead' => ['name' => 'Gateshead', 'common_name' => 'Gateshead', 'nickname' => 'The Heed', 'short' => 'GAT', 'color' => '#FFFFFF'],
    'Gateshead FC' => ['name' => 'Gateshead', 'common_name' => 'Gateshead', 'nickname' => 'The Heed', 'short' => 'GAT', 'color' => '#FFFFFF'],
    'Hartlepool United' => ['name' => 'Hartlepool United', 'common_name' => 'Hartlepool', 'nickname' => 'The Monkey Hangers', 'short' => 'HAR', 'color' => '#005DAA'],
    'Maidenhead United' => ['name' => 'Maidenhead United', 'common_name' => 'Maidenhead', 'nickname' => 'The Magpies', 'short' => 'MAI', 'color' => '#000000'],
    'Oldham Athletic' => ['name' => 'Oldham Athletic', 'common_name' => 'Oldham', 'nickname' => 'The Latics', 'short' => 'OLD', 'color' => '#005DAA'],
    'Rochdale' => ['name' => 'Rochdale', 'common_name' => 'Rochdale', 'nickname' => 'The Dale', 'short' => 'ROC', 'color' => '#005DAA'],
    'Rochdale AFC' => ['name' => 'Rochdale', 'common_name' => 'Rochdale', 'nickname' => 'The Dale', 'short' => 'ROC', 'color' => '#005DAA'],
    'Scunthorpe United' => ['name' => 'Scunthorpe United', 'common_name' => 'Scunthorpe', 'nickname' => 'The Iron', 'short' => 'SCU', 'color' => '#FFD200'],
    'Solihull Moors' => ['name' => 'Solihull Moors', 'common_name' => 'Solihull', 'nickname' => 'The Moors', 'short' => 'SOL', 'color' => '#FFD200'],
    'Southend United' => ['name' => 'Southend United', 'common_name' => 'Southend', 'nickname' => 'The Shrimpers', 'short' => 'SOU', 'color' => '#002D56'],
    'Sutton United' => ['name' => 'Sutton United', 'common_name' => 'Sutton', 'nickname' => 'The Yellows', 'short' => 'SUT', 'color' => '#FFD200'],
    'Tamworth' => ['name' => 'Tamworth', 'common_name' => 'Tamworth', 'nickname' => 'The Lambs', 'short' => 'TAM', 'color' => '#E30613'],
    'Tamworth FC' => ['name' => 'Tamworth', 'common_name' => 'Tamworth', 'nickname' => 'The Lambs', 'short' => 'TAM', 'color' => '#E30613'],
    'Wealdstone' => ['name' => 'Wealdstone', 'common_name' => 'Wealdstone', 'nickname' => 'The Stones', 'short' => 'WEA', 'color' => '#0000FF'],
    'Woking' => ['name' => 'Woking', 'common_name' => 'Woking', 'nickname' => 'The Cards', 'short' => 'WOK', 'color' => '#E30613'],
    'Woking FC' => ['name' => 'Woking', 'common_name' => 'Woking', 'nickname' => 'The Cards', 'short' => 'WOK', 'color' => '#E30613'],
    'Yeovil Town' => ['name' => 'Yeovil Town', 'common_name' => 'Yeovil', 'nickname' => 'The Glovers', 'short' => 'YEO', 'color' => '#008000'],
    'York City' => ['name' => 'York City', 'common_name' => 'York', 'nickname' => 'The Minstermen', 'short' => 'YOR', 'color' => '#E30613'],
];

// ---------------------------------------------------------------------------
// Shared helper
// ---------------------------------------------------------------------------
if (!function_exists('getTeamInfo')) {
    function getTeamInfo(string $team_name, array $team_info): array {
        if (isset($team_info[$team_name])) {
            return $team_info[$team_name];
        }
        foreach ($team_info as $key => $info) {
            if (stripos($team_name, $key) !== false || stripos($key, $team_name) !== false) {
                return $info;
            }
        }
        return [
            'name'        => $team_name,
            'common_name' => $team_name,
            'nickname'    => 'Unknown',
            'short'       => strtoupper(substr($team_name, 0, 3)),
            'color'       => '#888888',
        ];
    }
}
