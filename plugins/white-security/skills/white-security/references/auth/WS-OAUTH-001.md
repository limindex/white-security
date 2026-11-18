<!-- generated from white-security issues; do not edit -->
# WS-OAUTH-001 OAuth Login Flaws (간편 로그인 state·redirect_uri 검증 누락)

## 탐지 규칙

- **찾는 곳**: `->stateless()`, `allowDangerousEmailAccountLinking`, `redirect_uri`, `Socialite::driver(`, `verify_oauth2_token(`, `/oauth/callback`, 그 밖에 /auth/callback 경로, Auth.js checks 설정, 직접 만든 토큰 교환(grant_type=authorization_code), 제공자 이메일로 사용자 찾기.
- **검사 1 — state·PKCE 없음**: 지적할 때: 콜백에서 `state`도 PKCE도 확인하지 않는 경우(웹 로그인에 PKCE 없이 Socialite `->stateless()`, 직접 만든 흐름에 둘 다 없음, Auth.js `checks: ["none"]`). 앱·SPA가 PKCE 없이 인가 코드를 쓰는 경우. / 지적하지 않을 때: Socialite 기본 흐름(state), Auth.js 기본값(PKCE), Authlib 기본값처럼 라이브러리가 state나 PKCE 중 하나 이상을 처리하는 경우.
- **검사 2 — redirect_uri·이동 주소**: 지적할 때: `redirect_uri`를 요청 값(`?redirect_uri=`, Host 헤더)으로 만드는 경우. 로그인 뒤 `returnUrl`·`next`·`callbackUrl`로 확인 없이 다른 사이트로 이동하는 경우. / 지적하지 않을 때: 설정에 고정된 주소 하나를 쓰고, 이동 주소를 같은 사이트 안으로 제한하는 경우.
- **검사 3 — 계정 연결·토큰 대상**: 지적할 때: 제공자가 준 이메일로 기존 사용자를 찾아 로그인시키는 경우(`allowDangerousEmailAccountLinking: true` 포함). 앱이 보낸 액세스 토큰으로 사용자 정보만 조회해 로그인시키고 발급 대상(client_id·`aud`)을 확인하지 않는 경우. ID 토큰을 서명 확인 없이 디코딩만 하는 경우. / 지적하지 않을 때: (제공자, `sub`)로 연결하고, 토큰의 서명·`iss`·`aud`를 라이브러리로 확인하는 경우.
- **등급**: 위험: state·PKCE 확인이 둘 다 없음, redirect_uri를 요청 값으로 정함, 이메일로 기존 계정 자동 연결, 토큰 발급 대상 확인 없음. / 주의: 앱·SPA에 PKCE 없음, 로그인 뒤 이동 주소 확인 없음. / 참고: 필요 이상 scope 요청, 제공자 토큰을 평문으로 DB에 저장.
- **보고 예**: `[위험] WS-OAUTH-001 routes/web.php:14 — 웹 로그인 콜백에 Socialite ->stateless() 를 써 state 확인이 꺼짐 → stateless 를 빼고 enablePKCE() 추가, 계정은 provider_id 로 연결`
- **고칠 방법**: 검증된 라이브러리의 기본 흐름(state·PKCE·nonce)을 끄지 말고 써야 하고, 계정 연결 키는 (제공자, `sub`)로 바꿔야 한다.

## 점검 항목

- 로그인 CSRF 방지로 `state`나 PKCE 중 하나 이상(둘 다 권장)을 확인해야 한다.
- 앱·SPA처럼 비밀값을 숨길 수 없는 클라이언트는 PKCE를 꼭 켜야 한다.
- `redirect_uri`는 제공자 콘솔과 코드 모두 정확히 같은 주소로 맞춰야 한다(와일드카드·요청 값 금지).
- 계정은 제공자의 고유 ID(`sub`)로 연결하고, 이메일만으로 기존 계정에 붙이지 않아야 한다.
- ID 토큰·앱이 보낸 토큰은 서명·`iss`·`aud`(우리 client_id)·`exp`·`nonce`를 확인해야 한다.
- client secret은 서버에만 두고, 로그인 뒤 이동 주소는 우리 사이트 안 주소만 허용해야 한다.

## 검토 주의

제공자가 이메일 소유를 확인했는지(email_verified)는 제공자·계정마다 다르므로, 이메일 일치를 계정 연결의 근거로 삼은 코드는 안전하다고 보지 않아야 한다.

Code examples (read only the file for the stack you are writing): `WS-OAUTH-001.code.laravel.md` (Laravel) · `WS-OAUTH-001.code.nextjs.md` (Next.js) · `WS-OAUTH-001.code.fastapi.md` (FastAPI)

기본 심각도: 위험
기준: OWASP Top 10:2025 A07(인증 실패) · OWASP Top 10:2025 A01(접근 통제 실패) · OWASP API 2023 API2(인증 실패) · ASVS 5.0 V10.1.2 · ASVS 5.0 V10.2.1 · ASVS 5.0 V10.5.1 · ASVS 5.0 V10.5.2 · ASVS 5.0 V10.5.4 · ASVS 5.0 V6.8.1 · CWE-352(크로스사이트 요청 위조) · CWE-601 · CWE-287 · CWE-345 · 국내 보안약점 49 입력데이터 검증 및 표현-11(크로스사이트 요청 위조) · 국내 보안약점 49 입력데이터 검증 및 표현-7(신뢰되지 않는 URL 주소로 자동접속 연결) · 국내 보안약점 49 보안기능-10(부적절한 전자서명 확인) · 기반시설 웹 21 IA(불충분한 인증) · 기반시설 웹 21 CF(크로스사이트 요청 위조)
