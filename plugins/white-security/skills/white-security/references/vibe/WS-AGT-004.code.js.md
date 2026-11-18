<!-- generated from white-security issues; do not edit -->
# WS-AGT-004 코드 예시 — js

✗ 취약 코드 (vulnerable example — do not use)

```js
app.post("/agent/inbox", express.json(), async (req, res) => {
  const { from, action, args } = req.body; // 누가 보냈는지 확인하지 않는다
  if (from === "billing-agent") await actions[action](args);
  res.sendStatus(200);
});
```

✓ 안전 코드 (safe example)

```js
import crypto from "node:crypto";

const PEERS = { "billing-agent": { key: process.env.BILLING_AGENT_KEY, allowed: new Set(["create_invoice"]) } };
const seen = new Set(); // 실제로는 만료 시간이 있는 저장소(Redis 등)

app.post("/agent/inbox", express.raw({ type: "application/json" }), async (req, res) => {
  const peer = PEERS[req.get("X-Agent-Id")];
  if (!peer || !Buffer.isBuffer(req.body) || req.body.length === 0) return res.sendStatus(401);
  const sig = Buffer.from(req.get("X-Signature") ?? "", "hex");
  const mac = crypto.createHmac("sha256", peer.key).update(req.body).digest();
  if (sig.length !== mac.length || !crypto.timingSafeEqual(sig, mac)) return res.sendStatus(401);
  let msg;
  try { msg = JSON.parse(req.body); } catch { return res.sendStatus(400); }
  if (msg.to !== "orders-agent" || !Number.isFinite(msg.ts) || Math.abs(Date.now() - msg.ts) > 60_000 || seen.has(msg.id)) return res.sendStatus(409);
  seen.add(msg.id); // 같은 메시지를 두 번 실행하지 않는다
  if (!peer.allowed.has(msg.action)) return res.sendStatus(403); // 보낸 쪽이 할 수 있는 일인가
  await actions[msg.action](msg.args);
  res.sendStatus(202);
});
```
