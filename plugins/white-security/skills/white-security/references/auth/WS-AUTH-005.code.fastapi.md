<!-- generated from white-security issues; do not edit -->
# WS-AUTH-005 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
class OrderIn(BaseModel):
    product_id: int
    is_adult: bool                 # 앱이 "성인 인증 완료"라고 보내는 값

@app.post("/orders")
def order(body: OrderIn, user: User = Depends(get_current_user)):
    product = get_product(body.product_id)
    if product.adult_only and not body.is_adult:  # 값 하나만 바꾸면 통과
        raise HTTPException(403)
    return create_order(user, product)
```

✓ 안전 코드 (safe example)

```python
class OrderIn(BaseModel):
    product_id: int

def age_on(birth: date, today: date) -> int:
    return today.year - birth.year - ((today.month, today.day) < (birth.month, birth.day))

@app.post("/orders")
def order(body: OrderIn, user: User = Depends(get_current_user)):
    product = get_product(body.product_id)
    # 생년월일은 서버가 본인확인 결과를 검증해 저장한 값만 쓴다(가입 폼 입력값 사용 금지)
    if product.adult_only and (user.verified_birth is None
                               or age_on(user.verified_birth, date.today()) < ADULT_AGE):  # 서비스 법령 기준
        raise HTTPException(403, "성인 인증이 필요합니다")
    return create_order(user, product)
```
