---
id: WS-OAUTH-001
title: OAuth Login Flaws (간편 로그인 state·redirect_uri 검증 누락)
slug: oauth-login-flaws
group: auth
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs, fastapi]
maps:
  owasp2025: [A07, A01]
  api2023: [API2]
  llm2026: []
  agentic2026: []
  asvs5: [V10.1.2, V10.2.1, V10.5.1, V10.5.2, V10.5.4, V6.8.1]
  cwe: [CWE-352, CWE-601, CWE-287, CWE-345]
  kr_guide: ["입력데이터 검증 및 표현-11", "입력데이터 검증 및 표현-7", "보안기능-10"]
  kr_ciip: [IA, CF]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel Socialite, Next.js·Auth.js, FastAPI, 간편 로그인(OAuth 2.0·OpenID Connect)을 붙인 웹·앱 서비스]
severity_reason: "영향: 남의 계정 로그인·계정 연결 탈취 · 악용: 조작한 로그인 링크 하나, 다른 앱에서 받은 토큰, 같은 이메일의 다른 제공자 계정으로 가능"
sources:
  - title: "RFC 9700 Best Current Practice for OAuth 2.0 Security"
    url: https://www.rfc-editor.org/rfc/rfc9700
    year: 2025
  - title: OpenID Connect Core 1.0 (ID Token Validation·nonce)
    url: https://openid.net/specs/openid-connect-core-1_0.html
    year: 2023
  - title: OWASP OAuth 2.0 Protocol Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/OAuth2_Cheat_Sheet.html
    year: 2026
  - title: "Laravel 12.x - Socialite (stateless·PKCE)"
    url: https://laravel.com/docs/12.x/socialite
    year: 2026
  - title: "Auth.js - Providers (allowDangerousEmailAccountLinking·checks)"
    url: https://authjs.dev/reference/core/providers
    year: 2026
  - title: "Google Identity - Verify the Google ID token on your server side"
    url: https://developers.google.com/identity/gsi/web/guides/verify-google-id-token
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

간편 로그인(OAuth 2.0·OpenID Connect)에서 `state` 확인·PKCE를 빼거나 `redirect_uri`를 느슨하게 맞춰, 공격자 계정이 피해자 계정에 연결되거나(로그인 CSRF) 인증 코드·토큰이 공격자 쪽으로 넘어가는 문제(진단가이드 이름: 크로스사이트 요청 위조). 제공자가 준 이메일만으로 기존 계정에 자동 연결하거나 앱이 보낸 토큰의 발급 대상을 확인하지 않는 경우도 포함.

## 대응 방법

```text
간편 로그인(OAuth·OIDC) 시작·콜백 코드와 제공자 콘솔에 등록할 redirect_uri를 찾아 줘.
state와 PKCE를 켜고(최소 하나, 둘 다 권장), redirect_uri는 정확히 같은 주소 하나만 쓰게 해 줘.
계정 연결은 제공자의 고유 사용자 ID(sub)로 하고, 이메일만 같다고 기존 계정에 자동 연결하지 않게 바꿔 줘.
앱에서 받은 토큰으로 로그인시키는 API는 서명과 aud(발급 대상)가 우리 client_id인지 확인하게 해 줘.
```

- 콜백 주소의 `state` 값을 바꾸거나 지우고 열면 로그인이 실패해야 한다.
- 같은 이메일을 쓰는 다른 제공자 계정으로 로그인해도 기존 계정에 자동으로 붙지 않아야 한다.

## 점검 항목

