<!-- generated from white-security issues; do not edit -->
# WS-SEC-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
# settings.py (저장소에 커밋됨)
import os

DATABASE_URL = "postgresql://app:Winter2026!@db.internal:5432/shop"
PAYMENT_SECRET_KEY = "live_secret_9f8a7c6d5e4b3a2f1e0d"
JWT_SECRET = os.getenv("JWT_SECRET", "dev-secret")  # 없으면 모두가 아는 값으로 돈다
```

✓ 안전 코드 (safe example)

```python
# settings.py
import os

def required(name: str) -> str:
    value = os.environ.get(name)
    if not value:
        raise RuntimeError(f"{name} 환경 변수가 없습니다")  # 기본값으로 대신하지 않는다
    return value

DATABASE_URL = required("DATABASE_URL")
PAYMENT_SECRET_KEY = required("PAYMENT_SECRET_KEY")
JWT_SECRET = required("JWT_SECRET")

# .gitignore 에 .env 를 넣고, 저장소에는 값을 비운 .env.example 만 둔다.
# 이미 커밋된 값은 지금 서비스 관리 화면에서 새로 발급하고 옛 값은 폐기한다.
```
