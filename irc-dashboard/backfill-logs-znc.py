#!/usr/bin/env python3
import sqlite3
import json
import os
import re
from datetime import datetime
from pathlib import Path

# Database path
DB_PATH = '/home/paul/.thelounge/logs/DinoDude.sqlite3'

# ZNC logs - USE LINUX PATH
ZNC_LOGS = '/home/paul/.znc/users/CleverDino/networks/MyAnonamouse/moddata/log'

# Date range to backfill (from the very beginning)
START_DATE = datetime(2024, 12, 4, 14, 5, 37)
END_DATE = datetime(2026, 1, 7, 13, 00, 00)

def parse_znc_line(line):
    """Parse a ZNC log line"""
    # ZNC format: [HH:MM:SS] <nick> message
    match = re.match(r'\[(\d{2}:\d{2}:\d{2})\] (.+)', line)
    if not match:
        return None
    
    time_str, content = match.groups()
    return {'time_str': time_str, 'content': content}

def process_daily_log(db, log_file, channel_name, network_uuid, date):
    """Process a single daily log file"""
    if not os.path.exists(log_file):
        return 0
    
    count = 0
    
    with open(log_file, 'r', encoding='utf-8', errors='ignore') as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            
            parsed = parse_znc_line(line)
            if not parsed:
                continue
            
            # Combine date from filename with time from log
            try:
                timestamp = datetime.combine(date, datetime.strptime(parsed['time_str'], '%H:%M:%S').time())
            except:
                continue
            
            # Skip if outside range
            if timestamp < START_DATE or timestamp > END_DATE:
                continue
            
            time_ms = int(timestamp.timestamp() * 1000)
            content = parsed['content']
            
            # Parse message type
            msg_data = None
            msg_type = None
            
            if content.startswith('<'):
                # Regular message: <nick> text
                nick_match = re.match(r'<(.+?)> (.+)', content)
                if nick_match:
                    nick, text = nick_match.groups()
                    msg_type = 'message'
                    msg_data = {'from': {'nick': nick}, 'text': text}
            
            elif content.startswith('***') or content.startswith('*'):
                # System messages
                if 'joined' in content.lower():
                    nick_match = re.search(r'(\S+).*joined', content)
                    if nick_match:
                        msg_type = 'join'
                        msg_data = {'from': {'nick': nick_match.group(1)}}
                
                elif 'left' in content.lower() or 'quit' in content.lower():
                    nick_match = re.search(r'(\S+).*(?:left|quit)', content)
                    if nick_match:
                        msg_type = 'part' if 'left' in content.lower() else 'quit'
                        msg_data = {'from': {'nick': nick_match.group(1)}}
                
                elif 'mode' in content.lower() or 'sets mode' in content.lower():
                    msg_type = 'mode'
                    msg_data = {'text': content}
                
                elif 'kicked' in content.lower():
                    msg_type = 'kick'
                    msg_data = {'text': content}
            
            if msg_data:
                try:
                    db.execute('''
                        INSERT OR IGNORE INTO messages (time, type, msg, network, channel)
                        VALUES (?, ?, ?, ?, ?)
                    ''', (time_ms, msg_type, json.dumps(msg_data), network_uuid, channel_name))
                    count += 1
                except sqlite3.Error as e:
                    pass  # Silently skip duplicates
    
    return count

def main():
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # Get network UUID from database
    cursor.execute("SELECT DISTINCT network FROM messages WHERE channel LIKE '#%' LIMIT 1")
    result = cursor.fetchone()
    if not result:
        print("❌ No network UUID found. Make sure CleverLounge is running and has some messages!")
        return
    
    network_uuid = result[0]
    print(f"✅ Using network UUID: {network_uuid}\n")
    
    znc_path = Path(ZNC_LOGS)
    if not znc_path.exists():
        print(f"❌ ZNC logs not found at: {ZNC_LOGS}")
        return
    
    total_imported = 0
    
    # Process each channel folder
    for channel_dir in sorted(znc_path.iterdir()):
        if not channel_dir.is_dir():
            continue
        
        channel_name = channel_dir.name
        print(f"\n📁 {channel_name}")
        
        channel_count = 0
        # Process each daily log file
        for log_file in sorted(channel_dir.glob('*.log')):
            # Extract date from filename (YYYY-MM-DD.log)
            try:
                date_str = log_file.stem
                date = datetime.strptime(date_str, '%Y-%m-%d').date()
                
                # Skip if outside range
                if date < START_DATE.date() or date > END_DATE.date():
                    continue
                
                count = process_daily_log(cursor, log_file, channel_name, network_uuid, date)
                if count > 0:
                    channel_count += count
                    print(f"  ✓ {date_str}: +{count} messages")
            except ValueError:
                print(f"  ⚠ Skipping: {log_file.name}")
        
        if channel_count > 0:
            total_imported += channel_count
            print(f"  → Total: {channel_count} messages")
        
        # Commit after each channel
        conn.commit()
    
    conn.close()
    print(f"\n{'='*50}")
    print(f"✅ Import complete!")
    print(f"📊 Total imported: {total_imported:,} messages (Dec 24 - Jan 2)")
    print(f"🌐 Refresh your dashboard to see the data!")

if __name__ == '__main__':
    main()
