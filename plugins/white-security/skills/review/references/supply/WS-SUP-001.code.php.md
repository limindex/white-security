<!-- generated from white-security issues; do not edit -->
# WS-SUP-001 코드 예시 — PHP

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
// 관리 도구의 "패키지 추가": AI가 추천한 이름을 그대로 설치한다
shell_exec('composer require ' . escapeshellarg($suggested)); // 따옴표 처리를 해도 실재 확인은 없다
```

✓ 안전 코드 (safe example)

```php
<?php
/** @return array{0: bool, 1: string} [설치해도 되는가, 이유] */
function check_packagist(string $name, int $minAgeDays = 90): array
{
    if (!preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $name)) return [false, '이름 형식이 아님'];
    $ch = curl_init("https://packagist.org/packages/{$name}.json");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($body === false) return [false, '확인 못 함(네트워크)'];
    if ($status === 404) return [false, '없음'];
    if ($status !== 200) return [false, "확인 못 함(HTTP $status)"];
    $created = strtotime(json_decode($body, true)['package']['time'] ?? '');
    if ($created === false) return [false, '등록일 확인 못 함'];
    if (time() - $created < $minAgeDays * 86400) return [false, '새 패키지(' . date('Y-m-d', $created) . ' 등록)'];
    return [true, '있음'];
}

// 사람이 승인한 뒤: composer require --no-plugins vendor/name:<정확한 버전> (config.allow-plugins 에는 허용한 플러그인만)
// composer.lock 을 함께 커밋하고, 배포는 composer install 로 잠금 파일대로만 설치한다.
```
