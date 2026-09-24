import urllib.parse

AUTHORIZATION_ENDPOINT = 'https://www.linkedin.com/oauth/v2/authorization'
SCOPES = 'openid profile w_member_social'


def build_authorization_url(client_id, redirect_uri, state):
    params = {
        'response_type': 'code',
        'client_id': client_id,
        'redirect_uri': redirect_uri,
        'state': state,
        'scope': SCOPES,
    }
    return AUTHORIZATION_ENDPOINT + '?' + urllib.parse.urlencode(params)


def parse_callback_query(query_string):
    parsed = urllib.parse.parse_qs(query_string)
    return {
        'code': parsed.get('code', [None])[0],
        'state': parsed.get('state', [None])[0],
        'error': parsed.get('error', [None])[0],
    }


def build_token_request_body(code, client_id, client_secret, redirect_uri):
    return {
        'grant_type': 'authorization_code',
        'code': code,
        'client_id': client_id,
        'client_secret': client_secret,
        'redirect_uri': redirect_uri,
    }


def state_matches(expected, received):
    return received is not None and received == expected
