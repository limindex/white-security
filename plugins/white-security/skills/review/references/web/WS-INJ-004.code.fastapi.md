<!-- generated from white-security issues; do not edit -->
# WS-INJ-004 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/products")
def products(max_price: str):
    # 입력을 서버에서 실행되는 JS 식에 이어 붙인다
    return list(db.products.find({"$where": f"this.price <= {max_price}"}, {"_id": 0}))
```

✓ 안전 코드 (safe example)

```python
from fastapi import Query

@app.get("/products")
def products(max_price: int = Query(ge=0, le=10_000_000)):   # 숫자로만 받는다
    return list(db.products.find({"price": {"$lte": max_price}}, {"_id": 0}).limit(100))
```
