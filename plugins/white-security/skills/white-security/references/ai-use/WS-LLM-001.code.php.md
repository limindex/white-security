<!-- generated from white-security issues; do not edit -->
# WS-LLM-001 코드 예시 — php

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
$sql = llm_chat("이 질문에 맞는 SQL 을 써라: " . $_GET['q']);
foreach ($pdo->query($sql) as $row) {          // AI가 쓴 SQL 을 그대로 실행한다
    echo $row['name'], '<br>';
}
```

✓ 안전 코드 (safe example)

```php
<?php
$f = json_decode(llm_chat('질문을 JSON {"category": 문자열, "max_price": 숫자} 로만 바꿔라: ' . $_GET['q']), true);
$categories = ['shoes', 'bags', 'hats'];
if (!is_array($f) || !in_array($f['category'] ?? null, $categories, true) || !is_numeric($f['max_price'] ?? null)) {
    http_response_code(422);
    exit('질문을 이해하지 못했습니다');
}
$st = $pdo->prepare('SELECT name FROM products WHERE category = ? AND price <= ? LIMIT 50');
$st->execute([$f['category'], (int) $f['max_price']]);
foreach ($st as $row) {
    echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'), '<br>';
}
```
