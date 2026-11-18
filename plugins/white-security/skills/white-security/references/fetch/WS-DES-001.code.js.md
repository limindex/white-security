<!-- generated from white-security issues; do not edit -->
# WS-DES-001 코드 예시 — js

✗ 취약 코드 (vulnerable example — do not use)

```js
import serialize from "node-serialize";

// 함수까지 되살려 실행할 수 있다(CVE-2017-5941)
const prefs = serialize.unserialize(Buffer.from(req.cookies.prefs, "base64").toString());
```

✓ 안전 코드 (safe example)

```js
let prefs;
try {
  prefs = JSON.parse(Buffer.from(req.cookies.prefs ?? "", "base64").toString());
} catch {
  prefs = {};
}
const theme = ["light", "dark"].includes(prefs?.theme) ? prefs.theme : "light"; // 쓸 값만 꺼내 검사한다
```
