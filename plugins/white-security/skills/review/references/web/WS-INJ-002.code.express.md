<!-- generated from white-security issues; do not edit -->
# WS-INJ-002 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
import { exec } from "node:child_process";

app.post("/ping", (req, res) => {
  exec(`ping -c 1 ${req.body.host}`, (err, out) => res.send(out)); // host 에 "; echo banana"
});
```

✓ 안전 코드 (safe example)

```js
import { execFile } from "node:child_process";
import net from "node:net";

const HOST = /^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i; // '-' 로 시작 불가

app.post("/ping", (req, res) => {
  const host = String(req.body.host ?? "");
  if (!net.isIPv4(host) && !HOST.test(host)) return res.status(400).send("잘못된 주소");
  execFile("ping", ["-c", "1", host], { timeout: 5000 }, (err, out) => { // 셸 없이 인자 목록으로
    if (err) return res.status(502).send("실패");
    res.type("text/plain").send(out);
  });
});
```
