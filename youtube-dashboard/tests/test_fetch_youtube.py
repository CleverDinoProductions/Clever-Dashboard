"""Offline importer regression tests: python -m unittest discover -s youtube-dashboard/tests."""
import contextlib
import importlib.util
import io
from pathlib import Path
import sqlite3
import sys
import types
import unittest
from unittest.mock import Mock, patch


spec = importlib.util.spec_from_file_location(
    'fetch_youtube', Path(__file__).resolve().parents[1] / 'fetch-youtube.py')
fetcher = importlib.util.module_from_spec(spec)
# Replace only the network-facing dependency; no feed requests or installs needed.
with patch.dict(sys.modules, {'feedparser': types.SimpleNamespace(parse=Mock())}):
    spec.loader.exec_module(fetcher)


class Entry(dict):
    __getattr__ = dict.__getitem__


CHANNEL = 'UC' + 'a' * 22
OTHER_CHANNEL = 'UC' + 'b' * 22


def entry(video_id='Abc_12-xyZ9', **overrides):
    return Entry(yt_videoid=video_id, yt_channelid=CHANNEL, title='Example',
                 published='2026-01-01T12:00:00Z', link='https://www.youtube.com/watch?v=' + str(video_id),
                 **overrides)


class FetchChannelTests(unittest.TestCase):
    def setUp(self):
        with patch.object(fetcher, 'DB_PATH', ':memory:'):
            self.db = fetcher.init_db()
        self.addCleanup(self.db.close)
        self.output = contextlib.redirect_stdout(io.StringIO())
        self.output.__enter__()
        self.addCleanup(self.output.__exit__, None, None, None)
        fetcher.feedparser.parse.reset_mock()

    def feed(self, entries, channel=CHANNEL):
        feed = Entry(feed={'yt_channelid': channel}, entries=entries)
        fetcher.feedparser.parse.return_value = feed

    def test_invalid_channel_ids_do_not_fetch_or_update_metadata(self):
        for channel in [None, '', 'a' * 24, CHANNEL + '\n', CHANNEL[:-1], 'https://youtube.com/' + CHANNEL]:
            self.assertEqual(fetcher.fetch_channel('Test', channel, self.db), 0)
        fetcher.feedparser.parse.assert_not_called()
        self.assertEqual(self.db.execute('SELECT COUNT(*) FROM channels').fetchone()[0], 0)

    def test_invalid_entries_do_not_stop_later_valid_entries(self):
        missing = entry()
        del missing['yt_videoid']
        wrong_channel = entry()
        wrong_channel['yt_channelid'] = OTHER_CHANNEL
        self.feed([missing, entry(None), entry(''), entry('too-short'),
                   entry('Abc_12-xyZ9\n'), entry('Abc!12-xyZ9'), wrong_channel, entry()])
        self.assertEqual(fetcher.fetch_channel('Test', CHANNEL, self.db), 1)
        self.assertEqual(self.db.execute('SELECT video_id, channel_id FROM videos').fetchall(),
                         [('Abc_12-xyZ9', CHANNEL)])

    def test_mismatched_feed_is_rejected(self):
        self.feed([entry()], OTHER_CHANNEL)
        self.assertEqual(fetcher.fetch_channel('Test', CHANNEL, self.db), 0)
        self.assertEqual(self.db.execute('SELECT COUNT(*) FROM channels').fetchone()[0], 0)
        self.assertEqual(self.db.execute('SELECT COUNT(*) FROM videos').fetchone()[0], 0)

    def test_ids_are_case_sensitive_and_repeated_import_is_idempotent(self):
        self.feed([entry(), entry('abc_12-xyZ9'), entry()])
        self.assertEqual(fetcher.fetch_channel('Test', CHANNEL, self.db), 2)
        self.assertEqual(fetcher.fetch_channel('Test', CHANNEL, self.db), 0)
        self.assertEqual(self.db.execute('SELECT video_count FROM channels').fetchone()[0], 2)


if __name__ == '__main__':
    unittest.main()
