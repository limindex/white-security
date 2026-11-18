<!-- generated from white-security issues; do not edit -->
# WS-BOT-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/api/products")
def products():
    size = request.args.get("size", 1_000_000, type=int)  # 한 번에 전부 내준다
    return jsonify(db.search(request.args.get("q", ""), limit=size))  # 매번 무거운 검색, 횟수 제한 없음
```

✓ 안전 코드 (safe example)

```python
import time

@app.get("/api/products")
def products():
    key = f"rl:{request.remote_addr}:{int(time.time() // 60)}"  # 프록시 뒤라면 신뢰하는 프록시가 넣은 IP
    if redis.incr(key) > 60:  # IP 당 분당 60회
        return {"error": "잠시 뒤 다시 시도하세요"}, 429, {"Retry-After": "60"}
    redis.expire(key, 120)
    size = min(max(request.args.get("size", 20, type=int), 1), 100)  # 한 쪽 최대 100개
    q = request.args.get("q", "")[:100]
    data = cache.get_or_set(f"p:{q}:{size}", lambda: db.search(q, limit=size), ttl=300)
    return jsonify(data), 200, {"Cache-Control": "public, max-age=300"}
```
