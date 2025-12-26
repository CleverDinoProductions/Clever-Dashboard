import requests
import sqlite3
import time

print("Fetching Premier League & World Cup data...")

# API Configuration
API_KEY = '2467baa7f4d747f5b7d2d99498a70172'
headers_api = {'X-Auth-Token': API_KEY}

# Database connection
conn = sqlite3.connect('football-stats.sqlite3')
cursor = conn.cursor()

# Create tables if they don't exist
cursor.execute("""
    CREATE TABLE IF NOT EXISTS league_table (
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

conn.commit()

# ============================================
# FETCH PREMIER LEAGUE DATA
# ============================================
print("\n[1/2] Fetching Premier League...")
url_pl = "https://api.football-data.org/v4/competitions/PL/standings"

response = requests.get(url_pl, headers=headers_api)

if response.status_code == 200:
    data = response.json()
    standings = data['standings'][0]['table']
    
    teams_data = []
    for team in standings:
        teams_data.append((
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
    
    # Update database
    cursor.execute("DELETE FROM league_table")
    
    timestamp = int(time.time() * 1000)
    for team in teams_data:
        cursor.execute("""
            INSERT INTO league_table VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (*team, timestamp))
    
    conn.commit()
    
    print(f"  ✓ Successfully fetched {len(teams_data)} Premier League teams")
    print(f"  ✓ Updated at: {time.strftime('%Y-%m-%d %H:%M:%S')}")
else:
    print(f"  ✗ Failed: {response.status_code}")

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
    
    timestamp = int(time.time() * 1000)
    
    # Process group standings (12 groups of 4 teams)
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
    
    conn.commit()
    print(f"  ✓ Successfully fetched World Cup group data")
    
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

conn.close()

print("\n" + "="*50)
print("✓ Data fetch complete!")
print("="*50)
