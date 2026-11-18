<!-- generated from white-security issues; do not edit -->
# WS-SESS-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
import jwt

def issue(user) -> str:
    return jwt.encode({"sub": str(user.id)}, "secret", algorithm="HS256")  # 짧은 고정 키, 만료 없음

def current_user(authorization: str = Header()):
    token = authorization.removeprefix("Bearer ")
    claims = jwt.decode(token, options={"verify_signature": False})       # 서명을 보지 않는다
    return get_user(claims["sub"])
```

✓ 안전 코드 (safe example)

```python
import os
from datetime import datetime, timedelta, timezone
import jwt

KEY = os.environ["JWT_SECRET"]                  # 32바이트 이상 무작위 값, 코드 밖
ISS, AUD = "https://api.example.com", "example-web"

def issue(user) -> str:
    now = datetime.now(timezone.utc)
    return jwt.encode({"sub": str(user.id), "iss": ISS, "aud": AUD, "iat": now,
                       "exp": now + timedelta(minutes=15)}, KEY, algorithm="HS256")

def current_user(authorization: str = Header()):
    try:
        claims = jwt.decode(authorization.removeprefix("Bearer "), KEY, algorithms=["HS256"],
                            audience=AUD, issuer=ISS, options={"require": ["exp", "iss", "aud", "sub"]})
    except jwt.PyJWTError:
        raise HTTPException(401)
    return get_user(claims["sub"])
```
