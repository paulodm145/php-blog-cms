#!/usr/bin/env python3
import http.server
import json
import secrets
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import webbrowser
from datetime import datetime, timedelta, timezone

import credentials
import oauth

REDIRECT_URI = 'http://localhost:8734/callback'
CALLBACK_PORT = 8734
CALLBACK_TIMEOUT_SECONDS = 180
TOKEN_ENDPOINT = 'https://www.linkedin.com/oauth/v2/accessToken'
USERINFO_ENDPOINT = 'https://api.linkedin.com/v2/userinfo'

_callback_result = {}


class CallbackHandler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        path, _, query = self.path.partition('?')
        if path != '/callback':
            self.send_response(404)
            self.end_headers()
            return

        _callback_result.update(oauth.parse_callback_query(query))

        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.end_headers()
        if _callback_result.get('error'):
            self.wfile.write('<h1>Autorização cancelada.</h1><p>Pode fechar esta aba.</p>'.encode('utf-8'))
        else:
            self.wfile.write('<h1>Autorizado!</h1><p>Pode fechar esta aba e voltar pro terminal.</p>'.encode('utf-8'))

    def log_message(self, format, *args):
        pass


def wait_for_callback(timeout=CALLBACK_TIMEOUT_SECONDS):
    try:
        server = http.server.HTTPServer(('localhost', CALLBACK_PORT), CallbackHandler)
    except OSError as e:
        raise RuntimeError(
            'Não consegui abrir a porta {} pra receber o callback do LinkedIn ({}). '
            'Feche qualquer processo antigo de authorize.py e tente de novo.'.format(CALLBACK_PORT, e)
        )

    server.timeout = timeout
    deadline = time.monotonic() + timeout
    try:
        while not _callback_result and time.monotonic() < deadline:
            server.handle_request()
    finally:
        server.server_close()

    if not _callback_result:
        raise TimeoutError(
            'Nenhum callback do LinkedIn recebido em {}s — autorização não concluída.'.format(timeout)
        )
    return dict(_callback_result)


def exchange_code_for_token(code, client_id, client_secret):
    body = oauth.build_token_request_body(code, client_id, client_secret, REDIRECT_URI)
    data = urllib.parse.urlencode(body).encode('utf-8')
    req = urllib.request.Request(TOKEN_ENDPOINT, data=data, method='POST')
    req.add_header('Content-Type', 'application/x-www-form-urlencoded')
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode('utf-8'))


def fetch_person_urn(access_token):
    req = urllib.request.Request(USERINFO_ENDPOINT)
    req.add_header('Authorization', 'Bearer ' + access_token)
    with urllib.request.urlopen(req) as resp:
        payload = json.loads(resp.read().decode('utf-8'))
    return payload['sub']


def load_or_ask_app_credentials():
    try:
        return credentials.load()
    except FileNotFoundError:
        print('Nenhuma credencial encontrada.')
        print('Cole o Client ID e o Client Secret do app criado em https://www.linkedin.com/developers/apps:')
        client_id = input('Client ID: ').strip()
        client_secret = input('Client Secret: ').strip()
        return {'client_id': client_id, 'client_secret': client_secret}


def main():
    creds = load_or_ask_app_credentials()
    credentials.save(creds)

    state = secrets.token_urlsafe(16)
    url = oauth.build_authorization_url(creds['client_id'], REDIRECT_URI, state)
    print('Abrindo o navegador pra autorizar. Se não abrir sozinho, acesse:')
    print(url)
    webbrowser.open(url)

    try:
        result = wait_for_callback()
    except (TimeoutError, RuntimeError) as e:
        print(str(e))
        sys.exit(1)

    if result.get('error'):
        print('Autorização cancelada ou negada: {}'.format(result['error']))
        sys.exit(1)

    if not oauth.state_matches(state, result.get('state')):
        print('O "state" recebido não bate com o esperado — abortando por segurança.')
        sys.exit(1)

    if not result.get('code'):
        print('Callback recebido sem "code" — algo deu errado no fluxo.')
        sys.exit(1)

    try:
        token_response = exchange_code_for_token(result['code'], creds['client_id'], creds['client_secret'])
    except urllib.error.HTTPError as e:
        print('LinkedIn recusou a troca do código por token: HTTP {}'.format(e.code))
        sys.exit(1)

    access_token = token_response.get('access_token')
    if not access_token:
        print('Resposta da LinkedIn sem access_token — algo deu errado na troca do código.')
        sys.exit(1)
    expires_in = token_response.get('expires_in', 60 * 24 * 3600)

    try:
        person_urn = fetch_person_urn(access_token)
    except (urllib.error.HTTPError, urllib.error.URLError, KeyError) as e:
        print('Falha ao buscar os dados do perfil na LinkedIn depois de obter o token: {}'.format(e))
        sys.exit(1)

    creds.update({
        'access_token': access_token,
        'expires_at': (datetime.now(timezone.utc) + timedelta(seconds=expires_in)).isoformat(),
        'person_urn': person_urn,
    })
    credentials.save(creds)
    print('Autenticado com sucesso. Token válido até {}.'.format(creds['expires_at']))


if __name__ == '__main__':
    main()
