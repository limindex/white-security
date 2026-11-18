<!-- generated from white-security issues; do not edit -->
# WS-CRY-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
import hashlib, random, requests
from Crypto.Cipher import AES   # pycryptodome

def encrypt(data: bytes, key: bytes) -> bytes:
    return AES.new(key, AES.MODE_ECB).encrypt(data.ljust(32))   # 같은 평문 → 같은 암호문

def reset_token() -> str:
    return "".join(random.choice("0123456789abcdef") for _ in range(32))   # 예측 가능

def sign(body: bytes) -> str:
    return hashlib.md5(SECRET + body).hexdigest()               # MD5, 비밀값 앞붙이기

requests.post("https://pay.example.com/api", json=payload, verify=False)   # 인증서 검증 끔
```

✓ 안전 코드 (safe example)

```python
import hashlib, hmac, os, secrets, requests
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

KEY = bytes.fromhex(os.environ["DATA_KEY_HEX"])   # 32바이트(AES-256), 키는 KMS·실행 환경에서
SIGN_KEY = os.environ["SIGN_KEY"].encode()

def encrypt(data: bytes, aad: bytes = b"") -> bytes:
    nonce = os.urandom(12)                         # 매번 새 nonce
    return nonce + AESGCM(KEY).encrypt(nonce, data, aad)

def decrypt(blob: bytes, aad: bytes = b"") -> bytes:
    return AESGCM(KEY).decrypt(blob[:12], blob[12:], aad)   # 변조되면 InvalidTag

def reset_token() -> str:
    return secrets.token_urlsafe(32)               # 256비트

def sign(body: bytes) -> str:
    return hmac.new(SIGN_KEY, body, hashlib.sha256).hexdigest()   # 비교는 hmac.compare_digest

requests.post("https://pay.example.com/api", json=payload, timeout=10)   # verify 기본값(True) 유지
```
