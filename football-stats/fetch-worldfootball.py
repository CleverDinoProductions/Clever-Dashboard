import requests
import sqlite3
import time

print("Fetching Premier League & World Cup data...")

# API Configuration
API_KEY = '2467baa7f4d747f5b7d2d99498a70172'
headers_api = {'X-Auth-Token': API_KEY}

# Database connection
conn = sqlite3.connect('football-stats.sqlite3')
conn.row_factory = sqlite3.Row
cursor = conn.cursor()


def build_season_label(season_data):
    start_date = (season_data or {}).get('startDate')
    end_date = (season_data or {}).get('endDate')

    if start_date and end_date:
        return f"{start_date[:4]}-{end_date[:4]}"

    if start_date:
        start_year = int(start_date[:4])
        return f"{start_year}-{start_year + 1}"

    current_year = time.gmtime().tm_year
    return f"{current_year}-{current_year + 1}"


def get_live_table_metadata(db_cursor, competition_code):
    db_cursor.execute(
        """
        SELECT competition_code, live_table_name, season_label, matchweek, updated_at
        FROM live_table_metadata
        WHERE competition_code = ?
        """,
        (competition_code,)
    )
    return db_cursor.fetchone()


def get_live_table_rows(db_cursor, live_table_name):
    db_cursor.execute(f"SELECT * FROM {live_table_name} ORDER BY position ASC")
    return db_cursor.fetchall()


def archive_live_table_if_needed(db_cursor, competition_code, live_table_name, incoming_season_label, incoming_matchweek, archived_at):
    metadata = get_live_table_metadata(db_cursor, competition_code)
    live_rows = get_live_table_rows(db_cursor, live_table_name)

    if not live_rows:
        return

    existing_matchweek = metadata['matchweek'] if metadata else max(row['played'] for row in live_rows)
    existing_season_label = metadata['season_label'] if metadata else incoming_season_label

    if existing_matchweek is None:
        return

    if metadata and metadata['season_label'] == incoming_season_label and metadata['matchweek'] == incoming_matchweek:
        print(f"  · {competition_code}: live table already represents matchweek {incoming_matchweek}; snapshot unchanged")
        return

    db_cursor.execute(
        """
        SELECT COUNT(*)
        FROM league_table_snapshots
        WHERE competition_code = ? AND season_label = ? AND matchweek = ?
        """,
        (competition_code, existing_season_label, existing_matchweek)
    )
    existing_snapshot_count = db_cursor.fetchone()[0]

    if existing_snapshot_count:
        print(f"  · {competition_code}: snapshot for {existing_season_label} MW{existing_matchweek} already stored")
        return

    snapshot_rows = [
        (
            competition_code,
            existing_season_label,
            existing_matchweek,
            row['team_crest'],
            row['team_name'],
            row['position'],
            row['played'],
            row['won'],
            row['drawn'],
            row['lost'],
            row['gf'],
            row['ga'],
            row['gd'],
            row['points'],
            row['updated_at'],
            archived_at
        )
        for row in live_rows
    ]

    db_cursor.executemany(
        """
        INSERT OR REPLACE INTO league_table_snapshots (
            competition_code,
            season_label,
            matchweek,
            team_crest,
            team_name,
            position,
            played,
            won,
            drawn,
            lost,
            gf,
            ga,
            gd,
            points,
            source_updated_at,
            archived_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """,
        snapshot_rows
    )

    print(f"  · {competition_code}: archived {len(snapshot_rows)} rows for {existing_season_label} matchweek {existing_matchweek}")


def replace_live_table(db_cursor, live_table_name, teams_data, updated_at):
    db_cursor.execute(f"DELETE FROM {live_table_name}")
    db_cursor.executemany(
        f"INSERT INTO {live_table_name} VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [(*team, updated_at) for team in teams_data]
    )


def update_live_table_metadata(db_cursor, competition_code, live_table_name, season_label, matchweek, updated_at):
    db_cursor.execute(
        """
        INSERT INTO live_table_metadata (
            competition_code,
            live_table_name,
            season_label,
            matchweek,
            updated_at
        ) VALUES (?, ?, ?, ?, ?)
        ON CONFLICT(competition_code) DO UPDATE SET
            live_table_name = excluded.live_table_name,
            season_label = excluded.season_label,
            matchweek = excluded.matchweek,
            updated_at = excluded.updated_at
        """,
        (competition_code, live_table_name, season_label, matchweek, updated_at)
    )

