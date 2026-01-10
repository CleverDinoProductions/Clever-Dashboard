#!/usr/bin/env python3
import sqlite3
import json
import os
import re
from datetime import datetime
from pathlib import Path


# Database path - adjust if needed
DB_PATH = '/home/paul/.thelounge/logs/DinoDude.sqlite3'

# The Lounge logs directory
LOUNGE_LOGS = '/home/paul/.thelounge/logs/DinoDude/myanonamouse--4914-9827-2b9e97529b74'

# Network UUID (from the folder name)
NETWORK_UUID = 'myanonamouse--4914-9827-2b9e97529b74'

# Date range to backfill
START_DATE = datetime(2024, 12, 4, 14, 5, 37)
END_DATE = datetime(2025, 1, 3, 23, 59, 59)

DEBUG = True  # Enable debug output


def parse_lounge_line(line):
    """Parse a The Lounge log line"""
    # Format: 2024-12-04T133615.217Z nick hostmask message/action
    # Example: 2024-12-04T133719.041Z manonamo ThunderbirdAHIP quit Ping timeout

    match = re.match(r'(\d{4}-\d{2}-\d{2}T\d{2}\d{2}\d{2}\.\d{3}Z)\s+(.+)', line)
    if not match:
        if DEBUG:
            print(f"  ❌ No match: {line[:80]}")
        return None

    timestamp_str, rest = match.groups()

    # Parse timestamp (format: 2024-12-04T133615.217Z)
    try:
        # The format is YYYY-MM-DDTHHMMSS.sssZ (no colons in time)
        timestamp = datetime.strptime(timestamp_str, '%Y-%m-%dT%H%M%S.%fZ')
    except Exception as e:
        if DEBUG:
            print(f"  ❌ Timestamp parse error: {timestamp_str} - {e}")
        return None

    # Skip if outside range
    if timestamp < START_DATE or timestamp > END_DATE:
        return None

    time_ms = int(timestamp.timestamp() * 1000)

    # Parse message content
    msg_data = None
    msg_type = None

    # Check for different message types
    if ' joined' in rest:
        # Join: nick hostmask joined
        parts = rest.split(' joined', 1)
        user_info = parts[0].strip().split(' ', 1)
        nick = user_info[0]
        hostmask = user_info[1] if len(user_info) > 1 else ''
        msg_type = 'join'
        msg_data = {'from': {'nick': nick, 'hostname': hostmask}}

    elif ' quit' in rest:
        # Quit: nick hostmask quit reason
        parts = rest.split(' quit', 1)
        user_info = parts[0].strip().split(' ', 1)
        nick = user_info[0]
        hostmask = user_info[1] if len(user_info) > 1 else ''
        reason = parts[1].strip() if len(parts) > 1 else ''
        msg_type = 'quit'
        msg_data = {'from': {'nick': nick, 'hostname': hostmask}, 'text': reason}

    elif ' left' in rest:
        # Part: nick hostmask left channel
        parts = rest.split(' left', 1)
        user_info = parts[0].strip().split(' ', 1)
        nick = user_info[0]
        hostmask = user_info[1] if len(user_info) > 1 else ''
        reason = parts[1].strip() if len(parts) > 1 else ''
        msg_type = 'part'
        msg_data = {'from': {'nick': nick, 'hostname': hostmask}, 'text': reason}

    elif ' changed nick to ' in rest:
        # Nick change
        parts = rest.split(' changed nick to ', 1)
        old_nick = parts[0].strip()
        new_nick = parts[1].strip() if len(parts) > 1 else ''
        msg_type = 'nick'
        msg_data = {'from': {'nick': old_nick}, 'new_nick': new_nick}

    elif ' kicked ' in rest:
        msg_type = 'kick'
        msg_data = {'text': rest}

    else:
        # Regular message or action: nick hostmask message text
        # Try to split into nick, hostmask, and message
        parts = rest.split(' ', 2)
        if len(parts) >= 2:
            nick = parts[0]
            hostmask = parts[1]
            text = parts[2] if len(parts) > 2 else ''

            msg_type = 'message'
            msg_data = {'from': {'nick': nick, 'hostname': hostmask}, 'text': text}

    if msg_data and msg_type:
        return {
            'time': time_ms,
            'type': msg_type,
            'msg': json.dumps(msg_data)
        }

    return None


def process_log_file(db, log_file, channel_name, network_uuid):
    """Process a single The Lounge log file"""
    if not os.path.exists(log_file):
        return 0

    count = 0
    line_count = 0

    with open(log_file, 'r', encoding='utf-8', errors='ignore') as f:
        for line in f:
            line_count += 1
            line = line.strip()
            if not line:
                continue

            # Show first few lines for debugging
            if DEBUG and line_count <= 3:
                print(f"  Sample line {line_count}: {line[:100]}")

            parsed = parse_lounge_line(line)
            if not parsed:
                continue

            try:
                db.execute(
                    "INSERT OR IGNORE INTO messages (time, type, msg, network, channel) VALUES (?, ?, ?, ?, ?)",
                    (parsed['time'], parsed['type'], parsed['msg'], network_uuid, channel_name)
                )
                count += 1
            except sqlite3.Error as e:
                if DEBUG and count == 0:
                    print(f"  ❌ DB Error: {e}")

    if DEBUG:
        print(f"  📊 Processed {line_count} lines, imported {count} messages")

    return count


def main():
    # Check if database exists
    if not os.path.exists(DB_PATH):
        print(f"❌ Database not found at: {DB_PATH}")
        print("Make sure The Lounge is configured with SQLite storage.")
        return

    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    print(f"✅ Connected to database: {DB_PATH}")
    print(f"🌐 Network UUID: {NETWORK_UUID}\n")

    logs_path = Path(LOUNGE_LOGS)
    if not logs_path.exists():
        print(f"❌ Logs directory not found at: {LOUNGE_LOGS}")
        return

    total_imported = 0

    # Process each .log file in the directory
    for log_file in sorted(logs_path.glob('*.log')):
        channel_name = log_file.stem  # e.g., "#anonamouse.net"

        # Skip system logs
        if channel_name in ['chanserv', 'hostserv', 'mousebot']:
            print(f"⏭️  Skipping {channel_name}")
            continue

        print(f"\n📁 {channel_name}")

        count = process_log_file(cursor, log_file, channel_name, NETWORK_UUID)

        if count > 0:
            total_imported += count
            print(f"  ✓ Imported {count:,} messages")
            conn.commit()
        else:
            print(f"  ⚠ No messages imported")

    conn.close()
    print(f"\n{'='*50}")
    print(f"✅ Import complete!")
    print(f"📊 Total imported: {total_imported:,} messages")
    print(f"🌐 Restart The Lounge to see the data!")


if __name__ == '__main__':
    main()
