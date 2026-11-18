<!-- generated from white-security issues; do not edit -->
# WS-ERR-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.post("/orders")
def create_order(body: OrderIn):
    try:
        verify_payment(body.payment_id, body.amount)   # 결제 검증
    except Exception:
        pass                                          # 검증 실패를 무시하고 주문 생성
    return save_order(body)
```

✓ 안전 코드 (safe example)

```python
import logging, uuid
log = logging.getLogger(__name__)

@app.post("/orders")
def create_order(body: OrderIn):
    try:
        verify_payment(body.payment_id, body.amount)
    except PaymentError:                              # 예상한 실패만 잡아 거절
        raise HTTPException(402, "결제를 확인하지 못했습니다")
    return save_order(body)                           # 그 밖의 예외는 아래 처리기로 → 주문 생성 안 됨

@app.exception_handler(Exception)                    # 마지막 처리기
async def unhandled(request: Request, exc: Exception):
    ref = uuid.uuid4().hex
    log.exception("unhandled %s", ref)                # 원문·스택은 로그에만
    return JSONResponse({"error": "처리하지 못했습니다", "ref": ref}, status_code=500)
```
