<!-- generated from white-security issues; do not edit -->
# WS-PAY-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.post("/webhooks/pg")
async def pg_webhook(payload: dict):                   # 서명 확인 없음
    if payload["status"] == "paid":
        mark_paid(payload["order_id"], payload["amount"])  # 보낸 금액·상태를 그대로 믿는다
    return {"ok": True}
```

✓ 안전 코드 (safe example)

```python
import hashlib, hmac, json, time

@app.post("/webhooks/pg")
async def pg_webhook(request: Request):
    raw = await request.body()                         # 파싱 전 원문으로 서명 확인
    ts = request.headers.get("x-pg-timestamp", "")     # 헤더 이름·서명 방식은 PG 문서대로
    sig = request.headers.get("x-pg-signature", "")
    expected = hmac.new(PG_WEBHOOK_SECRET, ts.encode() + b"." + raw, hashlib.sha256).hexdigest()
    if not ts.isdigit() or abs(time.time() - int(ts)) > 300 or not hmac.compare_digest(expected, sig):
        raise HTTPException(400)                       # 위조·오래된 알림 거절
    event = json.loads(raw)
    payment = pg_client.get_payment(event["payment_id"])  # 알림 내용 대신 조회 API 로 다시 확인
    order = get_order(payment["order_id"])
    if payment["status"] == "paid" and payment["amount"] == order.total_amount:
        mark_paid_once(order.id, payment["payment_id"])   # 대기 상태일 때만 갱신 + payment_id 유니크
    return {"ok": True}
```
