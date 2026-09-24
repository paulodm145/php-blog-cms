import os
import sys
import tempfile
import unittest
from datetime import datetime, timedelta, timezone

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), '..'))

import credentials


class TestCredentials(unittest.TestCase):
    def test_save_then_load_roundtrip(self):
        with tempfile.TemporaryDirectory() as tmp:
            path = os.path.join(tmp, '.credentials.json')
            credentials.save({'access_token': 'abc'}, path=path)
            loaded = credentials.load(path=path)
            self.assertEqual(loaded['access_token'], 'abc')

    def test_load_missing_file_raises_clear_error(self):
        with tempfile.TemporaryDirectory() as tmp:
            path = os.path.join(tmp, 'nope.json')
            with self.assertRaises(FileNotFoundError):
                credentials.load(path=path)

    def test_is_token_expired_true_for_past_date(self):
        past = (datetime.now(timezone.utc) - timedelta(days=1)).isoformat()
        self.assertTrue(credentials.is_token_expired({'expires_at': past}))

    def test_is_token_expired_false_for_future_date(self):
        future = (datetime.now(timezone.utc) + timedelta(days=1)).isoformat()
        self.assertFalse(credentials.is_token_expired({'expires_at': future}))


if __name__ == '__main__':
    unittest.main()