# Create tables if they don't exist
# League tables for Premier League and Championship
cursor.execute("""
    CREATE TABLE IF NOT EXISTS league_table_PL (
        team_crest TEXT,
        team_name TEXT,
        position INTEGER,
        played INTEGER,
        won INTEGER,
        drawn INTEGER,
        lost INTEGER,
        gf INTEGER,
        ga INTEGER,
        gd INTEGER,
        points INTEGER,
        updated_at INTEGER
    )
""")

cursor.execute("""
    CREATE TABLE IF NOT EXISTS league_table_ELC (
        team_crest TEXT,
        team_name TEXT,
        position INTEGER,
        played INTEGER,
        won INTEGER,
        drawn INTEGER,
        lost INTEGER,
        gf INTEGER,
        ga INTEGER,
        gd INTEGER,
        points INTEGER,
        updated_at INTEGER
    )
""")

cursor.execute("""
    CREATE TABLE IF NOT EXISTS league_table_snapshots (
        competition_code TEXT,
        season_label TEXT,
        matchweek INTEGER,
        team_crest TEXT,
        team_name TEXT,
        position INTEGER,
        played INTEGER,
        won INTEGER,
        drawn INTEGER,
        lost INTEGER,
        gf INTEGER,
        ga INTEGER,
        gd INTEGER,
        points INTEGER,
        source_updated_at INTEGER,
        archived_at INTEGER,
        PRIMARY KEY (competition_code, season_label, matchweek, team_name)
    )
""")

cursor.execute("""
    CREATE TABLE IF NOT EXISTS live_table_metadata (
        competition_code TEXT PRIMARY KEY,
        live_table_name TEXT NOT NULL,
        season_label TEXT NOT NULL,
        matchweek INTEGER NOT NULL,
        updated_at INTEGER NOT NULL
    )
""")

cursor.execute("""
    CREATE INDEX IF NOT EXISTS idx_league_table_snapshots_lookup
    ON league_table_snapshots (competition_code, season_label, matchweek, position)
""")


cursor.execute("""
    CREATE TABLE IF NOT EXISTS wc_groups (
        group_name TEXT,
        team_name TEXT,
        position INTEGER,
        played INTEGER,
        won INTEGER,
        drawn INTEGER,
        lost INTEGER,
        gf INTEGER,
        ga INTEGER,
        gd INTEGER,
        points INTEGER,
        updated_at INTEGER
    )
""")

cursor.execute("""
    CREATE TABLE IF NOT EXISTS wc_knockout (
        stage TEXT,
        match_number INTEGER,
        match_date TEXT,
        home_team TEXT,
        away_team TEXT,
        home_score INTEGER,
        away_score INTEGER,
        status TEXT,
        venue TEXT,
        updated_at INTEGER
    )
""")

cursor.execute("""
    CREATE TABLE IF NOT EXISTS wc_third_place (
        group_name TEXT,
        team_name TEXT,
        rank INTEGER,
        played INTEGER,
        won INTEGER,
        drawn INTEGER,
        lost INTEGER,
        gf INTEGER,
        ga INTEGER,
        gd INTEGER,
        points INTEGER,
        qualified INTEGER,
        updated_at INTEGER
    )
""")

cursor.execute("""
    CREATE TABLE IF NOT EXISTS wc_standings (
        team_name TEXT,
        stage TEXT,
        rank INTEGER,
        played INTEGER,
        won INTEGER,
        drawn INTEGER,
        lost INTEGER,
        gf INTEGER,
        ga INTEGER,
        gd INTEGER,
        points INTEGER,
        updated_at INTEGER
    )
""")

conn.commit()

# ============================================
# FETCH PREMIER LEAGUE DATA
# ============================================

print("\n[1/2] Fetching Premier League...")
url_pl = "https://api.football-data.org/v4/competitions/PL/standings"
url_pl_matches = "https://api.football-data.org/v4/competitions/PL/matches"

