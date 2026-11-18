<!-- generated from white-security issues; do not edit -->
# WS-CFG-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// config/app.php — 값이 없으면 켜지게 기본값을 바꿔 둠
'debug' => (bool) env('APP_DEBUG', true),

// 운영 서버 .env — 개발용 .env.example 을 그대로 복사해 씀
// APP_ENV=local
// APP_DEBUG=true
```

✓ 안전 코드 (safe example)

```php
// config/app.php — 프레임워크 기본값 그대로(값이 없으면 꺼짐)
'debug' => (bool) env('APP_DEBUG', false),

// 운영 서버 .env
// APP_ENV=production
// APP_DEBUG=false

// 배포 명령(.env 를 바꾼 뒤에는 설정 캐시를 다시 만든다)
// composer install --no-dev --optimize-autoloader
// php artisan config:cache
```
