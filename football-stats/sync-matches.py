#!/usr/bin/env python3
"""Merge fixtures from multiple APIs and rebuild all affected table snapshots.

TheSportsDB remains useful for history, but it occasionally omits fixtures.  This
command complements it with football-data.org and API-Football. Credentials are
read from the environment; no keys are committed to the repository.
"""
from __future__ import annotations

import argparse
import json
import os
import sqlite3
import time
import urllib.parse
import urllib.request
from collections import defaultdict
from pathlib import Path

DB = Path(__file__).with_name("football-stats.sqlite3")
FOOTBALL_DATA_CODES = {"PL": "PL", "ELC": "ELC", "L1": "EL1", "L2": "EL2"}
API_FOOTBALL_IDS = {"PL": 39, "ELC": 40, "L1": 41, "L2": 42, "NL": 43}
SOURCE_PRIORITY = {"thesportsdb": 10, "api-football": 20, "football-data.org": 30}


def request_json(url: str, headers: dict[str, str]) -> dict:
    request = urllib.request.Request(url, headers={**headers, "User-Agent": "Clever-Dashboard/1.0"})
    with urllib.request.urlopen(request, timeout=30) as response:
        return json.load(response)


def football_data(competition: str, season: str, token: str) -> list[dict]:
    code = FOOTBALL_DATA_CODES.get(competition)
    if not code:
        return []
    year = season.split("-", 1)[0]
    data = request_json(
        f"https://api.football-data.org/v4/competitions/{code}/matches?season={year}",
        {"X-Auth-Token": token},
    )
    result = []
    for match in data.get("matches", []):
        score = match.get("score", {}).get("fullTime", {})
        result.append({
            "matchweek": match.get("matchday"), "timestamp": match.get("utcDate"),
            "home": match.get("homeTeam", {}).get("name"), "away": match.get("awayTeam", {}).get("name"),
            "home_goals": score.get("home"), "away_goals": score.get("away"),
            "status": "played" if match.get("status") == "FINISHED" else match.get("status", "scheduled").lower(),
            "source": "football-data.org",
        })
    return result


def api_football(competition: str, season: str, token: str) -> list[dict]:
    league_id = API_FOOTBALL_IDS.get(competition)
    if not league_id:
        return []
    query = urllib.parse.urlencode({"league": league_id, "season": season[:4]})
    data = request_json(f"https://v3.football.api-sports.io/fixtures?{query}", {"x-apisports-key": token})
    result = []
    for item in data.get("response", []):
        fixture, league = item.get("fixture", {}), item.get("league", {})
        goals, teams = item.get("goals", {}), item.get("teams", {})
        result.append({
            "matchweek": _round_number(league.get("round")), "timestamp": fixture.get("date"),
            "home": teams.get("home", {}).get("name"), "away": teams.get("away", {}).get("name"),
            "home_goals": goals.get("home"), "away_goals": goals.get("away"),
            "status": "played" if fixture.get("status", {}).get("short") in {"FT", "AET", "PEN"} else "scheduled",
            "source": "api-football",
        })
    return result


def _round_number(value) -> int | None:
    digits = "".join(character for character in str(value or "") if character.isdigit())
    return int(digits) if digits else None


def prepare_schema(db: sqlite3.Connection) -> None:
    columns = {row[1] for row in db.execute("PRAGMA table_info(matches)")}
    if not columns:
        db.execute("""CREATE TABLE matches (
            id INTEGER PRIMARY KEY AUTOINCREMENT, competition_code TEXT, competition_name TEXT,
            season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT,
            home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER,
            home_pens INTEGER, away_pens INTEGER, status TEXT, source TEXT)""")
        columns = {row[1] for row in db.execute("PRAGMA table_info(matches)")}
    for definition in ("match_timestamp TEXT", "status TEXT", "source TEXT", "competition_name TEXT"):
        if definition.split()[0] not in columns:
            db.execute(f"ALTER TABLE matches ADD COLUMN {definition}")
    # Old importers allowed the same fixture to be inserted repeatedly. Keep the
    # newest copy before enforcing the natural key used by all providers.
    db.execute("""DELETE FROM matches WHERE id NOT IN (
        SELECT MAX(id) FROM matches GROUP BY competition_code,season_label,home_team,away_team,match_date
    )""")
    db.execute("""CREATE UNIQUE INDEX IF NOT EXISTS idx_matches_natural_key
        ON matches (competition_code, season_label, home_team, away_team, match_date)""")


