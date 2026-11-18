<!-- generated from white-security issues; do not edit -->
# WS-LLM-001 코드 예시 — js

✗ 취약 코드 (vulnerable example — do not use)

```js
import { exec } from "node:child_process";

app.post("/api/git", async (req, res) => {
  const reply = await llm.chat(`사용자 요청을 git 명령으로 바꿔라: ${req.body.text}`);
  exec(reply.text, (err, out) => res.send(out)); // AI가 쓴 명령을 셸로 실행한다
});
// 브라우저: resultEl.innerHTML = AI 답  → AI가 쓴 태그가 그대로 화면에 들어간다
```

✓ 안전 코드 (safe example)

```js
import { execFile } from "node:child_process";

const ACTIONS = {
  status: ["git", ["status", "--short"]],
  log: ["git", ["log", "-5", "--oneline"]],
};
app.post("/api/git", async (req, res) => {
  const reply = await llm.chat(`요청을 {"action": "status" | "log"} JSON 으로만 답해라: ${req.body.text}`);
  let action;
  try { action = JSON.parse(reply.text).action; } catch { action = null; }
  if (!Object.hasOwn(ACTIONS, action)) return res.status(422).send("할 수 없는 요청입니다");
  const [cmd, args] = ACTIONS[action];
  execFile(cmd, args, { timeout: 5000 }, (err, out) => res.json({ out }));
});
// 브라우저: resultEl.textContent = AI 답  → 글자로만 보인다
```
