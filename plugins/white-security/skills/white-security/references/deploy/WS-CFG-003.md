<!-- generated from white-security issues; do not edit -->
# WS-CFG-003 Missing Security Headers (CSP·HSTS 등 보안 헤더 누락)

## 탐지 규칙

- **찾는 곳**: `Content-Security-Policy`, `Strict-Transport-Security`, `SECURE_HSTS_SECONDS`, `async headers()`, `add_header`, 그 밖에 Django SECURE_CSP, helmet 설정, 응답 헤더 미들웨어, vercel.json·netlify.toml 의 headers, X-Frame-Options.
- **검사 1 — 빠진 헤더**: 지적할 때: HTML을 내보내는 앱의 설정(next.config `headers()`, Django `MIDDLEWARE`·`SECURE_*`, nginx `add_header`, Laravel·Express 미들웨어) 어디에도 CSP·HSTS·nosniff·`frame-ancestors`(또는 `X-Frame-Options`)가 없는 경우. nginx에서 `add_header`가 있는 하위 `location`이 위쪽 보안 헤더를 다시 넣지 않는 경우, `always`가 없어 오류 응답에서 빠지는 경우. Django에서 `SecurityMiddleware`를 뺀 경우. / 지적하지 않을 때: 앞단(CDN·호스팅 설정 파일)에서 같은 헤더를 붙이는 것이 보이는 경우, JSON만 내보내는 API에 CSP가 없는 경우. 호스팅이 기본으로 붙이는 헤더(예: Vercel의 HSTS)는 저장소에 보이지 않으므로 "확인 필요"로 표시.
- **검사 2 — 효과 없는 값**: 지적할 때: CSP에 `default-src *`·`script-src *`·`data:` 스크립트, nonce·hash 없는 `'unsafe-inline'`, 운영에도 켜진 `'unsafe-eval'`, `base-uri`가 없는 경우, HSTS `max-age`가 1년 미만이거나 0인 경우. / 지적하지 않을 때: nonce·hash와 `'strict-dynamic'`으로 스크립트를 허용하는 경우, 개발 환경에만 `'unsafe-eval'`이 붙는 경우.
- **등급**: 주의: HTML 앱에 CSP·HSTS가 모두 없음, 로그인·결제 화면에 `frame-ancestors`·`X-Frame-Options`가 모두 없음, HTTP 접속을 HTTPS로 넘기지 않음. / 참고: nonce 없는 `'unsafe-inline'`(Next.js 공식 기본 예시), `X-Frame-Options`만 있고 `frame-ancestors` 없음, `base-uri` 없음, nosniff·Referrer-Policy 없음, nginx `always` 누락, HSTS 1년 미만.
- **보고 예**: `[주의] WS-CFG-003 next.config.js:1 — 응답 헤더 설정이 없어 CSP·HSTS·frame-ancestors 없이 배포됨 → proxy.ts 에 nonce CSP, headers() 에 HSTS·nosniff 추가`
- **고칠 방법**: 보안 헤더를 앱 설정이나 웹 서버 한곳에 묶어 모든 응답(오류 응답 포함)에 붙여야 하고, CSP는 Report-Only로 시험한 뒤 차단 모드로 바꿔야 한다.

## 점검 항목

- HTML 응답의 CSP에는 `object-src 'none'`·`base-uri 'none'`·`frame-ancestors`를 정해야 한다.
- HSTS는 `max-age` 1년 이상, `includeSubDomains`는 모든 하위 도메인이 HTTPS일 때만 붙여야 한다.
- 모든 응답에 `X-Content-Type-Options: nosniff`를 붙여야 한다.
- `Referrer-Policy`로 주소 속 정보가 외부로 새지 않게 해야 한다.
- HTTP 요청은 HTTPS로 넘기고, 개인정보는 HTTPS로만 주고받아야 한다.
- nginx는 `add_header`에 `always`를 붙이고, 하위 블록에서도 다시 넣어야 한다.

## 검토 주의

HSTS의 `includeSubDomains`·`preload`는 HTTP만 쓰는 하위 도메인을 막고 되돌리기 어려우므로, 모든 하위 도메인이 HTTPS인지 사용자에게 확인을 받고 권해야 한다. `X-Frame-Options`는 낡은 방식이라 `frame-ancestors`를 함께 쓰라고 안내해야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-CFG-003.code.nextjs.md` (Next.js) · `WS-CFG-003.code.django.md` (Django) · `WS-CFG-003.code.nginx.md` (Nginx)

기본 심각도: 주의 · CWE-1021 · CWE-693 · CWE-319
