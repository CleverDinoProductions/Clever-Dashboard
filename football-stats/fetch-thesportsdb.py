import requests
import sqlite3
import time

# Configuration
API_KEY = "876419"  # Standard API key for TheSportsDB
DB_PATH = "football-stats.sqlite3"

# Mapping TheSportsDB League IDs
LEAGUES = {
    '4328': 'Premier League',
    '4329': 'Championship'
}

SEASON = "2025-2026"

def update_league_table(league_id, league_name):
    print(f"Fetching data for {league_name} ({league_id})...")
    
    # TheSportsDB lookup table endpoint
    url = f"https://www.thesportsdb.com/api/v1/json/{API_KEY}/lookuptable.php?l={league_id}&s={SEASON}"
    
    try:
        response = requests.get(url)
        data = response.json()
        
        if not data or 'table' not in data:
            print(f"No data found for {league_name}")
            return

        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        
        # Use the specific table name format used in your PHP files (e.g., league_table_PL)
        table_name = "league_table_PL" if league_id == '4328' else "league_table"
        
        timestamp = int(time.time() * 1000)
        
        for row in data['table']:
            team_data = (
                row['strTeam'],         # team_name
                int(row['intRank']),    # position
                int(row['intPlayed']),  # played
                int(row['intWin']),     # won
                int(row['intDraw']),    # drawn
                int(row['intLoss']),    # lost
                int(row['intGoalsFor']),# gf
                int(row['intGoalsAgainst']), # ga
                int(row['intGoalDifference']), # gd
                int(row['intPoints']),  # points
                timestamp               # updated_at
            )
            
            # Upsert logic for SQLite
            cursor.execute(f"""
                INSERT INTO {table_name} 
                (team_name, position, played, won, drawn, lost, gf, ga, gd, points, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(team_name) DO UPDATE SET
                    position=excluded.position,
                    played=excluded.played,
                    won=excluded.won,
                    drawn=excluded.drawn,
                    lost=excluded.lost,
                    gf=excluded.gf,
                    ga=excluded.ga,
                    gd=excluded.gd,
                    points=excluded.points,
                    updated_at=excluded.updated_at
            """, team_data)

        conn.commit()
        conn.close()
        print(f"Successfully updated {league_name}")

    except Exception as e:
        print(f"Error updating {league_name}: {e}")

if __name__ == "__main__":
    for lid, lname in LEAGUES.items():
        update_league_table(lid, lname)