- 로그인 CSRF 방지로 `state`나 PKCE 중 하나 이상(둘 다 권장)을 확인해야 한다.
- 앱·SPA처럼 비밀값을 숨길 수 없는 클라이언트는 PKCE를 꼭 켜야 한다.
- `redirect_uri`는 제공자 콘솔과 코드 모두 정확히 같은 주소로 맞춰야 한다(와일드카드·요청 값 금지).
- 계정은 제공자의 고유 ID(`sub`)로 연결하고, 이메일만으로 기존 계정에 붙이지 않아야 한다.
- ID 토큰·앱이 보낸 토큰은 서명·`iss`·`aud`(우리 client_id)·`exp`·`nonce`를 확인해야 한다.
- client secret은 서버에만 두고, 로그인 뒤 이동 주소는 우리 사이트 안 주소만 허용해야 한다.

## 탐지 규칙

- **찾는 곳**: `->stateless()`, `allowDangerousEmailAccountLinking`, `redirect_uri`, `Socialite::driver(`, `verify_oauth2_token(`, `/oauth/callback`, 그 밖에 /auth/callback 경로, Auth.js checks 설정, 직접 만든 토큰 교환(grant_type=authorization_code), 제공자 이메일로 사용자 찾기.
- **검사 1 — state·PKCE 없음**: 지적할 때: 콜백에서 `state`도 PKCE도 확인하지 않는 경우(웹 로그인에 PKCE 없이 Socialite `->stateless()`, 직접 만든 흐름에 둘 다 없음, Auth.js `checks: ["none"]`). 앱·SPA가 PKCE 없이 인가 코드를 쓰는 경우. / 지적하지 않을 때: Socialite 기본 흐름(state), Auth.js 기본값(PKCE), Authlib 기본값처럼 라이브러리가 state나 PKCE 중 하나 이상을 처리하는 경우.
- **검사 2 — redirect_uri·이동 주소**: 지적할 때: `redirect_uri`를 요청 값(`?redirect_uri=`, Host 헤더)으로 만드는 경우. 로그인 뒤 `returnUrl`·`next`·`callbackUrl`로 확인 없이 다른 사이트로 이동하는 경우. / 지적하지 않을 때: 설정에 고정된 주소 하나를 쓰고, 이동 주소를 같은 사이트 안으로 제한하는 경우.
- **검사 3 — 계정 연결·토큰 대상**: 지적할 때: 제공자가 준 이메일로 기존 사용자를 찾아 로그인시키는 경우(`allowDangerousEmailAccountLinking: true` 포함). 앱이 보낸 액세스 토큰으로 사용자 정보만 조회해 로그인시키고 발급 대상(client_id·`aud`)을 확인하지 않는 경우. ID 토큰을 서명 확인 없이 디코딩만 하는 경우. / 지적하지 않을 때: (제공자, `sub`)로 연결하고, 토큰의 서명·`iss`·`aud`를 라이브러리로 확인하는 경우.
- **등급**: 위험: state·PKCE 확인이 둘 다 없음, redirect_uri를 요청 값으로 정함, 이메일로 기존 계정 자동 연결, 토큰 발급 대상 확인 없음. / 주의: 앱·SPA에 PKCE 없음, 로그인 뒤 이동 주소 확인 없음. / 참고: 필요 이상 scope 요청, 제공자 토큰을 평문으로 DB에 저장.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-OAUTH-001 routes/web.php:14 — 웹 로그인 콜백에 Socialite ->stateless() 를 써 state 확인이 꺼짐 → stateless 를 빼고 enablePKCE() 추가, 계정은 provider_id 로 연결`
- **검토 주의**: 제공자가 이메일 소유를 확인했는지(email_verified)는 제공자·계정마다 다르므로, 이메일 일치를 계정 연결의 근거로 삼은 코드는 안전하다고 보지 않아야 한다.
- **고칠 방법**: 검증된 라이브러리의 기본 흐름(state·PKCE·nonce)을 끄지 말고 써야 하고, 계정 연결 키는 (제공자, `sub`)로 바꿔야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// routes/web.php
Route::get('/auth/google', fn () => Socialite::driver('google')->stateless()->redirect());

Route::get('/auth/google/callback', function () {
    $g = Socialite::driver('google')->stateless()->user();  // state 확인을 끔 → 로그인 CSRF
    $user = User::firstOrCreate(['email' => $g->getEmail()], ['name' => $g->getName()]);  // 이메일로 기존 계정에 연결
    Auth::login($user);
    return redirect('/');
});
```

