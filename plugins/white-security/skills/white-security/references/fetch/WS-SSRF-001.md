<!-- generated from white-security issues; do not edit -->
# WS-SSRF-001 SSRF (서버사이드 요청 위조)

심각도: 위험
기준: OWASP Top 10:2025 A01(접근 통제 실패) · OWASP API 2023 API7(서버사이드 요청 위조) · ASVS 5.0 V1.3.6 · ASVS 5.0 V15.3.2 · ASVS 5.0 V13.2.4 · CWE-918(서버사이드 요청 위조) · 국내 보안약점 49 입력데이터 검증 및 표현-12(서버사이드 요청 위조) · 기반시설 웹 21 SF(서버사이드 요청 위조)

## 점검 항목

- 서버가 대신 여는 URL은 `https`와 기본 포트만 받아야 하고, `file://`·`gopher://` 같은 다른 스킴은 막아야 한다.
- 이름(IP를 직접 쓴 주소 포함)을 IP로 바꾼 뒤 사설·루프백·링크 로컬·예약 대역(`10.*`, `172.16~31.*`, `192.168.*`, `127.*`, 메타데이터가 있는 `169.254.*`, `100.64.*`)을 막고, 검사한 그 IP로만 접속해야 한다. 검사 뒤 DNS 답을 바꾸는 DNS Rebinding 대비. IPv6까지 받으려면 대역을 따로 검토해야 하므로, 예시는 IPv4만 받음.
- 리다이렉트는 따라가지 않거나, 따라갈 때마다 새 주소를 같은 검사에 다시 넣어야 한다.
- 시간·크기 상한을 두어야 하고, 가져온 응답이나 오류 원문을 사용자에게 그대로 돌려주지 않아야 한다.
- 가져올 도메인이 정해져 있으면 허용 목록을 써야 한다. 클라우드에서는 IMDSv2(토큰이 있어야 답하는 메타데이터 방식)를 켜고, 서버의 바깥 요청은 전용 프록시·방화벽으로 좁혀야 한다.
- AI 에이전트의 웹 가져오기 도구도 이 함수를 거쳐야 한다.

## 탐지 규칙

- **찾는 곳**: 외부 입력이나 LLM 출력이 들어가는 `requests.get(`, `httpx.`, `urllib.request.urlopen(`, `fetch(`, `axios(`, `got(`, `http.get(`, `file_get_contents(`, `curl_init(`, `Guzzle`, 헤드리스 브라우저의 `page.goto(`.
- **검사 1 — 주소 검사 없음**: 지적할 때: 요청·DB·LLM 출력에서 온 URL을 검사 없이 서버가 여는 경우. / 지적하지 않을 때: URL이 코드에 고정된 경우. 서버가 정한 도메인 허용 목록에서만 고르는 경우.
- **검사 2 — 문자열에만 머무는 검사**: 지적할 때: 호스트 이름을 문자열로만 막는 경우(`"localhost" in url`, `startswith`). IP를 검사한 뒤 라이브러리가 DNS를 다시 조회해 접속하는 경우. Node처럼 IP 주소면 `lookup`을 건너뛰는 구조에서 IP 주소를 따로 막지 않는 경우. 리다이렉트를 검사 없이 따라가는 경우. / 지적하지 않을 때: 확인한 IP로 접속을 고정하고(`CURLOPT_RESOLVE`, 사용자 정의 `lookup`, 연결 고정), 리다이렉트를 끄거나 매번 다시 검사하는 경우. 바깥 요청 전용 프록시가 내부 대역을 막는 경우.
- **등급**: 위험: 사용자·AI가 준 URL을 검사 없이 여는 경우. 특히 응답이 사용자에게 돌아가거나 클라우드 서버에서 도는 경우. / 주의: 문자열 검사만 있거나 DNS를 두 번 조회하는 경우. 리다이렉트를 따라가는 경우. / 참고: 시간·크기 제한이 없는 경우. PHP `CURLOPT_MAXFILESIZE`에만 기대는 경우(curl 8.4 전에는 길이를 모르는 응답에 효과 없음).
- **보고 예**: `[위험] WS-SSRF-001 api/preview.py:12 — 링크 미리보기가 받은 URL 을 requests.get 으로 그대로 열고 본문을 돌려줌 → https 기본 포트만, 내부 IP 차단, 검사한 IP 로만 접속, 리다이렉트 끔`

