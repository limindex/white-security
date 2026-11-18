---
id: WS-CFG-002
title: CORS Misconfiguration (CORS 설정 오류)
slug: cors-misconfiguration
group: deploy
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs, fastapi]
maps:
  owasp2025: [A02, A01]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  asvs5: [V3.4.2]
  cwe: [CWE-942, CWE-346]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel(Sanctum SPA 포함), Next.js Route Handler, FastAPI·Starlette, 쿠키·토큰으로 인증하는 모든 API]
severity_reason: "영향: 로그인한 방문자의 개인정보·계정 데이터를 다른 사이트가 읽어 감 · 악용: 방문자가 공격자 페이지를 여는 것만으로 가능"
sources:
  - title: "OWASP ASVS 5.0 - V3.4 Browser Security Mechanism Headers (3.4.2 CORS)"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "MDN - Cross-Origin Resource Sharing (CORS)"
    url: https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS
    year: 2026
  - title: "CWE-942: Permissive Cross-domain Security Policy with Untrusted Domains"
    url: https://cwe.mitre.org/data/definitions/942.html
    year: 2025
  - title: "Starlette CORSMiddleware 소스 (allow_origins=['*'] + allow_credentials=True 일 때 Origin 반사)"
    url: https://github.com/encode/starlette/blob/master/starlette/middleware/cors.py
    year: 2026
  - title: "fruitcake/php-cors CorsService 소스 (Laravel config/cors.php 처리)"
    url: https://github.com/fruitcake/php-cors/blob/master/src/CorsService.php
    year: 2026
  - title: "FastAPI - CORS (Cross-Origin Resource Sharing)"
    url: https://fastapi.tiangolo.com/tutorial/cors/
    year: 2026
---

## 요약

CORS(Cross-Origin Resource Sharing, 다른 출처의 웹페이지가 내 API 응답을 읽도록 허락하는 브라우저 규칙)를 `*`나 요청 Origin을 그대로 돌려주는 방식으로 열고 쿠키 인증(credentials)까지 허용해, 아무 사이트나 로그인한 방문자 이름으로 내 API를 불러 응답을 읽어 가는 문제. CORS는 브라우저에 읽기를 허락하는 장치일 뿐 서버의 인증·권한 확인을 대신하지 않음.

## 대응 방법

```text
CORS 설정(Laravel config/cors.php, Next.js 응답 헤더·proxy, FastAPI CORSMiddleware, Express cors)을 모두 찾아 허용 출처와 credentials 값을 표로 보여 줘.
쿠키·인증 헤더를 쓰는 API는 허용 출처를 실제 프런트엔드 주소 목록으로 바꾸고, 요청 Origin 을 그대로 돌려주는 코드는 목록 비교로 바꿔 줘.
```

- `Origin: https://evil.example` 헤더를 붙여 내 API를 부르면 응답에 `Access-Control-Allow-Origin`이 없어야 한다.
- `Access-Control-Allow-Credentials: true`인 응답의 허용 출처는 목록 속 주소 하나여야 한다.

## 점검 항목

- 쿠키·인증 헤더를 쓰는 API는 허용 출처를 정확한 주소 목록으로 정해야 한다.
- 요청 `Origin` 값을 검사 없이 허용 출처 헤더에 되돌려 주지 않아야 한다.
- 출처 정규식은 앞뒤를 고정하고 점(`.`)을 이스케이프해야 한다.
- `null` 출처, 운영의 `http://`·`localhost` 출처는 허용하지 않아야 한다.
- 출처마다 응답이 달라지면 `Vary: Origin`을 붙여야 한다.
- CORS와 별개로 API는 서버에서 인증·권한을 확인해야 한다.

## 탐지 규칙