# Fetch standings (for league table)
response = requests.get(url_pl, headers=headers_api)
if response.status_code == 200:
    data = response.json()
    standings = data['standings'][0]['table']
    season_label = build_season_label(data.get('season'))
    matchweek = data.get('season', {}).get('currentMatchday') or max(team['playedGames'] for team in standings)

    teams_data = []
    for team in standings:
        teams_data.append((
            team['team']['crest'],
            team['team']['name'],
            team['position'],
            team['playedGames'],
            team['won'],
            team['draw'],
            team['lost'],
            team['goalsFor'],
            team['goalsAgainst'],
            team['goalDifference'],
            team['points']
        ))

    timestamp = int(time.time() * 1000)
    archive_live_table_if_needed(cursor, 'PL', 'league_table_PL', season_label, matchweek, timestamp)
    replace_live_table(cursor, 'league_table_PL', teams_data, timestamp)
    update_live_table_metadata(cursor, 'PL', 'league_table_PL', season_label, matchweek, timestamp)
    conn.commit()
    print(f"  ✓ Successfully fetched {len(teams_data)} Premier League teams")
    print(f"  ✓ Season: {season_label} • Matchweek: {matchweek}")
    print(f"  ✓ Updated at: {time.strftime('%Y-%m-%d %H:%M:%S')}")
else:
    print(f"  ✗ Failed: {response.status_code}")

# Fetch matches (for matches tab)
print("  • Fetching Premier League matches from API...")
cursor.execute('''CREATE TABLE IF NOT EXISTS matches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    competition_code TEXT,
    season_label TEXT,
    matchweek INTEGER,
    match_date TEXT,
    home_team TEXT,
    away_team TEXT,
    home_goals INTEGER,
    away_goals INTEGER,
    ht_home_goals INTEGER,
    ht_away_goals INTEGER,
    source TEXT
)''')
response_matches = requests.get(url_pl_matches, headers=headers_api)
if response_matches.status_code == 200:
    matches_data = response_matches.json()
    # Remove old API matches for this season
    cursor.execute("DELETE FROM matches WHERE competition_code = ? AND season_label = ? AND source = 'api'", ('PL', season_label))
    for m in matches_data.get('matches', []):
        # Only insert matches with both teams known
        if not m.get('homeTeam') or not m.get('awayTeam'):
            continue
        home = m['homeTeam']['name']
        away = m['awayTeam']['name']
        matchweek = m.get('matchday') or m.get('matchWeek') or None
        match_date = m.get('utcDate', '')[:10]
        ft_home = m['score']['fullTime']['home'] if m['score']['fullTime']['home'] is not None else None
        ft_away = m['score']['fullTime']['away'] if m['score']['fullTime']['away'] is not None else None
        ht_home = m['score']['halfTime']['home'] if m['score']['halfTime']['home'] is not None else None
        ht_away = m['score']['halfTime']['away'] if m['score']['halfTime']['away'] is not None else None
        cursor.execute('''INSERT INTO matches (
            competition_code, season_label, matchweek, match_date, home_team, away_team, home_goals, away_goals, ht_home_goals, ht_away_goals, source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)''', (
            'PL', season_label, matchweek, match_date, home, away, ft_home, ft_away, ht_home, ht_away, 'api'
        ))
    conn.commit()
    print(f"  ✓ Inserted {len(matches_data.get('matches', []))} Premier League matches from API.")
else:
    print(f"  ✗ Failed to fetch matches: {response_matches.status_code}")

