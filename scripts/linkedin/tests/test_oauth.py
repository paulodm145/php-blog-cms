import os
import sys
import unittest
import urllib.parse

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), '..'))

import oauth


class TestOauth(unittest.TestCase):
    def test_build_authorization_url_includes_required_params(self):
        url = oauth.build_authorization_url('client123', 'http://localhost:8734/callback', 'state456')
        parsed = urllib.parse.urlparse(url)
        params = urllib.parse.parse_qs(parsed.query)
        self.assertEqual(params['client_id'], ['client123'])
        self.assertEqual(params['redirect_uri'], ['http://localhost:8734/callback'])
        self.assertEqual(params['state'], ['state456'])
        self.assertEqual(params['response_type'], ['code'])
        self.assertIn('w_member_social', params['scope'][0])

    def test_parse_callback_query_extracts_code_and_state(self):
        result = oauth.parse_callback_query('code=abc&state=xyz')
        self.assertEqual(result, {'code': 'abc', 'state': 'xyz', 'error': None})

    def test_parse_callback_query_extracts_error_when_user_cancels(self):
        result = oauth.parse_callback_query('error=user_cancelled_authorize&state=xyz')
        self.assertEqual(result['error'], 'user_cancelled_authorize')
        self.assertIsNone(result['code'])

    def test_build_token_request_body_has_required_fields(self):
        body = oauth.build_token_request_body('code1', 'client1', 'secret1', 'http://localhost:8734/callback')
        self.assertEqual(body['grant_type'], 'authorization_code')
        self.assertEqual(body['code'], 'code1')
        self.assertEqual(body['client_id'], 'client1')
        self.assertEqual(body['client_secret'], 'secret1')
        self.assertEqual(body['redirect_uri'], 'http://localhost:8734/callback')

    def test_state_matches_rejects_mismatch(self):
        self.assertFalse(oauth.state_matches('expected123', 'different456'))
        self.assertFalse(oauth.state_matches('expected123', None))

    def test_state_matches_accepts_equal_values(self):
        self.assertTrue(oauth.state_matches('expected123', 'expected123'))


if __name__ == '__main__':
    unittest.main()
