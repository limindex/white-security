<!-- generated from white-security issues; do not edit -->
# WS-CFG-002 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// config/cors.php — Sanctum SPA 쿠키 로그인을 쓰려고 credentials 만 켬
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],                       // credentials 와 함께면 요청 Origin 을 그대로 돌려줌
    'allowed_origins_patterns' => ['#example.com#'],  // evil-example.com.attacker.io 도 통과
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

✓ 안전 코드 (safe example)

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    // 예: CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
    'allowed_origins' => array_values(array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))),
    'allowed_origins_patterns' => [],                 // 꼭 필요하면 '#^https://app\.example\.com$#' 처럼 고정
    'allowed_headers' => ['Content-Type', 'X-XSRF-TOKEN', 'X-Requested-With'],
    'supports_credentials' => true,
];
// 바꾼 뒤 운영에서 php artisan config:cache
```
