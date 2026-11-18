<!-- generated from white-security issues; do not edit -->
# WS-LLM-004 코드 예시 — Python

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
