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
import hmac, secrets

def issue_code(phone: str) -> str:
    code = f"{secrets.randbelow(1_000_000):06d}"            # 암호학적 난수
    r.set(f"otp:{phone}", code, ex=300)                     # 5분
    r.set(f"otp-tries:{phone}", 0, ex=300)
    return code

@app.post("/otp/verify")
def verify(body: VerifyIn, request: Request):
    code = r.get(f"otp:{body.phone}")
    if code is None or r.incr(f"otp-tries:{body.phone}") > 5:  # 인증 건당 5번까지
        r.delete(f"otp:{body.phone}")
        raise HTTPException(400, "인증번호를 다시 받아 주세요")
    if not hmac.compare_digest(code.decode(), body.code):
        raise HTTPException(400, "인증번호가 맞지 않습니다")
    r.delete(f"otp:{body.phone}", f"otp-tries:{body.phone}")   # 한 번만
    request.session["phone_verified"] = {"phone": body.phone, "purpose": body.purpose}  # 서명된 세션(SessionMiddleware)
    return {"ok": True}
```
