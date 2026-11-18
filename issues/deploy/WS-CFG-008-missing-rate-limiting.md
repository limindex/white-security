---
id: WS-CFG-008
title: Missing Rate Limiting (요청 횟수·크기 제한 없음)
slug: missing-rate-limiting
group: deploy
severity: medium
site_only: false
depth: brief
languages: [laravel, fastapi, nginx]
maps:
  owasp2025: []
  api2023: [API4]
  llm2026: []
  agentic2026: []
  asvs5: [V2.4.1, V15.2.2]
  cwe: [CWE-770, CWE-400, CWE-799]
  kr_guide: []
  kr_ciip: [AU]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel 11 이상, FastAPI, Nginx, Next.js Route Handler·Server Action, 공개 API를 운영하는 모든 서비스]
severity_reason: "영향: 서버·DB 과부하로 서비스 중단, 문자·메일·외부 API 요금 폭증 · 악용: 반복 요청 스크립트 하나로 가능"
sources:
  - title: "OWASP API Security Top 10 2023 - API4 Unrestricted Resource Consumption"
    url: https://owasp.org/API-Security/editions/2023/en/0xa4-unrestricted-resource-consumption/
    year: 2023
  - title: "Laravel 12.x - Routing (Rate Limiting)"
    url: https://laravel.com/docs/12.x/routing
    year: 2026
  - title: "slowapi - Rate limiting for Starlette and FastAPI"
    url: https://slowapi.readthedocs.io/en/latest/
    year: 2026
  - title: "nginx - ngx_http_limit_req_module·client_max_body_size"
    url: https://nginx.org/en/docs/http/ngx_http_limit_req_module.html
    year: 2026
  - title: OWASP Denial of Service Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Denial_of_Service_Cheat_Sheet.html
    year: 2026
---

## 요약

API 전체에 요청 횟수 제한(rate limiting)·본문 크기·페이지 크기·처리 시간 상한이 없어, 한 사람이 초당 수천 번 부르거나 아주 큰 본문·`per_page=1000000`을 보내 서버·DB·요금을 바닥내는 문제. Laravel 11 이후 기본 골격은 api 경로에 횟수 제한을 자동으로 걸지 않음(`throttleApi()`를 불러야 붙음).

## 대응 방법

```text
로그인 없이 열리는 경로와 무거운 작업(보고서·내보내기·파일 처리·문자·메일 발송) 경로를 찾아 횟수·크기 제한이 있는지 표로 보여 줘.
API 전체에 기본 횟수 제한을 넣고 무거운 경로에는 더 낮은 한도를, 페이지 크기·본문 크기·처리 시간에는 상한을 넣어 줘.
여러 서버·워커로 돌면 카운터를 Redis 같은 공용 저장소에 두게 해 줘.
```

- 같은 API를 1분에 수백 번 부르면 HTTP 429가 나와야 한다.
- `per_page=1000000`이나 아주 큰 본문을 보내면 거절되거나 상한까지만 처리돼야 한다.

## 점검 항목

- API 전체에 사용자·IP별 기본 횟수 제한을 걸어야 한다.
- 문자·메일·외부 API를 부르는 무거운 경로에는 더 낮은 한도를 둬야 한다.
- 페이지 크기 인자에는 최댓값(예: 100)을 정해야 한다.
- 요청 본문 크기와 외부 호출·쿼리 시간에 상한을 둬야 한다.
- 제한을 넘으면 429와 다시 시도할 시간을 알려야 한다.
- 여러 서버·워커면 카운터를 공용 저장소에 둬야 한다.

## 탐지 규칙

