-- Created automatically by the Nations League sync in fetch-worldfootball.php.
CREATE TABLE IF NOT EXISTS nl_standings (
    tournament TEXT NOT NULL,
    league TEXT NOT NULL,
    group_name TEXT NOT NULL,
    position INTEGER NOT NULL,
    team_name TEXT NOT NULL,
    played INTEGER NOT NULL DEFAULT 0,
    won INTEGER NOT NULL DEFAULT 0,
    drawn INTEGER NOT NULL DEFAULT 0,
    lost INTEGER NOT NULL DEFAULT 0,
    gf INTEGER NOT NULL DEFAULT 0,
    ga INTEGER NOT NULL DEFAULT 0,
    gd INTEGER NOT NULL DEFAULT 0,
    points INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (tournament, league, group_name, team_name)
);
CREATE TABLE IF NOT EXISTS nl_matches (
    id TEXT PRIMARY KEY,
    tournament TEXT NOT NULL,
    league TEXT NOT NULL,
    stage TEXT NOT NULL CHECK (stage IN ('groups', 'finals', 'playoffs')),
    round_name TEXT NOT NULL,
    match_date TEXT,
    home_team TEXT NOT NULL,
    away_team TEXT NOT NULL,
    home_score INTEGER,
    away_score INTEGER,
    status TEXT NOT NULL DEFAULT 'Scheduled',
    home_pens INTEGER,
    away_pens INTEGER
);
CREATE INDEX IF NOT EXISTS nl_matches_edition ON nl_matches(tournament, league, stage, match_date);

CREATE TABLE IF NOT EXISTS nl_match_leagues (
    match_id TEXT NOT NULL,
    league TEXT NOT NULL,
    PRIMARY KEY (match_id, league)
);
CREATE TABLE IF NOT EXISTS nl_sync_status (
    tournament TEXT PRIMARY KEY,
    synced_at TEXT NOT NULL,
    match_count INTEGER NOT NULL,
    skipped_count INTEGER NOT NULL DEFAULT 0
);
