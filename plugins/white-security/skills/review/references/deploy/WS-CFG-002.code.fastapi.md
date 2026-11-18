<!-- generated from white-security issues; do not edit -->
# WS-CFG-002 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI()
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,   # 이 조합이면 Starlette 가 요청 Origin 을 그대로 돌려줌
    allow_methods=["*"],
    allow_headers=["*"],
)
```

✓ 안전 코드 (safe example)

```python
import os
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

# 예: CORS_ORIGINS=https://app.example.com,https://admin.example.com
ORIGINS = [o for o in os.environ.get("CORS_ORIGINS", "").split(",") if o]

app = FastAPI()
app.add_middleware(
    CORSMiddleware,
    allow_origins=ORIGINS,            # 정확한 주소 목록, "*" 금지
    allow_credentials=True,
    allow_methods=["GET", "POST"],
    allow_headers=["Content-Type", "Authorization"],
)
```
