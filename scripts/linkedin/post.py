#!/usr/bin/env python3
import argparse
import json
import sys
import urllib.error
import urllib.request

import credentials

POSTS_ENDPOINT = 'https://api.linkedin.com/rest/posts'
LINKEDIN_API_VERSION = '202401'


def build_post_payload(person_urn, text):
    return {
        'author': 'urn:li:person:' + person_urn,
        'commentary': text,
        'visibility': 'PUBLIC',
        'distribution': {
            'feedDistribution': 'MAIN_FEED',
            'targetEntities': [],
            'thirdPartyDistributionChannels': [],
        },
        'lifecycleState': 'PUBLISHED',
        'isReshareDisabledByAuthor': False,
    }


def parse_post_response(status_code, headers):
    if status_code not in (200, 201):
        raise RuntimeError('LinkedIn respondeu status {} — post NÃO publicado.'.format(status_code))
    post_id = headers.get('x-restli-id')
    if not post_id:
        raise RuntimeError('Resposta 2xx sem header x-restli-id — não dá pra confirmar que publicou.')
    return 'https://www.linkedin.com/feed/update/' + post_id


def read_text(args):
    if args.file:
        with open(args.file, encoding='utf-8') as f:
            text = f.read()
    else:
        text = sys.stdin.read()
    text = text.strip()
    if not text:
        raise ValueError('Texto do post está vazio.')
    return text


def call_linkedin(payload, access_token):
    body = json.dumps(payload).encode('utf-8')
    req = urllib.request.Request(POSTS_ENDPOINT, data=body, method='POST')
    req.add_header('Content-Type', 'application/json')
    req.add_header('Authorization', 'Bearer ' + access_token)
    req.add_header('LinkedIn-Version', LINKEDIN_API_VERSION)
    req.add_header('X-Restli-Protocol-Version', '2.0.0')
    try:
        with urllib.request.urlopen(req) as resp:
            return resp.status, {k.lower(): v for k, v in resp.headers.items()}
    except urllib.error.HTTPError as e:
        return e.code, {k.lower(): v for k, v in e.headers.items()}
    except urllib.error.URLError as e:
        raise RuntimeError('Falha de rede ao publicar no LinkedIn: {}'.format(e.reason))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--file', help='Arquivo com o texto do post (default: lê do stdin)')
    parser.add_argument('--dry-run', action='store_true', help='Só imprime o payload, não publica')
    args = parser.parse_args()

    try:
        text = read_text(args)
    except (ValueError, FileNotFoundError) as e:
        print(str(e))
        sys.exit(1)

    try:
        creds = credentials.load()
    except FileNotFoundError as e:
        print(str(e))
        sys.exit(1)

    try:
        expired = credentials.is_token_expired(creds)
        person_urn = creds['person_urn']
        access_token = creds['access_token']
    except KeyError as e:
        print('Arquivo de credenciais incompleto (faltando {}). Rode scripts/linkedin/authorize.py de novo.'.format(e))
        sys.exit(1)

    if expired:
        print('Token expirado. Rode scripts/linkedin/authorize.py de novo antes de publicar.')
        sys.exit(1)

    payload = build_post_payload(person_urn, text)

    if args.dry_run:
        print(json.dumps(payload, indent=2, ensure_ascii=False))
        return

    try:
        status, headers = call_linkedin(payload, access_token)
        url = parse_post_response(status, headers)
    except RuntimeError as e:
        print(str(e))
        sys.exit(1)

    print('Publicado: {}'.format(url))


if __name__ == '__main__':
    main()
