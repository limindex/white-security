<!-- generated from white-security issues; do not edit -->
# WS-MOB-003 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
app.post("/auth/magic/start", async (req, res) => {
  const user = await db.users.findByEmail(req.body.email);
  if (user) {
    const token = jwt.sign({ sub: user.id }, process.env.JWT_SECRET, { expiresIn: "30d" });
    await sendMail(user.email, `myapp://login?token=${token}`);   // 같은 스킴을 등록한 앱이 받아 가면 계정 탈취
  }
  res.sendStatus(204);
});
```

✓ 안전 코드 (safe example)

```js
import crypto from "node:crypto";
const sha256 = (s) => crypto.createHash("sha256").update(s).digest("base64url");

app.post("/auth/magic/start", magicLimiter, async (req, res) => {        // 발송 한도(WS-SMS-001)
  const { email, codeChallenge } = req.body;
  const user = await db.users.findByEmail(email);
  if (user && /^[A-Za-z0-9_-]{43}$/.test(codeChallenge ?? "")) {
    const code = crypto.randomBytes(32).toString("base64url");             // 43자, 1회용
    await db.magicCodes.insert({ codeHash: sha256(code), userId: user.id, codeChallenge,
                                 expiresAt: new Date(Date.now() + 10 * 60_000) });
    await sendMail(user.email, `https://app.example.com/magic-login?code=${code}`); // App Links·유니버설 링크
  }
  res.sendStatus(204);                                                     // 계정 유무와 관계없이 같은 응답
});

app.post("/auth/magic/exchange", async (req, res) => {
  const { code, codeVerifier } = req.body;
  if (typeof code !== "string" || typeof codeVerifier !== "string") return res.sendStatus(400);
  // used_at IS NULL AND expires_at > now() 인 행만 used_at 을 채우고 돌려줌(한 번만 성공)
  const row = await db.magicCodes.consume(sha256(code));
  if (!row) return res.sendStatus(400);
  const a = Buffer.from(sha256(codeVerifier)), b = Buffer.from(row.codeChallenge);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return res.sendStatus(400); // 링크만 가로챈 기기는 실패
  res.json({ accessToken: issueAccessToken(row.userId) });                // 짧은 수명 토큰(WS-SESS-001)
});
```
