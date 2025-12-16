import requests
from bs4 import BeautifulSoup
import sqlite3
import time

print("Fetching Premier League data...")

session = requests.Session()

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    'Accept-Language': 'en-US,en;q=0.5',
    'Accept-Encoding': 'gzip, deflate, br',
    'Connection': 'keep-alive',
    'Upgrade-Insecure-Requests': '1',
    'Sec-Fetch-Dest': 'document',
    'Sec-Fetch-Mode': 'navigate',
    'Sec-Fetch-Site': 'none',
    'Cache-Control': 'max-age=0',
}

# Try football-data.org free API instead (100 requests/day)
url = "https://api.football-data.org/v4/competitions/PL/standings"
headers_api = {
    'X-Auth-Token': '2467baa7f4d747f5b7d2d99498a70172'  # Get from https://www.football-data.org/client/register
}

response = requests.get(url, headers=headers_api)

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
    
    # Save to database
    conn = sqlite3.connect('football-stats.sqlite3')
    cursor = conn.cursor()
    cursor.execute("DELETE FROM league_table")
    
    timestamp = int(time.time() * 1000)
    for team in teams_data:
        cursor.execute("""
            INSERT INTO league_table VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (*team, timestamp))
    
    conn.commit()
    conn.close()
    
    print(f"✓ Successfully fetched {len(teams_data)} teams")
    print(f"✓ Updated at: {time.strftime('%Y-%m-%d %H:%M:%S')}")
else:
    print(f"Failed: {response.status_code}")
    print("Get free API key from: https://www.football-data.org/client/register")
