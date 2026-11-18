---
id: WS-API-001
title: Dangerous API Use (위험한 API 사용·DNS 기반 보안 결정)
slug: dangerous-api
group: web
severity: medium
site_only: false
depth: brief
languages: [express, python, php]
maps:
  owasp2025: [A06]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V15.3.5]
  cwe: [CWE-350, CWE-676, CWE-242, CWE-377, CWE-697]
  kr_guide: ["API 오용-1", "API 오용-2"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Express·Node.js, Python, PHP, 접근 허용·비밀값 비교·임시 파일을 다루는 코드]
severity_reason: "영향: 관리 기능 접근 제한 우회, 임시 파일 가로채기, 토큰 비교 약화 · 악용: 역방향 DNS 기록이나 요청 값 조작으로 가능"
sources:
  - title: "CWE-350: Reliance on Reverse DNS Resolution for a Security-Critical Action"
    url: https://cwe.mitre.org/data/definitions/350.html
    year: 2025
  - title: "CWE-676: Use of Potentially Dangerous Function"
    url: https://cwe.mitre.org/data/definitions/676.html
    year: 2025
  - title: "Python Docs - tempfile.mktemp (Deprecated, 보안 경고)"
    url: https://docs.python.org/3/library/tempfile.html#tempfile.mktemp
    year: 2026
  - title: "PHP Manual - extract() (신뢰할 수 없는 데이터에 쓰지 말 것)"
    url: https://www.php.net/manual/en/function.extract.php
    year: 2026
  - title: "PHP Manual - hash_equals()"
    url: https://www.php.net/manual/en/function.hash-equals.php
    year: 2026
---

## 요약

역방향 DNS 이름(`*.corp.example.com`)만 보고 관리 기능을 열거나, `extract($_GET)`·`tempfile.mktemp()`·비밀값의 일반 비교(`==`)처럼 위험하다고 알려진 쓰임새에 기대 보안 판단이 뒤집히는 문제(진단가이드 이름: DNS lookup에 의존한 보안결정·취약한 API 사용).

## 대응 방법

```text
접근 허용 판단에 역방향 DNS(gethostbyaddr, dns.reverse)나 호스트 이름을 쓰는 곳을 찾아 로그인·권한·서명 검사로 바꿔 줘.
extract($_GET/$_POST/$_REQUEST), tempfile.mktemp, new Buffer(숫자), 토큰·서명을 == 나 === 로 비교하는 코드를 찾아 안전한 대안으로 바꿔 줘.
```

- 바꾼 뒤 허용 판단이 위조하기 어려운 값(세션 권한·서명·인증서)으로만 이뤄져야 한다.

## 점검 항목

