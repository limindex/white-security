---
id: WS-CSRF-001
title: CSRF (크로스사이트 요청 위조)
title_en: CSRF (Cross-Site Request Forgery)
slug: csrf
group: web
severity: medium
grade_hint: "계정·결제 변경에 CSRF 보호 없음"
site_only: false
depth: brief
languages: [laravel, django, nextjs]
maps:
  owasp2025: [A01]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V3.5.1, V3.5.3, V3.3.2]
  cwe: [CWE-352]
  kr_guide: ["입력데이터 검증 및 표현-11"]
  kr_ciip: [CF]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Django, Next.js, Express, 쿠키로 로그인을 유지하는 웹 애플리케이션]
severity_reason: "영향: 로그인한 사용자 몰래 이메일·비밀번호 변경, 글 삭제, 결제 · 악용: 사용자가 공격자 페이지를 여는 것만으로 가능"
sources:
  - title: OWASP Cross-Site Request Forgery Prevention Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
    year: 2026
  - title: "Next.js Docs - Data Security (Server Actions 의 Origin 검사)"
    url: https://nextjs.org/docs/app/guides/data-security
    year: 2026
  - title: "Laravel 12.x - CSRF Protection"
    url: https://laravel.com/docs/12.x/csrf
    year: 2026
  - title: "Django - Cross Site Request Forgery protection"
    url: https://docs.djangoproject.com/en/5.2/ref/csrf/
    year: 2026
---

## 요약

로그인 쿠키가 있는 사용자가 다른 사이트를 열었을 때, 그 페이지가 몰래 내 사이트로 요청을 보내 이메일 변경·삭제·결제를 실행하는 문제(진단가이드 이름: 크로스사이트 요청 위조). 프레임워크 기본 보호를 끄거나(`csrf_exempt`, 예외 목록) 보호가 없는 API 라우트에 쿠키 로그인을 붙일 때 주로 생김.

## 대응 방법

```text
쿠키로 로그인하는 서비스에서 상태를 바꾸는 요청(POST·PUT·PATCH·DELETE)을 받는 곳을 모두 찾아 CSRF 보호가 꺼진 곳을 표로 보여 줘.
csrf_exempt·CSRF 예외 목록은 서명을 검사하는 외부 웹훅만 남기고, Next.js Route Handler 는 Origin 헤더를 검사하게 해 줘.
GET 요청으로 데이터를 바꾸는 곳은 POST 로 바꿔 줘.
```

- 다른 도메인의 테스트 페이지에서 폼을 자동 제출하면 403이 나와야 한다.
- 세션 쿠키에 `SameSite=Lax` 이상이 붙어 있어야 한다.

## 점검 항목

