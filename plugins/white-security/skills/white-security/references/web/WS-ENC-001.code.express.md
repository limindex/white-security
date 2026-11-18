<!-- generated from white-security issues; do not edit -->
# WS-ENC-001 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
let currentUser = null;                       // 모든 요청이 함께 쓰는 변수

app.use(async (req, res, next) => {
  currentUser = await findUserBySession(req.cookies.sid);
  next();
});

app.get("/me", async (req, res) => {
  await loadSomething();                      // 기다리는 사이 다른 요청이 currentUser 를 바꾼다
  res.json(currentUser);                      // 남의 정보, 비밀번호 해시까지
});
app.get("/debug/env", (req, res) => res.json(process.env)); // 남은 디버그 라우트
```

✓ 안전 코드 (safe example)

```js
app.use(async (req, res, next) => {
  req.user = await findUserBySession(req.cookies.sid);   // 요청 객체에 둔다
  next();
});

app.get("/me", async (req, res) => {
  if (!req.user) return res.sendStatus(401);
  const { id, name, email } = req.user;                  // 보낼 필드만 고른다
  res.json({ id, name, email });
});
// /debug 라우트는 지운다
```
