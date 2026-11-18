<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-AGT-004 Confused Deputy (권한 빌려 쓰기·에이전트 간 통신 검증 누락)

위험 · OWASP A01 · 에이전트 ASI03 · 에이전트 ASI07 · ASVS V8.3.3 · ASVS V13.2.1 · CWE-441 · CWE-306 · 진단가이드 보안기능-2 · 기반시설 IN

## 점검 항목

- 도구는 요청한 사용자의 권한으로 데이터에 접근해야 하고, 사용자 ID는 모델이 준 인자가 아니라 서버 세션에서 가져와야 한다.
- 받은 사용자 토큰을 다른 서비스에 그대로 넘기지 않아야 한다. MCP 보안 모범 사례도 자기에게 발급되지 않은 토큰을 받아 쓰는 Token Passthrough를 금지함.
- 에이전트끼리 주고받는 요청은 인증·서명하고, 받는 쪽이 보낸 쪽 권한을 다시 확인해야 한다. 메시지 ID와 시각으로 재전송(Replay)을 막아야 하고, 가능하면 mTLS(서버와 클라이언트가 서로 인증서로 확인하는 연결)나 검증된 서명 라이브러리를 써야 한다.
- 메시지 안의 "관리자 에이전트" 같은 주장은 믿지 않고, 권한은 서버 설정으로 정해야 한다.
- 에이전트마다 따로 계정·키를 주어야 하고, 관리자 키 하나를 여러 에이전트가 나눠 쓰지 않아야 한다.

## 탐지 규칙

**찾는 곳**: 에이전트 도구 함수의 인자(`user_id`, `customer_id`, `tenant_id`, `as_user`), 도구 안의 DB·API 호출이 쓰는 계정, 에이전트 사이 통신 엔드포인트(`/agent`, `/a2a`, 큐 소비자, 웹훅).

**검사 1. 원래 요청자 권한 미사용**
- 지적할 때: 도구가 넓은 서비스 계정으로 조회하면서 사용자 범위 조건이 없는 경우. 사용자 ID를 모델이 채운 인자에서 받는 경우.
- 지적하지 않을 때: 서버가 세션 사용자를 도구에 묶어 두고 모든 조회에 그 범위를 거는 경우.

**검사 2. 다른 에이전트 요청의 무조건 신뢰**
- 지적할 때: 에이전트 간 엔드포인트에 인증·서명 검사가 없는 경우. 본문의 `from`·`role` 값으로 권한을 정하는 경우.
- 지적하지 않을 때: mTLS·서명 검사·재전송 방지가 있고, 보낸 쪽별 허용 작업 목록을 서버가 확인하는 경우.

**등급**
- 위험: 에이전트를 통해 다른 사용자 데이터에 닿는 경우. 인증 없는 에이전트 엔드포인트가 쓰기 작업을 하는 경우.
- 주의: 서명은 있지만 재전송 방지·보낸 쪽 권한 확인이 없는 경우. 여러 에이전트가 관리자 키 하나를 나눠 쓰는 경우.
- 참고: 에이전트별 호출 기록이 없는 경우.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-AGT-004 agent/tools.py:30 — get_invoice 가 모델이 준 user_id 로 서비스 계정 조회 → 세션 사용자로 도구를 만들고 customer_id 조건을 고정`

## 코드 예시

### python

✗ 취약 코드

```python
@tool
def get_invoice(invoice_id: str, user_id: str) -> dict:  # user_id 를 모델이 채운다
    return service_db.invoices.find_one({"id": invoice_id})  # 서비스 계정은 모든 고객 송장을 본다
```

✓ 안전 코드

```python
def make_tools(session_user):  # 요청마다 서버가 세션 사용자로 도구를 만든다
    @tool
    def get_invoice(invoice_id: str) -> dict:
        inv = service_db.invoices.find_one({"id": invoice_id, "customer_id": session_user.customer_id})
        return inv or {"error": "송장을 찾을 수 없습니다"}
    return [get_invoice]
```

### js

✗ 취약 코드

```js
app.post("/agent/inbox", express.json(), async (req, res) => {
  const { from, action, args } = req.body; // 누가 보냈는지 확인하지 않는다
  if (from === "billing-agent") await actions[action](args);
  res.sendStatus(200);
});
```

✓ 안전 코드

```js
import crypto from "node:crypto";

const PEERS = { "billing-agent": { key: process.env.BILLING_AGENT_KEY, allowed: new Set(["create_invoice"]) } };
const seen = new Set(); // 실제로는 만료 시간이 있는 repository(Redis 등)

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
