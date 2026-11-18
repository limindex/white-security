<!-- generated from white-security issues; do not edit -->
# WS-DOS-001 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
const NAME = /^([a-zA-Z0-9]+\s?)*$/;   // 중첩 반복: 끝이 틀린 긴 입력에서 오래 걸린다
app.post("/signup", (req, res) => {
  if (!NAME.test(req.body.name)) return res.status(400).send("이름 형식이 맞지 않습니다");
  res.json({ ok: true });
});
```

✓ 안전 코드 (safe example)

```js
const NAME = /^[a-zA-Z0-9]+(?: [a-zA-Z0-9]+)*$/;   // 단어 사이 공백 하나, 겹치지 않는 반복
app.post("/signup", (req, res) => {
  const name = String(req.body.name ?? "");
  if (name.length > 50 || !NAME.test(name)) return res.status(400).send("이름 형식이 맞지 않습니다"); // 길이 먼저
  res.json({ ok: true });
});
```