- 접근 허용은 역방향 DNS 이름이 아니라 인증·서명·IP 허용 목록으로 판단해야 한다.
- 요청 값을 `extract()`로 변수에 풀지 말고 필요한 값만 꺼내야 한다.
- 임시 파일은 `mktemp` 대신 `NamedTemporaryFile`·`mkstemp`로 만들어야 한다.
- 토큰·서명 비교는 `hash_equals`·`timingSafeEqual`·`compare_digest`로 해야 한다.
- PHP 비교는 `===`로 하고, 해시 문자열을 `==`로 비교하지 않아야 한다.
- 언어 문서가 폐기·위험으로 표시한 함수(`mysql_*`, `new Buffer()`)는 쓰지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `gethostbyaddr(`, `dns.reverse(`, `extract($_`, `tempfile.mktemp(`, 그 밖에 new Buffer·mysql_* 함수, 토큰·서명을 == 나 === 로 비교하는 코드.
- **검사 1 — DNS 이름으로 허용**: 지적할 때: 요청 IP의 역방향 DNS 이름이나 `Host` 헤더로 관리자·내부 기능을 허용하는 경우. / 지적하지 않을 때: 인증·mTLS·서명 검증으로 허용하는 경우. 검색 크롤러 확인처럼 정방향 조회로 다시 맞춰 보고(이름→IP 일치) 접근 권한과 무관한 곳에만 쓰는 경우.
- **검사 2 — 위험한 함수**: 지적할 때: 요청 배열에 `extract()`, `tempfile.mktemp`, Node `new Buffer(숫자)`, 제거된 `mysql_*` 함수를 쓰는 경우. / 지적하지 않을 때: 코드가 만든 배열에 `extract()`를 `EXTR_SKIP`으로 쓰는 경우.
- **검사 3 — 비밀값 비교**: 지적할 때: 토큰·서명·해시를 `==`·`===`·`!=`로 비교하는 경우, PHP 느슨한 비교(`==`)로 해시·숫자 문자열을 비교하는 경우. / 지적하지 않을 때: `hash_equals`·`password_verify`·`crypto.timingSafeEqual`·`hmac.compare_digest`를 쓰는 경우.
- **등급**: 위험: 역방향 DNS 이름으로 관리 기능 허용, 요청 배열에 `extract()`. / 주의: 비밀값 일반 비교, PHP `==` 해시 비교, `mktemp`. / 참고: `new Buffer()` 등 폐기 API.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-API-001 admin/index.php:4 — gethostbyaddr 결과가 .corp.example.com 으로 끝나면 관리자 허용 → 로그인·권한 확인으로 교체`
- **고칠 방법**: 위험 함수를 바꾼 뒤 같은 기능의 기존 테스트로 동작이 같은지 확인해야 한다.

## 코드 예시

### express

✗ 취약 코드

```js
import dns from "node:dns/promises";

app.use("/admin", async (req, res, next) => {
  const names = await dns.reverse(req.socket.remoteAddress).catch(() => []);
  if (names.some((n) => n.endsWith(".corp.example.com"))) return next(); // 역방향 DNS 이름은 IP 주인이 정한다
  res.sendStatus(403);
});
```

✓ 안전 코드

```js
import crypto from "node:crypto";

app.use("/admin", requireLogin, (req, res, next) => {   // 로그인 + 권한으로 판단
  if (req.user.role !== "admin") return res.sendStatus(403);
  next();
});

// 상대 서버를 확인해야 하면(웹훅) 이름 대신 서명을 검사한다
function verifySignature(body, sig, secret) {
  const expected = crypto.createHmac("sha256", secret).update(body).digest();
  const given = Buffer.from(String(sig), "hex");
  return given.length === expected.length && crypto.timingSafeEqual(given, expected); // 일정 시간 비교
}
```

### python

✗ 취약 코드

```python
import tempfile

def export_csv(rows):
    path = tempfile.mktemp(suffix=".csv")   # 이름만 정하고 파일은 나중에: 그 사이 다른 프로세스가 선점
    with open(path, "w") as f:
        f.writelines(rows)
    return path

def check_token(given: str, expected: str) -> bool:
    return given == expected                # 비교 시간이 값에 따라 달라진다
```

✓ 안전 코드

```python
import hmac
import tempfile

def export_csv(rows):
    with tempfile.NamedTemporaryFile("w", suffix=".csv", delete=False) as f:  # 만들면서 바로 연다(권한 0600)
        f.writelines(rows)
        return f.name

def check_token(given: str, expected: str) -> bool:
    return hmac.compare_digest(given.encode(), expected.encode())           # 일정 시간 비교
```

### php

✗ 취약 코드

```php
<?php
extract($_GET);                                     // 요청 값이 같은 이름의 내부 변수를 덮어쓴다
if (!empty($is_admin)) show_admin_menu();

if (md5($_POST['code']) == $storedHash) grant();    // 느슨한 비교: 형식에 따라 다른 문자열도 같다고 본다
```

✓ 안전 코드

```php
<?php
$isAdmin = ($_SESSION['role'] ?? null) === 'admin';            // 권한은 서버 세션에서, 기본값은 거부
if ($isAdmin) show_admin_menu();

$code = is_string($_POST['code'] ?? null) ? $_POST['code'] : '';
if (hash_equals($storedHash, hash('sha256', $code))) grant();  // 엄격·일정 시간 비교
```