## 코드 예시

### python

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/preview")
def preview():
    return requests.get(request.args["url"], timeout=10).text  # 내부 주소도 대신 열어 준다
```

✓ 안전 코드 (safe example)

```python
import http.client
import ipaddress
import socket
import ssl
from urllib.parse import urlsplit

def safe_fetch(url: str, limit: int = 2_000_000) -> bytes:
    u = urlsplit(url)
    if u.scheme != "https" or not u.hostname or u.port not in (None, 443) or u.username:
        raise ValueError("https 기본 포트 주소만 받습니다")
    ip = ipaddress.IPv4Address(socket.getaddrinfo(u.hostname, 443, socket.AF_INET, socket.SOCK_STREAM)[0][4][0])
    if not ip.is_global or ip.is_reserved or ip.is_multicast:  # IPv4 만 받는다
        raise ValueError("내부 주소로는 요청하지 않습니다")  # 127.*, 10.*, 169.254.169.254 등
    sock = socket.create_connection((str(ip), 443), timeout=5)  # 검사한 IP 로만 접속한다
    conn = http.client.HTTPSConnection(u.hostname, 443, timeout=5)
    conn.sock = ssl.create_default_context().wrap_socket(sock, server_hostname=u.hostname)
    conn.request("GET", (u.path or "/") + (f"?{u.query}" if u.query else ""))
    return conn.getresponse().read(limit)  # 리다이렉트는 따라가지 않는다
```

### js

✗ 취약 코드 (vulnerable example — do not use)

```js
app.get("/preview", async (req, res) => {
  const r = await fetch(req.query.url); // 내부 주소도 대신 열어 준다
  res.send(await r.text());
});
```

✓ 안전 코드 (safe example)

```js
import dns from "node:dns";
import https from "node:https";
import net from "node:net";

const blocked = new net.BlockList();
for (const [ip, bits] of [["0.0.0.0", 8], ["10.0.0.0", 8], ["100.64.0.0", 10], ["127.0.0.0", 8], ["169.254.0.0", 16],
  ["172.16.0.0", 12], ["192.0.0.0", 24], ["192.168.0.0", 16], ["198.18.0.0", 15], ["224.0.0.0", 3]]) blocked.addSubnet(ip, bits, "ipv4");

function checkedLookup(host, opts, cb) { // 실제로 접속할 IP 를 여기서 검사한다
  dns.lookup(host, { family: 4 }, (err, ip) => {
    if (err) return cb(err);
    if (blocked.check(ip, "ipv4")) return cb(new Error("내부 주소로는 요청하지 않습니다"));
    opts.all ? cb(null, [{ address: ip, family: 4 }]) : cb(null, ip, 4);
  });
}

export function safeFetch(url, limit = 2_000_000) {
  const u = new URL(url);
  if (u.protocol !== "https:" || u.port || u.username) throw new Error("https 기본 포트 주소만 받습니다");
  if (net.isIP(u.hostname.replace(/^\[|\]$/g, ""))) throw new Error("IP 주소는 받지 않습니다"); // IP 는 lookup 을 거치지 않는다
  return new Promise((resolve, reject) => {
    const req = https.get(u, { lookup: checkedLookup, timeout: 5000 }, (res) => { // 리다이렉트는 따라가지 않는다
      const chunks = []; let size = 0;
      res.on("data", (c) => { size += c.length; if (size > limit) req.destroy(new Error("너무 큽니다")); else chunks.push(c); });
      res.on("end", () => resolve(Buffer.concat(chunks)));
    });
    req.on("timeout", () => req.destroy(new Error("시간 초과")));
    req.on("error", reject);
  });
}
```

### php

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