- 상태를 바꾸는 요청은 POST·PUT·PATCH·DELETE로만 받아야 한다.
- 프레임워크 CSRF 보호(Laravel `@csrf`, Django `{% csrf_token %}`)를 끄지 않아야 한다.
- 보호 예외는 서명을 검사하는 외부 웹훅에만 둬야 한다.
- 직접 만든 쿠키 로그인 API는 `Origin`·`Sec-Fetch-Site`로 같은 출처만 받아야 한다.
- 세션 쿠키는 `SameSite=Lax` 이상으로 두되 이것만 믿지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `csrf_exempt`, `validateCsrfTokens(`, `VerifyCsrfToken`, `allowedOrigins`, 그 밖에 쿠키 세션으로 사용자를 찾는 Next.js Route Handler(route.ts 의 POST)·Express 라우트.
- **검사 1 — 보호 끔**: 지적할 때: 쿠키 세션을 쓰는 화면·API에 `@csrf_exempt`나 Laravel CSRF 예외(`validateCsrfTokens(except:)`, `$except`)가 걸린 경우. / 지적하지 않을 때: 예외 대상이 서명을 검사하는 외부 웹훅(결제·메신저)뿐인 경우.
- **검사 2 — 보호 없는 쿠키 API**: 지적할 때: Next.js Route Handler(`route.ts`의 `POST`)나 Express 라우트가 쿠키 세션으로 사용자를 알아내 상태를 바꾸고 Origin·토큰 검사가 없는 경우. / 지적하지 않을 때: Server Actions(Next.js가 Origin과 Host를 비교함), `Authorization` 헤더 토큰만 쓰는 API, Origin·`Sec-Fetch-Site`를 검사하는 경우.
- **검사 3 — GET으로 상태 변경**: 지적할 때: GET 라우트가 삭제·변경·결제처럼 상태를 바꾸는 경우. / 지적하지 않을 때: 조회만 하는 GET인 경우.
- **등급**: 위험: 비밀번호·이메일·결제·권한 변경 요청에 CSRF 보호가 없는 경우. / 주의: 그 밖의 상태 변경 요청, GET으로 상태 변경, `allowedOrigins`의 넓은 와일드카드. / 참고: 세션 쿠키 `SameSite` 미지정.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CSRF-001 app/api/account/email/route.ts:4 — 쿠키 세션으로 이메일을 바꾸는 POST 에 Origin 검사 없음 → Sec-Fetch-Site·Origin 이 자기 사이트일 때만 처리`
- **고칠 방법**: 보호를 다시 켠 뒤 화면의 폼·fetch 요청에 토큰이 실리는지(`@csrf`, `X-CSRF-TOKEN`, `X-CSRFToken`) 함께 고쳐야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// bootstrap/app.php — 오류가 귀찮아 CSRF 검사를 통째로 뺌
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['*']);
})
```

✓ 안전 코드

```php
// bootstrap/app.php — 예외는 서명을 검사하는 외부 웹훅만
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['webhooks/payments']);
})

// 폼마다 토큰: <form method="POST" action="/account/email"> @csrf ... </form>
// fetch 는 X-CSRF-TOKEN 헤더에 <meta name="csrf-token"> 값을 싣는다
```

### django

✗ 취약 코드

```python
from django.views.decorators.csrf import csrf_exempt

@csrf_exempt                      # 폼 오류가 나서 보호를 끔
@login_required
def change_email(request):
    request.user.email = request.POST["email"]
    request.user.save()
    return redirect("/account")
```

✓ 안전 코드

```python
from django.views.decorators.http import require_POST

@require_POST                     # 상태 변경은 POST 로만, CSRF 미들웨어가 토큰 검사
@login_required
def change_email(request):
    form = EmailForm(request.POST)
    if not form.is_valid():
        return render(request, "account.html", {"form": form}, status=400)
    request.user.email = form.cleaned_data["email"]
    request.user.save(update_fields=["email"])
    return redirect("/account")
# 템플릿 폼에는 {% csrf_token %}, fetch 는 X-CSRFToken 헤더
```

### nextjs

✗ 취약 코드

```ts
// app/api/account/email/route.ts — 쿠키 세션으로 사용자 확인, 출처 검사 없음
export async function POST(req: Request) {
  const user = await getSessionUser();   // 쿠키는 다른 사이트가 보낸 요청에도 실린다
  if (!user) return new Response(null, { status: 401 });
  const { email } = await req.json();    // text/plain 폼으로 보낸 본문도 읽힌다
  await db.user.update({ where: { id: user.id }, data: { email } });
  return Response.json({ ok: true });
}
```

✓ 안전 코드

```ts
// app/api/account/email/route.ts
const APP_ORIGIN = process.env.APP_ORIGIN; // 예: https://app.example.com

export async function POST(req: Request) {
  const site = req.headers.get("sec-fetch-site");
  const origin = req.headers.get("origin");
  const sameSite = site ? site === "same-origin" : !!APP_ORIGIN && origin === APP_ORIGIN;
  if (!sameSite) return new Response(null, { status: 403 }); // 다른 사이트에서 온 요청 거절
  const user = await getSessionUser();
  if (!user) return new Response(null, { status: 401 });
  const { email } = await req.json();
  await db.user.update({ where: { id: user.id }, data: { email } });
  return Response.json({ ok: true });
}
```
