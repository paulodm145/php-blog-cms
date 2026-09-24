import argparse
import os
import sys
import tempfile
import unittest
import urllib.error
from io import StringIO
from unittest.mock import MagicMock, patch

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), '..'))

import post


class TestBuildPostPayload(unittest.TestCase):
    def test_author_urn_and_text_are_set(self):
        payload = post.build_post_payload('abc123', 'Meu post de teste')
        self.assertEqual(payload['author'], 'urn:li:person:abc123')
        self.assertEqual(payload['commentary'], 'Meu post de teste')
        self.assertEqual(payload['lifecycleState'], 'PUBLISHED')
        self.assertEqual(payload['visibility'], 'PUBLIC')


class TestParsePostResponse(unittest.TestCase):
    def test_success_returns_post_url(self):
        url = post.parse_post_response(201, {'x-restli-id': 'urn:li:share:999'})
        self.assertEqual(url, 'https://www.linkedin.com/feed/update/urn:li:share:999')

    def test_non_2xx_status_raises(self):
        with self.assertRaises(RuntimeError):
            post.parse_post_response(401, {})

    def test_2xx_without_id_header_raises(self):
        with self.assertRaises(RuntimeError):
            post.parse_post_response(201, {})


class TestReadText(unittest.TestCase):
    def test_empty_stdin_raises(self):
        args = argparse.Namespace(file=None)
        with patch('sys.stdin', StringIO('   \n')):
            with self.assertRaises(ValueError):
                post.read_text(args)

    def test_reads_from_file(self):
        with tempfile.NamedTemporaryFile(mode='w', suffix='.txt', delete=False) as f:
            f.write('Texto do post\n')
            path = f.name
        try:
            args = argparse.Namespace(file=path)
            text = post.read_text(args)
            self.assertEqual(text, 'Texto do post')
        finally:
            os.remove(path)


class TestCallLinkedin(unittest.TestCase):
    def test_normalizes_header_casing_from_response(self):
        mock_resp = MagicMock()
        mock_resp.status = 201
        mock_resp.headers = {'X-RestLi-Id': 'urn:li:share:123'}
        mock_resp.__enter__.return_value = mock_resp
        mock_resp.__exit__.return_value = False
        with patch('urllib.request.urlopen', return_value=mock_resp):
            status, headers = post.call_linkedin({'author': 'x'}, 'token')
        self.assertEqual(status, 201)
        self.assertEqual(headers.get('x-restli-id'), 'urn:li:share:123')

    def test_network_error_raises_runtime_error(self):
        with patch('urllib.request.urlopen', side_effect=urllib.error.URLError('sem rota')):
            with self.assertRaises(RuntimeError):
                post.call_linkedin({'author': 'x'}, 'token')


if __name__ == '__main__':
    unittest.main()
