<!-- generated from white-security issues; do not edit -->
# WS-SEC-001 코드 예시 — JavaScript

✗ 취약 코드 (vulnerable example — do not use)

```js
// src/api.js (브라우저 번들에 들어간다)
export async function ask(prompt) {
  const res = await fetch("https://api.example-ai.com/v1/chat", {
    method: "POST",
    headers: { Authorization: `Bearer ${import.meta.env.VITE_AI_SECRET_KEY}` },
    body: JSON.stringify({ prompt }),
  });
  return res.json();
}
```

✓ 안전 코드 (safe example)

```js
// server.js (Node, 서버에서만 실행)
import express from "express";

const AI_KEY = process.env.AI_SECRET_KEY;
if (!AI_KEY) throw new Error("AI_SECRET_KEY 환경 변수가 없습니다");

const app = express();
app.use(express.json({ limit: "20kb" }));

app.post("/api/chat", requireLogin, async (req, res) => { // requireLogin: 앱의 로그인 확인 미들웨어
  const r = await fetch("https://api.example-ai.com/v1/chat", {
    method: "POST",
    headers: { Authorization: `Bearer ${AI_KEY}`, "Content-Type": "application/json" },
    body: JSON.stringify({ prompt: String(req.body.prompt ?? "").slice(0, 4000) }),
  });
  res.status(r.status).json(await r.json());
});
// 브라우저는 fetch("/api/chat") 만 부른다. 키는 번들에 들어가지 않는다.
```