# --- Recalculate league_table_snapshots from matches table ---
def recalculate_snapshots_from_matches(db_conn, competition_code, season_label):
    # Check if snapshots already exist for all matchweeks in this season
    c = db_conn.cursor()
    c.execute("SELECT DISTINCT matchweek FROM matches WHERE competition_code = ? AND season_label = ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL", (competition_code, season_label))
    matchweeks = sorted([row[0] for row in c.fetchall()])
    if not matchweeks:
        print(f"  • No matches found for {competition_code} {season_label}, skipping snapshot recalculation.")
        return
    c.execute("SELECT DISTINCT matchweek FROM league_table_snapshots WHERE competition_code = ? AND season_label = ?", (competition_code, season_label))
    existing_snapshots = set(row[0] for row in c.fetchall())
    if set(matchweeks).issubset(existing_snapshots):
        print(f"  • Snapshots already exist for all matchweeks in {competition_code} {season_label}, skipping recalculation.")
        return
    print(f"  • Recalculating league_table_snapshots for {competition_code} {season_label} from matches table...")
    c = db_conn.cursor()

    c.execute("SELECT matchweek, home_team, away_team, home_goals, away_goals FROM matches WHERE competition_code = ? AND season_label = ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL ORDER BY matchweek, id", (competition_code, season_label))
    matches = c.fetchall()
    # Group matches by matchweek
    from collections import defaultdict
    matches_by_week = defaultdict(list)
    for match in matches:
        matches_by_week[match[0]].append(match)
    # Get all teams
    c.execute("SELECT DISTINCT home_team FROM matches WHERE competition_code = ? AND season_label = ? UNION SELECT DISTINCT away_team FROM matches WHERE competition_code = ? AND season_label = ?", (competition_code, season_label, competition_code, season_label))
    teams_list = [row[0] for row in c.fetchall()]
    def new_team_stats(name):
        return {
            'team_crest': '',
            'team_name': name,
            'position': 0,
            'played': 0,
            'won': 0,
            'drawn': 0,
            'lost': 0,
            'gf': 0,
            'ga': 0,
            'gd': 0,
            'points': 0,
            'updated_at': 0,
        }
    teams = {name: new_team_stats(name) for name in teams_list}
    updated_at = int(time.time() * 1000)
    def update_standings(teams, match):
        _, home, away, home_goals, away_goals = match
        teams[home]['played'] += 1
        teams[away]['played'] += 1
        teams[home]['gf'] += home_goals
        teams[home]['ga'] += away_goals
        teams[away]['gf'] += away_goals
        teams[away]['ga'] += home_goals
        teams[home]['gd'] = teams[home]['gf'] - teams[home]['ga']
        teams[away]['gd'] = teams[away]['gf'] - teams[away]['ga']
        if home_goals > away_goals:
            teams[home]['won'] += 1
            teams[away]['lost'] += 1
            teams[home]['points'] += 3
        elif home_goals < away_goals:
            teams[away]['won'] += 1
            teams[home]['lost'] += 1
            teams[away]['points'] += 3
        else:
            teams[home]['drawn'] += 1
            teams[away]['drawn'] += 1
            teams[home]['points'] += 1
            teams[away]['points'] += 1
    def standings_snapshot(teams):
        sorted_teams = sorted(teams.values(), key=lambda t: (-t['points'], -t['gd'], -t['gf'], t['team_name']))
        for pos, team in enumerate(sorted_teams, 1):
            team['position'] = pos
        return sorted_teams
    def insert_snapshot(conn, matchweek, teams, updated_at):
        c2 = conn.cursor()
        for team in standings_snapshot(teams):
            c2.execute('''
                INSERT OR REPLACE INTO league_table_snapshots (
                    competition_code, season_label, matchweek, team_crest, team_name, position, played, won, drawn, lost, gf, ga, gd, points, source_updated_at, archived_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ''', (
                competition_code,
                season_label,
                matchweek,
                team['team_crest'],
                team['team_name'],
                team['position'],
                team['played'],
                team['won'],
                team['drawn'],
                team['lost'],
                team['gf'],
                team['ga'],
                team['gd'],
                team['points'],
                updated_at,
                updated_at
            ))
        conn.commit()

    # Remove old snapshots for this comp/season
    c.execute("DELETE FROM league_table_snapshots WHERE competition_code = ? AND season_label = ?", (competition_code, season_label))
    # Rebuild snapshots by processing all matches in each matchweek together
    for mw in sorted(matches_by_week.keys()):
        for match in matches_by_week[mw]:
            update_standings(teams, match)
        insert_snapshot(db_conn, mw, teams, updated_at)
        print(f"  · Snapshots recalculated for matchweek {mw}...")
    print(f"  ✓ Snapshots recalculated from matches.")

# Call after matches are fetched
recalculate_snapshots_from_matches(conn, 'PL', season_label)

# ============================================
# FETCH Championship DATA (standings and matches)
# ============================================
print("\n[1.5/2] Fetching Championship...")
url_champ = "https://api.football-data.org/v4/competitions/ELC/standings"
url_champ_matches = "https://api.football-data.org/v4/competitions/ELC/matches"

