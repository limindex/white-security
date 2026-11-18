<!-- generated from white-security issues; do not edit -->
# WS-ENC-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/me")
def read_me(user: User = Depends(current_user)):
    return user          # User 가 SQLModel 테이블이면 password_hash·reset_token 까지 나간다
```

✓ 안전 코드 (safe example)

```python
from pydantic import BaseModel, ConfigDict

class UserOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)
    id: int
    name: str
    email: str

@app.get("/me", response_model=UserOut)      # 여기 적은 필드만 나간다
def read_me(user: User = Depends(current_user)):
    return user
```
