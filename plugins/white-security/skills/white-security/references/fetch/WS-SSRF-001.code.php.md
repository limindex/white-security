<!-- generated from white-security issues; do not edit -->
# WS-SSRF-001 코드 예시 — PHP

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
echo file_get_contents($_GET['url']); // 내부 주소·file:// 도 대신 열어 준다
```

✓ 안전 코드 (safe example)

```php
<?php
function safe_fetch(string $url): string
{
    $u = parse_url($url);
    $host = $u['host'] ?? '';
    if (($u['scheme'] ?? '') !== 'https' || isset($u['port']) || isset($u['user'])
        || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || filter_var($host, FILTER_VALIDATE_IP)) {
        throw new InvalidArgumentException('https 기본 포트의 도메인 주소만 받습니다');
    }
    $ip = gethostbyname($host); // 실패하면 이름을 그대로 돌려주므로 아래 검사에서 걸린다
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE)) { // PHP 8.2+
        throw new InvalidArgumentException('내부 주소로는 요청하지 않습니다');
    }
    $ch = curl_init("https://$host" . ($u['path'] ?? '/') . (isset($u['query']) ? "?{$u['query']}" : ''));
    curl_setopt_array($ch, [
        CURLOPT_RESOLVE => ["$host:443:$ip"], // 검사한 IP 로만 접속한다
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5, CURLOPT_NOPROGRESS => false,
        CURLOPT_XFERINFOFUNCTION => fn($c, $total, $now) => $now > 2_000_000 ? 1 : 0, // 길이를 모르는 응답도 2MB에서 끊는다
    ]);
    $body = curl_exec($ch);
    if ($body === false) throw new RuntimeException('가져오지 못했습니다');
    return $body;
}
```
