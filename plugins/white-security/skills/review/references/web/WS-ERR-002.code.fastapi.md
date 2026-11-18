<!-- generated from white-security issues; do not edit -->
# WS-ERR-002 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/orders/{order_id}")
def get_order(order_id: int, user=Depends(current_user), db=Depends(get_db)):
    order = db.get(Order, order_id)
    if order.user_id != user.id:          # 없는 id 면 None 의 속성 접근 → 500
        raise HTTPException(403)
    return order
```

✓ 안전 코드 (safe example)

```python
@app.get("/orders/{order_id}", response_model=OrderOut)
def get_order(order_id: int, user=Depends(current_user), db=Depends(get_db)):
    order = db.get(Order, order_id)
    if order is None or order.user_id != user.id:   # 없음과 남의 것을 같은 404 로
        raise HTTPException(404)
    return order
```