- **찾는 곳**: `RateLimiter::for(`, `throttleApi(`, `throttle:`, `slowapi`, `limit_req_zone`, `client_max_body_size`, 그 밖에 per_page·limit 인자를 그대로 쓰는 paginate·쿼리, Next.js bodySizeLimit, 문자·메일·내보내기 핸들러, 외부 호출의 timeout.
- **검사 1 — 횟수 제한 없음**: 지적할 때: 로그인 없이 열리는 API나 무거운 작업(보고서·내보내기·파일 처리·문자·메일 발송)에 앱(`throttle:`, slowapi, 미들웨어)이나 앞단(nginx `limit_req`, 호스팅 방화벽) 어디에도 횟수 제한이 보이지 않는 경우. Laravel 11 이상에서 `throttleApi()`나 `throttle:` 없이 api 경로를 여는 경우. / 지적하지 않을 때: 앞단 설정에 같은 경로의 제한이 보이는 경우(설정이 저장소 밖이면 "확인 필요"로 표시).
- **검사 2 — 크기·개수·시간 상한 없음**: 지적할 때: 페이지 크기 인자를 상한 없이 쿼리에 넣는 경우, `client_max_body_size 0`(크기 검사 끔), 배열·문자열 입력의 길이 제한이 없는 경우, 외부 호출에 timeout이 없는 경우. / 지적하지 않을 때: `min(…, 100)`·`le=100` 같은 상한, 검증 규칙의 `max:`가 있는 경우.
- **등급**: 위험: 요금이 드는 호출(문자·메일·결제·외부 API)이나 무거운 작업이 제한 없이 열린 경우. / 주의: API 전체 기본 한도 없음, 페이지 크기 상한 없음. / 참고: 초과 응답이 429가 아님, 여러 서버인데 메모리 카운터 사용.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[주의] WS-CFG-008 routes/api.php:14 — per_page 를 그대로 paginate 에 넣어 한 번에 수십만 행 조회 가능 → min(per_page, 100) 상한과 throttleApi() 추가`
- **검토 주의**: 로그인 시도 제한은 WS-AUTH-003, AI 호출 비용은 WS-LLM-004, 크롤러 대량 수집은 WS-BOT-001에서 자세히 다룬다. 프록시·CDN 뒤에서 IP로 셀 때는 신뢰하는 프록시가 넣은 주소만 쓰게 설정돼 있는지(Laravel TrustProxies, uvicorn `--forwarded-allow-ips`, nginx real_ip) 함께 봐야 한다.
- **고칠 방법**: 앱이나 앞단 한곳에 기본 횟수 제한을 넣고, 무거운 경로·페이지 크기·본문 크기·처리 시간에 각각 상한을 둬야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// bootstrap/app.php — Laravel 11+ 기본 골격: api 경로에 횟수 제한 없음
->withMiddleware(function (Middleware $middleware): void {
    //
})

// routes/api.php
Route::get('/products', fn (Request $r) => Product::paginate($r->integer('per_page', 20)));  // per_page=1000000 가능
Route::post('/reports', [ReportController::class, 'store']);   // 무거운 작업, 제한 없음
```

✓ 안전 코드

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    RateLimiter::for('api', fn (Request $r) =>
        Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
    RateLimiter::for('heavy', fn (Request $r) =>
        Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));
}

// bootstrap/app.php — api 경로 전체에 'api' 한도
->withMiddleware(function (Middleware $middleware): void {
    $middleware->throttleApi();
})

// routes/api.php
Route::get('/products', fn (Request $r) =>
    Product::paginate(min(max($r->integer('per_page', 20), 1), 100)));   // 페이지 크기 상한
Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:heavy');
// 서버가 여럿이면 캐시 저장소를 Redis 로(카운터 공유), 본문 크기는 웹 서버 client_max_body_size 로
```

### fastapi

✗ 취약 코드

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

✓ 안전 코드

```python
from fastapi import FastAPI, Query, Request
from pydantic import BaseModel, Field
from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.errors import RateLimitExceeded
from slowapi.middleware import SlowAPIMiddleware
from slowapi.util import get_remote_address

class ReportIn(BaseModel):
    title: str = Field(max_length=200)

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

### nginx

✗ 취약 코드

```nginx
server {
    listen 443 ssl;
    server_name api.example.com;
    client_max_body_size 0;            # 0 = 본문 크기 검사 끔

    location /api/ {
        proxy_pass http://app:8000;    # 횟수 제한·시간 제한 없음
    }
}
```

✓ 안전 코드

```nginx
# conf.d 파일은 http 블록 안에 들어간다
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=heavy:10m rate=5r/m;

server {
    listen 443 ssl;
    server_name api.example.com;
    client_max_body_size 2m;           # 업로드 경로만 따로 크게
    client_body_timeout 10s;
    limit_req_status 429;              # 기본값은 503

    location /api/ {
        limit_req zone=api burst=20 nodelay;
        proxy_read_timeout 30s;
        proxy_pass http://app:8000;
    }
    location = /api/reports {
        limit_req zone=heavy burst=2 nodelay;
        proxy_pass http://app:8000;
    }
}
# CDN·로드밸런서 뒤라면 real_ip 모듈로 실제 접속 IP 를 복원한 뒤 센다
```
