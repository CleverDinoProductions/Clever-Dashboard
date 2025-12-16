#!/usr/bin/env python3
"""
YouTube RSS Dashboard - Video Fetcher
Matches your football-stats structure
"""

import feedparser
import sqlite3
from datetime import datetime
from dateutil import parser as date_parser

# Configuration
DB_PATH = 'youtube-dashboard.sqlite3'

#use config.php CHANNELS values
CHANNELS = {
    'Family Fun Pack': 'UCvthuVsurPaVz2a7_4LepGg',
    'Theme Park Worldwide (TPWW)': 'UCwqDAwhG1JCxTNr6HnB7gnQ',
}

LOCATIONS = {
    'UK': ['UK', 'England', 'British', 'London', 'Hull'],
    'Germany': ['Germany', 'Berlin', 'Munich'],
    'Spain': ['Spain', 'Barcelona', 'Madrid'],
    'France': ['France', 'Paris'],
    'USA': ['California', 'America', 'Home'],
}

def init_db():
    """Initialize database"""
    db = sqlite3.connect(DB_PATH)
    db.execute("CREATE TABLE IF NOT EXISTS videos (video_id TEXT PRIMARY KEY, channel_name TEXT, channel_id TEXT, title TEXT, published_date TEXT, url TEXT, detected_location TEXT, first_seen TEXT)")
    db.execute("CREATE TABLE IF NOT EXISTS channels (channel_id TEXT PRIMARY KEY, channel_name TEXT, last_checked TEXT, video_count INTEGER DEFAULT 0)")
    db.execute("CREATE TABLE IF NOT EXISTS upload_gaps (id INTEGER PRIMARY KEY AUTOINCREMENT, channel_id TEXT, video_id TEXT, gap_days INTEGER, previous_video_date TEXT, current_video_date TEXT)")
    db.commit()
    return db

def detect_location(title):
    """Detect location from title"""
    title_lower = title.lower()
    for country, keywords in LOCATIONS.items():
        if any(kw.lower() in title_lower for kw in keywords):
            return country
    return None

def calculate_gap(channel_id, published_date, db):
    """Calculate upload gap"""
    prev = db.execute("SELECT published_date FROM videos WHERE channel_id = ? AND published_date < ? ORDER BY published_date DESC LIMIT 1", 
                     (channel_id, published_date)).fetchone()
    if prev:
        current = date_parser.parse(published_date)
        previous = date_parser.parse(prev[0])
        gap_days = (current - previous).days
        return gap_days, prev[0]
    return None, None

def fetch_channel(channel_name, channel_id, db):
    """Fetch videos from RSS feed"""
    rss_url = f"https://www.youtube.com/feeds/videos.xml?channel_id={channel_id}"
    print(f"\n📺 {channel_name}")
    
    try:
        feed = feedparser.parse(rss_url)
        new_videos = 0
        
        for entry in feed.entries:
            video_id = entry.yt_videoid
            title = entry.title
            published = entry.published
            url = entry.link
            
            existing = db.execute("SELECT video_id FROM videos WHERE video_id = ?", (video_id,)).fetchone()
            
            if not existing:
                location = detect_location(title)
                gap_days, prev_date = calculate_gap(channel_id, published, db)
                
                db.execute("INSERT INTO videos VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                          (video_id, channel_name, channel_id, title, published, url, location, datetime.now().isoformat()))
                
                if gap_days:
                    db.execute("INSERT INTO upload_gaps (channel_id, video_id, gap_days, previous_video_date, current_video_date) VALUES (?, ?, ?, ?, ?)",
                              (channel_id, video_id, gap_days, prev_date, published))
                
                new_videos += 1
                print(f"   ✅ {title[:60]}")
                if location: print(f"      📍 {location}")
                if gap_days: print(f"      ⏱️  {gap_days} days")
        
        if not new_videos:
            print("   ℹ️  No new videos")
        
        db.execute("INSERT OR REPLACE INTO channels VALUES (?, ?, ?, (SELECT COUNT(*) FROM videos WHERE channel_id = ?))",
                  (channel_id, channel_name, datetime.now().isoformat(), channel_id))
        
        return new_videos
    except Exception as e:
        print(f"   ❌ {e}")
        return 0

def main():
    print("=" * 70)
    print("🎬 YouTube RSS Dashboard - Fetcher")
    print("=" * 70)
    
    db = init_db()
    total_new = 0
    
    for name, cid in CHANNELS.items():
        total_new += fetch_channel(name, cid, db)
    
    db.commit()
    db.close()
    
    print(f"\n✅ Found {total_new} new video(s)")
    print("=" * 70)

if __name__ == "__main__":
    main()
