<!-- generated from white-security issues; do not edit -->
# WS-SHELL-001 코드 예시 — PHP

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
// public_html/upload.php
$name = $_FILES['file']['name'];                   // 사용자가 정한 이름(shell.php 도 가능)
move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . '/uploads/' . $name);
echo '/uploads/' . $name;                           // 주소로 열면 서버가 실행한다
```

✓ 안전 코드 (safe example)

```php
<?php
const UPLOAD_DIR = '/srv/app/storage/uploads';      // 웹 루트 밖
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

$f = $_FILES['file'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5_000_000) { http_response_code(400); exit; }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); // 내용으로 형식 판단
if (!isset($allowed[$mime])) { http_response_code(415); exit; }

$id = bin2hex(random_bytes(16));                    // 이름·확장자는 서버가 정한다
if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . "/$id." . $allowed[$mime])) { http_response_code(500); exit; }
// DB 에 id·원래 이름·올린 사람을 남기고, 내려받기는 권한 확인 뒤 download.php?id= 로
```
