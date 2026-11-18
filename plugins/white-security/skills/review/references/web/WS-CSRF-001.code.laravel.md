<!-- generated from white-security issues; do not edit -->
# WS-CSRF-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// bootstrap/app.php — 오류가 귀찮아 CSRF 검사를 통째로 뺌
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['*']);
})
```

✓ 안전 코드 (safe example)

```php
// bootstrap/app.php — 예외는 서명을 검사하는 외부 웹훅만
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['webhooks/payments']);
})

// 폼마다 토큰: <form method="POST" action="/account/email"> @csrf ... </form>
// fetch 는 X-CSRF-TOKEN 헤더에 <meta name="csrf-token"> 값을 싣는다
```
