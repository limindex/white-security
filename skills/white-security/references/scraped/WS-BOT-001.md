<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-BOT-001 Scraping · AI 크롤러 대량 수집(robots.txt만으로는 못 막음)

주의 · API API4 · ASVS V2.4.1 · ASVS V15.3.4 · CWE-799 · CWE-770 · 기반시설 AU

## 체크 항목

- robots.txt는 막는 장치가 아니다. 표준(RFC 9309)도 접근 권한 수단이 아니라고 적는다. 공개하면 안 되는 내용은 로그인 뒤에 둔다.
- IP·API 키별 요청 횟수를 제한하고, 넘으면 429와 `Retry-After`를 돌려준다.
- 목록 개수(페이지 크기)와 검색어 길이에 상한을 두고, 무거운 응답은 캐시한다.
- 프록시·CDN 뒤라면 신뢰하는 프록시가 넣은 IP만 쓴다. `X-Forwarded-For`는 누구나 바꿔 보낼 수 있다. User-Agent만 보고 막거나 허용하지 않는다.
- 트래픽·대역폭 비용 경보를 건다. Read the Docs는 2024년 5월 한 크롤러가 73TB를 내려받아 대역폭 비용이 5천 달러를 넘었다고 밝혔다. Wikimedia는 2025년 가장 비싼 트래픽의 65% 이상이 봇이라고 밝혔다.

## 스킬이 잡는 방식

**찾는 곳**: 로그인 없이 열리는 목록·검색·파일 내려받기 라우트, 페이지 크기 인자(`limit`, `size`, `per_page`), 속도 제한 미들웨어·웹 서버 설정(`limit_req`, `rateLimit`), IP를 읽는 코드(`X-Forwarded-For`, `remote_addr`).

**검사 1. 횟수 제한이 없다**
- 지적할 때: 공개 라우트 중 DB 검색·파일 전송·외부 API 호출처럼 무거운 일을 하는 곳에 앱·웹 서버·CDN 어디에도 요청 횟수 제한이 없다.
- 지적하지 않을 때: 정적 파일만 CDN 캐시로 나간다. 또는 웹 서버·게이트웨이 설정에 제한이 있다(설정을 못 봤으면 "확인 필요").

**검사 2. 한 번에 너무 많이 준다**
- 지적할 때: 페이지 크기를 요청값 그대로 쓰거나 상한이 아주 크다. 전체 목록 내보내기가 로그인 없이 열린다.
- 지적하지 않을 때: 서버가 정한 상한(예: 100)으로 자른다.

**등급**
- 위험: 로그인 없이 전체 데이터 내보내기나 무거운 검색이 횟수 제한 없이 열려 있다.
- 주의: 횟수 제한이 `X-Forwarded-For`처럼 바꿀 수 있는 값에 기댄다. 페이지 크기 상한이 없다.
- 참고: 캐시·비용 경보가 없다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[주의] WS-BOT-001 api/products.py:7 — size 를 요청값 그대로 쓰고 횟수 제한이 없음 → 한 쪽 최대 100개, IP별 분당 60회, 결과 5분 캐시`

## 코드

### python

✗ 뚫리는 코드

```python
@app.get("/api/products")
def products():
    size = request.args.get("size", 1_000_000, type=int)  # 한 번에 전부 내준다
    return jsonify(db.search(request.args.get("q", ""), limit=size))  # 매번 무거운 검색, 횟수 제한 없음
```

✓ 막는 코드

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

### nginx

✗ 뚫리는 코드

```nginx
location /api/ {
    proxy_pass http://app;  # 횟수 제한 없이 앱으로 넘긴다
}
```

✓ 막는 코드

```nginx
# http 블록: IP 마다 초당 2회, 잠깐 몰리는 요청은 20개까지 허용
limit_req_zone $binary_remote_addr zone=api:10m rate=2r/s;

location /api/ {
    limit_req zone=api burst=20 nodelay;
    limit_req_status 429;
    proxy_pass http://app;
}
```
