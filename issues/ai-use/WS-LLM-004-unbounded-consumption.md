---
id: WS-LLM-004
title: Unbounded Consumption (토큰·비용 폭탄)
slug: unbounded-consumption
group: ai-use
severity: high
grade_hint: "로그인·횟수 제한 없는 AI 호출, 브라우저에 AI 키"
always_on: "`chat.completions.create(` `dangerouslyAllowBrowser` → AI 호출은 서버에서 로그인 사용자만, 횟수 제한을 두고 해야 하며 키는 서버에만 두어야 한다."
site_only: false
depth: brief
languages: [python, js]
maps:
  owasp2025: []
  api2023: [API4]
  llm2026: [LLM06]
  agentic2026: []
  asvs5: [V2.4.1, V15.2.2]
  cwe: [CWE-770, CWE-400]
  kr_guide: []
  kr_ciip: [AU]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, Node.js, AI 호출 API를 여는 모든 서비스]
severity_reason: "영향: 사용량 과금 폭증·서비스 중단 · 악용: 로그인·한도 없는 주소 하나로 자동화 요청만으로 가능"
sources:
  - title: "OWASP Top 10 for LLM Applications 2026 - LLM06:2026 Unbounded Consumption"
    url: https://genai.owasp.org/resource/owasp-genai-llm-top-10-2026/
    year: 2026
  - title: "OWASP API Security Top 10 2023 - API4 Unrestricted Resource Consumption"
    url: https://owasp.org/API-Security/editions/2023/en/0xa4-unrestricted-resource-consumption/
    year: 2023
  - title: "Sysdig - LLMjacking: Stolen Cloud Credentials Used in New AI Attack"
    url: https://sysdig.com/blog/llmjacking-stolen-cloud-credentials-used-in-new-ai-attack/
    year: 2024
  - title: 과학기술정보통신부·KISA 인공지능(AI) 보안 안내서
    url: https://www.kisa.or.kr/2060204/form?postSeq=19
    year: 2025
---

## 요약

로그인도 횟수 제한도 없이 AI 호출 API를 열어 둘 때, 누군가 긴 글을 수만 번 보내 서비스 운영자 비용으로 AI를 쓰는 문제. OWASP 분류로는 Unbounded Consumption(자원 소비 무제한).

## 위험 배경

- 2024년 Sysdig 분석, 훔친 클라우드 키로 남의 AI를 쓰는 LLMjacking 피해가 하루 4만 6천 달러를 넘을 수 있다는 계산.

## 대응 방법

```text
AI 모델을 부르는 API 경로를 모두 찾아 줘.
로그인한 사용자만 쓰게 하고, 사용자별 분당·하루 호출 횟수, 입력 길이, 답 길이(max_tokens), 시간 초과를 정해 줘.
하루 비용 한도를 넘으면 멈추고 나에게 알리게 해 줘.
```

- 같은 질문을 짧은 시간에 여러 번 보냈을 때, 정한 횟수를 넘으면 429(요청 과다) 응답이 와야 한다.
- AI 회사 콘솔에도 월 사용 한도를 걸어 두어야 한다.

## 점검 항목

- AI를 부르는 API는 로그인한 사용자만 쓰게 하고, 사용자별·IP별 호출 횟수를 제한해야 한다.
- 입력 길이, 대화 기록 길이, 답 길이 상한(`max_tokens`·`max_output_tokens`)을 정해야 한다.
- 하루·한 달 비용 한도를 내 서버와 AI 회사 콘솔 양쪽에 걸어야 한다.
- AI API 키는 서버에만 두어야 한다.
- 시간 초과와 재시도 횟수를 정해야 한다.

## 탐지 규칙

- **찾는 곳**: LLM 호출(`chat.completions.create(`, `messages.create(`, `generateContent(`)이 있는 라우트·핸들러, 그 앞의 인증·속도 제한 미들웨어, `max_tokens`·timeout 인자, 브라우저 코드 속 AI API 호출(`dangerouslyAllowBrowser`).
- **검사 1 — 무제한 호출**: 지적할 때: LLM을 부르는 공개 라우트에 인증과 속도 제한이 둘 다 없는 경우. / 지적하지 않을 때: 인증과 사용자별 제한이 있는 경우. 게이트웨이·WAF 설정의 제한을 확인한 경우(확인하지 못했으면 "확인 필요").
- **검사 2 — 한 번 호출의 크기 상한 없음**: 지적할 때: 입력 길이 제한이 없고, `max_tokens`가 없거나 기능에 비해 아주 큰 경우. / 지적하지 않을 때: 입력을 자르고 답 길이 상한이 기능에 맞는 경우.
- **등급**: 위험: 로그인 없이 누구나 LLM 호출 API를 횟수 제한 없이 부를 수 있는 경우. 브라우저 코드에 AI API 키가 있는 경우. / 주의: 로그인은 있지만 사용자별 횟수·비용 한도가 없는 경우. / 참고: 시간 초과·재시도 상한이 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-LLM-004 app/routes.py:40 — /api/ask 가 로그인·횟수 제한 없이 모델을 호출 → 로그인 필수, 사용자별 하루 50회, 입력 4,000자, max_tokens 800`

## 코드 예시

### python

✗ 취약 코드

```python
@app.post("/api/ask")
def ask():
    q = request.json["q"]  # 길이 제한 없음
    reply = client.messages.create(model=MODEL, max_tokens=8192,
                                   messages=[{"role": "user", "content": q}])
    return {"answer": reply.content[0].text}  # 로그인·횟수 제한 없음
```

✓ 안전 코드

```python
import time

DAILY_LIMIT, MAX_INPUT = 50, 4000

@app.post("/api/ask")
@login_required
def ask():
    q = str(request.json.get("q", ""))[:MAX_INPUT]
    key = f"ai:{current_user.id}:{time.strftime('%Y%m%d')}"
    if redis.incr(key) > DAILY_LIMIT:  # 사용자별 하루 횟수
        return {"error": "오늘 사용량을 다 썼습니다"}, 429
    redis.expire(key, 86400)
    reply = client.messages.create(model=MODEL, max_tokens=800, timeout=30,
                                   messages=[{"role": "user", "content": q}])
    return {"answer": reply.content[0].text}
```

### js

✗ 취약 코드

```js
app.post("/api/ask", async (req, res) => {
  const r = await openai.responses.create({ model: MODEL, input: req.body.q });
  res.json({ answer: r.output_text });
});
```

✓ 안전 코드

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
