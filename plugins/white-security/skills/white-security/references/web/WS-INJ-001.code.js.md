<!-- generated from white-security issues; do not edit -->
# WS-INJ-001 코드 예시 — JavaScript

✗ 취약 코드 (vulnerable example — do not use)

```js
// Express + mysql2
app.post("/login", async (req, res) => {
  const { email, password } = req.body;
  const [rows] = await pool.query(
    `SELECT id, password_hash FROM users WHERE email = '${email}'` // 입력이 명령문에 끼워진다
  );
  const user = rows[0];
  if (!user || !(await bcrypt.compare(password, user.password_hash))) {
    return res.status(401).json({ error: "이메일 또는 비밀번호가 맞지 않습니다" });
  }
  req.session.userId = user.id;
  res.json({ ok: true });
});
```

✓ 안전 코드 (safe example)

```js
app.post("/login", async (req, res) => {
  const email = String(req.body.email ?? "");
  const [rows] = await pool.execute(
    "SELECT id, password_hash FROM users WHERE email = ?", // 빈칸만 둔다
    [email]                                                // 값은 따로
  );
  const user = rows[0];
  if (!user || !(await bcrypt.compare(String(req.body.password ?? ""), user.password_hash))) {
    return res.status(401).json({ error: "이메일 또는 비밀번호가 맞지 않습니다" });
  }
  req.session.regenerate((err) => {              // 로그인 뒤 세션 ID 를 새로 발급한다
    if (err) return res.sendStatus(500);
    req.session.userId = user.id;
    res.json({ ok: true });
  });
});
```
