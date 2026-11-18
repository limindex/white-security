<!-- generated from white-security issues; do not edit -->
# WS-SUP-007 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
import requests

@app.get("/stores/{store_id}")
def store(store_id: int, db: Session = Depends(get_db)):
    # 시간 제한 없음(requests 기본값 없음), 리다이렉트 자동 추적
    data = requests.get(f"https://partner.example.com/stores/{store_id}").json()
    db.execute(text(f"UPDATE stores SET name = '{data['name']}' WHERE id = {store_id}"))  # 외부 값이 SQL 로
    return data                                                                         # 응답 전체를 그대로
```

✓ 안전 코드 (safe example)

```python
import httpx
from pydantic import BaseModel, Field, ValidationError

class PartnerStore(BaseModel):          # 쓰는 필드만, 모르는 필드는 버림
    name: str = Field(max_length=100)
    open: bool

client = httpx.Client(base_url="https://partner.example.com", timeout=5.0, follow_redirects=False)

@app.get("/stores/{store_id}")
def store(store_id: int, db: Session = Depends(get_db)):
    try:
        r = client.get(f"/stores/{store_id}")
        r.raise_for_status()                                 # 3xx·4xx·5xx 는 예외
        s = PartnerStore.model_validate_json(r.content)      # 형식·길이가 다르면 예외
    except (httpx.HTTPError, ValidationError):
        raise HTTPException(status_code=502, detail="partner unavailable")   # 실패하면 거절
    db.execute(text("UPDATE stores SET name = :n WHERE id = :id"), {"n": s.name, "id": store_id})
    return {"name": s.name, "open": s.open}
```
