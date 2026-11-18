<!-- generated from white-security issues; do not edit -->
# WS-PRV-002 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
import logging, os, sentry_sdk
from fastapi import FastAPI, Request

sentry_sdk.init(dsn=os.environ["SENTRY_DSN"], send_default_pii=True)   # IP·사용자 정보까지 자동 수집
app = FastAPI()
log = logging.getLogger("app")

@app.post("/signup")
async def signup(req: Request):
    body = await req.json()
    log.info("signup %s", body)          # 이름·전화번호·생년월일이 로그 파일에
    return create_user(body)             # 여기서 오류가 나면 지역 변수 body 도 오류 보고에 실림
```

✓ 안전 코드 (safe example)

```python
import logging, os, re, sentry_sdk
from fastapi import FastAPI

PII = re.compile(r"\d{6}-?[1-8]\d{6}|01[016789]-?\d{3,4}-?\d{4}|[\w.+-]+@[\w-]+\.[\w.]+")

def scrub(event, hint):
    req = event.get("request", {})
    for key in ("data", "cookies", "query_string"):
        req.pop(key, None)                                     # 요청 본문·쿠키·쿼리는 보내지 않음
    for exc in (event.get("exception") or {}).get("values", []):
        exc["value"] = PII.sub("[가림]", exc.get("value") or "")  # 오류 메시지 속 번호·메일 가림
        for frame in (exc.get("stacktrace") or {}).get("frames", []):
            frame.pop("vars", None)                            # 지역 변수 값 제거
    for crumb in (event.get("breadcrumbs") or {}).get("values", []):
        crumb["message"] = PII.sub("[가림]", crumb.get("message") or "")
        crumb.pop("data", None)                                # 로그 인자·extra 값
    log = event.get("logentry") or {}
    for key in ("message", "formatted"):
        if isinstance(log.get(key), str):
            log[key] = PII.sub("[가림]", log[key])             # 로그 메시지 가림
    log.pop("params", None)                                    # 포맷 인자(원문 값)는 빼기
    event.pop("extra", None)                                   # logging extra 등 임의 값
    return event

# send_default_pii 는 켜지 않는다(기본 꺼짐)
sentry_sdk.init(dsn=os.environ["SENTRY_DSN"], before_send=scrub)
app = FastAPI()
log = logging.getLogger("app")

@app.post("/signup")
async def signup(body: SignupIn):
    user = create_user(body)
    log.info("signup user_id=%s", user.id)                     # 식별자만
    return {"id": user.id}
```
