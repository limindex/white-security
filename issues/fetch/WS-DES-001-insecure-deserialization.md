---
id: WS-DES-001
title: Insecure Deserialization (안전하지 않은 역직렬화)
slug: insecure-deserialization
group: fetch
severity: high
site_only: false
depth: brief
languages: [python, js, php]
maps:
  owasp2025: [A08]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.5.2]
  cwe: [CWE-502]
  kr_guide: ["코드오류-5"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, Node.js, PHP, 모든 웹 애플리케이션]
severity_reason: "영향: 서버 원격 코드 실행 · 악용: 쿠키·업로드 등 조작한 데이터 하나로 가능"
sources:
  - title: OWASP Deserialization Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Deserialization_Cheat_Sheet.html
    year: 2026
  - title: "OWASP Top 10:2025 - A08 Software or Data Integrity Failures (CWE-502 포함)"
    url: https://top10.owasp.org/2025/A08_2025-Software_or_Data_Integrity_Failures
    year: 2025
  - title: "Python Docs - pickle (Warning)"
    url: https://docs.python.org/3/library/pickle.html
    year: 2026
  - title: "PHP Manual - unserialize"
    url: https://www.php.net/manual/en/function.unserialize.php
    year: 2026
  - title: "PyYAML - yaml.load(input) Deprecation"
    url: https://github.com/yaml/pyyaml/wiki/PyYAML-yaml.load(input)-Deprecation
    year: 2019
  - title: "NVD CVE-2017-5941 (node-serialize unserialize 원격 코드 실행)"
    url: https://nvd.nist.gov/vuln/detail/CVE-2017-5941
    year: 2017
---

## 요약

`pickle`, `yaml.load`, PHP `unserialize`처럼 글자를 프로그램 객체로 되살리는 함수에 남이 준 데이터를 넣을 때, 객체가 만들어지는 순간 공격자가 심은 코드가 실행되는 문제. Insecure Deserialization(안전하지 않은 역직렬화)이라 부름(진단가이드 이름: 신뢰할 수 없는 데이터의 역직렬화).

## 대응 방법

```text
pickle, yaml.load, unserialize, node-serialize, ObjectInputStream 으로 바깥 데이터를 읽는 곳을 모두 찾아 줘.
JSON 으로 바꾸고 읽은 뒤 형식을 검사해 줘. YAML 은 yaml.safe_load 로 바꿔 줘.
쿠키처럼 클라이언트가 들고 있는 값은 서명을 확인하거나, ID만 두고 내용은 서버에 두게 해 줘.
```

- 쿠키 값을 한 글자 바꿔 요청했을 때, 오류 화면 없이 거절(400)되어야 한다.

## 점검 항목

- 남이 준 데이터(쿠키·요청 본문·큐 메시지·업로드 파일)는 `pickle`, 안전 로더 없는 `yaml.load`, PHP `unserialize`, Java `ObjectInputStream`, `node-serialize`로 되살리지 않고 JSON을 써야 한다.
- YAML은 `yaml.safe_load`를 써야 한다.
- PHP `unserialize`에는 `allowed_classes` 옵션과 관계없이 믿을 수 없는 입력을 넣지 않아야 한다(PHP 공식 문서의 경고).
- 서버가 만든 값을 클라이언트에 맡겨야 하면 HMAC 서명(서버 비밀 키로 만든 확인값)을 붙이고 되살리기 전에 확인해야 한다.
- JSON으로 읽은 뒤에도 타입·필드를 검사하고, 쓸 값만 꺼내야 한다.
- AI 모델 파일(`.pkl`, `.pt` 등)도 pickle일 수 있으므로 같은 기준으로 다뤄야 한다(모델 공급망은 WS-SUP-008).

## 탐지 규칙

