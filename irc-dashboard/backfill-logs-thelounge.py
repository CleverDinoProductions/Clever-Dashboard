#!/usr/bin/env python3
import sqlite3
import json
import os
import re
from datetime import datetime
from pathlib import Path


# Database path
DB_PATH = '/home/paul/.thelounge/logs/DinoDude.sqlite3'

# The Lounge logs directory
LOUNGE_LOGS = '/home/paul/.thelounge/logs/DinoDude/myanonamouse--4914-9827-2b9e97529b74'

# Network UUID
NETWORK_UUID = 'myanonamouse--4914-9827-2b9e97529b74'

# Date range to backfill
START_DATE = datetime(2024, 12, 4, 14, 5, 37)
END_DATE = datetime(2026, 1, 3, 23, 59, 59)


def parse_lounge_line(line):
    """Parse The Lounge log line format: [2025-12-11T18:15:13.090Z] content"""

    # Match timestamp in brackets: [YYYY-MM-DDTHH:MM:SS.sssZ]
    match = re.match(r'\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z)\]\s+(.+)', line)
    if not match:
        return None

    timestamp_str, content = match.groups()

    # Parse timestamp
    try:
        timestamp = datetime.strptime(timestamp_str, '%Y-%m-%dT%H:%M:%S.%fZ')
    except Exception:
        return None

    # Skip if outside date range
    if timestamp < START_DATE or timestamp > END_DATE:
        return None

    time_ms = int(timestamp.timestamp() * 1000)

    msg_data = None
    msg_type = None

    # Parse different message types

    # System messages start with ***
    if content.startswith('***'):
        rest = content[3:].strip()

        # Join: *** nick (hostmask) joined
        if ' joined' in rest:
            match = re.match(r'(.+?)\s+\((.+?)\)\s+joined', rest)
            if match:
                nick, hostmask = match.groups()
                msg_type = 'join'
                msg_data = {'from': {'nick': nick, 'hostname': hostmask}}

        # Quit: *** nick (hostmask) quit (reason)
        elif ' quit' in rest:
            match = re.match(r'(.+?)\s+\((.+?)\)\s+quit(?:\s+\((.+)\))?', rest)
            if match:
                nick, hostmask, reason = match.groups()
                msg_type = 'quit'
                msg_data = {
                    'from': {'nick': nick, 'hostname': hostmask},
                    'text': reason or ''
                }

        # Part: *** nick (hostmask) left
        elif ' left' in rest:
            match = re.match(r'(.+?)\s+\((.+?)\)\s+left', rest)
            if match:
                nick, hostmask = match.groups()
                msg_type = 'part'
                msg_data = {'from': {'nick': nick, 'hostname': hostmask}}

        # Nick change: *** oldnick changed nick to newnick
        elif ' changed nick to ' in rest:
            match = re.match(r'(.+?)\s+changed nick to\s+(.+)', rest)
            if match:
                old_nick, new_nick = match.groups()
                msg_type = 'nick'
                msg_data = {'from': {'nick': old_nick}, 'new_nick': new_nick}

        # Mode: *** Nick set mode +v user
        elif ' set mode ' in rest or ' sets mode ' in rest:
            msg_type = 'mode'
            msg_data = {'text': rest}

        # Kick: *** nick kicked user
        elif ' kicked ' in rest:
            msg_type = 'kick'
            msg_data = {'text': rest}

    # Regular messages: <nick> or <@nick> or <+nick> text
    elif content.startswith('<'):
        match = re.match(r'<([+@%~&]?)([^>]+)>\s*(.*)', content)
        if match:
            mode_char, nick, text = match.groups()
            msg_type = 'message'
            msg_data = {
                'from': {'nick': nick, 'mode': mode_char},
                'text': text
            }

    # Action messages: * nick does something
    elif content.startswith('* '):
        match = re.match(r'\*\s+([^\s]+)\s+(.*)', content)
        if match:
            nick, text = match.groups()
            msg_type = 'action'
            msg_data = {'from': {'nick': nick}, 'text': text}

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

    with open(log_file, 'r', encoding='utf-8', errors='ignore') as f:
        for line in f:
            line = line.strip()
            if not line:
                continue

            parsed = parse_lounge_line(line)
            if not parsed:
                continue

            try:
                db.execute(
                    "INSERT OR IGNORE INTO messages (time, type, msg, network, channel) VALUES (?, ?, ?, ?, ?)",
                    (parsed['time'], parsed['type'], parsed['msg'], network_uuid, channel_name)
                )
                count += 1
            except sqlite3.Error:
                pass

    return count


def main():
    if not os.path.exists(DB_PATH):
        print(f"❌ Database not found: {DB_PATH}")
        return

    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    print(f"✅ Connected to database")
    print(f"🌐 Network: {NETWORK_UUID}")
    print(f"📅 Date range: {START_DATE.strftime('%Y-%m-%d')} to {END_DATE.strftime('%Y-%m-%d')}\n")

    logs_path = Path(LOUNGE_LOGS)
    if not logs_path.exists():
        print(f"❌ Logs directory not found: {LOUNGE_LOGS}")
        return

    total_imported = 0

    # Process each .log file
    for log_file in sorted(logs_path.glob('*.log')):
        channel_name = log_file.stem

        # Skip system logs
        if channel_name in ['chanserv', 'hostserv', 'mousebot']:
            print(f"⏭️  Skipping {channel_name}")
            continue

        print(f"📁 {channel_name}...", end=' ', flush=True)

        count = process_log_file(cursor, log_file, channel_name, NETWORK_UUID)

        if count > 0:
            total_imported += count
            print(f"✓ {count:,} messages")
            conn.commit()
        else:
            print(f"⚠ No messages")

    conn.close()

    print(f"\n{'='*60}")
    print(f"✅ Import complete!")
    print(f"📊 Total imported: {total_imported:,} messages")
    print(f"\n💡 Next steps:")
    print(f"   1. Restart The Lounge: thelounge restart")
    print(f"   2. Check your channels - history should now be available!")


if __name__ == '__main__':
    main()