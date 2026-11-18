---
id: WS-RED-001
title: Open Redirect (외부 주소로 자동 이동)
slug: open-redirect
group: web
severity: medium
site_only: false
depth: brief
languages: [nextjs, laravel, django]
maps:
  owasp2025: [A01]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V3.7.2, V3.7.3]
  cwe: [CWE-601]
  kr_guide: ["입력데이터 검증 및 표현-7"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Next.js, Laravel, Django, 로그인 뒤 이동·외부 링크 이동이 있는 웹 애플리케이션]
severity_reason: "영향: 내 도메인 링크를 미끼로 한 피싱 페이지 이동, OAuth 인증 코드 유출 경로 · 악용: 로그인 링크의 next 값 하나로 가능"
sources:
  - title: OWASP Unvalidated Redirects and Forwards Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html
    year: 2026
  - title: "CWE-601: URL Redirection to Untrusted Site ('Open Redirect')"
    url: https://cwe.mitre.org/data/definitions/601.html
    year: 2025
  - title: "WHATWG URL Standard (상대 주소 해석 규칙)"
    url: https://url.spec.whatwg.org/
    year: 2026
---

## 요약

로그인 뒤 이동할 주소(`?next=`, `?returnUrl=`)를 확인 없이 따라가, 내 도메인 링크를 눌렀는데 다른 사이트(피싱 페이지)로 넘어가는 문제(진단가이드 이름: 신뢰되지 않는 URL 주소로 자동접속 연결). OAuth 연동에서는 인증 코드·토큰이 새는 통로가 되기도 함.

## 대응 방법

```text
요청 값(next·returnUrl·redirect·url)으로 이동하는 코드를 모두 찾아 줘(redirect(), Location 헤더, window.location).
내 사이트 안의 상대 경로만 허용하고 // 나 \ 로 시작하는 값은 막는 함수를 하나 만들어 모든 이동에 써 줘.
외부로 보내야 하면 허용 도메인 목록에서만 고르게 해 줘.
```

- `?next=//example.org`, `?next=https://example.org`로 로그인하면 둘 다 내 사이트 첫 화면으로 가야 한다.

## 점검 항목

- 이동 주소는 `/`로 시작하는 내 사이트 상대 경로만 허용해야 한다.
- `//`·`/\`로 시작하는 값과 제어 문자는 막아야 한다(브라우저가 다른 도메인으로 읽음).
- 검사는 브라우저와 같은 방식(`new URL`)으로 해석한 결과에 해야 한다.
- 외부 이동은 허용 도메인 목록이나 id→주소 매핑으로만 해야 한다.
- 요청 값으로 정해지는 외부 이동은 이동 안내 화면을 거치게 해야 한다.

## 탐지 규칙

- **찾는 곳**: `->input('next'`, `request.GET.get("next"`, `searchParams.get("next"`, `formData.get("next"`, `window.location`, 그 밖에 returnUrl·redirect_uri 값으로 부르는 redirect()·->away()·Location 헤더.
- **검사 1 — 검사 없는 이동**: 지적할 때: 요청·DB·OAuth state 값이 `redirect()`·`Location`·`window.location`으로 그대로 가는 경우. / 지적하지 않을 때: 상대 경로 검사 함수, Django `url_has_allowed_host_and_scheme`, 허용 도메인 목록을 거친 경우. 이동 대상이 코드 상수인 경우.
- **검사 2 — 허술한 검사**: 지적할 때: `startsWith("/")`만 보는 경우(`//`·`/\` 통과), `includes("mysite.com")`·정규식 부분 일치, 검사 뒤 다시 디코딩하는 경우. / 지적하지 않을 때: `new URL(value, base)`로 해석해 origin을 비교하고, 결과 경로가 `//`로 시작하지 않는지까지 확인하는 경우.
- **등급**: 위험: 로그인·OAuth 콜백 뒤 이동에 검사가 없는 경우. / 주의: 그 밖의 검사 없는 이동, 허술한 문자열 검사. / 참고: 외부 이동에 안내 화면이 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-RED-001 app/login/actions.ts:21 — 로그인 뒤 폼의 next 값으로 그대로 redirect → 같은 origin 의 상대 경로만 허용하는 safeNext() 사용`
- **고칠 방법**: 검사 함수 하나를 만들어 모든 이동에 써야 하고, 검사에 실패하면 오류 대신 첫 화면으로 보내야 한다.

## 코드 예시

### nextjs

✗ 취약 코드

```ts
"use server";
import { redirect } from "next/navigation";

export async function login(formData: FormData) {
  await signIn(formData);
  redirect(String(formData.get("next") ?? "/")); // 다른 사이트 주소도 그대로 이동
}
```

✓ 안전 코드

```ts
"use server";
import { redirect } from "next/navigation";

const ORIGIN = "https://app.example.com";

function safeNext(value: unknown): string {
  if (typeof value !== "string" || !value.startsWith("/")) return "/";
  const u = new URL(value, ORIGIN);                          // 브라우저와 같은 방식으로 해석
  const path = u.pathname + u.search + u.hash;
  return u.origin === ORIGIN && !path.startsWith("//") ? path : "/";
}

export async function login(formData: FormData) {
  await signIn(formData);
  redirect(safeNext(formData.get("next")));                  // 내 사이트 안 경로만
}
```

### laravel

✗ 취약 코드

```php
public function login(Request $request)
{
    if (! Auth::attempt($request->only('email', 'password'))) abort(401);
    return redirect($request->input('next', '/'));            // 외부 주소도 따라간다
}
```

✓ 안전 코드

```php
public function login(Request $request)
{
    if (! Auth::attempt($request->only('email', 'password'))) abort(401);
    $request->session()->regenerate();
    $next = (string) $request->input('next', '/');
    $ok = str_starts_with($next, '/') && ! str_starts_with($next, '//')
        && ! str_contains($next, '\\') && ! preg_match('/[\x00-\x1F\x7F]/', $next); // 내 사이트 상대 경로만
    return redirect()->to($ok ? $next : '/');
}
```

### django

✗ 취약 코드

```python
def login_view(request):
    user = authenticate(request, username=request.POST["username"], password=request.POST["password"])
    if user is None:
        return render(request, "login.html", status=401)
    login(request, user)
    return redirect(request.GET.get("next", "/"))    # 외부 주소도 따라간다
```

✓ 안전 코드

```python
from django.utils.http import url_has_allowed_host_and_scheme

def login_view(request):
    user = authenticate(request, username=request.POST["username"], password=request.POST["password"])
    if user is None:
        return render(request, "login.html", status=401)
    login(request, user)
    nxt = request.POST.get("next") or request.GET.get("next", "")
    if not url_has_allowed_host_and_scheme(nxt, allowed_hosts={request.get_host()}, require_https=request.is_secure()):
        nxt = "/"                                      # 허용 호스트가 아니면 첫 화면
    return redirect(nxt)
```