- **찾는 곳**: `pickle.load(s)`, `yaml.load(`·`yaml.unsafe_load`, `unserialize(`, `node-serialize`, `serialize-javascript`를 되살릴 때의 eval, `joblib.load`, `jsonpickle.decode`, `cPickle`, `dill.load`, `ObjectInputStream`, `XMLDecoder`, `BinaryFormatter`.
- **검사 1 — 바깥 데이터의 역직렬화**: 지적할 때: 요청·쿠키·파일·큐·외부 API에서 온 값이 위 함수로 들어가는 경우. / 지적하지 않을 때: 서버가 만들고 서버만 쓰는 파일(배포 산출물, 사내 캐시)이고 외부에서 쓸 수 없는 경우(참고로 JSON 전환 권장).
- **검사 2 — YAML 로더**: 지적할 때: `yaml.load(x)`에 `Loader`가 없거나 `Loader=yaml.Loader`·`UnsafeLoader`인 경우. `yaml.unsafe_load`를 쓰는 경우. / 지적하지 않을 때: `yaml.safe_load`나 `Loader=yaml.SafeLoader`인 경우. `FullLoader`는 참고로만 `safe_load` 권장.
- **등급**: 위험: 요청·쿠키·업로드 데이터가 `pickle`·`unserialize`·`node-serialize`·안전하지 않은 YAML 로더로 들어가는 경우. / 주의: 서명은 확인하지만 여전히 `pickle`·`unserialize`를 쓰는 경우(키가 새면 바로 뚫림). / 참고: 서버 내부 데이터에 `pickle`을 쓰는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-DES-001 shop/cart.py:19 — cart 쿠키를 pickle.loads 로 되살림 → JSON + HMAC 서명 확인, 또는 쿠키에는 장바구니 ID만`

## 코드 예시

### python

✗ 취약 코드

```python
cart = pickle.loads(base64.b64decode(request.cookies["cart"]))  # 쿠키를 만든 사람의 코드가 돈다
config = yaml.load(request.data, Loader=yaml.Loader)  # 태그로 파이썬 객체를 만든다
```

✓ 안전 코드

```python
import base64, hashlib, hmac, json, yaml

def load_cart(cookie: str) -> list:
    raw, _, sig = cookie.rpartition(".")
    good = hmac.new(SECRET_KEY, raw.encode(), hashlib.sha256).hexdigest()
    if not hmac.compare_digest(sig, good):  # 서버가 만든 값인지 확인한다
        raise ValueError("변조된 쿠키입니다")
    cart = json.loads(base64.urlsafe_b64decode(raw))  # JSON 은 데이터만 만든다
    if not isinstance(cart, list):
        raise ValueError("장바구니 형식이 아닙니다")
    return cart

config = yaml.safe_load(request.data)  # 기본 타입만 만든다
```

### js

✗ 취약 코드

```js
import serialize from "node-serialize";

// 함수까지 되살려 실행할 수 있다(CVE-2017-5941)
const prefs = serialize.unserialize(Buffer.from(req.cookies.prefs, "base64").toString());
```

✓ 안전 코드

```js
let prefs;
try {
  prefs = JSON.parse(Buffer.from(req.cookies.prefs ?? "", "base64").toString());
} catch {
  prefs = {};
}
const theme = ["light", "dark"].includes(prefs?.theme) ? prefs.theme : "light"; // 쓸 값만 꺼내 검사한다
```

### php

✗ 취약 코드

```php
<?php
// __wakeup·__destruct 같은 매직 메서드를 가진 클래스 객체가 만들어진다
$cart = unserialize(base64_decode($_COOKIE['cart']));
```

✓ 안전 코드

```php
<?php
[$raw, $sig] = explode('.', $_COOKIE['cart'] ?? '', 2) + ['', ''];
if (!hash_equals(hash_hmac('sha256', $raw, $secretKey), $sig)) { // 서버가 만든 값인지 확인한다
    http_response_code(400);
    exit('잘못된 요청입니다');
}
$cart = json_decode(base64_decode($raw, true) ?: '[]', true, 8, JSON_THROW_ON_ERROR); // 깊이 8까지만
if (!is_array($cart)) {
    http_response_code(400);
    exit('잘못된 요청입니다');
}
```
