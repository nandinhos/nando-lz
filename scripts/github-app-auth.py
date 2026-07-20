#!/usr/bin/env python3
"""
github-app-auth.py — Helper de autenticação do agente via GitHub App.

Gera um installation token (válido por 1h) que o agente usa pra:
- Abrir PRs
- Comentar em PRs
- Fazer merge (se label auto-merge)
- Criar issues

Setup (uma vez):
  1. Criar GitHub App em https://github.com/settings/apps/new
     - Nome: nando-lz-agent
     - Permissions: Contents (R/W), Pull requests (R/W), Issues (R/W)
     - Subscribe: Push, Pull request
  2. Install no repo nandinhos/nando-lz
  3. Baixar private key (.pem) → ~/.config/nando-lz-agent.pem (chmod 600)
  4. Definir env vars: GITHUB_APP_ID, GITHUB_APP_INSTALLATION_ID, GITHUB_APP_PRIVATE_KEY_PATH

Uso:
  ./scripts/github-app-auth.py                    # imprime token
  eval $(./scripts/github-app-auth.py --export)   # exporta GH_TOKEN pro shell
  ./scripts/github-app-auth.py --check            # valida que token funciona

Referência: PRD §11.3 (auto-update autônomo)
"""

import argparse
import os
import sys
import time
import urllib.request
import urllib.error
import json

# ─── Validação de env vars ────────────────────────────────────────────────────

def require_env(name: str) -> str:
    value = os.environ.get(name)
    if not value:
        print(f"ERRO: variável de ambiente {name} não definida", file=sys.stderr)
        print(f"  Defina: export {name}=<valor>", file=sys.stderr)
        sys.exit(2)
    return value


def load_private_key(path: str) -> str:
    if not os.path.exists(path):
        print(f"ERRO: chave privada não encontrada em {path}", file=sys.stderr)
        sys.exit(2)
    with open(path, "r") as f:
        return f.read()


# ─── JWT generation (HS256-style com RSA) ────────────────────────────────────

def base64url_encode(data: bytes) -> str:
    import base64
    return base64.urlsafe_b64encode(data).rstrip(b"=").decode("ascii")


def make_jwt(app_id: str, private_key: str) -> str:
    """Gera JWT assinado com a chave privada do App (válido por 10 min)."""
    try:
        import jwt  # PyJWT
    except ImportError:
        print("ERRO: PyJWT não instalado. Rode: pip install pyjwt cryptography", file=sys.stderr)
        sys.exit(2)

    now = int(time.time())
    payload = {
        "iat": now - 60,        # issued at (60s no passado pra absorver clock skew)
        "exp": now + 600,       # expires (10 min no futuro)
        "iss": app_id,          # issuer = App ID
    }
    return jwt.encode(payload, private_key, algorithm="RS256")


# ─── Installation token ───────────────────────────────────────────────────────

def get_installation_token(jwt_token: str, installation_id: str) -> dict:
    """Troca JWT por installation token (válido por 1h)."""
    url = f"https://api.github.com/app/installations/{installation_id}/access_tokens"
    req = urllib.request.Request(
        url,
        headers={
            "Authorization": f"Bearer {jwt_token}",
            "Accept": "application/vnd.github+json",
            "X-GitHub-Api-Version": "2022-11-28",
            "User-Agent": "nando-lz-agent",
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            return json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace")
        print(f"ERRO GitHub API {e.code}: {body[:500]}", file=sys.stderr)
        sys.exit(1)


# ─── Validação do token ───────────────────────────────────────────────────────

def check_token(token: str) -> None:
    """Valida que o token funciona e mostra a identidade do App."""
    req = urllib.request.Request(
        "https://api.github.com/user",
        headers={
            "Authorization": f"Bearer {token}",
            "Accept": "application/vnd.github+json",
            "User-Agent": "nando-lz-agent",
        },
    )
    with urllib.request.urlopen(req, timeout=15) as resp:
        data = json.loads(resp.read().decode("utf-8"))
        print(f"✓ Token válido para: {data.get('login')} ({data.get('html_url')})")
        print(f"  Tipo: {data.get('type')}")


# ─── Main ─────────────────────────────────────────────────────────────────────

def main() -> int:
    parser = argparse.ArgumentParser(
        description="Gera installation token do GitHub App nando-lz-agent"
    )
    parser.add_argument(
        "--export",
        action="store_true",
        help="exporta GH_TOKEN=<token> pro shell (eval $(... --export))",
    )
    parser.add_argument(
        "--check",
        action="store_true",
        help="valida que o token funciona (ping /user)",
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="imprime token + expiração em JSON",
    )
    args = parser.parse_args()

    app_id = require_env("GITHUB_APP_ID")
    installation_id = require_env("GITHUB_APP_INSTALLATION_ID")
    key_path = require_env("GITHUB_APP_PRIVATE_KEY_PATH")
    private_key = load_private_key(key_path)

    jwt_token = make_jwt(app_id, private_key)
    token_data = get_installation_token(jwt_token, installation_id)
    token = token_data["token"]
    expires_at = token_data["expires_at"]

    if args.check:
        check_token(token)
        return 0

    if args.json:
        print(json.dumps({
            "token": token,
            "expires_at": expires_at,
        }))
        return 0

    if args.export:
        # exporta pro shell — uso: eval $(./scripts/github-app-auth.py --export)
        print(f'export GH_TOKEN="{token}"')
        print(f'export GH_TOKEN_EXPIRES_AT="{expires_at}"')
        return 0

    # default: imprime só o token
    print(token)
    return 0


if __name__ == "__main__":
    sys.exit(main())
