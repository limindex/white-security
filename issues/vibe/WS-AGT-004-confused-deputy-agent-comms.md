---
id: WS-AGT-004
title: Confused Deputy (권한 빌려 쓰기·에이전트 간 통신 검증 누락)
slug: confused-deputy-agent-comms
group: vibe
severity: high
site_only: false
depth: brief
languages: [python, js]
maps:
  owasp2025: [A01]
  api2023: []
  llm2026: []
  agentic2026: [ASI03, ASI07]
  asvs5: [V8.3.3, V13.2.1]
  cwe: [CWE-441, CWE-306]
  kr_guide: ["보안기능-2"]
  kr_ciip: [IN]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, Node.js, 멀티 에이전트·서비스 계정 기반 에이전트]
severity_reason: "영향: 사용자 권한 밖 데이터 조회·작업 실행 · 악용: 에이전트에 요청하거나 가짜 에이전트 메시지 하나로 가능"
sources:
  - title: OWASP Top 10 for Agentic Applications 2026
    url: https://genai.owasp.org/resource/owasp-top-10-for-agentic-applications-for-2026/
    year: 2025
  - title: OWASP AI Agent Security Cheat Sheet (Multi-Agent Security)
    url: https://cheatsheetseries.owasp.org/cheatsheets/AI_Agent_Security_Cheat_Sheet.html
    year: 2026
  - title: "Model Context Protocol - Security Best Practices 2025-11-25 (Confused Deputy, Token Passthrough)"
    url: https://modelcontextprotocol.io/docs/2025-11-25/tutorials/security/security_best_practices
    year: 2025
  - title: "OWASP ASVS 5.0 - V8 Authorization (8.3.3)"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
---

## 요약

에이전트가 넓은 서비스 계정으로 일하면서 '누구 대신' 일하는지 확인하지 않아, 사용자가 볼 수 없는 데이터도 에이전트를 통해 꺼낼 수 있는 문제. Confused Deputy(권한을 빌려 주는 대리인 문제)라 부르며, 다른 에이전트가 보낸 요청을 확인 없이 믿는 것도 같은 구멍(진단가이드 이름: 부적절한 인가).

## 대응 방법

```text
에이전트 도구가 DB·API에 접근할 때 어떤 계정과 사용자 ID를 쓰는지 찾아 줘.
사용자 ID는 모델이 준 값 대신 서버 세션 값을 쓰고, 그 사용자 권한 안에서만 조회하게 바꿔 줘.
에이전트끼리 주고받는 요청은 서명을 검사하고 보낸 쪽이 할 수 있는 일인지 확인하게 해 줘.
```

- A 사용자로 로그인해 에이전트에게 "B 사용자 주문 내역 보여 줘"라고 요청했을 때, 거절되어야 한다.

## 점검 항목

- 도구는 요청한 사용자의 권한으로 데이터에 접근해야 하고, 사용자 ID는 모델이 준 인자가 아니라 서버 세션에서 가져와야 한다.
- 받은 사용자 토큰을 다른 서비스에 그대로 넘기지 않아야 한다. MCP 보안 모범 사례도 자기에게 발급되지 않은 토큰을 받아 쓰는 Token Passthrough를 금지함.
- 에이전트끼리 주고받는 요청은 인증·서명하고, 받는 쪽이 보낸 쪽 권한을 다시 확인해야 한다. 메시지 ID와 시각으로 재전송(Replay)을 막아야 하고, 가능하면 mTLS(서버와 클라이언트가 서로 인증서로 확인하는 연결)나 검증된 서명 라이브러리를 써야 한다.
- 메시지 안의 "관리자 에이전트" 같은 주장은 믿지 않고, 권한은 서버 설정으로 정해야 한다.
- 에이전트마다 따로 계정·키를 주어야 하고, 관리자 키 하나를 여러 에이전트가 나눠 쓰지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: 에이전트 도구 함수(`@tool`, `tools=`)의 인자(user_id, customer_id, tenant_id, `as_user`), 도구 안의 DB·API 호출이 쓰는 계정, 에이전트 사이 통신 엔드포인트(`/agent`, `/a2a`, 큐 소비자, 웹훅).
- **검사 1 — 원래 요청자 권한 미사용**: 지적할 때: 도구가 넓은 서비스 계정으로 조회하면서 사용자 범위 조건이 없는 경우. 사용자 ID를 모델이 채운 인자에서 받는 경우. / 지적하지 않을 때: 서버가 세션 사용자를 도구에 묶어 두고 모든 조회에 그 범위를 거는 경우.
- **검사 2 — 다른 에이전트 요청의 무조건 신뢰**: 지적할 때: 에이전트 간 엔드포인트에 인증·서명 검사가 없는 경우. 본문의 `from`·`role` 값으로 권한을 정하는 경우. / 지적하지 않을 때: mTLS·서명 검사·재전송 방지가 있고, 보낸 쪽별 허용 작업 목록을 서버가 확인하는 경우.
- **등급**: 위험: 에이전트를 통해 다른 사용자 데이터에 닿는 경우. 인증 없는 에이전트 엔드포인트가 쓰기 작업을 하는 경우. / 주의: 서명은 있지만 재전송 방지·보낸 쪽 권한 확인이 없는 경우. 여러 에이전트가 관리자 키 하나를 나눠 쓰는 경우. / 참고: 에이전트별 호출 기록이 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-AGT-004 agent/tools.py:30 — get_invoice 가 모델이 준 user_id 로 서비스 계정 조회 → 세션 사용자로 도구를 만들고 customer_id 조건을 고정`

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
