<!-- generated from white-security issues; do not edit -->
# WS-AUTH-003 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function login(Request $request)
{
    // 몇 번이든 다시 시도할 수 있다
    if (Auth::attempt($request->only('email', 'password'))) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }
    return back()->withErrors(['email' => '이메일 또는 비밀번호가 맞지 않습니다.']);
}
```

✓ 안전 코드 (safe example)

```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

public function login(Request $request)
{
    $cred = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
    $email = Str::lower($cred['email']);
    $pair = 'login:'.$email.'|'.$request->ip();      // 계정+IP (프록시 뒤라면 TrustProxies 설정 확인)
    $acct = 'login-acct:'.$email;                    // 계정 단위 상한(IP를 바꿔 가며 시도하는 경우)
    if (RateLimiter::tooManyAttempts($pair, 5) || RateLimiter::tooManyAttempts($acct, 30)) {
        abort(429, '잠시 뒤 다시 시도해 주세요.');
    }
    if (Auth::attempt($cred)) {
        RateLimiter::clear($pair);
        $request->session()->regenerate();
        return redirect()->intended('/');
    }
    RateLimiter::hit($pair, 60);                     // 1분 창
    RateLimiter::hit($acct, 3600);                   // 1시간 창
    return back()->withErrors(['email' => '이메일 또는 비밀번호가 맞지 않습니다.']);
}
```
