<!-- generated from white-security issues; do not edit -->
# WS-INJ-004 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
app.post("/login", async (req, res) => {
  // 값 자리에 문자열 대신 연산자 객체가 들어오면 조건이 바뀐다
  const user = await User.findOne({ email: req.body.email, password: req.body.password });
  if (!user) return res.status(401).end();
  req.session.userId = user.id;
  res.json({ ok: true });
});
```

✓ 안전 코드 (safe example)

```js
mongoose.set("sanitizeFilter", true); // 값 자리의 연산자 객체를 $eq 로 감싸 값으로 비교

app.post("/login", async (req, res) => {
  const { email, password } = req.body ?? {};
  if (typeof email !== "string" || typeof password !== "string") return res.status(400).end(); // 형 확인
  const user = await User.findOne({ email });
  if (!user || !(await bcrypt.compare(password, user.passwordHash))) return res.status(401).end();
  req.session.regenerate((err) => {
    if (err) return res.sendStatus(500);
    req.session.userId = user.id;
    res.json({ ok: true });
  });
});
```
