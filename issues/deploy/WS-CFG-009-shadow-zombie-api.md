---
id: WS-CFG-009
title: Shadow · Zombie API (잊힌 API·옛 버전 엔드포인트)
slug: shadow-zombie-api
group: deploy
severity: medium
site_only: false
depth: brief
languages: [fastapi, laravel]
maps:
  owasp2025: [A02]
  api2023: [API9]
  llm2026: []
  agentic2026: []
  asvs5: [V13.4.5, V4.3.2, V4.1.4, V15.2.3]
  cwe: [CWE-912, CWE-284]
  kr_guide: []
  kr_ciip: [WM]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [FastAPI, Laravel, Next.js Route Handler, GraphQL, 버전을 나눠 운영하는 모든 API·스테이징 서버]
severity_reason: "영향: 고친 인증·권한 검사를 옛 경로로 우회, 숨은 관리·테스트 기능 실행 · 악용: API 문서·앱 코드·옛 앱 버전에서 경로를 찾아 직접 호출"
sources:
  - title: "OWASP API Security Top 10 2023 - API9 Improper Inventory Management"
    url: https://owasp.org/API-Security/editions/2023/en/0xa9-improper-inventory-management/
    year: 2023
  - title: "OWASP ASVS 5.0 - V13.4.5 문서·모니터링 엔드포인트, V4.3.2 GraphQL introspection"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "FastAPI - Conditional OpenAPI (openapi_url 을 비우면 문서 화면도 꺼짐)"
    url: https://fastapi.tiangolo.com/how-to/conditional-openapi/
    year: 2026
  - title: "Laravel 12.x - Routing (route:list)"
    url: https://laravel.com/docs/12.x/routing
    year: 2026
  - title: "RFC 8594 - The Sunset HTTP Header Field"
    url: https://www.rfc-editor.org/rfc/rfc8594
    year: 2019
---

## 요약

새 버전(`/v2`)을 만들고도 옛 `/v1`·테스트·임시 엔드포인트와 API 문서(`/docs`·`/openapi.json`)를 운영에 그대로 둬, 새 버전에만 넣은 인증·권한 검사를 옛 경로로 우회하거나 숨은 기능이 드러나는 문제(Shadow API는 목록에 없는 API, Zombie API는 끝냈어야 할 옛 API). 있는 줄 모르는 API는 점검과 업데이트에서도 빠짐.

## 대응 방법

```text
이 프로젝트의 모든 API 경로를 메서드·버전·인증 방식·용도와 함께 표로 뽑아 줘(php artisan route:list, FastAPI app.routes 등).
쓰지 않는 옛 버전·테스트 경로는 지우거나 로컬에서만 등록되게 하고, 남기는 옛 버전은 새 버전과 같은 인증·권한 검사를 쓰게 해 줘.
운영에서 /docs·/redoc·/openapi.json·GraphQL introspection 이 열려 있으면 꺼 줘.
```

- 공개 API가 아니라면 운영의 `/docs`·`/redoc`·`/openapi.json`이 404여야 한다.
- 뽑은 경로 목록의 모든 항목에 용도와 인증 방식이 적혀 있어야 한다.

## 점검 항목

- API 목록(경로·메서드·버전·인증·담당)을 두고 배포 때마다 갱신해야 한다.
- 옛 버전은 종료일을 정하고, 남은 동안 새 버전과 같은 검사를 써야 한다.
- 테스트·임시·디버그 경로는 운영에 등록되지 않아야 한다.
- 공개 API가 아니면 운영의 API 문서·스키마·introspection을 꺼야 한다.
- 스테이징·개발 서버는 인터넷에 열지 않고 운영 데이터를 쓰지 않아야 한다.
- 쓰지 않는 HTTP 메서드(TRACE 등)는 막아야 한다.

## 탐지 규칙

