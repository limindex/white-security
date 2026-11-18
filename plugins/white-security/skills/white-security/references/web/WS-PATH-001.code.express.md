<!-- generated from white-security issues; do not edit -->
# WS-PATH-001 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
app.get("/files", (req, res) => {
  res.sendFile(path.join(__dirname, "files", req.query.name)); // name=../.env 도 열린다
});
```

✓ 안전 코드 (safe example)

```js
import path from "node:path";

const BASE = path.resolve("/srv/app/files");
const NAME = /^[A-Za-z0-9._-]{1,100}$/;   // 허용 문자만(₩·\ 는 통과 못 함)

app.get("/files", (req, res) => {
  const name = String(req.query.name ?? "");
  if (!NAME.test(name) || name.startsWith(".")) return res.sendStatus(400);
  const full = path.resolve(BASE, name);
  if (!full.startsWith(BASE + path.sep)) return res.sendStatus(404);   // 정규화 뒤 기준 폴더 안인지
  res.sendFile(full, { dotfiles: "deny" }, (err) => { if (err && !res.headersSent) res.sendStatus(404); });
});
```
