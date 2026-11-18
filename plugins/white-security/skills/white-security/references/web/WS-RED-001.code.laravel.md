<!-- generated from white-security issues; do not edit -->
# WS-RED-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function login(Request $request)
{
    if (! Auth::attempt($request->only('email', 'password'))) abort(401);
    return redirect($request->input('next', '/'));            // 외부 주소도 따라간다
}
```

✓ 안전 코드 (safe example)

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
