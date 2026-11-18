<!-- generated from white-security issues; do not edit -->
# WS-SEC-001 코드 예시 — php

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
// config.php (repository에 커밋됨)
$pdo = new PDO('mysql:host=db;dbname=shop;charset=utf8mb4', 'shop', 'Winter2026!');
define('SMS_API_SECRET', 'b1946ac92492d2347c6235b4d2611184');
```

✓ 안전 코드 (safe example)

```php
<?php
// config.php
function env_required(string $name): string
{
    $v = getenv($name);
    if ($v === false || $v === '') throw new RuntimeException("$name 환경 변수가 없습니다");
    return $v;
}

$pdo = new PDO('mysql:host=db;dbname=shop;charset=utf8mb4', env_required('DB_USER'), env_required('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$smsSecret = env_required('SMS_API_SECRET');

// .env 는 웹 루트 밖에 두고 .gitignore 에 넣는다. 이미 올라간 비밀번호·키는 먼저 바꾼다.
```