def merge_matches(db: sqlite3.Connection, competition: str, season: str, matches: list[dict]) -> int:
    changed = 0
    for match in matches:
        if not match.get("home") or not match.get("away") or not match.get("timestamp"):
            continue
        date = match["timestamp"][:10]
        existing = db.execute("""SELECT id, source FROM matches WHERE competition_code=? AND season_label=?
            AND home_team=? AND away_team=? AND match_date=?""", (competition, season, match["home"], match["away"], date)).fetchone()
        if existing and SOURCE_PRIORITY.get(existing[1], 0) > SOURCE_PRIORITY.get(match["source"], 0):
            continue
        values = (match.get("matchweek"), date, match["timestamp"], match["home"], match["away"],
                  match.get("home_goals"), match.get("away_goals"), match["status"], match["source"])
        if existing:
            db.execute("""UPDATE matches SET matchweek=?,match_date=?,match_timestamp=?,home_team=?,away_team=?,
                home_goals=?,away_goals=?,status=?,source=? WHERE id=?""", (*values, existing[0]))
        else:
            db.execute("""INSERT INTO matches (competition_code,season_label,matchweek,match_date,match_timestamp,
                home_team,away_team,home_goals,away_goals,status,source) VALUES (?,?,?,?,?,?,?,?,?,?,?)""",
                       (competition, season, *values))
        changed += 1
    return changed


def rebuild_snapshots(db: sqlite3.Connection, competition: str, season: str) -> None:
    """Deterministically reconstruct matchweek snapshots from the merged results."""
    rows = db.execute("""SELECT matchweek,home_team,away_team,home_goals,away_goals FROM matches
        WHERE competition_code=? AND season_label=? AND matchweek>0 AND home_goals IS NOT NULL
        AND away_goals IS NOT NULL ORDER BY matchweek,COALESCE(match_timestamp,match_date),id""", (competition, season)).fetchall()
    if not rows:
        return
    teams = sorted({row[1] for row in rows} | {row[2] for row in rows})
    stats = {team: [0, 0, 0, 0, 0, 0, 0] for team in teams}  # P,W,D,L,GF,GA,Pts
    by_week = defaultdict(list)
    for row in rows: by_week[row[0]].append(row)
    crest = {row[0]: row[1] for row in db.execute("""SELECT team_name,MAX(team_crest) FROM league_table_snapshots
        WHERE competition_code=? AND season_label=? GROUP BY team_name""", (competition, season))}
    db.execute("DELETE FROM league_table_snapshots WHERE competition_code=? AND season_label=?", (competition, season))
    now = int(time.time() * 1000)
    insert_snapshot(db, competition, season, 0, stats, crest, now)
    for week in sorted(by_week):
        for _, home, away, hg, ag in by_week[week]:
            h, a = stats[home], stats[away]; h[0] += 1; a[0] += 1; h[4] += hg; h[5] += ag; a[4] += ag; a[5] += hg
            if hg > ag: h[1] += 1; h[6] += 3; a[3] += 1
            elif ag > hg: a[1] += 1; a[6] += 3; h[3] += 1
            else: h[2] += 1; a[2] += 1; h[6] += 1; a[6] += 1
        insert_snapshot(db, competition, season, week, stats, crest, now)


def insert_snapshot(db, competition, season, week, stats, crest, timestamp):
    ordered = sorted(stats, key=lambda team: (-stats[team][6], -(stats[team][4]-stats[team][5]), -stats[team][4], team))
    for position, team in enumerate(ordered, 1):
        p, w, d, loss, gf, ga, points = stats[team]
        db.execute("""INSERT INTO league_table_snapshots (competition_code,season_label,matchweek,team_crest,
            team_name,position,played,won,drawn,lost,gf,ga,gd,points,source_updated_at,archived_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)""", (competition, season, week, crest.get(team, ""), team,
            position, p, w, d, loss, gf, ga, gf-ga, points, timestamp, timestamp))


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("competition", choices=sorted(API_FOOTBALL_IDS))
    parser.add_argument("season", help="Season label, for example 2025-2026")
    parser.add_argument("--db", type=Path, default=DB)
    args = parser.parse_args()
    providers = []
    if os.getenv("FOOTBALL_DATA_TOKEN"): providers.append(football_data(args.competition, args.season, os.environ["FOOTBALL_DATA_TOKEN"]))
    if os.getenv("API_FOOTBALL_KEY"): providers.append(api_football(args.competition, args.season, os.environ["API_FOOTBALL_KEY"]))
    if not providers: parser.error("set FOOTBALL_DATA_TOKEN and/or API_FOOTBALL_KEY")
    with sqlite3.connect(args.db) as db:
        prepare_schema(db)
        count = sum(merge_matches(db, args.competition, args.season, matches) for matches in providers)
        rebuild_snapshots(db, args.competition, args.season)
    print(f"Merged {count} provider records and rebuilt {args.competition} {args.season} snapshots.")


if __name__ == "__main__":
    main()
