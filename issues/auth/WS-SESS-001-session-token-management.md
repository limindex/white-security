---
id: WS-SESS-001
title: Broken Session Management (세션·토큰 관리 미흡)
slug: session-token-management
group: auth
severity: high
grade_hint: "서명 확인 없는 토큰, none·알고리즘 혼용, 세션 고정"
always_on: "`jwt.decode(` `decodeJwt(` → 토큰은 서명·알고리즘·만료를 검증하는 함수로만 믿어야 하고, 로그인 때 세션 ID를 새로 만들어야 한다."
site_only: false
depth: brief
languages: [laravel, nextjs, fastapi]
maps:
  owasp2025: [A07]
  api2023: [API2]
  llm2026: []
  agentic2026: []
  asvs5: [V7.2.4, V7.4.1, V9.1.1, V9.1.2, V9.2.1, V9.2.3, V3.3.1, V3.3.4]
  cwe: [CWE-384, CWE-347, CWE-613, CWE-1004, CWE-614]
  kr_guide: ["보안기능-10", "보안기능-12"]
  kr_ciip: [IS, CC]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js(jose), FastAPI(PyJWT), 세션 쿠키나 JWT로 로그인을 유지하는 모든 서비스]
severity_reason: "영향: 다른 사람 계정으로 로그인 상태 도용 · 악용: 직접 만든 토큰, 미리 심어 둔 세션 ID, 훔친 쿠키로 가능"
sources:
  - title: "RFC 8725 JSON Web Token Best Current Practices"
    url: https://www.rfc-editor.org/rfc/rfc8725
    year: 2020
  - title: OWASP Session Management Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
    year: 2026
  - title: "OWASP ASVS 5.0 - V7 Session Management·V9 Self-contained Tokens"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "PyJWT - Usage Examples (algorithms·audience·issuer·require)"
    url: https://pyjwt.readthedocs.io/en/stable/usage.html
    year: 2026
  - title: "panva/jose - jwtVerify"
    url: https://github.com/panva/jose
    year: 2026
  - title: "Laravel 12.x - Authentication (Regenerating the Session·Logging Out)"
    url: https://laravel.com/docs/12.x/authentication
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

JWT(서명이 붙은 로그인 토큰)를 서명·알고리즘·만료 확인 없이 받아들여 직접 만든 토큰으로 남의 계정이 되거나, 로그인 뒤 세션 ID를 바꾸지 않아 미리 심어 둔 세션이 그대로 로그인되는(Session Fixation, 세션 고정) 문제(진단가이드 이름: 부적절한 전자서명 확인). 세션 쿠키에 `HttpOnly`·`Secure`·`SameSite`가 빠지거나 로그아웃 뒤에도 서버 세션이 살아 있는 경우도 이 이슈에서 다룸.

## 대응 방법

```text
로그인·로그아웃, 세션·쿠키 설정, JWT 발급·검증 코드를 찾아 줘.
로그인 성공 때 세션 ID를 새로 만들고, 로그아웃 때 서버 세션을 지우게 해 줘.
JWT는 서명을 확인하는 함수로 읽고, 허용 알고리즘을 하나로 고정하고, exp·iss·aud를 검사하게 해 줘.
세션 쿠키는 HttpOnly·Secure·SameSite=Lax 이상으로 설정해 줘.
```

- 로그인 전과 후의 세션 쿠키 값이 달라야 한다.
- 서명 부분을 바꾼 토큰, `alg`를 `none`으로 바꾼 토큰, 만료된 토큰은 모두 401이 나와야 한다.

## 점검 항목

- 로그인·권한 상승 때 세션 ID를 새로 만들고, 로그아웃 때 서버 쪽 세션을 지워야 한다.
- JWT는 서명을 확인하는 함수로 읽어야 하고, 내용만 읽는 `decode`를 믿지 않아야 한다.
- 허용 알고리즘은 하나로 고정하고 `exp`·`iss`·`aud`를 검사해야 한다.
- 서명 키는 32바이트 이상 무작위 값으로 코드 밖에 둬야 한다(HS256 기준).
- 세션 쿠키는 `HttpOnly`·`Secure`·`SameSite`를 켜고, 로그인 토큰은 가능하면 `localStorage`를 피해야 한다.
- 세션에는 비활동·최대 유지 시간을 두고, 비밀번호 변경 때 다른 세션을 끊을 수 있어야 한다.

## 탐지 규칙

