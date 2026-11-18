<!-- generated from white-security issues; do not edit -->
# WS-LLM-004 코드 예시 — js

✗ 취약 코드 (vulnerable example — do not use)

```js
app.post("/api/ask", async (req, res) => {
  const r = await openai.responses.create({ model: MODEL, input: req.body.q });
  res.json({ answer: r.output_text });
});
```

✓ 안전 코드 (safe example)

```js
import rateLimit from "express-rate-limit";

const perUser = rateLimit({ windowMs: 60_000, limit: 10, keyGenerator: (req) => String(req.user.id) });

app.post("/api/ask", requireLogin, perUser, async (req, res) => {
  const q = String(req.body.q ?? "").slice(0, 4000);
  if (!(await dailyBudget.tryUse(req.user.id))) return res.status(429).json({ error: "오늘 사용량을 다 썼습니다" });
  const r = await openai.responses.create({ model: MODEL, input: q, max_output_tokens: 800 }, { timeout: 30_000 });
  res.json({ answer: r.output_text });
});
```
