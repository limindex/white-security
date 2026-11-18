<!-- generated from white-security issues; do not edit -->
# WS-INJ-001 코드 예시 — PHP

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
$q = addslashes($_GET['q'] ?? '');               // 이스케이프 + 이어 붙이기
$sql = "SELECT id, title FROM board WHERE title LIKE '%$q%' ORDER BY " . ($_GET['sort'] ?? 'id');
$rows = $pdo->query($sql)->fetchAll();
```

✓ 안전 코드 (safe example)

```php
<?php
$pdo = new PDO('mysql:host=db;dbname=app;charset=utf8mb4', $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,         // 서버 쪽 준비문
]);

$sorts = ['id' => 'id DESC', 'title' => 'title ASC'];
$sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : '';
$orderBy = $sorts[$sort] ?? 'id DESC';           // 식별자는 허용 목록에서
$q = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';

$st = $pdo->prepare("SELECT id, title FROM board WHERE title LIKE ? ORDER BY $orderBy");
$st->execute(['%' . $q . '%']);                   // 값은 바인딩
$rows = $st->fetchAll();
```
