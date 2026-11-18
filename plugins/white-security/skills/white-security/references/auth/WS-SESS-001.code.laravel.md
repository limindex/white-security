<!-- generated from white-security issues; do not edit -->
# WS-SESS-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
