<!-- generated from white-security issues; do not edit -->
# WS-PRV-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
import hashlib
from django.db import models

class Customer(models.Model):
    passport_no = models.CharField(max_length=20)            # 평문
    passport_hash = models.CharField(max_length=64)          # 키 없는 SHA-256: 번호를 대입하면 되돌릴 수 있음

def save_customer(passport_no: str) -> Customer:
    return Customer.objects.create(passport_no=passport_no,
                                   passport_hash=hashlib.sha256(passport_no.encode()).hexdigest())
```

✓ 안전 코드 (safe example)

```python
# privacy/crypto.py — 키는 환경 변수에서(settings 가 os.environ 으로 읽음), 32바이트 Base64
import base64, hashlib, hmac, os
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from django.conf import settings

_KEY = base64.b64decode(settings.FIELD_ENCRYPTION_KEY)
_INDEX_KEY = base64.b64decode(settings.FIELD_INDEX_KEY)     # 검색 전용 별도 키

def encrypt(plain: str, aad: bytes) -> str:
    nonce = os.urandom(12)                                    # 암호화마다 새 nonce
    return base64.b64encode(nonce + AESGCM(_KEY).encrypt(nonce, plain.encode(), aad)).decode()

def decrypt(token: str, aad: bytes) -> str:
    raw = base64.b64decode(token)
    return AESGCM(_KEY).decrypt(raw[:12], raw[12:], aad).decode()   # 변조되면 InvalidTag

def blind_index(plain: str) -> str:
    normalized = "".join(plain.split()).replace("-", "")     # 하이픈·공백을 빼야 같은 번호가 같은 값이 됨
    return hmac.new(_INDEX_KEY, normalized.encode(), hashlib.sha256).hexdigest()

# models.py
class Customer(models.Model):
    passport_no_enc = models.TextField()
    passport_no_idx = models.CharField(max_length=64, db_index=True)

AAD = b"customer.passport_no"                                 # 다른 컬럼으로 옮겨 붙인 암호문은 거절
c = Customer.objects.create(passport_no_enc=encrypt(no, AAD), passport_no_idx=blind_index(no))
```
