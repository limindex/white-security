<!-- generated from white-security issues; do not edit -->
# WS-HDR-001 코드 예시 — PHP

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
$from = $_POST['email'];                    // 줄바꿈과 함께 다른 헤더가 섞일 수 있다
mail('help@example.com', '문의', $_POST['message'], "From: $from\r\nReply-To: $from");
```

✓ 안전 코드 (safe example)

```php
<?php
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);   // 줄바꿈이 든 주소는 false
if ($email === false) { http_response_code(400); exit('이메일 형식이 맞지 않습니다'); }

mail('help@example.com', '=?UTF-8?B?' . base64_encode('문의') . '?=', (string) ($_POST['message'] ?? ''), [
    'From' => 'no-reply@example.com',        // 보내는 주소는 고정
    'Reply-To' => $email,                    // 검사를 통과한 값만
    'Content-Type' => 'text/plain; charset=UTF-8',
]);
```
