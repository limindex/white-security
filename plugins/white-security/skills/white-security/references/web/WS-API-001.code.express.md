<!-- generated from white-security issues; do not edit -->
# WS-API-001 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
import dns from "node:dns/promises";

app.use("/admin", async (req, res, next) => {
  const names = await dns.reverse(req.socket.remoteAddress).catch(() => []);
  if (names.some((n) => n.endsWith(".corp.example.com"))) return next(); // 역방향 DNS 이름은 IP 주인이 정한다
  res.sendStatus(403);
});
```

✓ 안전 코드 (safe example)

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
