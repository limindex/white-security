<!-- generated from white-security issues; do not edit -->
# WS-AUTH-001 코드 예시 — php

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
// download.php?id=1043
session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); exit; }

$st = $pdo->prepare('SELECT path, name FROM files WHERE id = ?');
$st->execute([$_GET['id'] ?? 0]);           // 로그인만 보고 누구 파일인지는 안 본다
$file = $st->fetch();
if (!$file) { http_response_code(404); exit; }
readfile($file['path']);

// pay.php: 금액을 브라우저가 보낸 값 그대로 쓴다
$amount = (int)($_POST['amount'] ?? 0);
```

✓ 안전 코드 (safe example)

```php
<?php
session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); exit; }

$st = $pdo->prepare('SELECT path, name FROM files WHERE id = ? AND owner_id = ?');
$st->execute([(int)($_GET['id'] ?? 0), $_SESSION['user_id']]);
$file = $st->fetch();
if (!$file) { http_response_code(404); exit; }   // 남의 파일도 "없음"
header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($file['name']));
readfile($file['path']);

// pay.php: 금액은 서버의 주문 기록으로 정한다
$st = $pdo->prepare('SELECT total_amount FROM orders WHERE id = ? AND user_id = ? AND status = ?');
$st->execute([(int)($_POST['order_id'] ?? 0), $_SESSION['user_id'], 'pending']);
$amount = $st->fetchColumn();
if ($amount === false) { http_response_code(404); exit; }
```