# Fetch standings (for league table)
response_champ = requests.get(url_champ, headers=headers_api)
if response_champ.status_code == 200:
    data_champ = response_champ.json()
    standings_champ = data_champ['standings'][0]['table']
    season_label_champ = build_season_label(data_champ.get('season'))
    matchweek_champ = data_champ.get('season', {}).get('currentMatchday') or max(team['playedGames'] for team in standings_champ)

    teams_data_champ = []
    for team in standings_champ:
        teams_data_champ.append((
            team['team']['crest'],
            team['team']['name'],
            team['position'],
            team['playedGames'],
            team['won'],
            team['draw'],
            team['lost'],
            team['goalsFor'],
            team['goalsAgainst'],
            team['goalDifference'],
            team['points']
        ))

    timestamp = int(time.time() * 1000)
    archive_live_table_if_needed(cursor, 'ELC', 'league_table_ELC', season_label_champ, matchweek_champ, timestamp)
    replace_live_table(cursor, 'league_table_ELC', teams_data_champ, timestamp)
    update_live_table_metadata(cursor, 'ELC', 'league_table_ELC', season_label_champ, matchweek_champ, timestamp)
    conn.commit()
    print(f"  ✓ Successfully fetched {len(teams_data_champ)} Championship teams")
    print(f"  ✓ Season: {season_label_champ} • Matchweek: {matchweek_champ}")
    print(f"  ✓ Updated at: {time.strftime('%Y-%m-%d %H:%M:%S')}")

    # Fetch matches (for matches tab and snapshots)
    print("  • Fetching Championship matches from API...")
    response_matches_champ = requests.get(url_champ_matches, headers=headers_api)
    if response_matches_champ.status_code == 200:
        matches_data_champ = response_matches_champ.json()
        # Remove old API matches for this season
        cursor.execute("DELETE FROM matches WHERE competition_code = ? AND season_label = ? AND source = 'api'", ('ELC', season_label_champ))
        for m in matches_data_champ.get('matches', []):
            if not m.get('homeTeam') or not m.get('awayTeam'):
                continue
            home = m['homeTeam']['name']
            away = m['awayTeam']['name']
            matchweek = m.get('matchday') or m.get('matchWeek') or None
            match_date = m.get('utcDate', '')[:10]
            ft_home = m['score']['fullTime']['home'] if m['score']['fullTime']['home'] is not None else None
            ft_away = m['score']['fullTime']['away'] if m['score']['fullTime']['away'] is not None else None
            ht_home = m['score']['halfTime']['home'] if m['score']['halfTime']['home'] is not None else None
            ht_away = m['score']['halfTime']['away'] if m['score']['halfTime']['away'] is not None else None
            cursor.execute('''INSERT INTO matches (
                competition_code, season_label, matchweek, match_date, home_team, away_team, home_goals, away_goals, ht_home_goals, ht_away_goals, source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)''', (
                'ELC', season_label_champ, matchweek, match_date, home, away, ft_home, ft_away, ht_home, ht_away, 'api'
            ))
        conn.commit()
        print(f"  ✓ Inserted {len(matches_data_champ.get('matches', []))} Championship matches from API.")
        # Recalculate snapshots for Championship
        recalculate_snapshots_from_matches(conn, 'ELC', season_label_champ)
    else:
        print(f"  ✗ Failed to fetch Championship matches: {response_matches_champ.status_code}")

# ============================================
# FETCH WORLD CUP DATA (2026 FORMAT)
# ============================================
print("\n[2/2] Fetching World Cup...")
url_wc = "https://api.football-data.org/v4/competitions/WC/standings"

response_wc = requests.get(url_wc, headers=headers_api)

