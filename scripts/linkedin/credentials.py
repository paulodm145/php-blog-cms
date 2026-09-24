import json
import os
from datetime import datetime, timezone

CREDENTIALS_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), '.credentials.json')


def load(path=CREDENTIALS_PATH):
    if not os.path.exists(path):
        raise FileNotFoundError(
            'Nenhuma credencial salva em {}. Rode scripts/linkedin/authorize.py primeiro.'.format(path)
        )
    with open(path, encoding='utf-8') as f:
        return json.load(f)


def save(data, path=CREDENTIALS_PATH):
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2)
    os.chmod(path, 0o600)


def is_token_expired(creds, now=None):
    now = now or datetime.now(timezone.utc)
    expires_at = datetime.fromisoformat(creds['expires_at'])
    return now >= expires_at
