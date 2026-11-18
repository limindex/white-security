<!-- generated from white-security issues; do not edit -->
# WS-LLM-004 Unbounded Consumption (토큰·비용 폭탄)

심각도: 위험
기준: OWASP API 2023 API4(자원 소비 무제한) · OWASP LLM 2026 LLM06(자원 소비 무제한) · ASVS 5.0 V2.4.1 · ASVS 5.0 V15.2.2 · CWE-770(한도 없는 자원 할당) · CWE-400 · 기반시설 웹 21 AU(자동화 공격)

## 점검 항목

- AI를 부르는 API는 로그인한 사용자만 쓰게 하고, 사용자별·IP별 호출 횟수를 제한해야 한다.
- 입력 길이, 대화 기록 길이, 답 길이 상한(`max_tokens`·`max_output_tokens`)을 정해야 한다.
- 하루·한 달 비용 한도를 내 서버와 AI 회사 콘솔 양쪽에 걸어야 한다.
- AI API 키는 서버에만 두어야 한다. 2024년 Sysdig 분석은 훔친 클라우드 키로 남의 AI를 쓰는 LLMjacking 피해가 하루 4만 6천 달러를 넘을 수 있다고 계산함.
- 시간 초과와 재시도 횟수를 정해야 한다.

## 탐지 규칙

- **찾는 곳**: LLM 호출이 있는 라우트·핸들러, 그 앞의 인증·속도 제한 미들웨어, `max_tokens`·`timeout` 인자, 브라우저 코드 속 AI API 호출.
- **검사 1 — 무제한 호출**: 지적할 때: LLM을 부르는 공개 라우트에 인증과 속도 제한이 둘 다 없는 경우. / 지적하지 않을 때: 인증과 사용자별 제한이 있는 경우. 게이트웨이·WAF 설정의 제한을 확인한 경우(확인하지 못했으면 "확인 필요").
- **검사 2 — 한 번 호출의 크기 상한 없음**: 지적할 때: 입력 길이 제한이 없고, `max_tokens`가 없거나 기능에 비해 아주 큰 경우. / 지적하지 않을 때: 입력을 자르고 답 길이 상한이 기능에 맞는 경우.
- **등급**: 위험: 로그인 없이 누구나 LLM 호출 API를 횟수 제한 없이 부를 수 있는 경우. 브라우저 코드에 AI API 키가 있는 경우. / 주의: 로그인은 있지만 사용자별 횟수·비용 한도가 없는 경우. / 참고: 시간 초과·재시도 상한이 없는 경우.
- **보고 예**: `[위험] WS-LLM-004 app/routes.py:40 — /api/ask 가 로그인·횟수 제한 없이 모델을 호출 → 로그인 필수, 사용자별 하루 50회, 입력 4,000자, max_tokens 800`

## 코드 예시

### python

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.post("/api/ask")
def ask():
    q = request.json["q"]  # 길이 제한 없음
    reply = client.messages.create(model=MODEL, max_tokens=8192,
                                   messages=[{"role": "user", "content": q}])
    return {"answer": reply.content[0].text}  # 로그인·횟수 제한 없음
```

✓ 안전 코드 (safe example)

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
