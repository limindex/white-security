<!-- generated from white-security issues; do not edit -->
# WS-API-001 코드 예시 — PHP

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
extract($_GET);                                     // 요청 값이 같은 이름의 내부 변수를 덮어쓴다
if (!empty($is_admin)) show_admin_menu();

if (md5($_POST['code']) == $storedHash) grant();    // 느슨한 비교: 형식에 따라 다른 문자열도 같다고 본다
```

✓ 안전 코드 (safe example)

```php
<?php
$isAdmin = ($_SESSION['role'] ?? null) === 'admin';            // 권한은 서버 세션에서, 기본값은 거부
if ($isAdmin) show_admin_menu();

$code = is_string($_POST['code'] ?? null) ? $_POST['code'] : '';
if (hash_equals($storedHash, hash('sha256', $code))) grant();  // 엄격·일정 시간 비교
```
