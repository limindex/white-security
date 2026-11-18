<!-- generated from white-security issues; do not edit -->
# WS-DES-001 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
cart = pickle.loads(base64.b64decode(request.cookies["cart"]))  # 쿠키를 만든 사람의 코드가 돈다
config = yaml.load(request.data, Loader=yaml.Loader)  # 태그로 파이썬 객체를 만든다
```

✓ 안전 코드 (safe example)

```python
import base64, hashlib, hmac, json, yaml

def load_cart(cookie: str) -> list:
    raw, _, sig = cookie.rpartition(".")
    good = hmac.new(SECRET_KEY, raw.encode(), hashlib.sha256).hexdigest()
    if not hmac.compare_digest(sig, good):  # 서버가 만든 값인지 확인한다
        raise ValueError("변조된 쿠키입니다")
    cart = json.loads(base64.urlsafe_b64decode(raw))  # JSON 은 데이터만 만든다
    if not isinstance(cart, list):
        raise ValueError("장바구니 형식이 아닙니다")
    return cart

config = yaml.safe_load(request.data)  # 기본 타입만 만든다
```