- **찾는 곳**: `openapi_url`, `docs_url`, `introspection`, `/api/v1`, `route:list`, 그 밖에 v1·legacy·old·test·tmp 이름의 라우터 파일과 경로, Swagger UI 라우트, 스테이징 도메인 설정.
- **검사 1 — 검사가 빠진 옛 경로·남은 테스트 경로**: 지적할 때: 같은 기능의 `/v1`·`/v2` 라우트가 함께 있는데 옛 쪽에만 인증·권한 미들웨어나 입력 검증이 빠진 경우, `test`·`debug`·`old`·`legacy`·`tmp` 이름의 라우트가 환경 확인 없이 운영 라우트 파일에 등록된 경우. / 지적하지 않을 때: 옛 버전이 같은 미들웨어 그룹·의존성·핸들러를 쓰는 경우, 로컬 환경에서만 등록되는 경우.
- **검사 2 — 운영에 열린 API 문서**: 지적할 때: FastAPI 기본값(`/docs`·`/redoc`·`/openapi.json`)이 환경 구분 없이 켜진 경우, Swagger UI 라우트·GraphQL introspection이 운영에서도 켜진 경우. / 지적하지 않을 때: 외부 개발자용으로 공개하는 API 문서, 환경 변수로 운영에서 끄는 경우.
- **등급**: 위험: 옛 경로가 인증·권한 검사 없이 같은 데이터를 돌려주는 경우, 데이터를 지우거나 바꾸는 테스트 경로가 운영에 열린 경우. / 주의: 운영에 남은 테스트·관리 경로, 내부 API 문서 공개. / 참고: API 목록 없음, 종료 일정 없는 옛 버전.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CFG-009 routes/api.php:8 — v1 주문 조회가 auth 미들웨어 밖이라 v2 의 권한 검사를 우회해 남의 주문 조회 → v1 도 같은 auth·can:view 그룹으로 옮기고 종료일 공지`
- **검토 주의**: 경로별 권한 검사 자체의 결함은 WS-AUTH-001·WS-AUTH-002에서 다루므로 여기서는 버전·목록·문서 노출만 봐야 한다. 경로를 지우는 일은 앱·외부 연동이 아직 쓰는지 사용자에게 확인한 뒤 해야 한다.
- **고칠 방법**: 경로 목록을 뽑아 쓰지 않는 경로를 지우고, 남는 옛 버전은 새 버전과 같은 검사로 묶은 뒤 종료일을 알려야 한다.

## 코드 예시

### fastapi

✗ 취약 코드

```python
app = FastAPI()                                   # /docs·/redoc·/openapi.json 이 운영에도 열림

v1 = APIRouter(prefix="/api/v1")                  # 옛 앱용, 권한 검사 없음
@v1.get("/users/{user_id}")
async def get_user_v1(user_id: int):
    return await db.get_user(user_id)

v2 = APIRouter(prefix="/api/v2")
@v2.get("/users/{user_id}")
async def get_user_v2(user_id: int, me: User = Depends(require_user)):
    if me.id != user_id:
        raise HTTPException(404)
    return await db.get_user(user_id)

app.include_router(v1)
app.include_router(v2)
```

✓ 안전 코드

```python
import os

IS_PROD = os.environ.get("APP_ENV") == "production"
app = FastAPI(openapi_url=None if IS_PROD else "/openapi.json")   # None 이면 /docs·/redoc 도 꺼짐

def sunset_v1(response: Response):
    response.headers["Sunset"] = "Wed, 31 Mar 2027 00:00:00 GMT"   # 종료 예정일 안내

async def read_user(user_id: int, me: User = Depends(require_user)):
    if me.id != user_id and not me.is_admin:
        raise HTTPException(404)
    return await db.get_user_public(user_id)

# 옛 버전도 종료일까지 같은 함수·같은 검사를 쓴다
v1 = APIRouter(prefix="/api/v1", dependencies=[Depends(sunset_v1)])
v2 = APIRouter(prefix="/api/v2")
v1.add_api_route("/users/{user_id}", read_user, methods=["GET"])
v2.add_api_route("/users/{user_id}", read_user, methods=["GET"])
app.include_router(v1)
app.include_router(v2)
```

### laravel

✗ 취약 코드

```php
// routes/api.php
Route::prefix('v1')->group(function () {             // 옛 앱용, 인증 없이 남아 있음
    Route::get('/orders/{order}', [V1\OrderController::class, 'show']);
});
Route::prefix('v2')->middleware('auth:sanctum')->group(function () {
    Route::get('/orders/{order}', [OrderController::class, 'show'])->can('view', 'order');
});
Route::get('/test/reset-db', [DevController::class, 'reset']);   // 개발 중 만든 경로
```

✓ 안전 코드

```php
// routes/api.php — 버전과 관계없이 같은 인증·권한
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('v2')->group(function () {
        Route::get('/orders/{order}', [OrderController::class, 'show'])->can('view', 'order');
    });
    // v1 은 2027-03-31 종료 예정, 남는 동안 같은 컨트롤러·같은 정책
    Route::prefix('v1')->group(function () {
        Route::get('/orders/{order}', [OrderController::class, 'show'])->can('view', 'order');
    });
});

if (app()->environment('local')) {
    Route::get('/test/reset-db', [DevController::class, 'reset']);   // 로컬에서만 등록
}
// 배포 전 php artisan route:list --path=api 로 목록을 뽑아 API 목록과 대조한다
```
