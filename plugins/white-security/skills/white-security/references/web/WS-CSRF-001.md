<!-- generated from white-security issues; do not edit -->
# WS-CSRF-001 CSRF (크로스사이트 요청 위조)

## 탐지 규칙

- **찾는 곳**: `csrf_exempt`, `validateCsrfTokens(`, `VerifyCsrfToken`, `allowedOrigins`, 그 밖에 쿠키 세션으로 사용자를 찾는 Next.js Route Handler(route.ts 의 POST)·Express 라우트.
- **검사 1 — 보호 끔**: 지적할 때: 쿠키 세션을 쓰는 화면·API에 `@csrf_exempt`나 Laravel CSRF 예외(`validateCsrfTokens(except:)`, `$except`)가 걸린 경우. / 지적하지 않을 때: 예외 대상이 서명을 검사하는 외부 웹훅(결제·메신저)뿐인 경우.
- **검사 2 — 보호 없는 쿠키 API**: 지적할 때: Next.js Route Handler(`route.ts`의 `POST`)나 Express 라우트가 쿠키 세션으로 사용자를 알아내 상태를 바꾸고 Origin·토큰 검사가 없는 경우. / 지적하지 않을 때: Server Actions(Next.js가 Origin과 Host를 비교함), `Authorization` 헤더 토큰만 쓰는 API, Origin·`Sec-Fetch-Site`를 검사하는 경우.
- **검사 3 — GET으로 상태 변경**: 지적할 때: GET 라우트가 삭제·변경·결제처럼 상태를 바꾸는 경우. / 지적하지 않을 때: 조회만 하는 GET인 경우.
- **등급**: 위험: 비밀번호·이메일·결제·권한 변경 요청에 CSRF 보호가 없는 경우. / 주의: 그 밖의 상태 변경 요청, GET으로 상태 변경, `allowedOrigins`의 넓은 와일드카드. / 참고: 세션 쿠키 `SameSite` 미지정.
- **보고 예**: `[위험] WS-CSRF-001 app/api/account/email/route.ts:4 — 쿠키 세션으로 이메일을 바꾸는 POST 에 Origin 검사 없음 → Sec-Fetch-Site·Origin 이 자기 사이트일 때만 처리`
- **고칠 방법**: 보호를 다시 켠 뒤 화면의 폼·fetch 요청에 토큰이 실리는지(`@csrf`, `X-CSRF-TOKEN`, `X-CSRFToken`) 함께 고쳐야 한다.

## 점검 항목

- 상태를 바꾸는 요청은 POST·PUT·PATCH·DELETE로만 받아야 한다.
- 프레임워크 CSRF 보호(Laravel `@csrf`, Django `{% csrf_token %}`)를 끄지 않아야 한다.
- 보호 예외는 서명을 검사하는 외부 웹훅에만 둬야 한다.
- 직접 만든 쿠키 로그인 API는 `Origin`·`Sec-Fetch-Site`로 같은 출처만 받아야 한다.
- 세션 쿠키는 `SameSite=Lax` 이상으로 두되 이것만 믿지 않아야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-CSRF-001.code.laravel.md` (Laravel) · `WS-CSRF-001.code.django.md` (Django) · `WS-CSRF-001.code.nextjs.md` (Next.js)

기본 심각도: 주의 · CWE-352