if response_wc.status_code == 200:
    data_wc = response_wc.json()
    
    # Clear existing World Cup data
    cursor.execute("DELETE FROM wc_groups")
    cursor.execute("DELETE FROM wc_third_place")
    cursor.execute("DELETE FROM wc_knockout")
    cursor.execute("DELETE FROM wc_standings")
    
    timestamp = int(time.time() * 1000)
    
    # Process group standings (12 groups of 4 teams)
    # Determine matchday and season_label for snapshotting
    matchday = data_wc.get('season', {}).get('currentMatchday')
    season_label = build_season_label(data_wc.get('season'))
    archived_at = timestamp
    group_snapshot_rows = []
    for standing in data_wc.get('standings', []):
        if standing['type'] == 'TOTAL':
            group_name = standing.get('group', 'Overall')
            for team in standing['table']:
                # Insert into groups table
                cursor.execute("""
                    INSERT INTO wc_groups VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """, (
                    group_name,
                    team['team']['name'],
                    team['position'],
                    team['playedGames'],
                    team['won'],
                    team['draw'],
                    team['lost'],
                    team['goalsFor'],
                    team['goalsAgainst'],
                    team['goalDifference'],
                    team['points'],
                    timestamp
                ))

                # Prepare for snapshot
                group_snapshot_rows.append((
                    group_name,
                    team['team']['name'],
                    team['position'],
                    team['playedGames'],
                    team['won'],
                    team['draw'],
                    team['lost'],
                    team['goalsFor'],
                    team['goalsAgainst'],
                    team['goalDifference'],
                    team['points'],
                    matchday,
                    season_label,
                    timestamp,
                    archived_at
                ))

                # Track third-place teams separately for Round of 32 qualification
                if team['position'] == 3:
                    cursor.execute("""
                        INSERT INTO wc_third_place VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    """, (
                        group_name,
                        team['team']['name'],
                        0,  # Rank will be calculated after all groups finish
                        team['playedGames'],
                        team['won'],
                        team['draw'],
                        team['lost'],
                        team['goalsFor'],
                        team['goalsAgainst'],
                        team['goalDifference'],
                        team['points'],
                        0,  # Qualified flag (1 if in top 8)
                        timestamp
                    ))

                # Insert into overall standings for knockout qualification tracking
                cursor.execute("""
                    INSERT INTO wc_standings VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """, (
                    team['team']['name'],
                    group_name,
                    team['position'],
                    team['playedGames'],
                    team['won'],
                    team['draw'],
                    team['lost'],
                    team['goalsFor'],
                    team['goalsAgainst'],
                    team['goalDifference'],
                    team['points'],
                    timestamp
                ))
    
    # Fetch knockout matches (Round of 32, Round of 16, QF, SF, Final)
    url_wc_matches = "https://api.football-data.org/v4/competitions/WC/matches"
    response_matches = requests.get(url_wc_matches, headers=headers_api)
    
    if response_matches.status_code == 200:
        matches_data = response_matches.json()
        cursor.execute("DELETE FROM wc_knockout")
        
        # Stage mapping for 2026 format
        stage_order = {
            'ROUND_OF_32': 1,
            'ROUND_OF_16': 2,
            'QUARTER_FINALS': 3,
            'SEMI_FINALS': 4,
            'THIRD_PLACE': 5,
            'FINAL': 6
        }
        
        for match in matches_data.get('matches', []):
            # Process knockout stage matches (excluding group stage)
            if match.get('stage') in stage_order:
                cursor.execute("""
                    INSERT INTO wc_knockout VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """, (
                    match.get('stage', 'Unknown'),
                    match.get('matchNumber', 0),
                    match.get('utcDate', '')[:10],  # Date only
                    match['homeTeam']['name'] if match['homeTeam'] else 'TBD',
                    match['awayTeam']['name'] if match['awayTeam'] else 'TBD',
                    match['score']['fullTime']['home'] if match['score']['fullTime']['home'] is not None else None,
                    match['score']['fullTime']['away'] if match['score']['fullTime']['away'] is not None else None,
                    match.get('status', 'SCHEDULED'),
                    match.get('venue', 'TBD'),
                    timestamp
                ))
        
        conn.commit()
        print(f"  ✓ Successfully fetched World Cup knockout matches (including Round of 32)")
    
    print(f"  ✓ Updated at: {time.strftime('%Y-%m-%d %H:%M:%S')}")
    
elif response_wc.status_code == 404:
    print(f"  ⚠ World Cup data not available (competition may not be active)")
    print(f"  ℹ World Cup 2026 starts June 11, 2026")
else:
    print(f"  ✗ Failed: {response_wc.status_code}")

print("\n[3/2] Calculating Best 3rd Place Teams...")

# 1. Fetch all 3rd place teams
cursor.execute("SELECT team_name, points, gd, gf FROM wc_third_place")
teams = cursor.fetchall()

# 2. Sort them: Points -> Goal Difference -> Goals For
# (Python's sort is stable, so we sort in reverse order of importance)
teams.sort(key=lambda x: x[3], reverse=True) # GF
teams.sort(key=lambda x: x[2], reverse=True) # GD
teams.sort(key=lambda x: x[1], reverse=True) # Points

# 3. Update the database with their rank and qualification status
# Top 8 qualify for Round of 32
for rank, team in enumerate(teams, 1):
    team_name = team[0]
    qualified = 1 if rank <= 8 else 0
    
    cursor.execute("""
        UPDATE wc_third_place 
        SET rank = ?, qualified = ? 
        WHERE team_name = ?
    """, (rank, qualified, team_name))
    
    status = "✅ Q" if qualified else "❌"
    print(f"   {rank}. {team_name} ({status})")

conn.commit()

conn.close()

print("\n" + "="*50)
print("✓ Data fetch complete!")
print("="*50)
