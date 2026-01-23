import requests
import sqlite3
import time
from datetime import datetime


print("Fetching Premier League & World Cup data...")


# API Configuration
API_KEY = '2467baa7f4d747f5b7d2d99498a70172'
headers_api = {'X-Auth-Token': API_KEY}


# Database connection
conn = sqlite3.connect('football-stats.sqlite3')
cursor = conn.cursor()


# Create seasons tracking table
cursor.execute("""
    CREATE TABLE IF NOT EXISTS pl_seasons (
        season_id TEXT PRIMARY KEY,
        season_name TEXT,
        start_year INTEGER,
        end_year INTEGER,
        is_current INTEGER DEFAULT 0,
        first_updated INTEGER,
        last_updated INTEGER
    )
""")


# Create historical metadata table
cursor.execute("""
    CREATE TABLE IF NOT EXISTS pl_table_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        season_id TEXT,
        table_name TEXT,
        snapshot_date TEXT,
        matchweek INTEGER,
        updated_at INTEGER,
        FOREIGN KEY (season_id) REFERENCES pl_seasons(season_id)
    )
""")


# ============================================
# FETCH PREMIER LEAGUE DATA
# ============================================
print("\n[1/2] Fetching Premier League...")
url_pl = "https://api.football-data.org/v4/competitions/PL/standings"


response = requests.get(url_pl, headers=headers_api)


if response.status_code == 200:
    data = response.json()
    season_info = data['season']
    standings = data['standings'][0]['table']
    
    # Extract season information
    season_id = f"{season_info['startDate'][:4]}_{season_info['endDate'][:4]}"
    season_name = f"{season_info['startDate'][:4]}/{season_info['endDate'][2:4]}"
    start_year = int(season_info['startDate'][:4])
    end_year = int(season_info['endDate'][:4])
    current_matchweek = season_info.get('currentMatchday', 0)
    
    timestamp = int(time.time() * 1000)
    
    # Update or insert season record
    cursor.execute("""
        INSERT INTO pl_seasons (season_id, season_name, start_year, end_year, is_current, first_updated, last_updated)
        VALUES (?, ?, ?, ?, 1, ?, ?)
        ON CONFLICT(season_id) DO UPDATE SET
            last_updated = excluded.last_updated,
            is_current = 1
    """, (season_id, season_name, start_year, end_year, timestamp, timestamp))
    
    # Mark all other seasons as not current
    cursor.execute("""
        UPDATE pl_seasons SET is_current = 0 WHERE season_id != ?
    """, (season_id,))
    
    # Create season-specific table
    table_name = f"league_table_{season_id}"
    cursor.execute(f"""
        CREATE TABLE IF NOT EXISTS {table_name} (
            snapshot_id INTEGER,
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
            updated_at INTEGER,
            matchweek INTEGER
        )
    """)
    
    # Get next snapshot ID for this season
    cursor.execute(f"SELECT COALESCE(MAX(snapshot_id), 0) + 1 FROM {table_name}")
    snapshot_id = cursor.fetchone()[0]
    
    # Insert current standings with snapshot ID
    teams_data = []
    for team in standings:
        teams_data.append((
            snapshot_id,
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
            timestamp,
            current_matchweek
        ))
    
    for team in teams_data:
        cursor.execute(f"""
            INSERT INTO {table_name} VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, team)
    
    # Log this snapshot in history table
    cursor.execute("""
        INSERT INTO pl_table_history (season_id, table_name, snapshot_date, matchweek, updated_at)
        VALUES (?, ?, ?, ?, ?)
    """, (season_id, table_name, datetime.now().strftime('%Y-%m-%d'), current_matchweek, timestamp))
    
    conn.commit()
    
    print(f"  ✓ Season: {season_name} (Matchweek {current_matchweek})")
    print(f"  ✓ Snapshot #{snapshot_id} saved to table: {table_name}")
    print(f"  ✓ Successfully fetched {len(teams_data)} Premier League teams")
    print(f"  ✓ Updated at: {time.strftime('%Y-%m-%d %H:%M:%S')}")
else:
    print(f"  ✗ Failed: {response.status_code}")


# ============================================
# FETCH WORLD CUP DATA (unchanged)
# ============================================
# [World Cup code remains the same as your original]
print("\n[2/2] Fetching World Cup...")
url_wc = "https://api.football-data.org/v4/competitions/WC/standings"

response_wc = requests.get(url_wc, headers=headers_api)

if response_wc.status_code == 200:
    data_wc = response_wc.json()
    
    cursor.execute("DELETE FROM wc_groups")
    cursor.execute("DELETE FROM wc_third_place")
    
    timestamp = int(time.time() * 1000)
    
    for standing in data_wc.get('standings', []):
        if standing['type'] == 'TOTAL':
            group_name = standing.get('group', 'Overall')
            
            for team in standing['table']:
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
                
                if team['position'] == 3:
                    cursor.execute("""
                        INSERT INTO wc_third_place VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    """, (
                        group_name,
                        team['team']['name'],
                        0,
                        team['playedGames'],
                        team['won'],
                        team['draw'],
                        team['lost'],
                        team['goalsFor'],
                        team['goalsAgainst'],
                        team['goalDifference'],
                        team['points'],
                        0,
                        timestamp
                    ))
    
    conn.commit()
    print(f"  ✓ Successfully fetched World Cup group data")
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
