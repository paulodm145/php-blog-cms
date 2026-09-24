import http.client
import http.server
import os
import sys
import threading
import time
import unittest

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), '..'))

import authorize


class TestWaitForCallback(unittest.TestCase):
    def setUp(self):
        authorize._callback_result.clear()

    def test_ignores_stray_request_and_waits_for_real_callback(self):
        def hit_server():
            time.sleep(0.05)
            conn = http.client.HTTPConnection('localhost', authorize.CALLBACK_PORT)
            conn.request('GET', '/favicon.ico')
            conn.getresponse()
            conn.close()

            conn = http.client.HTTPConnection('localhost', authorize.CALLBACK_PORT)
            conn.request('GET', '/callback?code=abc123&state=xyz')
            conn.getresponse()
            conn.close()

        threading.Thread(target=hit_server, daemon=True).start()
        result = authorize.wait_for_callback(timeout=5)
        self.assertEqual(result['code'], 'abc123')
        self.assertEqual(result['state'], 'xyz')

    def test_raises_timeout_error_when_nothing_arrives(self):
        with self.assertRaises(TimeoutError):
            authorize.wait_for_callback(timeout=0.3)

    def test_raises_runtime_error_when_port_already_in_use(self):
        blocker = http.server.HTTPServer(('localhost', authorize.CALLBACK_PORT), http.server.BaseHTTPRequestHandler)
        try:
            with self.assertRaises(RuntimeError):
                authorize.wait_for_callback(timeout=0.3)
        finally:
            blocker.server_close()


if __name__ == '__main__':
    unittest.main()
