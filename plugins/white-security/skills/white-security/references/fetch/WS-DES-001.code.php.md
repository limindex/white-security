<!-- generated from white-security issues; do not edit -->
# WS-DES-001 코드 예시 — php

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
// __wakeup·__destruct 같은 매직 메서드를 가진 클래스 객체가 만들어진다
$cart = unserialize(base64_decode($_COOKIE['cart']));
```

✓ 안전 코드 (safe example)

```php
<?php
[$raw, $sig] = explode('.', $_COOKIE['cart'] ?? '', 2) + ['', ''];
if (!hash_equals(hash_hmac('sha256', $raw, $secretKey), $sig)) { // 서버가 만든 값인지 확인한다
    http_response_code(400);
    exit('잘못된 요청입니다');
}
$cart = json_decode(base64_decode($raw, true) ?: '[]', true, 8, JSON_THROW_ON_ERROR); // 깊이 8까지만
if (!is_array($cart)) {
    http_response_code(400);
    exit('잘못된 요청입니다');
}
```
