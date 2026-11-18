<!-- generated from white-security issues; do not edit -->
# WS-SMS-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
import random

def issue_code(phone: str) -> str:
    code = str(random.randint(100000, 999999))   # 예측 가능한 난수
    r.set(f"otp:{phone}", code)                  # 만료 없음
    return code

@app.post("/otp/verify")
def verify(body: VerifyIn):
    code = r.get(f"otp:{body.phone}")
    if code and code.decode() == body.code:      # 틀려도 몇 번이든 다시
        return {"ok": True, "phone_verified": True}  # 성공 표시를 클라이언트에 맡긴다
    return {"ok": False}
```

✓ 안전 코드 (safe example)

```python
import hmac, secrets, time
from typing import Literal
from pydantic import BaseModel, Field

class VerifyIn(BaseModel):
    phone: str = Field(pattern=r"^01[016789]\d{7,8}$")
    code: str = Field(pattern=r"^\d{6}$")                  # 숫자 6자리만
    purpose: Literal["signup", "reset"]

def issue_code(phone: str, purpose: str) -> str:
    code = f"{secrets.randbelow(1_000_000):06d}"            # 암호학적 난수
    key = f"otp:{purpose}:{phone}"                          # 목적별로 따로 저장
    r.set(key, code, ex=300)                                # 5분
    r.set(f"{key}:tries", 0, ex=300)
    return code

@app.post("/otp/verify")
def verify(body: VerifyIn, request: Request):
    key = f"otp:{body.purpose}:{body.phone}"                # 발급 때 목적과 같아야 찾힌다
    code = r.get(key)
    if code is None or r.incr(f"{key}:tries") > 5:          # 인증 건당 5번까지
        r.delete(key)
        raise HTTPException(400, "인증번호를 다시 받아 주세요")
    if not hmac.compare_digest(code.decode(), body.code):
        raise HTTPException(400, "인증번호가 맞지 않습니다")
    r.delete(key, f"{key}:tries")                           # 한 번만
    request.session["phone_verified"] = {"phone": body.phone, "purpose": body.purpose, "at": int(time.time())}
    return {"ok": True}

def require_verified(request: Request, phone: str, purpose: str) -> None:  # 가입·재설정 단계에서 호출
    v = request.session.pop("phone_verified", None)         # 서명된 세션(SessionMiddleware), 꺼내면 삭제
    if not v or v["phone"] != phone or v["purpose"] != purpose or time.time() - v["at"] > 600:
        raise HTTPException(403, "휴대폰 인증을 다시 해 주세요")
```
