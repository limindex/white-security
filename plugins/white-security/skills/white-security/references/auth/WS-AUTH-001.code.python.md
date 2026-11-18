<!-- generated from white-security issues; do not edit -->
# WS-AUTH-001 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
# Flask + Flask-SQLAlchemy
@app.get("/api/orders/<int:order_id>")
@login_required
def get_order(order_id):
    order = db.session.get(Order, order_id)        # 로그인만 확인, 누구 주문인지는 안 본다
    if order is None:
        abort(404)
    return jsonify(order.to_dict())

@app.post("/api/orders/<int:order_id>/refund")
@login_required
def refund(order_id):
    if request.cookies.get("role") != "admin":     # 쿠키는 사용자가 바꿀 수 있다
        abort(403)
    issue_refund(order_id, amount=request.json["amount"])  # 금액도 요청 값 그대로
    return {"ok": True}
```

✓ 안전 코드 (safe example)

```python
from sqlalchemy import select

@app.get("/api/orders/<int:order_id>")
@login_required
def get_order(order_id):
    order = db.session.execute(
        select(Order).where(Order.id == order_id, Order.user_id == current_user.id)
    ).scalar_one_or_none()
    if order is None:
        abort(404)                                  # 남의 주문도 "없음"으로 답한다
    return jsonify(order.to_dict())

@app.post("/api/orders/<int:order_id>/refund")
@login_required
def refund(order_id):
    if current_user.role != "admin":               # 앱의 User 모델에 저장된 역할(DB 값)로 판단
        abort(403)
    order = db.get_or_404(Order, order_id)
    issue_refund(order.id, amount=order.paid_amount)  # 금액은 서버 기록으로
    return {"ok": True}
```