✓ 안전 코드

```php
// state 는 Socialite 가 기본으로 세션에 저장·확인, PKCE 는 시작·콜백 모두 켠다
Route::get('/auth/google', fn () => Socialite::driver('google')->enablePKCE()->redirect());

Route::get('/auth/google/callback', function () {
    $g = Socialite::driver('google')->enablePKCE()->user();   // state 가 다르면 InvalidStateException
    $account = SocialAccount::firstWhere(['provider' => 'google', 'provider_id' => $g->getId()]);
    if (! $account) {
        abort_if(User::where('email', $g->getEmail())->exists(), 409, '기존 계정으로 로그인한 뒤 연결해 주세요.');
        $user = User::create(['name' => $g->getName(), 'email' => $g->getEmail()]);
        $account = $user->socialAccounts()->create(['provider' => 'google', 'provider_id' => $g->getId()]);
    }
    Auth::login($account->user);
    return redirect()->intended('/');                          // 우리 앱 안 주소만
});
// config/services.php 'redirect' 는 제공자 콘솔에 등록한 주소와 정확히 같은 값 하나
```

### nextjs

✗ 취약 코드

```ts
// auth.ts (Auth.js)
export const { handlers, auth } = NextAuth({
  providers: [
    Google({ allowDangerousEmailAccountLinking: true, checks: ["none"] }),  // 이메일로 자동 연결, state·PKCE 끔
  ],
  callbacks: {
    redirect: ({ url }) => url,             // 로그인 뒤 아무 주소로나 이동
  },
});
```

✓ 안전 코드

```ts
// auth.ts (Auth.js)
export const { handlers, auth } = NextAuth({
  adapter: PrismaAdapter(prisma),            // 계정은 (provider, providerAccountId)로 연결
  providers: [Google],                       // 기본 checks(PKCE) 그대로, 끄지 않기
  // redirect 콜백은 덮어쓰지 않는다(기본값이 같은 사이트 안 주소만 허용)
});
// 제공자 콘솔의 Redirect URI 는 https://example.com/api/auth/callback/google 하나만 정확히 등록
```

### fastapi

✗ 취약 코드

```python
@app.post("/auth/google/app")                  # 모바일 앱이 받은 토큰으로 로그인
def app_login(body: TokenIn, db: Session = Depends(get_db)):
    info = httpx.get("https://openidconnect.googleapis.com/v1/userinfo",
                     headers={"Authorization": f"Bearer {body.access_token}"}).json()
    # 다른 앱에서 발급된 토큰이어도 사용자 정보는 나온다 → 그 사람으로 로그인(토큰 바꿔치기)
    user = db.query(User).filter_by(email=info["email"]).first()
    return issue_session(user)
```

✓ 안전 코드

```python
import hmac
from google.oauth2 import id_token
from google.auth.transport import requests as google_requests

@app.post("/auth/google/app")
def app_login(body: IdTokenIn, db: Session = Depends(get_db)):
    try:  # 서명·만료·iss 확인, aud 가 우리 client_id 인지 확인
        claims = id_token.verify_oauth2_token(body.id_token, google_requests.Request(), GOOGLE_CLIENT_ID)
    except ValueError:
        raise HTTPException(401)
    expected = pop_login_nonce(body.login_id)              # 로그인 시작 때 발급한 1회용 값(꺼내면 삭제)
    if not expected or not hmac.compare_digest(claims.get("nonce", ""), expected):
        raise HTTPException(401)
    account = db.query(SocialAccount).filter_by(provider="google", sub=claims["sub"]).first()
    if account is None:
        raise HTTPException(404, "가입이 필요합니다")
    return issue_session(account.user)
```