- **찾는 곳**: `Access-Control-Allow-Origin`, `CORSMiddleware`, `allow_credentials=True`, `supports_credentials`, 그 밖에 config/cors.php의 allowed_origins·allowed_origins_patterns, FastAPI allow_origin_regex, Express cors() 옵션, next.config의 headers(), proxy·middleware에서 붙이는 응답 헤더.
- **검사 1 — 출처를 가리지 않는 허용**: 지적할 때: credentials를 허용하면서 허용 출처가 `*`인 경우(Laravel `config/cors.php`와 Starlette·FastAPI는 이 조합에서 요청 Origin을 그대로 돌려줌). 요청 `Origin` 헤더를 목록 비교 없이 `Access-Control-Allow-Origin`에 넣는 경우(Express `origin: true` 포함). / 지적하지 않을 때: credentials 없이 `*`로 여는 공개 API(로그인 없이 누구나 보는 데이터)인 경우.
- **검사 2 — 느슨한 출처 비교**: 지적할 때: `endsWith("example.com")`·`includes(`·이스케이프하지 않은 점·앞뒤를 고정하지 않은 `preg_match`·정규식으로 출처를 비교하는 경우, 운영 목록에 `null`·`http://`·`localhost` 출처가 있는 경우. / 지적하지 않을 때: 정확히 일치하는 목록 비교나 `#^https://app\.example\.com$#`처럼 고정된 정규식인 경우.
- **등급**: 위험: credentials 허용과 아무 출처 허용(`*` 반사·Origin 되돌려 주기)이 함께 있는 경우. / 주의: 느슨한 출처 비교, 운영 목록의 `null`·`http://`·`localhost`. / 참고: `Vary: Origin` 없음, 쓰지 않는 메서드·헤더까지 `*`로 허용.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CFG-002 main.py:9 — CORSMiddleware 에 allow_origins=["*"]·allow_credentials=True 라 아무 사이트가 로그인 사용자 응답을 읽음 → allow_origins 를 프런트엔드 주소 목록으로`
- **검토 주의**: FastAPI(Starlette)의 `allow_origin_regex`는 전체 일치로 비교하므로 앞뒤 고정이 없다고 지적하지 않아야 한다. 화면과 API가 같은 출처(같은 도메인·포트)이면 CORS 헤더 자체가 필요 없으므로 지우라고 안내해야 한다.
- **고칠 방법**: 허용 출처를 환경 변수의 정확한 주소 목록으로 바꾸고, credentials가 필요 없는 공개 API만 `*`를 써야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// config/cors.php — Sanctum SPA 쿠키 로그인을 쓰려고 credentials 만 켬
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],                       // credentials 와 함께면 요청 Origin 을 그대로 돌려줌
    'allowed_origins_patterns' => ['#example.com#'],  // evil-example.com.attacker.example 도 통과
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

✓ 안전 코드

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    // 예: CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
    'allowed_origins' => array_values(array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))),
    'allowed_origins_patterns' => [],                 // 꼭 필요하면 '#^https://app\.example\.com$#' 처럼 고정
    'allowed_headers' => ['Content-Type', 'X-XSRF-TOKEN', 'X-Requested-With'],
    'supports_credentials' => true,
];
// 바꾼 뒤 운영에서 php artisan config:cache
```

### nextjs

✗ 취약 코드

```ts
// app/api/me/route.ts — 요청 Origin 을 그대로 허용
export async function GET(req: Request) {
  const origin = req.headers.get("origin") ?? "*";
  const user = await getCurrentUser();
  return Response.json(user, {
    headers: {
      "Access-Control-Allow-Origin": origin,
      "Access-Control-Allow-Credentials": "true",
    },
  });
}
```

✓ 안전 코드

```ts
// lib/cors.ts
const ALLOWED = new Set((process.env.CORS_ALLOWED_ORIGINS ?? "").split(",").filter(Boolean));

export function corsHeaders(req: Request): Record<string, string> {
  const origin = req.headers.get("origin");
  const h: Record<string, string> = { Vary: "Origin" };
  if (origin && ALLOWED.has(origin)) {               // 정확히 같은 주소만
    h["Access-Control-Allow-Origin"] = origin;
    h["Access-Control-Allow-Credentials"] = "true";
  }
  return h;
}

// app/api/me/route.ts — CORS 와 별개로 서버에서 로그인 확인
export async function GET(req: Request) {
  const user = await requireUser();                  // 앱의 로그인 확인 함수
  return Response.json({ id: user.id, name: user.name }, { headers: corsHeaders(req) });
}
export function OPTIONS(req: Request) {              // 사전 요청(preflight)
  return new Response(null, { status: 204, headers: { ...corsHeaders(req),
    "Access-Control-Allow-Methods": "GET", "Access-Control-Allow-Headers": "Content-Type" } });
}
```

### fastapi

✗ 취약 코드

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

✓ 안전 코드

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