- **찾는 곳**: `jwt.decode(`, `decodeJwt(`, `jwtVerify(`, `ignoreExpiration`, `verify_signature`, `session_regenerate_id(`, 그 밖에 jsonwebtoken·PyJWT·jose 사용, config/session.php, 쿠키 옵션(httpOnly·secure·sameSite), localStorage 에 토큰 저장.
- **검사 1 — 서명·알고리즘·클레임 확인**: 지적할 때: jsonwebtoken `jwt.decode`·jose `decodeJwt`로만 읽고 그 값을 믿는 경우. PyJWT에 `algorithms`가 없거나 `verify_signature: False`인 경우. 허용 알고리즘에 `none`이 있거나 HS·RS 계열이 섞인 경우. `exp`가 없거나 `ignoreExpiration: true`인 경우, 여러 서비스가 키를 함께 쓰는데 `aud`·`iss` 검사가 없는 경우. / 지적하지 않을 때: `jwt.verify`·`jwtVerify`·PyJWT `jwt.decode(..., algorithms=[하나])`로 서명과 만료를 확인하는 경우.
- **검사 2 — 세션 고정·로그아웃**: 지적할 때: 직접 만든 로그인 처리에서 성공 뒤 세션 ID를 바꾸지 않는 경우(PHP `session_regenerate_id(true)`·Express `req.session.regenerate` 없음). 로그아웃이 클라이언트 토큰 삭제뿐이고 서버 세션·리프레시 토큰은 살아 있는 경우. / 지적하지 않을 때: Django `login()`·Laravel 인증 가드·Auth.js처럼 프레임워크가 로그인 때 세션 ID를 바꿔 주는 경우.
- **검사 3 — 쿠키 속성**: 지적할 때: 세션·로그인 토큰 쿠키에 `HttpOnly`·`Secure`·`SameSite`가 빠진 경우(`'http_only' => false`, 운영의 `SESSION_SECURE_COOKIE=false`). / 지적하지 않을 때: 프레임워크 기본값을 쓰고 운영에서 HTTPS·Secure가 켜진 경우.
- **등급**: 위험: 서명 확인 없이 토큰을 믿는 경우, `none`·알고리즘 혼용 허용, 세션 고정. / 주의: 만료 없음, 로그아웃 뒤에도 서버 세션 유지, 쿠키 HttpOnly·Secure 빠짐, 짧은 고정 서명 키. / 참고: 토큰을 localStorage에 보관, 세션 최대 유지 시간 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SESS-001 app/auth.py:12 — jwt.decode 에 verify_signature False 로 서명을 확인하지 않음 → 키와 algorithms=["HS256"], audience·issuer, require exp 로 검증`
- **고칠 방법**: 토큰은 검증 함수 하나로만 읽게 모아야 하고, 세션은 로그인·로그아웃·비밀번호 변경 때 서버에서 새로 만들거나 지워야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// config/session.php (운영)
'secure' => false,              // HTTP 로도 세션 쿠키 전송
'http_only' => false,           // 스크립트가 세션 쿠키를 읽을 수 있다
'same_site' => null,
'lifetime' => 60 * 24 * 30,     // 30일

public function logout()
{
    Auth::logout();             // 세션 데이터·CSRF 토큰은 그대로 남는다
    return redirect('/');
}
```

✓ 안전 코드

```php
// config/session.php — 운영 .env 에 SESSION_SECURE_COOKIE=true
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'lax',
'lifetime' => env('SESSION_LIFETIME', 120),        // 분 단위, 비활동 2시간

public function login(LoginRequest $request)
{
    $request->authenticate();                      // 시도 제한 포함(WS-AUTH-003)
    $request->session()->regenerate();             // 로그인 뒤 세션 ID 새로(세션 고정 방지)
    return redirect()->intended('/');
}

public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();             // 서버 세션 삭제
    $request->session()->regenerateToken();        // CSRF 토큰도 새로
    return redirect('/');
}
```

### nextjs

✗ 취약 코드

```ts
// lib/session.ts
import { decodeJwt } from "jose";
import { cookies } from "next/headers";

export async function getUser() {
  const token = (await cookies()).get("token")?.value;
  return token ? decodeJwt(token) : null;   // 서명 확인 없이 내용만 읽는다 → 직접 만든 토큰도 통과
}
// 로그인 화면: localStorage.setItem("token", token)  ← 스크립트 한 줄이면 토큰이 샌다
```

✓ 안전 코드

```ts
import { jwtVerify, SignJWT } from "jose";
import { cookies } from "next/headers";

const key = new TextEncoder().encode(process.env.SESSION_SECRET);  // 32바이트 이상 무작위 값
const OPT = { issuer: "example-web", audience: "example-web" };

export async function createSession(userId: string) {
  const token = await new SignJWT({ sub: userId }).setProtectedHeader({ alg: "HS256" })
    .setIssuer(OPT.issuer).setAudience(OPT.audience).setIssuedAt().setExpirationTime("2h").sign(key);
  (await cookies()).set("session", token, { httpOnly: true, secure: true, sameSite: "lax", path: "/", maxAge: 7200 });
}

export async function getUser() {
  const token = (await cookies()).get("session")?.value;
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, key, { ...OPT, algorithms: ["HS256"], requiredClaims: ["exp", "sub"] });
    return payload;
  } catch {
    return null;                              // 서명·알고리즘·만료·발급자·대상 중 하나라도 틀리면 거절
  }
}
// 무상태 JWT는 서버에서 지울 수 없으므로 로그아웃을 확실히 하려면 짧은 만료 + 차단 목록, 또는 서버 세션 저장을 함께 쓴다
```

### fastapi

✗ 취약 코드

```python
import jwt

def issue(user) -> str:
    return jwt.encode({"sub": str(user.id)}, "secret", algorithm="HS256")  # 짧은 고정 키, 만료 없음

def current_user(authorization: str = Header()):
    token = authorization.removeprefix("Bearer ")
    claims = jwt.decode(token, options={"verify_signature": False})       # 서명을 보지 않는다
    return get_user(claims["sub"])
```

✓ 안전 코드

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
