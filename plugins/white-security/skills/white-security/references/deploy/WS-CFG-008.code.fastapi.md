<!-- generated from white-security issues; do not edit -->
# WS-CFG-008 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
from fastapi import FastAPI

app = FastAPI()

@app.post("/api/reports")
async def create_report(body: dict):              # 크기·횟수 제한 없음
    return await build_report(body)

@app.get("/api/items")
async def items(limit: int = 20):
    return await db.fetch_items(limit=limit)      # limit=1000000 가능
```

✓ 안전 코드 (safe example)

```python
from fastapi import FastAPI, Query, Request
from pydantic import BaseModel, Field
from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.errors import RateLimitExceeded
from slowapi.middleware import SlowAPIMiddleware
from slowapi.util import get_remote_address

class ReportIn(BaseModel):
    title: str = Field(max_length=200)

# get_remote_address 는 접속 IP 를 쓴다. 프록시 뒤면 uvicorn --proxy-headers --forwarded-allow-ips=<프록시 IP> 로 실행
limiter = Limiter(key_func=get_remote_address, default_limits=["60/minute"],
                  storage_uri="redis://redis:6379")   # 여러 워커가 같은 카운터를 쓰게
app = FastAPI()
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)
app.add_middleware(SlowAPIMiddleware)                 # 모든 경로에 기본 한도

@app.post("/api/reports")
@limiter.limit("5/minute")                            # 무거운 경로는 더 낮게(request 인자 필수)
async def create_report(request: Request, body: ReportIn):
    return await build_report(body)

@app.get("/api/items")
async def items(limit: int = Query(20, ge=1, le=100)):
    return await db.fetch_items(limit=limit)
```
